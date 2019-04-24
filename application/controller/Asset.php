<?php


namespace app\controller;

use think\Controller;
use think\facade\Log;
use think\Request;

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
            $baseName = basename($path_tail);
            $targetName = sprintf("%s%s -- %s", str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $path_level-5), str_repeat("|", $path_level-5), $baseName);

            $assign_file_value = array();
            $assign_file_value["filePath"] = $path_tail;
            $assign_file_value["targetName"] = $targetName;
            $assign_file_list[] = $assign_file_value;

        }
//        Log::debug($assign_file_list);
        $this->assign("files", $assign_file_list);
        return $this->fetch();
    }

    public function build(Request $request)
    {
        return "asset build";
    }
}