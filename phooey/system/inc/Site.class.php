<?php

/**
* Encapsulates the data for all pages information and helpers
*/
class Site
{
  public $pages       = array();
  public $root_pages  = array();
  public $page                 ;
  public $status               ;
  public $master      = array();
  public $templates   = array();
  public $content_dir          ;
  public $config_dir           ;
  public $template_dir         ;
  public $root                 ;
  public $store       = 'file' ;
  
  function __construct($site_dir, $root, $status='published')
  {
    $this->content_dir  = $site_dir.'content/';
    $this->config_dir   = $site_dir.'config/';
    $this->template_dir = $site_dir.'templates/';
    $this->root         = $root;
    $this->status       = $status;
    
    $this->master       = Spyc::YAMLLoad($this->config_dir.'master.yaml');
    $this->templates    = Spyc::YAMLLoad($this->config_dir.'templates.yaml');
    
    if(array_key_exists('store', $this->master)) {
      $this->store = $this->master['store'];
    }
    switch($this->store) {
      case 'file':
        $this->pages = $this->get_pages_from_yaml();
        break;
      case 'db':
        if(!$this->pages_table_exists()) {
          $this->create_pages_table();
          $this->import_pages_to_db();
        }
        $this->pages = $this->get_pages_from_db();
        break;
    }
  }
  
  private function get_pages_from_db($conditions=array())
  {
    if($this->status != 'all') $conditions[] = " `status`='$this->status' ";
    $where = empty($conditions) ? '' : implode(' AND ', $conditions);
    if(!empty($where)) $where = " WHERE ".$where;
    $page_result = @mysql_query("
      SELECT 
        `p`.`id`, 
        `p`.`key`, 
        `p`.`meta`,
        `p`.`rev`,
        `p`.`status`,
        `p`.`position`,
        `p`.`author_id`,
        `p`.`updated_at`,
        `p`.`published_at`
      FROM 
        `phooey_pages` `p` 
      INNER JOIN (SELECT `key`, MAX(`rev`) as `rev` FROM `phooey_pages` GROUP BY `key`) `p2` 
      ON (`p`.`key` = `p2`.`key` AND `p`.`rev` = `p2`.`rev`) 
      $where 
      ORDER BY `position` ASC
    ");
    if(!$page_result) trigger_error("Error reading pages from database: ".mysql_error(), E_USER_ERROR);
    while($page = @mysql_fetch_object($page_result, 'Page', array(array('site' => $this)))) {
      $pages[] = $page;
    }
    return $pages;
  }
  
  private function create_pages_table()
  {
    $definition = "CREATE TABLE `phooey_pages` (
      `id` int(11) unsigned NOT NULL auto_increment,
      `key` varchar(255) default NULL,
      `meta` text NOT NULL,
      `rev` int(11) unsigned NOT NULL default '1',
      `content` text NOT NULL,
      `status` varchar(50) default NULL,
      `position` int(11) default NULL,
      `author_id` int(11) default NULL,
      `updated_at` datetime default NULL,
      `published_at` datetime default NULL,
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    if(!@mysql_query($definition))
      trigger_error("Pages table could not be created.", E_USER_ERROR);
    return true;
  }
  
  private function pages_table_exists()
  {
    return @mysql_num_rows(@mysql_query("SHOW TABLES LIKE 'phooey_pages'")) > 0;
  }
  
  private function get_pages_from_yaml()
  {
    $page_data = Spyc::YAMLLoad($this->config_dir.'pages.yaml');
    $i = 0;
    foreach($page_data as $key => $config) {
      $properties = array(
        'key'          => $key,
        'config'       => $config,
        'site'         => $this,
        'position'     => ++$i, 
        'rev'          => 0,
        'status'       => 'published',
        'published_at' => date('Y-m-d'),
        'updated_at'   => date('Y-m-d')
      );
        
      $pages[] = new Page($properties);
    }
    return $pages;
  }
  
  private function import_pages_to_db()
  {
    $pages = $this->get_pages_from_yaml();
    foreach($pages as $page) {
      if(!$page->save()) trigger_error("Failed to import pages from pages.yaml: ".mysql_error(), E_USER_ERROR);
    }
    return true;
  }
  
  public function get_store()
  {
    return $this->store;
  }
  
  public function get_root_pages()
  {
    if(empty($this->root_pages)) {
      foreach($this->pages as $key => $page) {
        if($page->get_level() == 1) $this->root_pages[$key] = $page;
      }
    }
    return $this->root_pages;
  }
  
  public function get_children_of($key_or_page, $depth=1)
  {
    $parent = $this->get_page($key_or_page);
    $parent_key = $parent->get_key();
    $children = array();
    foreach($this->pages as $page) {
      if(strpos($page->get_key(), $parent_key) === 0 && $page->get_key() != $parent_key && ($page->get_level() - $parent->get_level()) <= $depth) {
        $children[] = $page;
      }
    }
    return empty($children) ? false : $children;
  }

  public function key_of($page)
  {
    if(is_string($page)) {
      return $page;
    } elseif(is_object($page) && get_class($page) == 'Page') {
      return $page->get_key();
    } else {
      trigger_error("$page passed to method as a page.", E_USER_ERROR);
    }
  }

  public function get_root()
  {
    return array_key_exists('phooey_root', $this->master) ? $this->master['phooey_root'] : $this->root;
  }
  
  // Get the default page
  public function get_home_page_key()
  {
    return array_key_exists('home_page', $this->master) ? $this->master['home_page'] : $GLOBALS['defaults']['home_page'];
  }
  
  // public function save_page_config()
  // {
  //   $config = array();
  //   foreach($this->pages as $key => $page) {
  //     $config[$page->get_key()] = $page->get_config();
  //   }
  //   return file_put_contents($this->config_dir.'pages.yaml', Spyc::YAMLDump($config));
  // }
  // 
  public function serve($request, $boot_vars=array())
  {
    $req_path = $request->get_path();
    // Redirect home page path to root
    if($req_path == $this->get_home_page_key()) $this->redirect($this->root);
    // Set the key to retrieve the page (home if root)
    $key = ($req_path == '') ? $this->get_home_page_key() : trim($req_path, '/');
    // Get the page. If it doesn't exist, exit with 404.
    if(!$this->page = $this->get_page($key)) $this->serve_error('404');
    // Redirect if page is redirect
    if($redirect = $this->page->get_redirect()) $this->redirect($redirect);
    // Exit with 405 if method is not allowed by the page.
    if(!$this->page->is_method_allowed($request->get_method())) $this->serve_error('405');
    // Serve the page
    return $this->page->serve($request, $boot_vars);
  }

  public function has_page($key)
  {
    return $this->get_page($key);
  }
  
  public function get_page($key_or_page)
  {
    if(is_object($key_or_page) && get_class($key_or_page) == 'Page') {
      return $key_or_page;
    } else {
      $key = trim($key_or_page, '/');
      foreach($this->pages as $page) {
        if($page->get_key() == $key) return $page;
      }
    }
    return false;
  }
    
  public function get_page_key($key_or_page)
  {
    if(is_object($key_or_page) && get_class($key_or_page) == 'Page') {
      return $key_or_page->get_key();
    } else {
      return $key_or_page;
    }
    return false;
  }
    
  public function get_page_by_href($href)
  {
    foreach($this->pages as $page) {
      if($page->get_href() == $href) return $page;
    }
    return false;
  }
  
  public function redirect($path)
  {
    header("Location: $path");
    exit;
  }

  public function not_found()
  {
    $this->serve_error('404');
  }
  
  public function serve_error($code) 
  {
    $http_status_codes = array(
      '100' => 'Continue',
      '101' => 'Switching Protocols',
      '102' => 'Processing',
      '200' => 'OK',
      '201' => 'Created',
      '202' => 'Accepted',
      '203' => 'Non-Authoritative Information',
      '204' => 'No Content',
      '205' => 'Reset Content',
      '206' => 'Partial Content',
      '207' => 'Multi-Status',
      '226' => 'IM Used',
      '300' => 'Multiple Choices',
      '301' => 'Moved Permanently',
      '302' => 'Found',
      '303' => 'See Other',
      '304' => 'Not Modified',
      '305' => 'Use Proxy',
      '306' => 'Reserved',
      '307' => 'Temporary Redirect',
      '400' => 'Bad Request',
      '401' => 'Unauthorized',
      '402' => 'Payment Required',
      '403' => 'Forbidden',
      '404' => 'Not Found',
      '405' => 'Method Not Allowed',
      '406' => 'Not Acceptable',
      '407' => 'Proxy Authentication Required',
      '408' => 'Request Timeout',
      '409' => 'Conflict',
      '410' => 'Gone',
      '411' => 'Length Required',
      '412' => 'Precondition Failed',
      '413' => 'Request Entity Too Large',
      '414' => 'Request-URI Too Long',
      '415' => 'Unsupported Media Type',
      '416' => 'Requested Range Not Satisfiable',
      '417' => 'Expectation Failed',
      '422' => 'Unprocessable Entity',
      '423' => 'Locked',
      '424' => 'Failed Dependency',
      '426' => 'Upgrade Required',
      '500' => 'Internal Server Error',
      '501' => 'Not Implemented',
      '502' => 'Bad Gateway',
      '503' => 'Service Unavailable',
      '504' => 'Gateway Timeout',
      '505' => 'HTTP Version Not Supported',
      '506' => 'Variant Also Negotiates (Experimental)',
      '507' => 'Insufficient Storage',
      '510' => 'Not Extended'
    );
    if(!array_key_exists($code, $http_status_codes)) {
      trigger_error("Unknown HTTP status code.", E_USER_ERROR);
    } else {
      header("HTTP/1.1 $code {$http_status_codes[$code]}");
    }
    if($this->page = $this->get_page($code)) {
      $this->page->get();
    } else {
      echo $http_status_codes[$code];
      exit;
    }
  }

}
