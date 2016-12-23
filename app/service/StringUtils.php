<?php
declare (struct_types = 1);

namespace T\Service;

use T\Msg as msg;

class StringUtils extends IService {

    /**
     * 十个阿拉伯数字
     */
    const RAND_SRC_N                    = 0;

    /**
     * 小写字母
     */
    const RAND_SRC_LA                   = 1;

    /**
     * 大写字母
     */
    const RAND_SRC_UA                   = 2;

    /**
     * 大小写字母
     */
    const RAND_SRC_A                    = 3;

    /**
     * 特殊字符集 {
     *   '!', '@', '#', '$', '%', '^', '&', '*',
     *   '(', ')', '[', ']', '{', '}', ':', ';',
     *   '|', '"', '<', '>', ',', '.', '?', '/',
     *   '-', '_', '+', '=', '`', '~', '\\', '\'' 
     * }
     */
    const RAND_SRC_SC                   = 4;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_LA.
     */
    const RAND_SRC_N_LA                 = 5;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_UA.
     */
    const RAND_SRC_N_UA                 = 6;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_UA, RAND_SRC_LA.
     */
    const RAND_SRC_N_A                  = 7;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_SC.
     */
    const RAND_SRC_N_SC                 = 8;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_LA, RAND_SRC_SC.
     */
    const RAND_SRC_N_LA_SC              = 9;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_UA, RAND_SRC_SC.
     */
    const RAND_SRC_N_UA_SC              = 10;

    /**
     * Mix of RAND_SRC_N, RAND_SRC_A, RAND_SRC_SC.
     */
    const RAND_SRC_N_A_SC               = 11;

    /**
     * Mix of RAND_SRC_LA, RAND_SRC_SC.
     */
    const RAND_SRC_LA_SC                = 12;

    /**
     * Mix of RAND_SRC_UA, RAND_SRC_SC.
     */
    const RAND_SRC_UA_SC                = 13;

    /**
     * Mix of RAND_SRC_A, RAND_SRC_SC.
     */
    const RAND_SRC_A_SC                 = 14;

    /**
     * Use customized source
     */
    const RAND_SRC_CUSTOMIZED           = 0xFF;

    /**
     * 根据指定规则生成指定长度的随机字符串。
     * 
     * @param int $len
     *     要生成的字符串长度
     * @param int $source
     *     随机数生成规则，默认为数字和大小写字母
     * @param string $customSource
     *     使用自定义随机字符集，当 $source === RAND_SRC_CUSTOMIZED 时生效
     * 
     * @throws msg\ServiceFailure
     * @return string
     */
    public static function random(
        int $len,
        int $source = self::RAND_SRC_N_A,
        string $customSource = null
    ): string {

        if ($len < 0) {

            throw new msg\ServiceFailure(
                'Invalid parameter for $len'
            );
        }

        $ret = '';

        switch ($source) {
        default:
        case self::RAND_SRC_N:          while ($len--) { $ret .= '0123456789'[mt_rand(0, 9)]; }
            break;
        case self::RAND_SRC_LA:         while ($len--) { $ret .= 'abcdefghijklmnopqrstuvwxyz'[mt_rand(0, 25)]; }
            break;
        case self::RAND_SRC_UA:         while ($len--) { $ret .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[mt_rand(0, 25)]; }
            break;
        case self::RAND_SRC_A:          while ($len--) { $ret .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'[mt_rand(0, 51)]; }
            break;
        case self::RAND_SRC_SC:         while ($len--) { $ret .= '`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 31)]; }
            break;
        case self::RAND_SRC_N_LA:       while ($len--) { $ret .= '0123456789abcdefghijklmnopqrstuvwxyz'[mt_rand(0, 35)]; }
            break;
        case self::RAND_SRC_N_UA:       while ($len--) { $ret .= '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[mt_rand(0, 35)]; }
            break;
        case self::RAND_SRC_N_A:        while ($len--) { $ret .= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'[mt_rand(0, 61)]; }
            break;
        case self::RAND_SRC_N_SC:       while ($len--) { $ret .= '0123456789`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 41)]; }
            break;
        case self::RAND_SRC_N_LA_SC:    while ($len--) { $ret .= '0123456789abcdefghijklmnopqrstuvwxyz`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 67)]; }
            break;
        case self::RAND_SRC_N_UA_SC:    while ($len--) { $ret .= '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 67)]; }
            break;
        case self::RAND_SRC_N_A_SC:     while ($len--) { $ret .= '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 93)]; }
            break;
        case self::RAND_SRC_LA_SC:      while ($len--) { $ret .= 'abcdefghijklmnopqrstuvwxyz`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 57)]; }
            break;
        case self::RAND_SRC_UA_SC:      while ($len--) { $ret .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 57)]; }
            break;
        case self::RAND_SRC_A_SC:       while ($len--) { $ret .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz`~!@#$%^&*()_+-=[]{};\'\\:"|,./<>?'[mt_rand(0, 83)]; }
            break;
        case self::RAND_SRC_CUSTOMIZED:

            $l = strlen($customSource) - 1;

            while ($len--) { $ret .= $customSource[mt_rand(0, $l)]; }

            break;
        }

        return $ret;
    }

    /**
     * 检测一个字符串是否为十六进制字符串
     * @param string $str
     * @return bool
     */
    public static function isHexString(string $str): bool {

        return preg_match('/^[A-Fa-f0-9]+$/', $str) ? true : false;
    }
}
