<?php

namespace zyq;

class File
{
    public static function visit($directory, $recursive, $callback)
    {
        if (!is_dir($directory)) {
            return;
        }

        $dir = opendir($directory);
        $cur = null;
        if (!$dir)
            return;

        while(false !== ($cur = readdir($dir))) {
            if ($cur[0] == '.')
                continue;

            $filename = ($directory ? $directory . "/" : "") . $cur;
            $callback($filename);

            if (is_dir($filename) && $recursive) {
                self::visit($filename, $recursive, $callback);
            }
        }

        closedir($dir);
    }
}