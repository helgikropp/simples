<?php
namespace Storage\Model\Sp;

use SP\Utils\Lib;
use SP\Debug\Debug as SP_Debug;

class S3CloudWrapper  extends \Application\Model\AbstractWrapper {

    /** ========================================================================
     * @param string $bucket
     * @param string $folderPath
     * @param string $fileName
     * @return array|bool
     */
    public function removeFile(string $bucket, string $folderPath, string $fileName) {
        $authRes = $this->_getUserAuthorized();
        if (Lib::isFailed($authRes)) { return $authRes; }

        if(empty($folderPath)) { return Lib::getCustomResultRec('RC_WRONG_PATH'); }
        if(empty($fileName))   { return Lib::getCustomResultRec('RC_F_!DEF'); }

        $params = [
            'operationName' => 'Storage.S3CloudDelete',
            'bucketName' => $bucket,
            'folderPath' => $folderPath,
            'file_name'  => $fileName
        ];
        $res = $this->_exec('S3CloudDelete',$params);

        if(Lib::isFailed($res)) {
            if( $res['state']['code'] === 'RC_FORBIDDEN') {
                $res['state']['code'] = 'RC_ERROR';
                $res['state']['msg']  = 'ERR_FILE_NOT_FOUND';
            } else {
                $res['state']['code'] = 'RC_ERROR';
            }
        }

        return $res;
    }

    /** ========================================================================
     * @param string $bucket
     * @param string $folderPath
     * @param string $fileName
     * @return array|bool
     */
    public function getFile(string $bucketAlias, string $folderPath, string $fileName) {
        $authRes = $this->_getUserAuthorized();
        if (Lib::isFailed($authRes)) { return $authRes; }

        if(empty($folderPath)) { return Lib::getCustomResultRec('RC_WRONG_PATH'); }

        if(empty($fileName)) {
            $parts = explode('/',$folderPath);
            $fileName = $parts[count($parts)-1];
            unset($parts[count($parts)-1]);
            $folderPath = implode('/',$parts);
        }

        $cloud = $this->_getCloudOptions();
        $bucketName = $cloud['s3_buckets'][$bucketAlias];

        $params = [
            'operationName' => 'Storage.S3CloudFileDownload',
            'bucketName' => $bucketName,
            'folderPath' => $folderPath,
            'file_name' => $fileName
        ];

        $res = $this->_exec('S3CloudFileDownload',$params);
        if(Lib::isOK($res) && !empty($res['data']['File'])) {
            $res['data']['file'] = $res['data']['File'];
            unset($res['data']['File']);
            $res['data']['file_name']   = $fileName;
            $res['data']['contenttype'] = 'application/zip';
        }

        return $res;
    }
}
