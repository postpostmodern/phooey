<?php

/**
* Encapsulates the page information and helpers
*/
class Page
{
  private   $id;
  private   $key;
  private   $old_key;
  private   $data;
  public    $active = false;
  public    $request;
  private   $content = false;
  private   $config;
  private   $errors  = array();
  private   $valid   = true;
  public    $site;
  private   $position = 0;
  private   $rev     = 0;
  private   $meta; // Meta is $config in YAML form
  private   $status;
  private   $author_id;
  private   $updated_at;
  private   $published_at;
  private   $assignable_properties = array('key', 'config', 'site', 'content', 'position', 'rev', 'meta', 'status', 'author_id', 'published_at');
  
  function __construct($properties=array())
  {
    foreach($this->assignable_properties as $property) {
      if(array_key_exists($property, $properties)) $this->$property = $properties[$property];
    }

    if($this->meta && !$this->config) {
      $this->config = Spyc::YAMLLoad($this->meta);
    }
    
    if(!$this->key || !$this->config || !$this->site) {
      print_r($this);
      trigger_error("Page must be given key, config, and site", E_USER_ERROR);
    }
    $this->old_key = $this->key;
    $this->data = $this->master_merge($this->config, $this->site->master);
    if(!array_key_exists('vars', $this->data)) $this->data['vars'] = array();
  }
  
  public function is_valid()
  {
    return $this->valid;
  }
  
  // Key
  public function get_key()
  {
    return $this->key;
  }
  
  public function get_old_key()
  {
    return $this->old_key;
  }
  
  public function key_changed()
  {
    return $this->old_key != $this->key;
  }
  
  public function set_key($new_key)
  {
    $new_key = trim(trim($new_key, '/'));
    if($this->key != $new_key) {
      if($this->validate_key($new_key)) {
        $this->key = $new_key;
      }
    }
    return $this->key;
  }
  
  private function validate_key($new_key)
  { 
    if($new_key == '') {
      $this->add_error("Page path can't be blank");
      return false;
    }
    if(strpos($new_key, '/') !== false) {
      $parent_parts = explode('/', $new_key);
      array_pop($parent_parts);
      $parent_key = implode('/', $parent_parts);
      if(!$this->site->has_page($parent_key)) {
        $this->add_error("You can't currently create an orphaned page. Check the path.");
        return false;
      }
    }
    if(preg_match('/[^A-Za-z0-9\/\._-]/',$new_key)) {
      $this->add_error("Please use only letters, numbers, dashes, underscores or periods in the path.");
      return false;
    }
    return true;
  }
  
  // Config
  public function get_config()
  {
    return $this->config;
  }
  
  // Meta is config in YAML form
  public function get_meta()
  {
    return Spyc::YAMLDump($this->config);
  }
  
  public function set_meta($meta)
  {
    $this->meta = $meta;
    $this->config = Spyc::YAMLLoad($this->meta);
  }
  
  public function set_config($config)
  {
    if(!is_array($config)) trigger_error("Config must be an array.", E_USER_ERROR);
    $this->config = array_merge($this->config, $config);
    return $this->config;
  }
  
  private function save_config()
  {
    return $this->site->save_page_config();
  }
  
  public function get_data($datakey, $default=false)
  {
    return array_key_exists($datakey, $this->data) ? $this->data[$datakey] : $default;
  }
  
  // Vars
  public function is_var($varkey)
  {
    return array_key_exists($varkey, $this->data['vars']);
  }
  
  public function is_var_set($varkey)
  {
    return ($this->is_var($varkey) && (is_array($this->data['vars'][$varkey]) || !is_empty(trim($this->data['vars'][$varkey]))));
  }
  
  public function get_vars()
  {
    return $this->data['vars'];
  }
  
  public function get_var($varkey, $default=NULL)
  {
    return $this->is_var($varkey) ? $this->data['vars'][$varkey] : $default;
  }
  
  public function set_var($varkey, $val)
  {
    return $this->data['vars'][$varkey] = $val;
  }
  
  public function set_vars($vars)
  {
    $this->data['vars'] = array_merge_recursive($this->data['vars'], $vars);
  }
  
  // Content
  public function get_content_dir($key=false)
  {
    return dirname($this->get_content_file($key));
  }
  
  public function get_content_file($key=false)
  {
    if($key === false) $key = $this->key;
    return $this->site->content_dir.$key.'.page';
  }
  
  public function is_content_page()
  {
    return in_array('CONTENT', $this->get_template());
  }
  
  public function get_raw_content()
  {
    if(!$this->is_content_page()) return false;
    if($this->content) return $this->content;
    switch($this->site->get_store()) {
      case 'db':
        if($this->id) {
          return $this->content = mysql_select_value("SELECT `content` FROM `phooey_pages` WHERE `id` = ".mysql_real_escape_string($this->get_id()));
        } else {
          return $this->content = mysql_select_value("SELECT `content` FROM `phooey_pages` WHERE `key` = ".mysql_real_escape_string($this->get_key())." ORDER BY `rev` DESC LIMIT 1");
        }
        break;
      case 'file':    
        if(file_exists($this->get_content_file())) {
          return $this->content = file_get_contents($this->get_content_file());
        } else {
          return false;
        }
        break;
    }
    trigger_error("Content for page not found.", E_USER_NOTICE);
    return '';
  }
  
  public function get_evaluated_content($vars, $helper)
  {
    if($this->site->get_store() == 'file' && $this->get_data('eval_php', true) && file_exists($this->get_content_file())) {
      ob_start();
      require_once($this->get_content_file());
      $content = ob_get_contents();
      ob_end_clean();
      return $content;
    } else {
      return false;
    }
  }
  
  public function set_content($content)
  {
    return $this->content = $content;
  }

  public function get_content($vars, $helper)
  {
    if(!$this->is_content_page()) return '';
    
    if(!$content = $this->get_evaluated_content($vars, $helper)) {
      $content = $this->get_raw_content();
    }
    $content = $this->parse_content($content, $vars);
    $content = $this->filter_content($content);
    return $content;
  }
  
  private function parse_content($content, $vars)
  {
    $parser = new PhooeyParser();
    $parser->setIncludePath($this->site->template_dir);
    $parser->setParams($vars);
    $parser->setTemplateText($content);
    return $parser->parseAndReturn();
  }
  
  private function filter_content($content)
  {
    if($content_filter = $this->get_data('content_filter')) {
      if(!is_array($content_filter)) {
        $content_filter = array($content_filter);
      }
      foreach($content_filter as $filter) {
        require_once(FILTERS_DIR . $filter . '/init.php');
        $content = call_user_func($filter, $content);
      }
    }
    return $content;
  }
  
  // Actions
  public function run_actions()
  {
    $actions = new Actions($this);
    foreach($this->get_actions() as $action) {
      if(is_array($action)) {
        if($this->request->get_method() == $action[0] && $this->is_method_allowed($action[0])) {
          $action = $action[1];
        } else {
          continue;
        }
      }
      if(method_exists($actions, $action)) {
        $this->set_vars(call_user_func(array($actions, $action)));
      }
    }
  }
  
  public function get_actions()
  {
    $action = $this->get_data('action');
    $actions = $this->get_data('actions', array());
    if($action) {
      return array($action);
    } else {
      return $actions;
    }
  }

  public function get_action_methods()
  {
    $methods = array();
    foreach($this->get_actions() as $action) {
      if(is_array($action)) {
        $methods[] = $action[0];
      }
    }
    return $methods;
  }
  
  // Template
  public function get_template()
  {
    $template = $this->get_data('template', $GLOBALS['defaults']['template']);
    if(!array_key_exists($template, $this->site->templates)) {
      $template = $GLOBALS['defaults']['template'];
      trigger_error("Specified template does not exist.", E_USER_WARNING);
    }
    return $this->site->templates[$template];
  }
  
  // Other Pages within the site
  public function get_page($key_or_page)
  {
    return $this->site->get_page($key_or_page);
  }
  
  public function get_page_by_href($href)
  {
    return $this->site->get_page_by_href($href);
  }
  
  // Family
  // Parents
  public function get_parent()
  {
    if($parents = $this->get_parents()) {
      return $parents[0];
    } else {
      return false;
    }
  }
  
  public function get_parent_keys()
  {
    $parts = explode('/', $this->get_key());
    $parent_keys = array();
    while(array_pop($parts) && !empty($parts)) {
      $parent_keys[] = implode('/', $parts);
    }
    return empty($parent_keys) ? false : $parent_keys;
  }
  
  public function get_parents()
  {
    if(!$parent_keys = $this->get_parent_keys()) return false;
    $parents = array();
    foreach($parent_keys as $key) {
      $parents[] = $this->site->get_page($key);
    }
    return empty($parents) ? false : $parents;
  }
  
  // Children
  public function is_parent_of($key_or_page)
  {
    if($page = $this->get_page($key_or_page)) {
      return $page->is_child_of($this);
    }
    return false;
  }
  
  public function is_child_of($key_or_page)
  {
    if($page = $this->get_page($key_or_page)) {
      return $this->get_parents() ? in_array($page, $this->get_parents()) : false;
    } 
    return false;
  }
  
  public function get_children()
  {
    return $this->site->get_children_of($this);
  }
  
  // Siblings
  public function get_siblings($include_self=false)
  {
    $parent = $this->get_parent();
    $subpages = $parent->get_children();
    if(!$subpages) return false;
    $siblings = array();
    foreach($subpages as $key => $page) {
      if($include_self || $key != $this->key) {
        $siblings[$key] = $page;
      }
    }
  }

  public function get_next_sibling()
  {
    $parent = $this->get_parent();
    $pages = $parent ? $parent->get_children() : $this->site->pages;
    if(!$pages) return false;
    reset($pages);
    while(list($key, $page) = each($pages)) {
      if($key == $this->key) {
        return current($pages);
      }
    }
    return false;
  }

  public function get_prev_sibling()
  {
    $parent = $this->get_parent();
    $pages = $parent ? $parent->get_children() : $this->site->pages;
    if(!$pages) return false;
    reset($pages);
    while(list($key, $page) = each($pages)) {
      if($key == $this->key) {
        return $prev;
      }
      $prev = $page;
    }
    return false;
  }
  
  // Level within hierarchy
  public function get_level()
  {
    return count(explode('/', $this->key));
  }
  
  public function get_name() 
  {
    if(strpos($this->get_key(), '/') === false) {
      return $this->get_key();
    }
    $key_parts = explode('/', $this->get_key());
    return array_pop($key_parts);
  }
  
  public function get_title()
  {
    return $this->get_data('title');
  }
  
  public function get_nav_exclude()
  {
    return $this->get_data('nav_exclude');
  }
  
  public function get_href()
  {
    if($this->is_home()) {
      return $this->site->get_root();
    } elseif($this->get_redirect()) {
      return $this->get_redirect();
    } else {
      return $this->site->get_root() . $this->get_key();
    }
  }
  
  public function get_redirect()
  {
    return $this->get_data('redirect');
  }
  
  public function is_external_redirect()
  {
    if(!$this->get_redirect()) return false;
    return stripos($this->data['redirect'], 'http://') === 0;
  }
  
  public function is_home()
  {
    if($this->get_data('home_page')) {
      return ($this->get_key() == $this->get_data('home_page'));
    } else {
      return ($this->get_key() == $GLOBALS['defaults']['home_page']);
    }
  }
  
  public function is_active()
  {
    return $this->active;
  }
  
  public function get_active_class()
  {
    $class = '';
    if($this->is_active()) return ' active ';
    if($this->is_parent_of($this->site->page->key)) return ' parent ';
    return '';
  }
  
  public function get_nav_class()
  {
    return $this->get_data('nav_class', preg_replace('/[^\w\d]/i', '-', $this->get_name()));
  }
  
  public function get_nav_label()
  {
    return $this->get_data('nav_label', $this->get_data('title', ucwords($this->get_name())));
  }
  
  // Request Methods
  public function is_method_allowed($method)
  {
    return in_array($method, $this->get_allowed_methods());
  }
  
  public function get_allowed_methods()
  {
    $allowed_methods = $this->get_data('allowed_methods', array('GET'));
    $allowed_methods = array_merge($allowed_methods, array_intersect($GLOBALS['defaults']['request_methods'], $this->get_action_methods()));
    return $allowed_methods;
  }
  
  // CRUD
  public function serve($request, $boot_vars)
  {
    $this->active = true;
    $this->request = $request;
    $this->parse_querystring($request->get_querystring());
    $parts = $this->get_template();
    $this->set_vars($boot_vars);
    $this->run_actions();
    $helper = new Helper($this);
    $vars = $this->get_vars();
    foreach($parts as $part) {
      if($part == 'CONTENT') {
        echo $this->get_content($vars, $helper);
      } elseif(file_exists($this->site->template_dir.$part.'.part')) {
        include($this->site->template_dir.$part.'.part');
      } else {
        trigger_error("Template part not found: $part", E_USER_NOTICE);
      }
    }
  }
  
  public function save()
  {
    if(!$this->is_valid()) return false;

    $this->move_children();
    $key          = @mysql_real_escape_string($this->get_key());
    $old_key      = @mysql_real_escape_string($this->old_key);
    $meta         = @mysql_real_escape_string($this->get_meta());
    $rev          = @mysql_real_escape_string($this->increment_rev());
    $content      = @mysql_real_escape_string($this->get_raw_content());
    $status       = @mysql_real_escape_string($this->get_status());
    $position     = @mysql_real_escape_string($this->get_position());
    $author_id    = @mysql_real_escape_string($this->get_author_id());
    $updated_at   = date('Y-m-d');
    $published_at = @mysql_real_escape_string($this->get_published_at());
    
    $insert_query = "INSERT INTO `phooey_pages` SET ";

    if($key         ) $insert_query .= " `key`            = '$key',";
    if($meta        ) $insert_query .= " `meta`           = '$meta', ";
    if($rev         ) $insert_query .= " `rev`            =  $rev, ";
    if($content     ) $insert_query .= " `content`        = '$content', ";
    if($status      ) $insert_query .= " `status`         = '$status', ";
    if($position    ) $insert_query .= " `position`       =  $position, ";
    if($author_id   ) $insert_query .= " `author_id`      =  $author_id, ";
    if($updated_at  ) $insert_query .= " `updated_at`     = '$updated_at', ";
    if($published_at) $insert_query .= " `published_at`   = '$published_at' ";
    
    if($this->key_changed()) {
      @mysql_query("BEGIN");
      $update_keys_query = "UPDATE `phooey_pages` SET `key`=REPLACE(`key`, '$old_key', '$key') WHERE `key` LIKE '$old_key%'";
      if(@mysql_query($insert_query) && @mysql_query($update_keys_query)) {
        @mysql_query("COMMIT");
        return true;
      } else {
        $this->add_error(mysql_error());
        @mysql_query("ROLLBACK");
        return false;
      }
    } else {
      return @mysql_query($insert_query);
    }
  }
  
  public function move($new_parent)
  {
    // This function is not used anymore
    $new_key = $new_parent.'/'.$this->get_name();
    if($this->set_key($new_key) && $this->save()) {
      return true;
    } else {
      $this->add_error("Could not move page from $this->old_key to $new_key.");
      return false;
    }
  }
  
  private function move_children()
  {
    // This function is not used anymore
    if($this->site->get_children_of($this->old_key)) {
      foreach($this->site->get_children_of($this->old_key) as $child) {
        if(!$child->move($this->key)) {
          $this->add_error("Could not move child (".$child->get_error_string().").");
          return false;
        }
      }
    }
    return true;
  }
  
  // Authorship & Publishing
  public function get_author_id()
  {
    return $this->author_id;
  }
  
  public function get_status()
  {
    return $this->status;
  }
  
  public function get_published_at()
  {
    return $this->published_at;
  }
  
  public function get_rev()
  {
    return $this->rev;
  }
  
  public function increment_rev()
  {
    return ++$this->rev;
  }
  
  public function get_position()
  {
    return $this->position;
  }
  
  public function get_id()
  {
    return $this->id;
  }
  
  // Errors
  public function get_errors()
  {
    return $this->errors;
  }
  
  public function get_error_string()
  {
    return implode(' and ', $this->get_errors());
  }
  
  public function add_error($text)
  {
    $this->valid = false;
    $this->errors[] = $text;
  }
  
  // Private Utility Methods
  private function parse_querystring($querystring)
  {
    if(!$query_pattern = $this->get_data('querystring')) return false;
    if(strpos($query_pattern, '/') === false) {
      $this->set_var($query_pattern, $querystring);
    } elseif($querystring) {
      $var_values = explode('/', $querystring);
      $var_keys = explode('/', $query_pattern);
      foreach($var_keys as $key => $val) {
        if(array_key_exists($key, $var_values)) {
          $this->set_var($key, $val);
        }
      }
    }
  }
  
  private function master_merge($data, $master)
  {
    // Combine page values with master values
    foreach($data as $key => $value) {
      if(array_key_exists($key, $master)) {
        // Querystring and allowed_methods are special cases
        switch($key) {
          case 'querystring':
            break;
          case 'allowed_methods':
            break;
          default:
            if(is_array($value)) {
              $data[$key] = array_merge_recursive($master[$key], $value);
            } elseif(is_string($value)) {
              $data[$key] = $value;
            }
        }
        unset($master[$key]);
      }
    }
    return array_merge($data, $master);
  }
  
}
