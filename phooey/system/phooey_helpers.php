<?php

  function css_tags() {
    global $page;
    $css_tags = '';
    if(array_key_exists('css', $page) && is_array($page['css'])) {
      foreach($page['css'] as $css) {
        if(is_array($css)) {
          $css_tags .= '  <link href="/css/'.$css[0].'.css" rel="stylesheet" type="text/css" media="'.$css['1'].'" charset="utf-8" />'."\n";
        } else {
          $css_tags .= '  <link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8" />'."\n";
        }
      }
    }
    if(array_key_exists('ie_css', $page) && is_array($page['ie_css'])) {
      foreach($page['ie_css'] as $css => $condition) {
        $css_tags .= '  <!--[if '.$condition.']><link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8" /><![endif]-->'."\n";
      }
    }
    return $css_tags;
  }
  
  function js_tags() {
    global $page;
    $js_tags = google_jsapi();
    if(array_key_exists('js', $page) && is_array($page['js'])) {
      foreach($page['js'] as $js) {
        $js_tags .= '  <script src="/js/'.$js.'.js" type="text/javascript" charset="utf-8"></script>'."\n";
      }
    }
    return $js_tags;
  }
  
  function keywords() {
    global $page;
    return implode(', ', $page['keywords']);
  }
  
  function description() {
    global $page;
    return $page['description'];
  }
  
  function title($separator = ':') {
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
  
  function meta_tags() {
    global $page;
    
    $meta_http = array(
      'Content-Type'     => 'text/html; charset=utf-8',
      'Content-Language' => 'en-us',
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
        $meta .= "  <meta http-equiv='$equiv' content='$content' />\n";
    }
    
    foreach($meta_name as $key => $val) {
      $name    = trim(htmlspecialchars($key));
      $content = trim(htmlspecialchars($val));
      if(!empty($content))
        $meta .= "  <meta name='$name' content='$content' />\n";
    }
    
    return $meta;
  
  }
  
  function title_tag() {
    return "  <title>".title()."</title>\n";
  }

  function active_nav_class($link) {
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
  
  function render_page($file) {
    include(CONTENT_DIR . $file . '.page');
  }
  
  function render_part($file) {
    include(TEMPLATE_DIR . $file . '.part');
  }
  
  function render_content($file) {
    render_page($file);
  }
  
  function h($string) {
    echo htmlspecialchars($string);
  }
  
  function body_class() {
    global $page;
    return str_replace('/', ' ', $page['path']);
  }
  
  function google_jsapi() {
    global $page;
    
    if(!array_key_exists('jsapi', $page))
      return '';
    
    $library = $page['jsapi']['library'];
    $version = $page['jsapi']['version'];
    $jsapi  = "  <script type='text/javascript' src='http://www.google.com/jsapi'></script>\n";
    $jsapi .= "  <script type='text/javascript'>google.load('$library', '$version');</script>\n";
    
    return $jsapi;
  }
  
  function isvar($varname) {
    global $vars;
    return array_key_exists($varname, $vars);
  }
  
  function varset($varname) {
    global $vars;
    if(!isvar($varname))
      return false;
    $vars[$varname] = trim($vars[$varname]);
    return !empty($vars[$varname]);
  }
  
  function nav_list($depth=1000, $parent=false) {
    global $nested_pages;
    global $pages;
    if($parent && array_key_exists('subpages', $pages[$parent])) {
      return nav_list_from_tree(1, $depth, $pages[$parent]['subpages'], $parent.'/');
    } else {
      return nav_list_from_tree(1, $depth, $nested_pages, '');
    }
  }
  
  function nav_list_from_tree($level, $depth, $tree, $parent_path) {
    $list = '<ul class="nav">';
    foreach($tree as $page_name => $page_data) {
      $href = array_key_exists('redirect', $page_data) && $level < $depth && array_key_exists('subpages', $page_data) ? false : htmlspecialchars($parent_path . $page_name);
      if(array_key_exists('nav_label', $page_data)) {
        if(empty($page_data['nav_label'])) {
          break;
        }
        $label = htmlspecialchars($page_data['nav_label']);
      } elseif(array_key_exists('title', $page_data)) {
        $label = htmlspecialchars($page_data['title']);
      } else {
        $label = htmlspecialchars(ucwords($page_name));
      }
      $active_class = active_nav_class($href);
      $list .= "<li class='$active_class'>";
      if($href) 
        $list .= "<a class='$active_class' href='/$href'>";
      $list .= "$label";
      if($href)
        $list .= "</a>";
      if($level < $depth && array_key_exists('subpages', $page_data)) {
        $list .= nav_list_from_tree($level+1, $depth, $page_data['subpages'], $parent_path . $page_name . '/');
      }
      $list .= '</li>';
    }
    $list .= '</ul>';
    return $list;
  }
  
  function google_analytics() {
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