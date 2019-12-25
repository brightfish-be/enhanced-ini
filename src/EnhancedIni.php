<?php


namespace Brightfish\EnhancedIni;

use ErrorException;
use function PHPUnit\Framework\throwException ;

/**
 * Class EnhancedIni
 * @package Brightfish\EnhancedIni
 */
class EnhancedIni
{
    protected $data=[];
    protected $var_prefix="{";
    protected $var_postfix="}";
    protected $default_section="default";
    protected $ini_loaded=false;

    /**
     * @param $chapter_name
     * @return $this
     */
    public function set_default_section($section){
        $this->default_section=$section;
        return $this;
    }
    public function set_default_chapter($section){
        return $this->set_default_section($section);
    }


    /**
     * @param $substitute_characters
     * @return $this
     * @throws ErrorException
     */
    public function set_delimiters($delimiters){
        if(!$delimiters){
            throw New ErrorException("Delimiters cannot be empty");
        }
        // if $delimiters == "#"    =>  parameters are like #name#
        // if $delimiters == "{}"   =>  parameters are like {name}
        // if $delimiters == "[i]"  =>  parameters are like [name]
        $this->var_prefix=substr($delimiters,0,-1);
        $this->var_postfix=substr($delimiters,-1,1);
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
        return $this->load_from_file($filename,$typed_values,$stop_on_syntax_error);
    }

    public function load_from_file($filename,$typed_values=true,$stop_on_syntax_error=false){
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

    public function load_from_string($input,$typed_values=true,$stop_on_syntax_error=false){
        if($stop_on_syntax_error){
            if($this->has_syntax_errors($input,true)){
                return $this;
            }
        }
        if($typed_values){
            $this->data=parse_ini_string($input,true,INI_SCANNER_TYPED);
        } else {
            $this->data=parse_ini_string($input,true);
        }
        $this->ini_loaded=true;
        return $this;
    }

    /**
     * @param $key
     * @param bool $chapter
     * @return bool|mixed
     * alias for get - to look more like a resource controller
     */
    public function show($key,$chapter=false)
    {
        return $this->get($key,$chapter);
    }

    /**
     * @param $key
     * @param bool $chapter
     * @return bool|string|array
     */
    public function get($key,$chapter=false){
        if(!$this->ini_loaded)  return false;

        if(!$chapter)   $chapter=$this->default_section;
        if(isset($this->data[$chapter][$key])){
            return $this->resolve_value($this->data[$chapter][$key],$chapter);
        }
        if($chapter <> $this->default_section AND isset($this->data[$this->default_section][$key])){
            return $this->resolve_value($this->data[$this->default_section][$key],$chapter);
        }
        return false;
    }

    /**
     * @param string $chapter
     * @return array
     * alias for get_all - to look more like a resource controller
     */
    public function index($chapter=""){
        return $this->get_all($chapter);
    }

    /**
     * @param string $section
     * @return array
     */
    public function get_all($section="")
    {
        if(!$this->ini_loaded)  return [];
        if(!$section){
            // no section specified - get all sections and recurse
            $resolved=[];
            $sections=$this->get_sections();

            foreach($sections as $section){
                $resolved[$section]=$this->get_all($section);
            }
            return $resolved;
        } else {
            if(!isset($this->data[$section])){
                // this section doesn't exist
                return [];
            }
            $resolved=[];
            if(is_array($this->data[$section])){
                if(isset($this->data[$this->default_section])){
                    $keys=array_merge(array_keys($this->data[$this->default_section]),array_keys($this->data[$section]));
                } else {
                    $keys=array_keys($this->data[$section]);
                }
                foreach($keys as $key){
                    $resolved[$key]=$this->get($key,$section);
                }
            } else {
                $resolved=$this->data[$section];
            }
            return $resolved;
        }
    }

    /**
     * @return array
     */
    public function get_sections(){
        $sections=[];
        foreach(array_keys($this->data) as $section){
            if($section <> $this->default_section){
                $sections[$section]=$section;
            }
        }
        ksort($sections);
        return $sections;
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
     * @param $input
     * @param bool $is_string
     * @return array
     */
    protected function has_syntax_errors($input,$is_string=false){
        if(!$is_string){
            // $input is filename
            $raw=file_get_contents($input);
        } else {
            // $input is ini string
            $raw=$input;
        }
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
     * @param $pattern
     * @param $subject
     * @param bool $enhanced
     * @return mixed|string
     */
    protected function preg_extract($pattern,$subject,$enhanced=true){
        $matches=[];
        if($enhanced){
            if(substr($pattern,0,1)<>substr($pattern,-1,1)){
                // if pattern doesn't start and end with same character (typically /)
                switch(true){
                    case !strstr($pattern,"/"): $pattern="/${pattern}/";    break;
                    case !strstr($pattern,"#"): $pattern="#${pattern}#";    break;
                    case !strstr($pattern,"|"): $pattern="|${pattern}|";    break;
                    default:    $pattern="&${pattern}&";
                }
                // replace regex shortcuts
                $pattern=str_replace("%t","[^<>]*",$pattern);   // everything inside a tag: <(%t)>
                $pattern=str_replace("%q","[^\"]*",$pattern);   // everything inside double quotes: "(%q)"
                $pattern=str_replace("%s","[^']*",$pattern);    // everything inside single quotes: '(%s)'
                $pattern=str_replace("%b","[^\]\[]*",$pattern); // everything inside brackets: \[(%b)\]
                $pattern=str_replace("%l","[^\n]*",$pattern);   // everything on a line: (%l)\n
            }
        }
        preg_match($pattern,$subject,$matches);
        if($matches){
            return $matches[1];
        }
        return "";
    }

}