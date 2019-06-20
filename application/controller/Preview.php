<?php

namespace app\controller;


use think\Controller;
use zyq\File;

class Preview extends Controller
{
    public function index()
    {
        
    }

    public function sk()
    {
        $path = "/Users/preview/sk";
        $json = array();

        File::visit($path, false, function ($fileName) use (&$json) {
            if (preg_match('/\.json$/', $fileName)) {
                $json[] = basename($fileName, ".json");
            }
        });

        $data = array();
        $data["err"] = 0;
        $data["msg"] = "ok";
        $data["data"] = $json;
        $data["url"] = "http://" . $this->request->server("HTTP_HOST") . "/preview/sk";

        return json($data);
    }

    public function cbi()
    {
        $path = "/Users/tmp/preview/cbi";
        $json = array();

        File::visit($path, false, function ($fileName) use (&$json) {
            if (preg_match('/\.ccbi/', $fileName)) {
                $json[] = basename($fileName, ".ccbi");
            }
        });

        $data = array();
        $data["err"] = 0;
        $data["msg"] = "ok";
        $data["data"] = $json;
        $data["url"] = "http://" . $this->request->server("HTTP_HOST") . "/preview/cbi";

        return json($data);
    }
}