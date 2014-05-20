<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Description of test_model
 *
 * @author mtedemo
 */
class Test_model extends CI_Model {
    function __construct() {
        parent::__construct();
        echo '<p>core model</p>';
    }
}

?>