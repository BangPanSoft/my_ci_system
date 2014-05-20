<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class My_Router extends CI_Router {
    function __construct() {
        parent::__construct();
    }
    
    var $apps_name='';
    var $apps_dir='';
    
    
    function _set_routing() {
        // Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
        // since URI segments are more search-engine friendly, but they can optionally be used.
        // If this feature is enabled, we will gather the directory/class/method a little differently
        $segments = array();
        if ($this->config->item('enable_query_strings') === TRUE AND isset($_GET[$this->config->item('controller_trigger')])) {
            if (isset($_GET[$this->config->item('directory_trigger')])) {
                $this->set_directory(trim($this->uri->_filter_uri($_GET[$this->config->item('directory_trigger')])));
                $segments[] = $this->fetch_directory();
            }

            if (isset($_GET[$this->config->item('controller_trigger')])) {
                $this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
                $segments[] = $this->fetch_class();
            }

            if (isset($_GET[$this->config->item('function_trigger')])) {
                $this->set_method(trim($this->uri->_filter_uri($_GET[$this->config->item('function_trigger')])));
                $segments[] = $this->fetch_method();
            }
        }
        
        #####################################################################################
        // Check users apps
        $app_seg = $this->_valid_app_dir($segments);
        #####################################################################################

        // Load the routes.php file.
        if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/routes.php')) {
            include(APPPATH.'config/'.ENVIRONMENT.'/routes.php');
        } elseif (is_file(APPPATH.'config/routes.php')) {
            include(APPPATH.'config/routes.php');
        }

        $this->routes = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
        unset($route);
        
        #########################################################################################
        // Load App routes
        $this->_load_app_route();
        ##########################################################################################

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);
        
        // Were there any query string segments?  If so, we'll validate them and bail out since we're done.
        if (count($segments) > 0) {
            $segments = (!empty($this->apps_name)) ? $app_seg : $segments;
            return $this->_validate_request($segments);
        }
        
        unset($app_seg);

        // Fetch the complete URI string
        $this->uri->_fetch_uri_string();

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        //if ($this->uri->uri_string == '') {
        //    return $this->_set_default_controller();
        //}

        // Do we need to remove the URL suffix?
        $this->uri->_remove_url_suffix();

        // Compile the segments into an array
        $this->uri->_explode_segments();
        
        ################################################################
        // Check users apps
        $app_seg = $this->_valid_app_dir($this->uri->segments);
        
        // Load App routes
        $this->_load_app_route();
        //var_dump($app_seg);
        
        // Set default controller again if user app routes override default controller
        if(!empty($this->apps_name) && $this->default_controller!=$this->routes['default_controller']) {
            $this->default_controller = empty($this->routes['default_controller']) ? FALSE : strtolower($this->routes['default_controller']);
        }
        if (!empty($this->apps_name) && count($app_seg)==0) {
            return $this->_set_default_controller();
        }
        
        ################################################################
        
        // Parse any custom routing that may exist
        $this->_parse_routes();
        
        // Re-index the segment array so that it starts with 1 rather than 0
        $this->uri->_reindex_segments();
    }
    
    function _load_app_route(){
        if(!empty($this->apps_name)) {
            if (defined('ENVIRONMENT') AND is_file($this->apps_dir.'/config/'.ENVIRONMENT.'/routes.php')) {
                include($this->apps_dir.'/config/'.ENVIRONMENT.'/routes.php');
            } elseif (is_file($this->apps_dir.'/config/routes.php')) {
                include($this->apps_dir.'/config/routes.php');
            }
            if(isset($route) && is_array($route)){
                $app_route=array();
                foreach ($route as $cond=>$stage) {
                    switch($cond) {
                        case 'default_controller' :
                            $app_route[$cond] = $this->apps_name.'/'.$stage;
                            break;
                      //case '404_override' :
                      //    $app_route[$cond] = $this->apps_name.'/'.$stage;
                      //    break;
                        default :
                            $app_route[$this->apps_name.'/'.$cond] = $this->apps_name.'/'.$stage;
                    }
                }
                $this->routes = array_merge($this->routes,$app_route);
                unset($route);
                unset($app_route);
            }
        }
    }
    
    function _validate_request($segments) {
        $dir = APPPATH.'controllers/'.$this->fetch_directory();
        //var_dump($dir);
        if(!empty($dir)) {
            if(count($segments)>0){
                //echo APPPATH.'controllers/'.$this->fetch_directory().$segments[0].'.php';
                if(!file_exists(APPPATH.'controllers/'.$this->fetch_directory().$segments[0].'.php')){
                    if ( ! empty($this->routes['404_override'])) {
                        $x = explode('/', $this->routes['404_override']);

                        $this->set_directory('');
                        $this->set_class($x[0]);
                        $this->set_method(isset($x[1]) ? $x[1] : 'index');

                        return $x;
                    } else {
                        show_404($this->fetch_directory().$segments[0]);
                    }
                }
            } else {
                if (strpos($this->default_controller, '/') !== FALSE) {
                        $x = explode('/', $this->default_controller);

                        $this->set_class($x[0]);
                        $this->set_method($x[1]);
                } else {
                        $this->set_class($this->default_controller);
                        $this->set_method('index');
                }

                // Does the default controller exist in the sub-folder?
                if ( ! file_exists(APPPATH.'controllers/'.$this->fetch_directory().$this->default_controller.'.php')) {
                        $this->directory = '';
                        return array();
                }
            }
            return $segments;
        }


        // If we've gotten this far it means that the URI does not correlate to a valid
        // controller class.  We will now see if there is an override
        if ( !empty($this->routes['404_override'])) {
            $x = explode('/', $this->routes['404_override']);

            $this->set_class($x[0]);
            $this->set_method(isset($x[1]) ? $x[1] : 'index');

            return $x;
        }


        // Nothing else to do at this point but show a 404
        //var_dump($this);
        show_404($segments[0]);
    }
    
    function _valid_app_dir($segments){
        //var_dump($segments);
        if (count($segments) == 0) {
            return $segments;
        }
        
        if (file_exists(APPPATH.'controllers/'.$segments[0].'.php')) {
            return $segments;
        }

        // Is the controller in a sub-folder?
        // Check sub-folder
        $dir = array();
        $dir_app = '../../'.$this->config->item('apps_dir');
        $user_app = FALSE;
        $segments_ = $segments;
        foreach($segments_ as $k=>$seg){
            $d = implode('/', $dir);
            if(!empty($d)){
                $d .= '/';
            }
            if(is_dir(APPPATH.'controllers/'.$d.$seg)){
                array_push($dir, $seg);
                $segments=array_slice($segments, 1);
            }
            if($k==0 && is_dir(APPPATH.'controllers/'.$dir_app.'/'.$seg.'/controllers')){
                array_push($dir, $seg);
                array_push($dir, 'controllers');
                $this->apps_name = $seg;
                $this->apps_dir = APPPATH.'controllers/'.$dir_app.'/'.$seg;
                $user_app = TRUE;
                $segments=array_slice($segments, 1);
            } else if($user_app && is_dir(APPPATH.'controllers/'.$dir_app.'/'.$d.$seg)) {
                array_push($dir, $seg);
                $segments=array_slice($segments, 1);
            }
        }
        
        //var_dump($dir);
        $fdir = $this->fetch_directory();
        if(count($dir)>0){
            if(empty($fdir)){ 
                if(!$user_app){
                    $this->set_directory(implode('/', $dir));
                } else {
                    $this->set_directory($dir_app.'/'.implode('/', $dir));
                }
            }
            //echo $this->fetch_directory();
            //var_dump($segments);
            //echo '<hr>';
            return $segments;
        }
        
        if ( !empty($this->routes['404_override'])) {
            $x = explode('/', $this->routes['404_override']);

            $this->set_class($x[0]);
            $this->set_method(isset($x[1]) ? $x[1] : 'index');

            return $x;
        }


        // Nothing else to do at this point but show a 404
        //var_dump($this);
        show_404($segments[0]);
    }
    
    function _set_request($segments = array()) {
        $result = $this->_valid_app_dir($segments);
        $segments = ($result && !empty($this->apps_name)) ? $result : $segments;
        $segments = $result;
        //var_dump($segments);
        parent::_set_request($segments);
    }


    function set_directory($dir) {
        $this->directory = str_replace(array('//'), '/', $dir).'/';
    }
}