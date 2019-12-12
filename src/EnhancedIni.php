<?php


namespace Brightfish\EnhancedIni;

use function PHPUnit\Framework\throwException;

class EnhancedIni
{
    protected $data=[];
    protected $var_prefix="{";
    protected $var_postfix="}";
    protected $default_chapter="default";

    /**
     * @param $chapter_name
     * @return $this
     */
    public function set_default_chapter($chapter_name){
        $this->default_chapter=$chapter_name;
        return $this;
    }

    /**
     * @param $substitute_characters
     * @return $this
     * @throws \ErrorException
     */
    public function set_delimiters($substitute_characters){
        if(!$substitute_characters){
            throw New \ErrorException("Delimiters cannot be empty");
        }
        $this->var_prefix=substr($substitute_characters,0,-1);
        $this->var_postfix=substr($substitute_characters,-1,1);
        return $this;
    }

    /**
     * @param $filename
     * @param string $upon_errors
     * @param bool $typed_values
     * @return $this
     * @throws \Exception
     */
    public function load_ini($filename,$upon_errors="warn",$typed_values=true){
        if(!file_exists($filename)){
            throw New \ErrorException("File [$filename] does not exist");
        }
        if($typed_values){
            $this->data=parse_ini_file($filename,true,INI_SCANNER_TYPED);
        } else {
            $this->data=parse_ini_file($filename,true);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function get_all()
    {
        return $this->data;
    }


}