<?php

namespace Tests;

use Brightfish\EnhancedIni\EnhancedIni;
use PHPUnit\Framework\TestCase;

class ReadIniTest extends TestCase
{

     public function test_invalid_delimiters(){
         $ei=New EnhancedIni();
         $this->expectException(\ErrorException::class);
         $ei->set_delimiters("");
     }

    public function test_load_missing(){
        $ei=New EnhancedIni();
        $this->expectException(\ErrorException::class);
        $ei->load_ini("tests/ini/file_does_not_exist.ini");
    }

    public function test_load_normal(){
        $ei=New EnhancedIni();
        $ei->load_ini("tests/ini/example.ini");
        $data=$ei->get_all();
        $this->assertArrayHasKey("chapter1",$data);
    }

}
