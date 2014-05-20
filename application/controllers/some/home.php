<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
    public function index(){
        echo 'function : Some Index';
    }
    
    public function test() {
        echo 'Function : Test';
    }
}
