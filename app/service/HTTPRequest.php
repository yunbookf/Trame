<?php
declare(strict_types = 1);

namespace T\Service;

/**
 * 用于处理一些网络请求
 *
 * @author Han Guo Shuai
 *
 */
class HTTPRequest extends IService {

    /**
     * 通过 GET 方法获取一个 URL 的内容
     *
     * @param string $url 要获取的 URL
     *
     * @return bool
     */
    public static function get(string $url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);
        if ($output) {
            return $output;
        } else {
            return false;
        }

    }

    /**
     * 通过 POST 方法上传数据或文件并获取返回内容
     *
     * @param string $url
     *            要获取的 URL
     *
     * @param array $data
     *            要提交的数据对，若要上传文件，内容前加@
     *
     * @return boolean | string
     */
    public static function post(string $url, array $data = []) {

        $upload = false;
        foreach ($data as $i) {
            if ($i[0] === '@') {
                $upload = true;
                break;
            }
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            $upload ? $data : http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);
        if ($output) {
            return $output;
        } else {
            return false;
        }

    }

}

