<?php


namespace app\controller;

use think\App;
use think\Controller;
use think\facade\Log;
use think\facade\Request;
use zyq\File;

class Resource extends Controller
{
    protected $originPath;
    protected $resPath;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->originPath = $this->request->server("HOME") . "/" . ORIGINAL_PATH;
        $this->resGitPath = $this->request->server("HOME") . "/" . RES_GIT_PATH;
    }

    public function index()
    {

        $file_list = array();
        \zyq\File::visit($this->originPath, true, function ($file) use (&$file_list) {
            if (!is_dir($file))
                return;

            if (preg_match('/.*(策划档|多语言|启动图和icon|SDK).*/i', $file))
                return;

            $file_list[] = $file;
        });

        $assign_file_list = array();
        foreach ($file_list as $key => $value) {
            $config = is_file("{$value}/.admin_config") ? json_decode(file_get_contents("{$value}/.admin_config"), true) : null;
            $path_level = substr_count($value, "/");
            $path_tail = substr($value, strlen($this->originPath)+1);
            $baseName = basename($path_tail);
            $targetName = sprintf("%s%s -- %s", str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $path_level-4), str_repeat("|", $path_level-4), $baseName);

            $assign_file_value = array();
            $assign_file_value["filePath"] = $path_tail;
            $assign_file_value["targetName"] = $targetName;
            $assign_file_value["export_name"] = $config["export_name"];
            $assign_file_value["change_mode"] = $config["change_mode"];
            $assign_file_value["type"] = $config["type"];
            $assign_file_value["language"] = $config["language"];
            $assign_file_list[] = $assign_file_value;

        }
//        Log::debug($assign_file_list);
        $this->assign("files", $assign_file_list);
        return $this->fetch();
    }

    public function build()
    {
        $path = Request::param("path");
        $export_name = Request::param("export_name");
        $change_mode = Request::param("change_mode");
        $type = Request::param("type");
        $language = Request::param("language");

        $langPath = "cn";
        if ($language == "lang_cn") {
            $langPath = "cn";
        }

        $exportPath = $this->resGitPath . "/" . $langPath . "/" . $export_name;

        File::copyFileWithDirs($this->originPath . "/" . $path, $exportPath, function ($file, $target) {
            File::convertPng($file, dirname($target));
        });

        return "";
    }
}