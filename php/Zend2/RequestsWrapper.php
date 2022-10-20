<?php
namespace Request\Model;

use SP\Utils\Lib;
use SP\Utils\Locales as Loc;

//use SP\Debug\Debug as SP_Debug;

class RequestsWrapper extends \Application\Model\AbstractWrapper {

    /** ========================================================================
     *
     * @param $a
     * @param $b
     * @return int
     */
    private static function _compare_comments_date_reversely($a, $b): int
    {
        return $b['Date'] <=> $a['Date'];
    }

    /** ========================================================================
     *
     * @param $commentsObj
     * @return array
     */
    private static function _commentsTxtToHtml($commentsObj): array
    {
        $ret = [
            'Count' => 0,
            'LastDate'  => '',
            'Answered' => 1,
            'History' => []
        ];

        $comments = isset($commentsObj['Comment']['Id'])
            ? [$commentsObj['Comment']]
            : ($commentsObj['Comment'] ?? ($commentsObj?:[]));

        foreach($comments as $comment) {
            $parts = explode(':', $comment['Text'], 2);
            ++$ret['Count'];
            $ret['History'][] = [
                    'Id' => $comment['Id'],
                    'IsIncomming' => $comment['IsIncomming'],
                    'IsSystem' => $comment['IsSystem'],
                    'Date' => $comment['Date'],
                    'Person' => trim(count($parts)>1?$parts[0]:''),
                    'Text' => str_replace(
                                ["\r\n", "\n\r", '&#x0D;', '&#xD;', "\n", "\r"],
                                '<br>',
                                trim(empty($parts[1])?$parts[0]:$parts[1]))
                ];
            if($ret['LastDate'] < $comment['Date']) {
                $ret['LastDate'] = $comment['Date'];
                $ret['Answered'] = $comment['IsIncomming'] != 1;
            }
        }

        return $ret;
    }

    /** ========================================================================
     *
     * @param $comment
     * @return array
     */
    private static function _commentTxtToHtml($comment): array
    {
        $parts = explode(':', $comment['Text'], 2);
        $isSystem = count($parts) === 1;

        $ret = [
            'Id' => $comment['Id'],
            'IsIncomming' => '1',
            'IsSystem' => $comment['IsSystem'],
            'Date' => $comment['Date'],
            //'Person' => $isSystem ? 'SYSTEM' : trim($parts[0]),
            'Person' => trim($parts[0]),
            'Text' => str_replace(
                        ["\r\n", "\n\r", '&#x0D;', '&#xD;', "\n", "\r"],
                        '<br>',
                        \rim($isSystem ? $parts[0] : $parts[1]))
        ];

        return $ret;
    }

        
    /** ========================================================================
     * @param $requestId
     * @return array|bool
     */
    public function getComments($requestId) {
        $authRes = $this->_getUserAuthorized();
        if (Lib::isFailed($authRes)) { return $authRes; }

        $params = [
            'operationName' => 'Core.Web.Request@GetNotes',
            'request' => [
                ['type' => 'int', 'value' => $requestId, 'node' => 'requestId']
            ]
        ];
        $res = $this->_exec('Execute', $params);

        if (Lib::isFailed($res)) { return $res; }

        $res['data'] = self::_commentsTxtToHtml(Lib::xmlToNormalizedArray($res['data']));

        \uasort(
            $res['data']['History'],
            ['self','_compare_comments_date_reversely']
        );

        return $res;
    }

    /** ================================================================================================================
     * @param int $requestId
     * @param string $hash
     * @return array|array[]|bool
     */
    public function getApprovalTaskFromRequest(int $requestId, string $hash)
    {
        $authRes = $this->_getUserAuthorized();
        if (Lib::isFailed($authRes)) { return $authRes; }

        $params = [
            'operationName' => 'Core.Web.Request@GetDetails',
            'request' => self::_params([
                ['requestId', 'int', $requestId ],
                ['contractId', 'int', 0 ],
                ['ignoreContract', 'boolean', 'true']
            ])
        ];
        $res = $this->_exec('Execute', $params);

        if (Lib::isFailed($res)) { return $res; }

        $dt = &$res['data'];
        $dt = Lib::xmlToNormalizedArray($res['data']);

        if(!empty($dt['Error']) && $dt['Error'] === 'RC_OKPO_INVALID') {
            return ['state' =>
                [
                    'code' => $dt['Error'],
                    'msg' => str_replace("%s", $dt['OKPO'], $this->_T('ERR_OKPO_INVALID'))
                ],
            ];
        }

        if(!empty($dt['Comments'])) {
            $dt['Comments'] = Lib::rearrangeArray($dt,'Comments' ,'Comment', 'Id');
        }

        if(Lib::isOK($res) && empty($res['data'])) { return Lib::getCustomResultRec('RC_DRAFT_404'); }

        $promo = &$dt['ChangePricePromo'];
        if(!empty($promo['Lagers'])) {
            $dt['Lagers'] = Lib::rearrangeArray( $dt['ChangePricePromo'], 'Lagers', 'Lager', 'lagerId');
        }

        $locale = $this->getLocale();
        $xmlDatePattern     = Loc::getDateFmt('xml', Loc::STD, Loc::NONE);
        $xmlDateTimePattern = Loc::getDateFmt('xml', Loc::STD, Loc::STD);

        $promo['contractDate'] = Lib::dateToLocal($promo['contractDate']??'', $xmlDatePattern, $locale, Loc::LONG);
        $promo['dateFrom']     = Lib::dateToLocal($promo['dateFrom']??'', $xmlDatePattern, $locale, Loc::LONG);
        $promo['dateTo']       = Lib::dateToLocal($promo['dateTo']??'', $xmlDatePattern, $locale, Loc::LONG);

        $dt['RequestDate']     = Lib::dateToLocal(mb_substr($dt['RequestDate']??'',0,16)?:'', $xmlDateTimePattern, $locale, Loc::LONG, Loc::STD);

        if(is_array($dt['Contract'])) {
            if(is_array($dt['Contract']['ContractDate'])) {
                foreach($dt['Contract']['ContractDate'] as &$date) {
                    $date = Lib::dateToLocal($date, $xmlDatePattern, $locale, Loc::LONG);
                }
                unset($date);
            } else {
                $dt['Contract']['ContractDate'] = Lib::dateToLocal($dt['Contract']['ContractDate']??'', $xmlDatePattern, $locale, Loc::LONG);
            }
        }

        foreach($dt['Lagers'] as &$sku){
            $sku['dateFrom'] = Lib::dateToLocal($sku['dateFrom']??'', $xmlDatePattern, $locale, Loc::LONG);
            $sku['dateTo']   = Lib::dateToLocal($sku['dateTo']??'', $xmlDatePattern, $locale, Loc::LONG);
        }
        unset($sku);

        if(!empty($dt['Files'])) {
            $dt['Files'] = Lib::rearrangeArray($dt,'Files' ,'File', 'Id');
        }

        if(!empty($dt['ContractsWithoutAllows'])) {
            $dt['ContractsWithoutAllows']['ErrorContractsAllowed'] =  Lib::rearrangeArray($dt['ContractsWithoutAllows'], 'ErrorContractsAllowed' ,'ErrorContract', 'SAPID');
        }

        return $res;
    }    

   /** ========================================================================
     * @return array|bool
     */
    public function getProductsClassifier() {
        $authRes = $this->_getUserAuthorized();
        if (Lib::isFailed($authRes)) { return $authRes; }

        $cacheId = 'dict-products-classifier';
        $res = $this->getCacheData($cacheId);

        if (!$res
            || !is_array($res)
            || Lib::isFailed($res)) {

            $params = [
                'operationName' => 'Core.Web.ProductsClassifier@Get',
                'request' => []
            ];

            $res = $this->_exec('Execute', $params);

            if (Lib::isFailed($res)) {
                $this->clearCacheData($cacheId);
                return $res;
            }

            $res['data'] = Lib::xmlStrToRepackedArray($res['data'], 'Product', 'Id');

            if(count($res['data'])) { $this->setCacheData($cacheId, $res); }
        }
        return $res;
    }    

}
