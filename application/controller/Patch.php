<?php

namespace app\controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use think\Controller;
use yidas\phpSpreadsheet\Helper;

class Patch extends Controller
{
    public function index()
    {
        $text = "";
        $repo = $this->request->server("HOME") . "/Documents/sxd-js";
        $patch = "$repo/build/patch.php";

        $list = array();
        require($patch);

        $spreadsheet = $this->excel();

        Helper::newSpreadsheet($spreadsheet)->setSheet(0)->setRowOffset(1);
        $sheetColor = array("ff7f91b1", "ff4e6c9b", "ff728531", "ffd8b973", "ff96708a");
        $duplicate = array();
        foreach ($list as $channel) {
            $config = "$repo/build/compile/$channel.php";
            $settings = array();
            require($config);

            $package  = $settings["package"];
            $language = $settings["language"];

            $text .= "$channel:\n";

            foreach ($package as $each) {
                $remark = $each["remark"];
                $server = isset($each["server"]) ? $each["server"] : "Error";
                $version = $each["version"];
                $length = count($version);
                if ($length < 2) {
                    continue;
                }

                $text .= "    $version[0]($remark):\n";
                $cpp = explode("_", $version[0])[2];
                $target = $version[$length - 1];
                for ($i = 0; $i < $length - 1; $i++) {
                    $source = $version[$i];
                    $js_source = explode("_", $source)[0];
                    $js_target = explode("_", $target)[0];

                    $res_source = explode("_", $source)[1];
                    $res_target = explode("_", $target)[1];

//                    $file = "{$js_source}_{$res_source}_{$js_target}_{$res_target}.json";
                    $dir = __DIR__ . "/../../public/tmp/";
                    $file = "{$dir}datTest.json";
                    if ($file) {
                        $json = json_decode(file_get_contents($file), true);
                        foreach ($json as $dat) {
                            $size = filesize("{$dir}{$dat}");
                            $text .= "          {$js_source} -> {$js_target} : $dat, $size\n";
                            $dup = "$server-$js_source-$cpp";
                            if (array_search($dup, $duplicate) !== false)
                                continue;

                            $duplicate[] = $dup;

                            $val = [
                                $dat, $server, $js_source, $js_target, $cpp, $size
                            ];

                            Helper::addRow($val);

                        }
                    } else {
                        $text .= "          {$js_source} -> {$js_target}: unknown\n";
                    }
                }

            }

            $text .= "\n";
        }

        $text .= "\n";


        Helper::save(__DIR__ . "/../../public/tmp/patch.cn");

        $this->assign("text", $text);
        return $this->fetch();
    }

    private function excel()
    {
        $names = [
                ["value"=>"补丁", "width"=>80],
                ["value"=>"channel", "width"=>20],
                ["value"=>"start_ver", "width"=>20],
                ["value"=>"end_ver", "width"=>20],
                ["value"=>"c++ ver", "width"=>20],
                ["value"=>"extra_data", "width"=>20],
            ];

        $style = [
            "style" => [
                'fill' => [
                    'color' => ['argb' => 'ffcbcdfb'],
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ]
                ]
            ]
        ];



        $defaultStyle = array(
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'fillType' => 'solid'
            )
        );

        Helper::newSpreadsheet()
            ->setSheet(0);


        $obj = Helper::getSpreadsheet();
        $obj->getDefaultStyle()->applyFromArray($defaultStyle);

        Helper::addRow($names, $style);

        return $obj;
//        Helper::output();

    }
}
