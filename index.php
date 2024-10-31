<?php 
/*
Plugin Name: Plugin List Generator
Plugin URI: 
Description: Generates a list of plugins from wordpress.org   
Version: 0.1.1
Author: Marco Constâncio
Author URI: http://www.betasix.net
*/
if (!defined('PLG_PLUGIN_DIR'))
    define('PLG_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/plugin-list-generator/');

function plg_generate_list($plg_params,$plg_content){
		
	require_once "wp-admin/includes/plugin.php";		
	require_once "wp-admin/includes/plugin-install.php";	
	require_once "raycache.php";	
	
	//Starts vars
	$plg_output_html = "";
	
	$plg_data = array("parameters"=>array(),"content"=>$plg_content);

	$plg_param_names = array("name","description","slug","version","author",
							 "author_profile","requires","tested","rating",
							 "num_ratings","num_ratings","description","short_description");
	
	//Reads all Parameters						
	if(isset($plg_params["author"])){ $plg_data["parameters"]["author"] = $plg_params["author"]; }
	if(isset($plg_params["term"])){ $plg_data["parameters"]["term"] = $plg_params["term"]; }
	if(isset($plg_params["tag"])){ $plg_data["parameters"]["tag"] = $plg_params["tag"]; }
	if(isset($plg_params["num"])){ 
		$plg_data["parameters"]["per_page"] = $plg_params["num"]; 
	}else{
		$plg_data["parameters"]["per_page"] = 25; 
	}
	if(isset($plg_params["list_markup"])){ 
		$plg_data["parameters"]["list_markup"] = $plg_params["list_markup"]; 
		$plg_output_html.= "<".$plg_data["parameters"]["list_markup"].">"; 
	}
	
	if(isset($plg_params["exclude"])){ 
		$plg_params["exclude"]=explode(",",$plg_params["exclude"]);
		$plg_data["parameters"]["per_page"]+=count($plg_params["exclude"]);
	}	
	if(isset($plg_params["only"])){ 
		$plg_params["only"]=explode(",",$plg_params["only"]);
	}	
	
	//Sets cache expire time
	$plg_data["parameters"]["expire"] = 60 * 24;
	if(isset($plg_params["expire"])){ 
		if(is_numeric($plg_data["parameters"]["expire"])){
			$plg_data["parameters"]["expire"] = $plg_params["expire"];
		} 
	}
	$plg_expire_time = 60 * $plg_data["parameters"]["expire"];  
	
	//Check for available cache
	$cache = RayCache::getInstance('long', null, array('prefix' => 'plg_cache_', 'path' => PLG_PLUGIN_DIR, 'expire' => $plg_expire_time ));
	$md5_config_string = md5(serialize($plg_data));

	$cache_content = $cache->read($md5_config_string);

	if(!empty($cache_content)){ //Cache available
		return $cache_content;
	}else{ // Cache not available 
		if(!empty($plg_data["parameters"])){
			
			//Performs search on wordpress.org
			$plg_result = plugins_api('query_plugins',$plg_data["parameters"])->plugins;

			//Generates html
			if(isset($plg_params["exclude"])){
				foreach($plg_result as $plugin_data){	
					if(!in_array($plugin_data->slug,$plg_params["exclude"])){
						$plg_content_aux = $plg_data["content"];	
						foreach($plg_param_names as $plg_parameter){
							$plg_content_aux = str_replace("[".$plg_parameter."]",$plugin_data->$plg_parameter,$plg_content_aux);
						}
						if(isset($plg_data["parameters"]["list_markup"])){ $plg_content_aux = "<li>".$plg_content_aux."</li>"; }
						$plg_output_html .= $plg_content_aux;
					}
				}
			}else if(isset($plg_params["only"])){				
				foreach($plg_result as $plugin_data){	
					if(in_array($plugin_data->slug,$plg_params["only"])){
						$plg_content_aux = $plg_data["content"];	
						foreach($plg_param_names as $plg_parameter){
							$plg_content_aux = str_replace("[".$plg_parameter."]",$plugin_data->$plg_parameter,$plg_content_aux);
						}
						if(isset($plg_data["parameters"]["list_markup"])){ $plg_content_aux = "<li>".$plg_content_aux."</li>"; }
						$plg_output_html .= $plg_content_aux;
					}
				}							
			}else{
				foreach($plg_result as $plugin_data){	
					$plg_content_aux = $plg_data["content"];	
					foreach($plg_param_names as $plg_parameter){
						$plg_content_aux = str_replace("[".$plg_parameter."]",$plugin_data->$plg_parameter,$plg_content_aux);
					}
					if(isset($plg_data["parameters"]["list_markup"])){ $plg_content_aux = "<li>".$plg_content_aux."</li>"; }
					$plg_output_html .= $plg_content_aux;
				}
			}
			
			if(isset($plg_data["parameters"]["list_markup"])){ 
				$plg_output_html.= "</".$plg_data["parameters"]["list_markup"].">";
			}
			
			//Writes cache file
			$cache->write($md5_config_string, $plg_output_html);
			
			return $plg_output_html;
		}else{
			return "No valid parameters for the Plugin List Generator plugin.";
		}
	}
}

add_shortcode('plg', 'plg_generate_list');
