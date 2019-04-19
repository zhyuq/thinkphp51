<?php
namespace app\controller;

use think\Controller;
use zyq\File;


class Index extends Controller
{
    public function index()
    {
        \zyq\File::visit(__DIR__, true, function ($file) {
            echo $file . '<br>';
        });
        return $this->fetch("index");
    }

    public function hello($name = 'dddd')
    {
        return 'hello,' . $name;
    }
}
