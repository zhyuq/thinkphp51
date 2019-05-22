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
    private $unlock = false;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->originPath = $this->request->server("HOME") . "/" . ORIGINAL_PATH;
        $this->resGitPath = $this->request->server("HOME") . "/" . RES_GIT_PATH;
    }

    public function __destruct() {
        $this->unlock();
    }

    public function index()
    {

        $file_list = array();
        File::visit($this->originPath, true, function ($file) use (&$file_list) {
            if (!is_dir($file))
                return;

            if (preg_match('/.*(策划档|多语言|启动图和icon|SDK).*/i', $file))
                return;

            $file_list[] = $file;
        });

        $assign_file_list = array();
        foreach ($file_list as $key => $value) {
            $config = is_file("{$value}/.res-config") ? json_decode(file_get_contents("{$value}/.res-config"), true) : null;
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
        } elseif ($language == "lang_tw") {
            $langPath = "tw";
        }

        if ((!$export_name && $type) || ($export_name && !$type)) {
            printf("Resource: at the same time - export_name and type must null or not null<br><br>");
            return;
        }

        if ($export_name && !$language) {
            printf("Resource: language can not null<br>");
            return;
        }

        if (!$this->store())
            return $this->fetch("lock");

        $outPath = $this->originPath . "/" . $path;
        $repo = $this->resGitPath . "/" . $langPath;
        $funName = camelize("build_" . $type);
        if (!empty($type)) {
            call_user_func(array($this, $funName), $outPath, $repo, $export_name, $change_mode);
        } else {
            File::visit($outPath, true, function ($file) {
                if (!is_dir($file))
                    return;

                $value = null;
                if (is_file($file . "/.res-config")) {
                    $value = json_decode(file_get_contents($file . "/.res-config"), true);
                }

                if ($value) {
                    $export_name = $value["export_name"];
                    $change_mode = $value["change_mode"];
                    $type = $value["type"];
                    $language = $value["language"];
                    $langPath = "cn";
                    if ($language == "lang_cn") {
                        $langPath = "cn";
                    } elseif ($language == "lang_tw") {
                        $langPath = "tw";
                    }
                    $repo = $this->resGitPath . "/" . $langPath;
                    $funName = camelize("build_" . $type);
                    if ($export_name && $type) {
                        call_user_func(array($this, $funName), $file, $repo, $export_name, $change_mode);
                    }
                }
            });
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

    public function buildPlayer($path, $repo, $exportName, $changeMode)
    {
        $this->buildRole("role", $path, $repo, $exportName, $changeMode);
    }

    public function buildRole($type, $path, $repo, $exportName, $changeMode)
    {
        printf("Resource: build role: %s<br>", $path);
    }

    public function store()
    {
        if (!$this->lock())
            return false;

        $cache = json_decode(Request::param("cache", "{}"), true);
        foreach ($cache as $key => $value) {
            $folder = $this->originPath . "/" . $key;
            $config = $folder . "/.res-config";

            if (file_put_contents($config, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) == false) {
                printf("Resource: write .res-config file error in %s", $key);
                return false;
            }
        }

        return true;
    }

    //https://www.exakat.io/prevent-multiple-php-scripts-at-the-same-time/
    protected function lock()
    {
        $lock = $this->request->server("DOCUMENT_ROOT") . "/../../tmp/lock";
        if (file_exists($lock)) {
            $this->assign("ip", file_get_contents($lock));
            $this->assign("time", date(DATE_RFC3339, filemtime($lock)));
            return false;
        } else {
            $this->unlock = true;
            file_put_contents($lock, $this->request->server("REMOTE_ADDR"));
            return true;
        }
    }

    protected function unLock()
    {
        if ($this->unlock) {
            unlink($this->request->server("DOCUMENT_ROOT") . "/../../tmp/lock");
        }
    }
}