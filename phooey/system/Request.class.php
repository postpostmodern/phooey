<?php 

/**
* Processes an HTTP request
*/
class Request
{
  
  function __construct() {}
  
  public function get_path()
  {
    return array_key_exists('path', $_GET) ? trim($_GET['path'], '/') : '';
  }
  
  public function is_root()
  {
    return is_empty($this->get_path());
  }
  
  public function get_querystring()
  {
    return array_key_exists('querystring', $_GET) ? $_GET['querystring'] : false;
  }
  
  public function get_postvars()
  {
    return $_POST;
  }
  
  public function get_method()
  {
    return array_key_exists('_method', $_POST) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD'];
  }
  
}
