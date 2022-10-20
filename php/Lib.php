<?php
namespace SP\Utils;

class Lib
{
    /**
     * @return mixed|null
     */
    public static function coalesce() {
        foreach (func_get_args() as $arg) { 
            if ($arg !== null) { 
                return $arg; 
            } 
        }
        return null;
    }

    /**
     * @param $str
     * @return string
     */
    public static function json_encode_utf8($str):string {
        $je = preg_replace('/(\\\u([0-9A-F]{4}))/i', '&#x${2};', json_encode($str));
        return html_entity_decode($je, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * @param $arrMaster
     * @param $arrSlave
     * @return bool
     */
    public static function arraysLikeIdentical($arrMaster, $arrSlave): bool
    {
        $keysMaster  = array_keys($arrMaster);
        $keysSlave   = array_keys($arrSlave);
        $diffArr     = array_diff($keysMaster,$keysSlave);
        $isIdentical = !count($diffArr);
        if($isIdentical) {
            $diffArr = array_diff($keysSlave,$keysMaster);
            $isIdentical = !count($diffArr);
        }

        if($isIdentical) {
            foreach($arrSlave as $key => $val) {
                if(!$isIdentical) { break; }
                if(!isset($arrMaster[$key])) {
                    $isIdentical = false;
                    break;
                }
                foreach($val as $k => $v) {
                    if(!isset($arrMaster[$key][$k]) || $arrMaster[$key][$k] != $v) {
                        $isIdentical = false;
                        break;
                    }
                }
            }
        }
        return $isIdentical;
    }

    /**
     * @param $d
     * @return object
     */
    public static function arrayToObject($d): object
    {
        return is_array($d)
            ? (object) array_map(__FUNCTION__, $d)
            : $d;
    }

    /**
     * @param $array
     * @param string $rootName
     * @param string $childName
     * @return bool|string
     * @throws \Exception
     */
    public static function arrayToXML($array, string $rootName = 'Root')
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.(empty($rootName)?'Root':$rootName).'/>');

        function toXml($array, \SimpleXMLElement $xml, $tagName)
        {
            $genName = substr($tagName,-3) === 'ies'
                ? substr($tagName,0,-3) . 'y'
                : substr($tagName,0,-1);
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    is_int($k)
                        ? toXml($v, $xml->addChild($genName), $genName, $v)
                        : toXml($v, $xml->addChild($k), $k, $genName);
                } else {
                    is_int($k)
                        ? $xml->addChild($genName, $v)
                        : $xml->addChild($k, self::toXmlValue($v));
                }
            }
        }

        toXml($array, $xml, $rootName);

        $return = '';
        if(empty($rootName)) {
            foreach($xml->children() as $child) {
                $return .= $child->asXML();
            }
        } else {
            $return = $xml->asXML();
        }
        return $return;
    }

    /**
     * @param $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public static function toCamelCase($string, bool $capitalizeFirstCharacter = false): string
    {
        $str = str_replace(' ', '', ucwords(str_replace(['-','_'], ' ', $string)));
        if (!$capitalizeFirstCharacter) { $str[0] = lcfirst($str); }
        return $str;
    }

    /**
     * @param $d
     * @return array
     */
    public static function objectToArray($d): ?array
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        }

        return $d;
    }

    /**
     * @param $xml
     * @param $type
     * @return bool|int|mixed|null
     */
    public static function getSingleValueFromXml(&$xml, $type) {
        $regExps = [
            'bool' => 'true|false',
            'int' => '[-]?[0-9]+',
            'string' => '.*',
        ];
        $matches = [];
        preg_match('#^<'.$type.'>('.$regExps[$type].')</'.$type.'>$#uis', $xml, $matches);
        if(count($matches) === 2) {
            switch($type) {
                case 'bool': return strtolower($matches[1]) === 'true';
                case 'int':  return (int)$matches[1];
                default:     return $matches[1];
            }
        }
        return null;
    }

    /**
     * @param string $filePath
     * @return string
     */
    public static function getFileContentBase64(string $filePath): string
    {
        $data = file_get_contents($filePath);
        return $data === FALSE ? '' : base64_encode($data);
    }

    /**
     * @param string $fileName
     * @return false|string
     */
    public static function getFileMimeType(string $fileName) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // returns mime-type
        $result = finfo_file($finfo, $fileName);
        finfo_close($finfo);
        return $result;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function str2attr(string $str): string
    {
        return str_replace(
            ['<','>','&','"',"\r\n","\n\r","\n"],
            ['&lt;', '&gt;', '&amp;', '&quot;', '&#x0D;', '&#x0D;', '&#x0D;'],
            trim($str));
    }

    /**
     * @param string $xmlStr
     * @return array|string|string[]|null
     */
    public static function clearXmlDTD(string $xmlStr){
        $count = null;
        return preg_replace('/ ((?:xmlns:xsi)|(?:xsi:[a-z]+))="[^"\']*"/', '', $xmlStr, -1, $count);
    }

    public static function urlDecodeUtf8($str): string
    {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
        return html_entity_decode($str,null,'UTF-8');
    }

    /**
     * @param $dir
     * @return bool
     */
    public static function rrmdir($dir): bool
    {
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::rrmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @return array
     */
    public static function getInfoBrowser(): array
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        preg_match("/(MSIE|Opera|Firefox|Chrome|Version)(?:\/| )([0-9.]+)/", $agent, $bInfo);
        $browserInfo = [];
        $browserInfo['name'] = ($bInfo[1]==="Version") ? "Safari" : $bInfo[1];
        $browserInfo['version'] = $bInfo[2];
        return $browserInfo;
    }

    public static function urlExists($url=NULL)
    {
        if(!$url) { return false; }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if($data === false) {
            return [false,curl_error($ch)];
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpcode>=200 && $httpcode<300){
            return [true,true];
        }
        return [false,curl_error($ch)];
    }

}
