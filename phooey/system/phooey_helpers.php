<?php


  function lang()
  {
    global $page;
    return array_key_exists('language', $page) ? $page['language'] : 'en';
  }
  
  function is_xhtml()
  {
    return !(strpos(doctype(), 'XHTML') === false);
  }
  
  function tag_closer()
  {
    global $page;
    return is_xhtml() ? ' /' : '';
  }

  function doctype()
  {
    global $page;
    return array_key_exists('doctype', $page) ? $page['doctype'] : 'XHTML 1.0 Strict';
  }
  
  function doctype_tag()
  {
    global $page;
    $doctype_tags = array(
      'HTML 4.01 Strict'        => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>",
      'HTML 4.01 Transitional'  => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>",
      'HTML 4.01 Frameset'      => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/html4/frameset.dtd'>",
      'XHTML 1.0 Strict'        => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>",
      'XHTML 1.0 Transitional'  => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>",
      'XHTML 1.0 Frameset'      => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Frameset//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd'>",
      'XHTML 1.1'               => "<?xml version='1.0' encoding='UTF-8'?>\n<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.1//EN' 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'>",
      'HTML 5'                  => "<!DOCTYPE html>"
    );
    return $doctype_tags[doctype()]."\n";
  }
  
  function html_tag()
  {
    global $page;
    $lang = lang();
    $tag = "<html";
    $tag .= is_xhtml() ? " xmlns='http://www.w3.org/1999/xhtml' xml:lang='$lang'" : '';
    $tag .= strpos(doctype(), 'XHTML 1.1') === false ? " lang='$lang'" : '';
    $tag .= ">\n";
    return $tag;
  }
  
  function css_tags() 
  {
    global $page;
    $css_tags = '';
    if(array_key_exists('css', $page) && is_array($page['css'])) {
      foreach($page['css'] as $css) {
        if(is_array($css)) {
          $css_tags .= '  <link href="/css/'.$css[0].'.css" rel="stylesheet" type="text/css" media="'.$css['1'].'" charset="utf-8"'.tag_closer().'>'."\n";
        } else {
          $css_tags .= '  <link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8"'.tag_closer().'>'."\n";
        }
      }
    }
    if(array_key_exists('ie_css', $page) && is_array($page['ie_css'])) {
      foreach($page['ie_css'] as $css => $condition) {
        $css_tags .= '  <!--[if '.$condition.']><link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8"'.tag_closer().'><![endif]-->'."\n";
      }
    }
    return $css_tags;
  }
  
  function js_tags() 
  {
    global $page;
    $js_tags = google_jsapi();
    if(array_key_exists('js', $page) && is_array($page['js'])) {
      foreach($page['js'] as $js) {
        $js_tags .= '  <script src="/js/'.$js.'.js" type="text/javascript" charset="utf-8"></script>'."\n";
      }
    }
    return $js_tags;
  }
  
  function keywords() 
  {
    global $page;
    return implode(', ', $page['keywords']);
  }
  
  function description() 
  {
    global $page;
    return $page['description'];
  }
  
  function title($separator = ':') 
  {
    global $page;
    $title_string = '';
    if(array_key_exists('title', $page))
      $title_string .= $page['title'];
    if(array_key_exists('site_title', $page) && array_key_exists('title', $page))
      $title_string .= " $separator ";
    if(array_key_exists('site_title', $page))
      $title_string .= $page['site_title'];
    return $title_string;
  }
  
  function meta_tags() 
  {
    global $page;
    
    $closer = tag_closer();
    
    $meta_http = array(
      'Content-Type'     => 'text/html; charset=utf-8',
      'Content-Language' => lang(),
      'imagetoolbar'     => 'no'
    );
    $meta_name = array(
      'rating'                    => 'General',
      'MSSmartTagsPreventParsing' => 'true'
    );
    
    if(array_key_exists('meta_http', $page))
      $meta_http = array_merge($meta_http, $page['meta_http']);
    if(array_key_exists('meta', $page))
      $meta_name = array_merge($meta_name, $page['meta']);
    
    $meta = '';
    
    foreach($meta_http as $key => $val) {
      $equiv   = trim(htmlspecialchars($key));
      $content = trim(htmlspecialchars($val));
      if(!empty($content))
        $meta .= "  <meta http-equiv='$equiv' content='$content'$closer>\n";
    }
    
    foreach($meta_name as $key => $val) {
      $name    = trim(htmlspecialchars($key));
      $content = trim(htmlspecialchars($val));
      if(!empty($content))
        $meta .= "  <meta name='$name' content='$content'$closer>\n";
    }
    
    return $meta;
  
  }
  
  function title_tag() 
  {
    return "  <title>".htmlspecialchars(title())."</title>\n";
  }

  // Sets class to active if active current page
  // or parent if current page is a child
  function active_nav_class($link) 
  {
    global $page;
    global $home_page;
    $link = trim($link, '/');
    $path = trim($page['path'], '/');
    if($link == '')
      $link = $home_page;
    if($path == '')
      $path = $home_page;
    $class = '';
    if($path == $link) {
      $class .= ' active ';
    } elseif(strpos($path, $link) === 0) {
      $class .= ' parent ';
    }
    return $class;
  }
  
  function render_page($file) 
  {
    include(CONTENT_DIR . $file . '.page');
  }
  
  function render_part($file) 
  {
    include(TEMPLATE_DIR . $file . '.part');
  }
  
  function render_content($file) 
  {
    render_page($file);
  }
  
  function h($string) 
  {
    echo htmlspecialchars($string);
  }
  
  function body_class() 
  {
    global $page;
    return str_replace('/', ' ', $page['path']);
  }
  
  function google_jsapi() 
  {
    global $page;
    
    if(!array_key_exists('jsapi', $page))
      return '';
    
    $jsapi  = "  <script type='text/javascript' src='http://www.google.com/jsapi'></script>\n";
    
    foreach($page['jsapi'] as $lib) {
      if(is_array($lib) && count($lib) == 2) {
        $library = $lib[0];
        $version = $lib[1];
        $jsapi .= "  <script type='text/javascript'>google.load('$library', '$version');</script>\n";
      } else {
        trigger_error( "Incorrect jsapi designation in config. Missing version?", E_USER_WARNING );
      }
    }
    
    return $jsapi;
  }
  
  function isvar($varname) 
  {
    global $vars;
    return array_key_exists($varname, $vars);
  }
  
  function varset($varname) 
  {
    global $vars;
    if(!isvar($varname))
      return false;
    $vars[$varname] = trim($vars[$varname]);
    return !empty($vars[$varname]);
  }
  
  function nav_data($depth=1000, $parent=false) 
  {
    global $config;
    global $pages;
    if($parent && array_key_exists('subpages', $pages[$parent])) {
      return nav_data_from_tree(1, $depth, $pages[$parent]['subpages'], $parent.'/');
    } else {
      return nav_data_from_tree(1, $depth, $config['nested_pages'], '');
    }
  }
  
  function nav_data_from_tree($level, $depth, $tree, $parent_path) 
  {
    $nav_data = array();
    foreach($tree as $page_name => $page_data) {
      $current_nav_data = nav_data_for($parent_path . $page_name);
      // Don't link if it's a category
      if(array_key_exists('redirect', $page_data) && $level < $depth && array_key_exists('subpages', $page_data)) {
        $current_nav_data['href'] = false;
      }
      // Get subpage nav_data
      if($level < $depth && array_key_exists('subpages', $page_data)) {
        $current_nav_data['subpages'] = nav_data_from_tree($level+1, $depth, $page_data['subpages'], $current_nav_data['page_path'] . '/');
      } else {
        $current_nav_data['subpages'] = false;
      }
      // Add current_nav_data to nav_data
      $nav_data[] = $current_nav_data;
    }
    return $nav_data;
  }
  
  function parent($page_path) 
  {
    if(strpos($page_path, '/') === false) {
      return '';
    }
    $path_parts = explode('/', $page_path);
    array_pop($path_parts);
    $parent = implode('/', $path_parts);
    return $parent;
  }
  
  function name($page_path) 
  {
    if(strpos($page_path, '/') === false) {
      return $page_path;
    }
    $path_parts = explode('/', $page_path);
    return array_pop($path_parts);
  }
  
  function nav_data_for($for_page=false) 
  {
    global $page;
    global $pages;
    global $config;
    global $home_page;
    $page_path = $for_page ? $for_page : $page['path'];
    $page_data = $pages[$page_path];
    $parent_path = parent($page_path);
    $page_name = name($page_path);
    $exclude = $page_data['nav_exclude'] == 'true';
    $nav_class = array_key_exists('nav_class', $page_data) ? $page_data['nav_class'] : preg_replace('/[^\w\d]/i', '-', $page_name);
    if($page_path == $home_page) {
      $href = '/';
    } elseif(array_key_exists('redirect', $page_data) && (stripos($page_data['redirect'], 'http://') === 0)) {
      $href = $page_data['redirect'];
    } else {
      $href = '/'.htmlspecialchars($page_path);
    }
    if(array_key_exists('nav_label', $page_data)) {
      if(empty($page_data['nav_label'])) {
        $label = false;
      } else {
        $label = htmlspecialchars($page_data['nav_label']);
      }
    } elseif(array_key_exists('title', $page_data)) {
      $label = htmlspecialchars($page_data['title']);
    } else {
      $label = htmlspecialchars(ucwords($page_name));
    }
    
    // Set active class
    $active_class = active_nav_class($href);
    
    // Is it an external link?
    $external = stripos($href, 'http://') === 0;
    
    // Find next and previous pages
    $siblings = array();
    foreach($pages as $key => $val) {
      if($parent_path == parent($key)) {
        $siblings[] = $key;
      }
    }
    while(current($siblings) != $page_path) {
      $prev = current($siblings);
      next($siblings);
    }
    $next = next($siblings);
    
    // Put it all together
    return array(
      'prev'         => $prev,
      'page_path'    => $page_path,
      'next'         => $next,
      'page_name'    => $page_name,
      'parent_path'  => $parent_path,
      'active_class' => $active_class,
      'nav_class'    => $nav_class,
      'href'         => $href,
      'label'        => $label,
      'exclude'      => $exclude,
      'external'     => $external
    );
  }
  
  function nav_list($depth=1000, $parent=false) 
  {
    $nav_data = nav_data($depth, $parent);
    $list = '<ul class="nav">';
    foreach($nav_data as $page_data) {
      if(!$page_data['exclude'] && $page_data['label']) {
        $rel = $page_data['external'] ? "rel='external'" : '';
        $list .= "<li class='{$page_data['active_class']} {$page_data['nav_class']}'>";
        if($page_data['href'] !== false) 
          $list .= "<a class='{$page_data['active_class']} {$page_data['nav_class']}' $rel href='{$page_data['href']}'>";
        $list .= $page_data['label'];
        if($page_data['href'])
          $list .= "</a>";
        if($page_data['subpages']) {
          $list .= nav_list($depth, $page_data['page_path']);
        }
        $list .= '</li>';
      }
    }
    $list .= '</ul>';
    return $list;
  }
    
  function google_analytics() 
  {
    global $page;
    if(!array_key_exists('google_analytics_id', $page))
      return false;
    $tracking_code = "
      <script type=\"text/javascript\">
        var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
        document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
      </script>
      <script type=\"text/javascript\">
        var pageTracker = _gat._getTracker(\"{$page['google_analytics_id']}\");
        pageTracker._initData();
        pageTracker._trackPageview();
      </script>\n";
    return $tracking_code;
  }
  
?>