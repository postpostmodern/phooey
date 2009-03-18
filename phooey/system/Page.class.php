<?php

/**
* Encapsulates the page information and helpers
*/
class Page
{
  private $key;
  private $data;
  public $active = false;
  
  function __construct($key, $data, $site)
  {
    $this->key = $key;
    $this->site = $site;
    $this->data = $this->master_merge($data, $this->site->master);
    if(!array_key_exists('vars', $this->data)) $this->data['vars'] = array();
  }
  
  public function get_data($datakey, $default=false)
  {
    return array_key_exists($datakey, $this->data) ? $this->data[$datakey] : $default;
  }
  
  public function get_page($pagekey)
  {
    return $this->site->get_page($pagekey);
  }
  
  public function get_page_by_href($href)
  {
    return $this->site->get_page_by_href($href);
  }
  
  public function get_href()
  {
    if($this->is_home()) {
      return PHOOEY_ROOT;
    } elseif($this->get_redirect()) {
      return $this->get_redirect();
    } else {
      return PHOOEY_ROOT . $this->get_key();
    }
  }
  
  public function get_key()
  {
    return $this->key;
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
      $parents[] = $this->site->pages[$key];
    }
    return empty($parents) ? false : $parents;
  }
  
  public function get_siblings()
  {
    $parent = $this->get_parent();
    $subpages = $parent->get_subpages();
    if(!$subpages) return false;
    $siblings = array();
    foreach($subpages as $key => $page) {
      if($key != $this->key) {
        $siblings[$key] = $page;
      }
    }
  }

  public function get_next_sibling()
  {
    $parent = $this->get_parent();
    $pages = $parent ? $parent->get_subpages() : $this->site->pages;
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
    $pages = $parent ? $parent->get_subpages() : $this->site->pages;
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
  
  public function get_parent()
  {
    $parents = $this->get_parents();
    if($parents) {
      return $parents[0];
    } else {
      return false;
    }
  }
  
  public function get_subpages()
  {
    $subpages = array();
    if(!$this->get_data('subpages')) return false;
    foreach($this->get_data('subpages') as $key => $data) {
      $subpages[$this->key.'/'.$key] = $this->site->pages[$this->key.'/'.$key];
    }
    return $subpages;
  }
  
  public function get_name() 
  {
    if(strpos($this->get_key(), '/') === false) {
      return $this->get_key();
    }
    $key_parts = explode('/', $this->get_key());
    return array_pop($key_parts);
  }
  
  public function get_nav_exclude()
  {
    return $this->get_data('nav_exclude');
  }
  
  public function is_child_of($key)
  {
    return $this->get_parents() ? in_array($key, $this->get_parents()) : false;
  }
  
  public function is_home()
  {
    if($this->get_data('home_page')) {
      return ($this->get_key() == $this->get_data('home_page'));
    } else {
      return ($this->get_key() == DEFAULT_HOME_PAGE);
    }
  }
  
  public function get_redirect()
  {
    return $this->get_data('redirect');
  }
  
  public function is_external_redirect()
  {
    if(!$this->get_redirect()) return false;
    return stripos($this->data['redirect'], 'http://') !== 0;
  }
  
  public function get_active_class()
  {
    $class = '';
    if($this->is_active()) return ' active ';
    if($this->is_parent_of($this->site->page->key)) return ' parent ';
    return '';
  }
  
  public function is_active()
  {
    return $this->active;
  }
  
  public function get_nav_class()
  {
    return $this->get_data('nav_class', preg_replace('/[^\w\d]/i', '-', $this->get_name()));
  }
  
  public function get_nav_label()
  {
    return $this->get_data('nav_label', $this->get_data('title', ucwords($this->get_name())));
  }
  
  public function get_nav_data()
  {
    return array(
      'prev'         => $this->get_prev_sibling(),
      'page_key'     => $this->get_key(),
      'next'         => $this->get_next_sibling(),
      'page_name'    => $this->get_name(),
      'parent'       => $this->get_parent(),
      'parents'      => $this->get_parents(),
      'active_class' => $this->get_active_class(),
      'nav_class'    => $this->get_nav_class(),
      'href'         => $this->get_href(),
      'label'        => $this->get_nav_label(),
      'exclude'      => $this->get_nav_exclude(),
      'external'     => $this->is_external_redirect()
    );
  }
  
  public function get_content_file()
  {
    return CONTENT_DIR.$this->key.'.page';
  }
  
  public function get_template()
  {
    $template = $this->get_data('template', DEFAULT_TEMPLATE);
    if(!array_key_exists($template, $this->site->templates)) {
      $template = DEFAULT_TEMPLATE;
      trigger_error("Specified template does not exist.", E_USER_WARNING);
    }
    return $this->site->templates[$template];
  }
  
  public function has_content()
  {
    return in_array('CONTENT', $this->get_template()) && file_exists($this->get_content_file());
  }
  
  public function get_content($vars)
  {
    if(!$this->has_content()) return '';

    if($this->get_data('eval_php', true)) {
      ob_start();
      require_once($this->get_content_file());
      $content = ob_get_contents();
      ob_end_clean();
    } else {
      $content = file_get_contents($this->get_content_file());
    }
    
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
  
  public function is_var($varkey)
  {
    return array_key_exists($varkey, $this->data['vars']);
  }
  
  public function is_var_set($varkey)
  {
    return ($this->is_var($varkey) && !is_empty(trim($this->data['vars'][$varkey])));
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
    return $this->data['vars'][$key] = $val;
  }
  
  public function set_vars($vars)
  {
    $this->data['vars'] = array_merge_recursive($this->data['vars'], $vars);
  }
  
  public function is_parent_of($key)
  {
    if($page = $this->site->get_page($key)) {
      return $page->is_child_of($this->key);
    }
  }
  
  public function get($request)
  {
    $this->active = true;
    $this->parse_querystring($request->get_querystring());
    $parts = $this->get_template();
    $actions = new Actions($this);
    if($action_list = $this->get_actions()) {
      foreach($action_list as $action) {
        if(method_exists($actions, $action)) {
          $this->set_vars(call_user_func(array($actions, $action)));
        }
      }
    }
    $helper = new Helper($this);
    $vars = $this->get_vars();
    foreach($parts as $part) {
      if($part == 'CONTENT') {
        echo $this->get_content($vars);
      } elseif(file_exists(TEMPLATE_DIR.$part.'.part')) {
        include(TEMPLATE_DIR.$part.'.part');
      } else {
        trigger_error("Template part not found: $part", E_USER_NOTICE);
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

  public function parse_querystring($querystring)
  {
    if($querystring && $query_keys = $this->get_data('querystring')) {
      $var_values = explode('/', $querystring);
      $var_keys = explode('/', $query_keys);
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
        // Querystring is a special case
        switch($key) {
          case 'querystring':
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
