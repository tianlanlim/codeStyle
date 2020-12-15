<?php

/**
 * token认证模块管理
 * ----------------------------------------------------------------------------
 * $File: token.model.php
 */

class token extends modelBase
{

    /* token有效期，默认15天 */
    public static $token_time = 1296000;

    /**
     * 初始化token
     * @param string $token
     * @param array $device_info
     * @return string
     */
    public static function startToken($token = '', $device_info = array())
    {
        /* 1/500的概率清除过期token */
        if (mt_rand(0, 500) == 500) {
            token::delete(null, array('where' => " `expiry` < " . NOW_TIME));
        }

        /* 设备唯一性标识 */
        $device_md5 = md5('cor'.$device_info['deviceId'].$device_info['deviceModel']);
        $device_info = serialize($device_info);

        $row = array();
        if (!empty($token)) {
            $row = token::getRowInfo($token);
        }

        $_COOKIE['COR_API_ID'] = '';
        /* token为空或不存在或过期时，重新创建token */
        if (empty($token) || empty($row) || $row['expiry'] < NOW_TIME) {
            $token = self::makeToken();
            token::insert(array(
                'token' => $token,
                'expiry' => NOW_TIME + token::$token_time,
                'device_info' => $device_info,
                'device_md5' => $device_md5
            ));
        } else {
            if (!empty($row['sesskey'])) {
                $_COOKIE['COR_API_ID'] = $row['sesskey'];
            }
            token::update(array(
                'expiry' => NOW_TIME + token::$token_time,
                'device_info' => $device_info,
                'device_md5' => $device_md5
            ), $token);
        }
        return $token;
    }

    /**
     * 创建一个不重复的token
     * @return string
     */
    public static function makeToken()
    {
        $token = generate_str(32);
        if (token::isOnly('token', $token)) {
            return self::makeToken();
        } else {
            return $token;
        }
    }

    /**
     * 更新sesskey
     * @param $token
     * @param $sesskey
     * @return bool
     */
    public static function updateSessKey($token, $sesskey)
    {
        return token::update(array('sesskey' => $sesskey), $token);
    }

}