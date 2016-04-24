<?php

abstract class SOMWebPage{

	public function __construct(){
		
		global $path_params;
		
		$this->page_data["CLIENT"] = $_SERVER['HTTP_USER_AGENT'];
		$data1 = self::simpleXMLtoPageAssoc(simplexml_load_file("page_settings/global.xml"));
		$data2 = array();
		$data2_file = "page_settings/".$this->name().".xml";
		if (is_file($data2_file)){
			$data2 = self::simpleXMLtoPageAssoc(simplexml_load_file($data2_file));
		}
		
		
		foreach($data2 as $key => $val){
			if (!is_array($val)){
				if (trim($val) != ""){
					if (in_array($key, array("custom_css","custom_js","custom_tags"))){
						if ($key == "custom_tags"){
							$data1[$key] .= " \n".  str_replace("{%ROOT%}", _ROOT_, $val);
						}
						else {
							$data1[$key] .= " \n$val";
						}
					}
					else {
						$data1[$key] = $val;
					}
				}
			}
			else {
				foreach($val as $file){
					$data1[$key][] = $file;
				}
			}
		}
		
		$this->page_data["CURRENT_YEAR"]= date("Y",time());
		
		$this->page_data = array_merge($this->page_data, array(

			"META_TEXT"			=>	$data1['meta'],
			"META_DESCRIPTION"	=>	$data1['description'],
			"META_KEYWORDS"		=>	$data1['keywords'],
			"TITLE"				=>	$data1['title'],
			"LINKS_CSS"			=> call_user_func(function($array){
				$link = "";
				foreach($array['css'] as $css){
					$link .= HTMLFetcher::instance()->fetchHTML("simple_css", array("FILE" => $css));
				}
				foreach($array['css_path'] as $css){
					$link .= HTMLFetcher::instance()->fetchHTML("simple_css_path", array("PATH" => $css));
				}
				return $link;
			},$data1),
			"LINKS_JS"			=>	call_user_func(function($array){
				$link = "";
				foreach($array['js'] as $js){
					$link .= HTMLFetcher::instance()->fetchHTML("simple_js", array("FILE" => $js));
				}
				foreach($array['js_path'] as $js){
					$link .= HTMLFetcher::instance()->fetchHTML("simple_js_path", array("PATH" => $js));
				}
				return $link;
			},$data1),
			"CUSTOM_CSS"		=>	$data1['custom_css'],
			"CUSTOM_JS"			=>	$data1['custom_js'],
			"CUSTOM_TAGS"		=>	$data1['custom_tags'],
			"CUSTOM_SCRIPTS"	=>	$data1['custom_tags']
		));
	}

	public abstract function pageHTML();
	
	public abstract function name();
	
	private function simpleXMLtoPageAssoc($object){

		$array = array();

		/* @var $object SimpleXMLElement */

		$name = $object->getName();

		if (in_array($name, array("root","js","css", "css_path", "js_path"))){
			foreach ($object->children() as $child_name => $child ){
				if ($name == "root"){
					$array[$child_name] = self::simpleXMLtoPageAssoc($child);
				}
				else {
					$array[] = self::simpleXMLtoPageAssoc($child);
				}

			}
			return $array;

		}
		else if (in_array($name, array("css_path", "js_path", "file","title", "description", "keywords", "meta", "custom_css", "custom_js", "custom_tags"))/* && $object->children()->getName() == "" && $object->children()->count() == 0*/){
			# text content
			if ($name == "custom_tags"){
				$xml = array();
				foreach($object->children() as $tag){

					$xml[] = $tag->asXML();
				}
				return implode("\n",$xml);
			}
			return (string)$object;
		}

		return $array;
	}

}
?>