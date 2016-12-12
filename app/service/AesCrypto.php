<?php
declare(strict_types = 1);

namespace T\Service;

/**
 * 用于使用 Aes 进行加密与解密的库
 * 使用 ECB 128 填充
 *
 * @author Han Guo Shuai
 *
 */
class AesCrypto extends IService {

    /**
     * 通过使用一个 key 加密任意的字符串
     *
     * @param string $original 要加密的原始字符串
     * @param string $key 加密字符串使用的密钥
     *
     * @return string
     */
    public static function encrypt($original, $key): string{

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $original . '#', MCRYPT_MODE_ECB));

    }

    /**
     * 通过使用 key 来解密使用上述函数加密的字符串
     *
     * @param string $encrypt 要解密的原始字符串
     * @param string $key 要使用的解密密钥
     *
     * @return bool|string
     */
    public static function decrypt($encrypt, $key) {

        if ($rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypt), MCRYPT_MODE_ECB)) {
            if(strrpos($rtn, '#') !== false) {
                // --- 解密后, 最后一位是定位符 #, 后面会有 AES 的填充, 都通通不要.
                return substr($rtn, 0, strrpos($rtn, '#'));
            } else {
                return false;
            }
        } else
            return false;

    }

}

