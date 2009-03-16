<?php

/**
* Encapsulates the data for all pages information and helpers
*/
class Site
{
  public $pages     = array();
  public $page               ;
  public $page_tree = array();
  public $master    = array();
  public $templates = array();
  
  function __construct($config)
  {
    $this->master    = $config['master'];
    $this->templates = $config['templates'];
    $this->page_tree = $config['pages'];
    $page_data = $this->extract_pages($this->page_tree);
    foreach($page_data as $key => $data) {
      $this->pages[$key] = new Page($key, $data, $this);
    }
  }
  
  // Flattens nested page array
  private function extract_pages($page_tree, $parent_path = '') 
  {
    $pages = array();
    foreach($page_tree as $page_path => $page_data) {
      $pages[$parent_path.$page_path] = $page_data;
      if(array_key_exists('subpages', $page_data)) {
        extract_pages($page_data['subpages'], $parent_path.$page_path.'/');
      }
    }
    return $pages;
  }
  
  // Get the default page
  public function get_home_page_key()
  {
    return array_key_exists('home_page', $this->master) ? $this->master['home_page'] : DEFAULT_HOME_PAGE;
  }

  public function serve($request)
  {
    $req_path = $request->get_path();
    if($req_path == $this->get_home_page_key()) $this->redirect(PHOOEY_ROOT);
    $key = ($req_path == trim(PHOOEY_ROOT, '/')) ? $this->get_home_page_key() : trim($req_path);
    $this->page = $this->get_page($key);
    if(!$this->page) $this->not_found();
    if($redirect = $this->page->get_redirect()) $this->redirect($redirect);
    return $this->page->get($request);
  }

  public function has_page($key)
  {
    return array_key_exists($key, $this->pages);
  }
  
  public function get_page($key)
  {
    return $this->has_page($key) ? $this->pages[$key] : false;
  }
  
  public function get_page_by_href($href)
  {
    foreach($this->pages as $page) {
      if($page->get_href() == $href) {
        return $page;
      }
    }
    return false;
  }
  
  private function redirect($path)
  {
    header("Location: $path");
    exit;
  }

  private function not_found()
  {
    if($this->page = $this->get_page('404')) {
      $this->page->get();
    } else {
      header("HTTP/1.0 404 Not Found");
      echo "404 Not Found";
      exit;
    }
  }

}
