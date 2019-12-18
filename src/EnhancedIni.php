<?php


namespace Brightfish\EnhancedIni;

use ErrorException;
use function PHPUnit\Framework\throwException;

class EnhancedIni
{
    protected $data=[];
    protected $var_prefix="{";
    protected $var_postfix="}";
    protected $default_chapter="default";
    protected $ini_loaded=false;

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
     * @throws ErrorException
     */
    public function set_delimiters($substitute_characters){
        if(!$substitute_characters){
            throw New ErrorException("Delimiters cannot be empty");
        }
        $this->var_prefix=substr($substitute_characters,0,-1);
        $this->var_postfix=substr($substitute_characters,-1,1);
        return $this;
    }

    /**
     * @param $filename
     * @param bool $typed_values
     * @param bool $stop_on_syntax_error
     * @return $this
     * @throws ErrorException
     */
    public function load_ini($filename,$typed_values=true,$stop_on_syntax_error=false){
        if(!file_exists($filename)){
            throw New ErrorException("File [$filename] does not exist");
        }
        if($stop_on_syntax_error){
            if($this->has_syntax_errors($filename)){
                return $this;
            }
        }
        if($typed_values){
            $this->data=parse_ini_file($filename,true,INI_SCANNER_TYPED);
        } else {
            $this->data=parse_ini_file($filename,true);
        }
        $this->ini_loaded=true;
        return $this;
    }

    protected function has_syntax_errors($filename){
        $raw=file_get_contents($filename);
        $lines=explode("\n",$raw);
        $sections=[];
        $warnings=[];
        foreach($lines as $line){
            if($this->preg_extract("^\[(%b)\]",$line)){
                // is section header
                $section_name=$this->preg_extract("^\[(%b)\]",$line);
                if(isset($sections[$section_name])){
                    $warnings[]=sprintf("[%s]: duplicate section definition",$section_name);
                } else {
                    $sections[$section_name]=$section_name;
                }
            }
            if($this->preg_extract("^[\s\t]+\[(%b)\]",$line)){
                // is section header
                $section_name=$this->preg_extract("\[(%b)\]",$line);
                $warnings[]=sprintf("[%s]: spaces before section definition",$section_name);
            }
        }
        //print_r($warnings);
        return $warnings;
    }
    /**
     * @param $key
     * @param bool $chapter
     * @return bool|mixed
     */
    public function get($key,$chapter=false){
        if(!$this->ini_loaded)  return false;

        if(!$chapter)   $chapter=$this->default_chapter;
        if(isset($this->data[$chapter][$key])){
            return $this->resolve_value($this->data[$chapter][$key],$chapter);
        }
        if($chapter <> $this->default_chapter AND isset($this->data[$this->default_chapter][$key])){
            return $this->resolve_value($this->data[$this->default_chapter][$key],$chapter);
        }
        return false;
    }

    /**
     * @param string $chapter
     * @return array
     */
    public function get_all($chapter="")
    {
        if(!$this->ini_loaded)  return [];
        if(!$chapter){
            // no chapter specified - get all chapters - recursive
            $resolved=[];
            foreach(array_keys($this->data) as $chapter){
                if($chapter AND $chapter <> $this->default_chapter){
                    $resolved[$chapter]=$this->get_all($chapter);
                }
            }
            return $resolved;
        } else {
            if(!isset($this->data[$chapter])){
                // this chapter doesn't exist
                return [];
            }
            $resolved=[];
            if(is_array($this->data[$chapter])){
                if(isset($this->data[$this->default_chapter])){
                    $keys=array_merge(array_keys($this->data[$this->default_chapter]),array_keys($this->data[$chapter]));
                } else {
                    $keys=array_keys($this->data[$chapter]);
                }
                foreach($keys as $key){
                    $resolved[$key]=$this->get($key,$chapter);
                }
            } else {
                $resolved=$this->data[$chapter];
            }
            return $resolved;
        }
    }

    // ----------------------- PROTECTED STUFF

    /**
     * @param $value
     * @param string $chapter
     * @return array
     */
    protected function resolve_value($value,$chapter=""){
        if(!$this->ini_loaded)  return [];
        if(is_array($value)){
            // array_map not necessary, only 1-dim arrays are possible
            // return array_map('resolve_value',$value);
            $resolved=[];
            foreach($value as $key => $val){
                $resolved[$key]=$this->resolve_value($val,$chapter);
            }
            return $resolved;
        }
        if(!strstr($value,$this->var_prefix)){
            // nothing to resolve, no variables used
            return $value;
        }

        // find variables
        $var_pattern=$this->var_prefix . "([\w\d\_\-\.]+)" . $this->var_postfix;
        $matches=[];
        $variables_present=preg_match_all("|$var_pattern|",$value,$matches,PREG_SET_ORDER);
        if($variables_present){
            foreach($matches as $match){
                $keyword=$match[1];
                $pattern=$this->var_prefix . $keyword . $this->var_postfix;
                $value=str_replace($pattern,$this->get($keyword,$chapter),$value);
            }
        }
        return $value;

    }

    /**
     * @param $pattern
     * @param $subject
     * @param bool $enhanced
     * @return mixed|string
     */
    public function preg_extract($pattern,$subject,$enhanced=true){
        $matches=[];
        if($enhanced){
            //print_r("preg before = '$pattern'\n");
            if(substr($pattern,0,1)<>substr($pattern,-1,1)){
                $pattern="|${pattern}|";
                $pattern=str_replace("%t","[^<>]*",$pattern);
                $pattern=str_replace("%q","[^\"]*",$pattern);
                $pattern=str_replace("%b","[^\]\[]*",$pattern);
            }
            //print_r("preg after = '$pattern'\n");
        }
        preg_match($pattern,$subject,$matches);
        if($matches){
            return $matches[1];
        }
        return "";

    }

}