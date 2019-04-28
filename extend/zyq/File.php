<?php

namespace zyq;

use think\facade\Log;

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

    public static function copyFileWithDirs($folder, $target, $callback = null)
    {
        if (!file_exists($target)) {
            mkdir($target, 0755, true);
        }

        File::visit($folder, true, function ($fileName) use ($folder, $target, $callback) {
            if (!is_file($fileName)) {
                return;
            }

            printf("file: copy entire file: %s<br>", $fileName);

            $sub = substr($fileName, strlen($folder) + 1);
            $dir = $target;


            if (preg_match('/\//', $sub)) {
                $dir = $target . "/" . dirname($sub);
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $finalTarget = $dir . "/" . basename($fileName);

            Log::debug($finalTarget);
            if (!copy($fileName, $finalTarget)) {
                printf("file: copy entire file error: %s<br>", error_get_last()["message"]);
            }

            /** @var callable $callback */
            if ($callback) {
                $callback($fileName, $finalTarget);
            }
        });
    }
}