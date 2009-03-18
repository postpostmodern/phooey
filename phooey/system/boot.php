<?php

// Directories

define('PHOOEY_DIR',    dirname(__FILE__).'/../');
define('PUBLIC_DIR',    dirname(__FILE__).'/../../public/');
define('SITE_DIR',      PHOOEY_DIR.'site/');
define('CONFIG_DIR',    SITE_DIR.'config/');
define('LIB_DIR',       SITE_DIR.'lib/');
define('TEMPLATE_DIR',  SITE_DIR.'templates/');
define('CONTENT_DIR',   SITE_DIR.'content/');
define('SYSTEM_DIR',    PHOOEY_DIR.'system/');
define('PLUGINS_DIR',   PHOOEY_DIR.'plugins/');
define('FILTERS_DIR',   PLUGINS_DIR.'content_filters/');

// Defaults
define('PHOOEY_ROOT',         '/'   );
define('DEFAULT_HOME_PAGE',   'home'   );
define('DEFAULT_TEMPLATE',    'default');

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
include_dir(SYSTEM_DIR);
// Include Site-specific files
include_dir(LIB_DIR);

// Load the site's main config files
$config['master']       = Spyc::YAMLLoad(CONFIG_DIR.'master.yaml');
$config['templates']    = Spyc::YAMLLoad(CONFIG_DIR.'templates.yaml');
$config['pages']        = Spyc::YAMLLoad(CONFIG_DIR.'pages.yaml');

// Load the database connection file
if(file_exists(CONFIG_DIR.'db.yaml'))
  $config['db'] = Spyc::YAMLLoad(CONFIG_DIR.'db.yaml');

// Connect to a database if config is set
if(array_key_exists('db', $config) && !empty($config['db'])) {
  $db = mysql_pconnect($config['db']['hostname'], $config['db']['username'], $config['db']['password']);
  mysql_select_db($config['db']['database'], $db);
}

$request = new Request();
$site = new Site($config);
$site->serve($request);
