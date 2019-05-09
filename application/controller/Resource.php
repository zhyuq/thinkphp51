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

        $outPath = $this->originPath . "/" . $path;
        $repo = $this->resGitPath . "/" . $langPath;
        $funName = camelize("build_" . $type);
        if (!empty($type)) {
            call_user_func(array($this, $funName), $outPath, $repo, $export_name, $change_mode);
        } else {

        }

        return "";
    }

    public function buildCopyWithoutDir($path, $repo, $exportName, $changeMode)
    {
        $target = $repo . "/" . $exportName;
        if ($changeMode == "delete_old") {
            printf("Resource: delete folder %s <br>", $target);
            passthru("rm -rf $target");
        }

        File::copyFilesWithoutDirs($path, $repo . "/" . $exportName, function ($file, $target) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension == "png") {
                File::convertPng($target, dirname($target));
            } elseif ($extension == "plist") {

            } else {

            }

        });
    }

    public function buildCopyWithDir($path, $repo, $exportName, $changeMode)
    {
        $target = $repo . "/" . $exportName;
        if ($changeMode == "delete_old") {
            printf("Resource: delete folder %s <br>", $target);
            passthru("rm -rf $target");
        }

        File::copyFileWithDirs($path, $repo . "/" . $exportName, function ($file, $target) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension == "png") {
                File::convertPng($target, dirname($target));
            } elseif ($extension == "plist") {

            } else {

            }

        });
    }

    public function buildTown($path, $repo, $exportName, $changeMode)
    {
        printf("Resource: build town file %s <br>", $path);
        $target = $repo . "/" . "city";
        if (!file_exists($target))
            mkdir($target, 0755, true);

        $earth = "$path/地表.png";
        $size = getimagesize($earth);
        if ($size === false) {
            printf("Resource: build town image file error %s<br>", $earth);
            return;
        }

        $data = array(
            "earth" => array(),
            "distant" => array(),
            "cover" => array(),
            "path" => "",
            "size" => array(
                "width" => $size[0],
                "height" => $size[1]
            )
        );

        $isHaveXmlFile = false;
        File::visit($path, false, function ($fileName) use (&$isHaveXmlFile, $path, $target, $exportName, &$data) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if ($extension == "xml") {
                $isHaveXmlFile = true;
            } elseif (preg_match('/地表\.png/', $fileName)) {
                $arr = File::splitMapImage($fileName, $target, function ($fileName) use ($exportName, &$data) {
                    $reName = dirname($fileName) . "/{$exportName}_" . basename($fileName);
                    rename($fileName, $reName);

                    if (preg_match('/\.png$/', $reName)) {
                        File::convertPng($reName, dirname($reName));
                    }

                });

                foreach ($arr as $value) {
                    $data["earth"][] = array(
                        "image" => "city/{$exportName}_" . basename($value["image"]),
                        "x" => $value["x"],
                        "y" => $value["y"]
                    );
                }
            } elseif (preg_match('/远景\.png/', $fileName)) {
                $arr = File::splitMapImage($fileName, $target, function ($fileName) use ($exportName, &$data) {
                    $reName = dirname($fileName) . "/{$exportName}_" . basename($fileName);
                    rename($fileName, $reName);

                    if (preg_match('/\.png$/', $reName)) {
                        File::convertPng($reName, dirname($reName));
                    }
                });

                foreach ($arr as $value) {
                    $data["distant"][] = array(
                        "image" => "city/{$exportName}_" . basename($value["image"]),
                        "x" => $value["x"],
                        "y" => $value["y"]
                    );
                }
            } elseif (preg_match('/遮挡\.png/', $fileName)) {
                $arr = File::splitMapImage($fileName, $target, function ($fileName) use ($exportName, &$data) {
                    $reName = dirname($fileName) . "/{$exportName}_" . basename($fileName);
                    rename($fileName, $reName);

                    if (preg_match('/\.png$/', $reName)) {
                        File::convertPng($reName, dirname($reName));
                    }
                });

                foreach ($arr as $value) {
                    $data["cover"][] = array(
                        "image" => "city/{$exportName}_" . basename($value["image"]),
                        "x" => $value["x"],
                        "y" => $value["y"]
                    );
                }
            }
        });

        if (!$isHaveXmlFile) {
            printf("Resource: town xml file not found %s<br>", $path);
            return;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        file_put_contents("$target/{$exportName}.json", $json);

    }
}