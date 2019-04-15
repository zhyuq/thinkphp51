<?php


namespace app\controller;


class Study
{
    public function index()
    {
        return "study";
    }

    public function testStudy($name)
    {
        return "testStudy +++ " . $name;
    }
}