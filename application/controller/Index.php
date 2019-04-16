<?php
namespace app\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch("index");
    }

    public function hello($name = 'dddd')
    {
        return 'hello,' . $name;
    }
}
