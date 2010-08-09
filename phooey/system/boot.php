<?php

// Directories

define('PHOOEY_DIR',        dirname(__FILE__).'/../');
define('PUBLIC_DIR',        dirname(__FILE__).'/../../public/');
                            
define('SITE_DIR',          PHOOEY_DIR. 'site/');
                            
define('SYSTEM_DIR',        PHOOEY_DIR. 'system/');
define('GLOBAL_CONFIG_DIR', PHOOEY_DIR. 'config/');
define('SYSTEM_INC_DIR',    SYSTEM_DIR. 'inc/');
define('PLUGINS_DIR',       PHOOEY_DIR. 'plugins/');
define('FILTERS_DIR',       PLUGINS_DIR.'content_filters/');

// Defaults
$GLOBALS['defaults']['home_page']       = 'home';
$GLOBALS['defaults']['template']        = 'default';
$GLOBALS['defaults']['request_methods'] = array('GET', 'POST', 'PUT', 'DELETE');

// Function for including entire directories of files
function include_dir($path) {
  if(!is_dir($path)) {
    trigger_error("Can not find required directory: $path", E_USER_ERROR);
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
include_dir(SYSTEM_INC_DIR);
// Include Site-specific files
include_dir(SITE_DIR.'lib/');

// Load the database connection file
if(file_exists(GLOBAL_CONFIG_DIR.'db.yaml')) {
  $db_config = Spyc::YAMLLoad(GLOBAL_CONFIG_DIR.'db.yaml');
  // Connect to a database if config is set
  if(!empty($db_config)) {
    $db = @mysql_connect($db_config['hostname'], $db_config['username'], $db_config['password']);
    @mysql_select_db($db_config['database'], $db);
  }
}

$request = new Request();
$site = new Site(SITE_DIR, '/');
$site->serve($request);
