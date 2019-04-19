<?php


namespace app\controller;

use think\Controller;

class Asset extends Controller
{
    public function index()
    {

        $this->assign("files", array("file0", "file1", "file2", "file3", "file4"));
        return $this->fetch();
    }
}