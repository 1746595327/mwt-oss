<?php
/**
 * Author: 李硕
 * Email: kezuo@foxmail.com
 * Date: 2021/9/9
 * Time: 15:47
 */

namespace mwt\oss\storage\qiniu;


use mwt\oss\exception\ConfigException;
use mwt\oss\response\DeleteResponse;
use mwt\oss\response\PutResponse;
use mwt\oss\storage\ICloudStorage;
use mwt\oss\storage\StorageConfig;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu implements ICloudStorage
{

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var string;
     */
    private $bucket;

    /**
     * @param StorageConfig $config
     * @return mixed|void
     * @throws ConfigException
     */
    public function init(StorageConfig $config)
    {
        $config->checkParams();
        $this->auth = new Auth($config->getAppId(), $config->getAppKey());
        return $this;
    }

    public function bucket(string $bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    public function get(int $limit, string $delimiter = '', string $prefix = '', string $marker = '')
    {
        $bucketManager = new BucketManager($this->auth);
        list($ret, $err) = $bucketManager->listFiles($this->bucket, $prefix, $marker, $limit, $delimiter);

        return $err ?: $ret;
    }

    public function put(string $key, string $path): PutResponse
    {
        $token = $this->auth->uploadToken($this->bucket);
        $uploadMgr = new UploadManager();

        list($ret, $err) = $uploadMgr->putFile($token, $key, $path);

        $result = $err ?: $ret;
        return new PutResponse($key, $result);
    }

    public function putPart(string $key, string $path, int $partSize = null): PutResponse
    {
        return $this->put($key, $path);
    }

    public function delete(array $keys): DeleteResponse
    {
        $bucketManager = new BucketManager($this->auth);
        $ops = $bucketManager->buildBatchDelete($this->bucket, $keys);
        list($ret, $err) = $bucketManager->batch($ops);

        $result = $err ?: $ret;
        return new DeleteResponse($keys, $result);
    }
}