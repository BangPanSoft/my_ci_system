<?php
/**
 * Description of apps_control
 *
 * @author Pang25441
 */
class Apps_control {
    private $CI;
    function __construct() {
        $this->CI = &get_instance();
    }
    
    public function set_app_dir(){
        if(!empty($this->CI->router->apps_name)){
            $this->CI->load->add_app_path($this->CI->router->apps_dir);
        }
    }
}

?>
