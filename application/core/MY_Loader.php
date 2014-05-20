<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Description of MY_Loader
 *
 * @author Pang25441
 */
class MY_Loader extends CI_Loader {
    public function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
    }
    
    function add_app_path($path, $view_cascade=TRUE) {
        if(is_array($path)){
            foreach ($path as $p){
                parent::add_package_path($p[0], $p[1]);
            }
            return TRUE;
        }
        parent::add_package_path($path, $view_cascade);
    }
    
    function add_model_path($path){
        $path = rtrim($path, '/').'/';
        array_unshift($this->_ci_model_paths, $path);
    }
    
    function cross_resource($target='',$res=''){
        if(empty($target) || empty($target) || is_array($target)){
            return false;
        }
        $postfix='';
        if($res=='helper'){
            $postfix = '_helper';
        }
        switch($res) {
            case 'models':
                $default_path = $this->_ci_model_paths;
                $exists = file_exists(APPPATH.$res.'/'.$target.$postfix.'.php');
                break;
            case 'library':
                $default_path = $this->_ci_library_paths;
                $exists = file_exists(APPPATH.$res.'/'.$target.$postfix.'.php') && file_exists(BASEPATH.$res.'/'.$target.$postfix.'.php');
                break;
            case 'helpers':
                $default_path = $this->_ci_helper_paths;
                $exists = file_exists(APPPATH.$res.'/'.$target.$postfix.'.php') && file_exists(BASEPATH.$res.'/'.$target.$postfix.'.php');
                break;
        }
        
        if($this->CI->router->apps_name==NULL && !is_array($target) && !$exists){
            $str = explode('/',$target);
            if(count($str)>1){
                $new_path = APPPATH.'../'.$this->CI->config->item('apps_dir').'/'.  array_shift($str).'/';
                if(is_dir($new_path)){
                    
                    if(array_search($new_path, $default_path)===FALSE){
                        $this->add_app_path($new_path);
                    }
                    $app_target = implode('/',$str);
                    if(file_exists($new_path.$res.'/'.$app_target.$postfix.'.php')){
                        return $app_target;
                    }
                }
                
            }
            
        }
        return false;
    }
    
    function helper($helpers = array()) {
        $cross = $this->cross_resource($helpers,'helpers');
        if($cross){
            parent::helper($cross);
        }
        parent::helper($helpers);
    }


    function library($library = '', $params = NULL, $object_name = NULL) {
        $cross = $this->cross_resource($library,'library');
        if($cross){
            parent::library($cross, $params, $object_name);
        }
        parent::library($library, $params, $object_name);
    }

    function model($model, $name = '', $db_conn = FALSE) {
        /*if($this->CI->router->apps_name==NULL && !is_array($model) && (!file_exists($this->_ci_model_paths[0].'models/'.$model.'.php'))){
            $str = explode('/',$model);
            if(count($str)>1){
                $new_path = APPPATH.'../'.$this->CI->config->item('apps_dir').'/'.  array_shift($str).'/';
                if(is_dir($new_path)){
                    if(array_search($new_path, $this->_ci_model_paths)===FALSE){
                        $this->add_app_path($new_path);
                    }
                    $app_model = implode('/',$str);
                    if(file_exists($new_path.'/models/'.$app_model.'.php')){
                        parent::model($app_model, $name, $db_conn);
                    }
                }
            }
        }*/
        $cross = $this->cross_resource($model,'models');
        if($cross){
            parent::model($cross, $name, $db_conn);
        }
        parent::model($model, $name, $db_conn);
    }
}

?>
