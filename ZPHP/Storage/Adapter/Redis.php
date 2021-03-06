<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Storage\Adapter;
use ZPHP\Common\Log;
use ZPHP\Manager,
    ZPHP\Storage\IStorage;

class Redis implements IStorage
{
    private $redis;
    private $sRedis = null;
    private $suffix = "";
    private $pconnect;
    private $config;
    private $slaveConfig;
    const CONNECTION_TIME_OUT = 10;
    private $lastPing = 0;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
            $this->pconnect = $config['pconnect'];
            $this->config = $config;
        }
    }

    public function checkPing()
    {
        $now = time();
        if ($now - $this->lastPing > self::CONNECTION_TIME_OUT) {
            if ($this->redis->ping() != "PONG") {
                $this->close();

                $this->redis = Manager\Redis::getInstance($this->config);
                if (!empty($this->slaveConfig)) {
                    $this->sRedis = Manager\Redis::getInstance($this->slaveConfig);
                }

                Log::info('zphp_redis',['ping error, reconnect', $config]);
            } else {
                Log::info('zphp_redis',['ping succuss']);
            }

            $this->lastPing = $now;
        }
    }

    public function setSlave($config)
    {
        if (empty($this->sRedis)) {
            $this->sRedis = Manager\Redis::getInstance($config);
        }
    }

    public function setKeySuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    private function uKey($userId)
    {
        return $userId . '_' . $this->suffix;
    }

    public function getMutilMD($userId, $keys, $slaveConfig = '')
    {
        $this->checkPing();
        $uKey = $this->uKey($userId);
        $datas = $this->redis->hMGet($uKey, $keys);
        foreach ($datas as $key => $val) {
            if (false === $val) {
                $val = $this->getSD($userId, $key, $slaveConfig);
                if (false !== $val) {
                    $datas[$key] = $val;
                }
            }
        }
        return $datas;
    }

    public function getMD($userId, $key, $slaveConfig = "")
    {
        $this->checkPing();
        $uKey = $this->uKey($userId);
        $data = $this->redis->hGet($uKey, $key);
        return $data;
    }

    public function getSD($userId, $key, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        $this->setSlave($slaveConfig);
        $data = $this->sRedis->hGet($uKey, $key);
        return $data;
    }

    public function setSD($userId, $key, $data, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        $this->setSlave($slaveConfig);
        $data = $this->sRedis->hSet($uKey, $key, $data);
        return $data;
    }

    public function delSD($userId, $key, $slaveConfig = "")
    {
        $uKey = $this->uKey($userId);
        $this->setSlave($slaveConfig);
        $data = $this->sRedis->hDel($uKey, $key);
        return $data;
    }

    public function setMD($userId, $key, $data, $cas = false)
    {
        $this->checkPing();
        if ($cas) {
            return $this->setMDCAS($userId, $key, $data);
        }
        $uKey = $this->uKey($userId);
        return $this->redis->hSet($uKey, $key, $data);
    }

    public function addMD($userId, $key, $data)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->hSetNx($uKey, $key, $data);
    }

    public function setMDCAS($userId, $key, $data)
    {
        $uKey = $this->uKey($userId);
        $this->redis->watch($uKey);
        $result = $this->redis->multi()->hSet($uKey, $key, $data)->exec();
        if (false === $result) {
            throw new \Exception('cas error');
        }
        return $result;
    }

    public function del($userId, $key)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->hDel($uKey, $key);
    }

    public function setMultiMD($userId, $keys)
    {
        $uKey = $this->uKey($userId);
        return $this->redis->hMSet($uKey, $keys);
    }

    public function close()
    {
        if ($this->pconnect) {
            return true;
        }

        $this->redis->close();

        if (!empty($this->sRedis)) {
            $this->sRedis->close();
        }

        return true;
    }

    public function getMulti($cmds)
    {
        $this->redis->multi(\Redis::PIPELINE);
        foreach ($cmds as $userId => $key) {
            $uKey = $this->uKey($userId);
            $this->redis->hGet($uKey, $key);
        }

        return $this->redis->exec();
    }
}
