<?php

declare (strict_types = 1);

namespace T\Service;

/**
 * This class provides methods to write logs
 *
 * @author Angus.Fenying
 *
 */
class Logger extends IService {

    const NORMAL = 'Normal';
    const FETAL_ERROR = 'Fetal Error';

    /**
     * Write a piece of log into file.
     *
     * @param string $type
     *            Name of file, without extension.
     * @param string $text
     *            Log info.
     */
    public static function write(string $type, string $level, string $text) {

        $fn = T_LOGS_ROOT . $type . '.log';

        if (!file_exists($fn)) {
            touch($fn);
            chmod($fn, 0755);
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $date = DATENOW;
        $pos = getCallerLine();
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

