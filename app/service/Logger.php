<?php

declare (strict_types = 1);

namespace T\Service;

/**
 * 这个工具类提供日志的写入方法。
 *
 * @author Angus Fenying <i.am.x.fenying@gmail.com>
 *
 */
class Logger extends IService {

    const NORMAL = 'Normal';
    const FETAL_ERROR = 'Fetal Error';

    /**
     * 向指定的日志文件中写入一条记录。
     *
     * @param string $type
     *     日志文件名，不带后缀。
     * @param string $level
     *     日志的等级
     * @param string $text
     *     要写入的日志内容
     * @param string $from
     *     在代码的什么位置写入了该日志，默认null，系统则自动从调用栈中获取。
     */
    public static function write(string $type, string $level, string $text, string $from = null) {

        $fn = T_LOGS_ROOT . $type . '.log';

        if (!file_exists($fn)) {
            touch($fn);
            chmod($fn, 0755);
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $date = DATENOW;
        $pos = $from ?? getCallerLine();
        $log_info = <<<LOG
======================================
Type:       {$level}
Date:       {$date}
Access IP:  {$_SERVER['REMOTE_ADDR']}
Request:    {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}
Agent:      {$ua}
Position:   {$pos}
--------------------------------------
{$text}

LOG;

        file_put_contents($fn, $log_info, FILE_APPEND);
    }
}

