<?php
include_once("vendor/autoload.php");
use Brightfish\EnhancedIni\EnhancedIni;

$ei=New EnhancedIni();
try {
    $ei->load_ini("tests/ini/complex.ini");
    echo "# chapter1:list\n";
    print_r($ei->get("list","chapter1"));
    echo "# get_all\n";
    $data=$ei->get_all();
    var_export($data);
} catch (Exception $e) {
    print_r($e->getMessage());
}



