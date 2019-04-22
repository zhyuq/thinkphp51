<?php


namespace app\controller;

use think\Controller;
use think\facade\Log;

class Asset extends Controller
{
    public function index()
    {

        $file_list = array();
        \zyq\File::visit("/Users/staff/坚果云-典藏版", true, function ($file) use (&$file_list) {
            if (is_dir($file) && (strpos($file, "新UI界面") !== false)) {
                $file_list[] = $file;
            }

        });

        $assign_file_list = array();
        foreach ($file_list as $key => $value) {
            $path_arr = explode("/", $value);
            $path_level = count($path_arr);
            $path_tail = substr($value, strlen("/Users/staff/坚果云-典藏版/"));
            if ($path_level == 5) {
//                Log::debug($path_tail);
                $assign_file_list[] = " -- " . $path_tail;
            } elseif ($path_level >= 6) {
                $baseName = basename($path_tail);
                $targetName = sprintf("%s%s -- %s", str_repeat('&nbsp;', $path_level-5), str_repeat("|", $path_level-5), $baseName);

                $assign_file_list[] = $targetName;
            }
        }
        Log::debug($assign_file_list);
        $this->assign("files", $assign_file_list);
        return $this->fetch();
    }
}