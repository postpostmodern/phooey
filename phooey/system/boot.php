<?php

// Directories

define('PRIVATE_DIR',   dirname(__FILE__).'/../');
define('PUBLIC_DIR',    dirname(__FILE__).'/../../public/');
define('CONFIG_DIR',    PRIVATE_DIR.'config/');
define('LIB_DIR',       PRIVATE_DIR.'lib/');
define('SYSTEM_DIR',    PRIVATE_DIR.'system/');
define('TEMPLATE_DIR',  PRIVATE_DIR.'templates/');
define('CONTENT_DIR',   PRIVATE_DIR.'content/');

// Defaults
define('DEFAULT_HOME_PAGE',   'home');
define('DEFAULT_TEMPLATE',    'default');

// Function for including entire directories of files
function include_dir($path) {
  if(!is_dir($path)) {
    echo "Can not find required directory: $path";
    exit;
  }
  $dir = dir($path);
  while(($file = $dir->read()) !== false) {
    if(is_file($path .'/'. $file) and preg_match('/^(.+)\.php$/i', $file)) {
      require_once($path .'/'. $file);
    }
  }
  $dir->close();
}

// Include Phooey core files
include_dir(SYSTEM_DIR);
// Include Site-specific files
include_dir(LIB_DIR);

// Load the site's main config files
$master       = Spyc::YAMLLoad(CONFIG_DIR.'master.yaml');
$templates    = Spyc::YAMLLoad(CONFIG_DIR.'templates.yaml');
$nested_pages = Spyc::YAMLLoad(CONFIG_DIR.'pages.yaml');

// Load the database connection file
if(file_exists(CONFIG_DIR.'db.yaml'))
  $db_conf = Spyc::YAMLLoad(CONFIG_DIR.'db.yaml');

// Connect to a database if config is set
if(!empty($db_conf)) {
  $db = mysql_pconnect($db_conf['hostname'], $db_conf['username'], $db_conf['password']);
  mysql_select_db($db_conf['database'], $db);
}

// Get the default page
$home_page = array_key_exists('home_page', $master) ? $master['home_page'] : DEFAULT_HOME_PAGE;

// Get the path
$path = array_key_exists('path', $_GET) ? $_GET['path'] : $home_page;
$path = trim($path, '/');

// Reformat nested_pages array to flat list of pages
$pages = array();
extract_pages($nested_pages);

// If the path doesn't exist in the pages array, reset the path to 404
if(!array_key_exists($path, $pages)) {
  $path = '404';
}

// Find the page's entry in the pages array
if(array_key_exists($path, $pages)) {
  // Set the page's path in the $page array
  $page = $pages[$path];
  $page['path'] = $path;

  // Check for redirect
  if(array_key_exists('redirect', $page)) {
    header('Location: '.$page['redirect']);
    exit;
  }
  
  // Set the page's content file
  $content = CONTENT_DIR.$path.'.page';

  // Combine page values with master values
  foreach($page as $key => $value) {
    if(array_key_exists($key, $master)) {
      // Querystring is a special case
      switch($key) {
        case 'querystring':
          break;
        default:
          if(is_array($value)) {
            $page[$key] = array_merge_recursive($master[$key], $value);
          } elseif(is_string($value)) {
            $page[$key] = $master[$key] . $value;
          }
      }
      unset($master[$key]);
    }
  }
  $page = array_merge($page, $master);

  // Add get vars to $page['vars']
  if(isset($_GET['querystring'])) {
    $var_values = explode('/', $_GET['querystring']);
    $var_keys = explode('/', $page['querystring']);
    foreach($var_keys as $key => $val) {
      if(array_key_exists($key, $var_values)) {
        $page['vars'][$val] = $var_values[$key];
      }
    }
  }

  // Check for and evaluate action
  if(array_key_exists('action', $page)) {
    require_once(LIB_DIR.'actions.php');
    if(is_callable($page['action'])) {
      $page['vars'] = call_user_func($page['action'], $page['vars']);
    }
  }
  
  // Check for and evaluate multiple actions
  if(array_key_exists('actions', $page) && is_array($page['actions'])) {
    require_once(LIB_DIR.'actions.php');
    foreach($page['actions'] as $action) {
      if(is_callable($action)) {
        $page['vars'] = call_user_func($action, $page['vars']);
      }
    }
  }
  
  // $vars is a shortcut for $page['vars']
  $vars = $page['vars'];

  // Establish which template to use
  $template = array_key_exists('template', $page) ? $page['template'] : DEFAULT_TEMPLATE;

  // Build page parts based on template
  if(array_key_exists($template, $templates)) {
    foreach($templates[$template] as $part) {
      if($part == 'CONTENT' && file_exists($content)) {
        include($content);
      } elseif(file_exists(TEMPLATE_DIR.$part.'.part')) {
        include(TEMPLATE_DIR.$part.'.part');
      }
    }
  }
} else {
  // If all of the above fails...
  header("HTTP/1.0 404 Not Found");
  echo "404 Not Found";
}
  

?>