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

            printf("copy files with dirs file: %s<br>", $fileName);

            $sub = substr($fileName, strlen($folder) + 1);
            $dir = $target;


            if (preg_match('/\//', $sub)) {
                $dir = $target . "/" . dirname($sub);
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $finalTarget = $dir . "/" . basename($fileName);

            if (!copy($fileName, $finalTarget)) {
                printf("copy files with dirs file: %s<br>", error_get_last()["message"]);
            }

            /** @var callable $callback */
            if ($callback) {
                $callback($fileName, $finalTarget);
            }
        });

        printf("copy files with dirs done <br>");
    }

    public static function copyFilesWithoutDirs($folder, $target, $callback = null)
    {
        if (!file_exists($target))
            mkdir($target, 0755, true);

        File::visit($folder, true, function ($fileName) use ($target, $callback) {
            if (!is_file($fileName))
                return;

            printf("copy file without dirs %s<br>", $fileName);
            $baseName = basename($fileName);
            $dest = $target . "/" . $baseName;
            if (!copy($fileName, $dest)) {
                printf("copy file without dirs failure %s<br>", $fileName);
            }

            /** @var callable $callback */
            if ($callback) {
                $callback($fileName, $dest);
            }
        });

        printf("copy file without dirs done<br>");
    }

    public static function convertPng($file, $target)
    {
        printf("file: pngquant png file: %s", $file);

        if (!file_exists($target))
            mkdir($target, 0755, true);

        $cmd = __DIR__ . "/../../../pngquant/pngquant";
        $dest = $target . "/" . basename($file);

        passthru("$cmd --force --skip-if-larger --verbose $file -o $dest");

        printf("<br><br>");
    }

    public static function splitMapImage($file, $target, $callback)
    {
        printf("file: split map image %s<br>", $file);

        $isPng = preg_match('/\.png$/i', $file);
        $isJpg = preg_match('/\.jpg$/i', $file);

        if (!$isPng && !$isJpg) {
            return null;
        }

        if (!file_exists($file)) {
            return null;
        }

        if (!file_exists($target)) {
            mkdir($target, 0755, true);
        }

        $image = $isPng ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
        $width = imagesx($image);
        $height = imagesy($image);
        $limitW = 1024;
        $limitH = 1024;
        $countX = ceil($width/$limitW);
        $countY = ceil($height/$limitH);
        $info = array();

        for ($x = 0; $x < $countX; $x++) {
            for ($y = 0; $y < $countY; $y++) {
                $cropW = ($x == $countX-1) ? $width%$limitW : $limitW;
                $cropH = ($y == $countY-1) ? $height%$limitH : $limitH;
                $name = sprintf("%s_%d_%d%s", pathinfo($file, PATHINFO_FILENAME), $x, $y, $isPng?".png":".jpg");
                $im = @imagecreatetruecolor($cropW, $cropH);

                if ($isPng) {
                    $trans_colour = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    imagefill($im, 0, 0, $trans_colour);
                    imagealphablending($im , false);
                }

                imagecopyresized($im, $image, 0, 0, $x*$cropW, $y*$cropH, $cropW, $cropH, $cropW, $cropH);

                if ($isPng) {
                    imagesavealpha($im , true);
                    imagepng($im, "$target/$name");
                } else {
                    imagejpeg($im, "$target/$name");
                }

                if ($callback) {
                    $callback("$target/$name");
                }

                $info[] = array(
                    "image" => $name,
                    "x" => $x*$limitW,
                    "y" => $y*$limitH
                );

                imagedestroy($im);
            }
        }

        imagedestroy($image);

        return $info;
    }

    public static function convertFbx($fbx, $target)
    {
        printf("file: fbx to c3b: %s<br>", $fbx);

        if (!file_exists($target))
            mkdir($target, 0755, true);

        $baseName = basename($fbx, ".FBX");
        $dir = dirname($fbx);
        $c3b = "$target/$baseName.c3b";
        $c3t = "$dir/$baseName.c3t";
        $cmd = __DIR__ . "/../../../fbx-conv/mac/fbx-conv";

        if (!is_file($c3b) || (filemtime($c3b) < filemtime($fbx)) || !is_file($c3t)) {
            passthru("$cmd -a $fbx");
            rename("$dir/$baseName.c3b", $c3b);
            touch($c3b, filemtime($fbx));
        } else {
            printf("file: fbx file is latest.<br>");
        }

    }
}