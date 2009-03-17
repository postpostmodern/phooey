<?php

/**
* Contains methods for generating html and stuff
*/
class PhooeyHelper
{
  public $page;
  
  public function __construct($page)
  {
    $this->page = $page;
  }

  public function get_data($datakey, $default=false)
  {
    return $this->page->get_data($datakey, $default);
  }
  
  public function lang()
  {
    return $this->get_data('language', 'en');
  }
  
  public function is_xhtml()
  {
    return !(strpos($this->doctype(), 'XHTML') === false);
  }
  
  public function tag_closer()
  {
    return $this->is_xhtml() ? ' /' : '';
  }

  public function doctype()
  {
    return $this->get_data('doctype', 'XHTML 1.0 Strict');
  }
  
  public function doctype_tag()
  {
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
    return $doctype_tags[$this->doctype()]."\n";
  }
  
  public function html_tag()
  {
    $lang = $this->lang();
    $tag = "<html";
    $tag .= $this->is_xhtml() ? " xmlns='http://www.w3.org/1999/xhtml' xml:lang='$lang'" : '';
    $tag .= strpos($this->doctype(), 'XHTML 1.1') === false ? " lang='$lang'" : '';
    $tag .= ">\n";
    return $tag;
  }
  
  public function css_tags() 
  {
    $css_tags = '';
    $css_files = $this->get_data('css', array());
    $ie_css_files = $this->get_data('ie_css', array());
    foreach($css_files as $css) {
      if(is_array($css)) {
        $css_tags .= '  <link href="/css/'.$css[0].'.css" rel="stylesheet" type="text/css" media="'.$css['1'].'" charset="utf-8"'.$this->tag_closer().'>'."\n";
      } else {
        $css_tags .= '  <link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8"'.$this->tag_closer().'>'."\n";
      }
    }
    foreach($ie_css_files as $css => $condition) {
      $css_tags .= '  <!--[if '.$condition.']><link href="/css/'.$css.'.css" rel="stylesheet" type="text/css" media="all" charset="utf-8"'.$this->tag_closer().'><![endif]-->'."\n";
    }
    return $css_tags;
  }
  
  public function js_tags() 
  {
    $js_tags = $this->google_jsapi();
    $js_files = $this->get_data('js', array());
    foreach($js_files as $js) {
      $js_tags .= '  <script src="/js/'.$js.'.js" type="text/javascript" charset="utf-8"></script>'."\n";
    }
    return $js_tags;
  }
  
  public function keywords() 
  {
    return implode(', ', $this->get_data('keywords', ''));
  }
  
  public function description() 
  {
    return $this->get_data('description', '');
  }
  
  public function title($separator = ':') 
  {
    $title_string = '';
    $title = $this->get_data('title');
    $site_title = $this->get_data('site_title');
    if($title) $title_string .= $title;
    if($site_title && $title) $title_string .= " $separator ";
    if($site_title) $title_string .= $site_title;
    return $title_string;
  }
  
  public function meta_tags() 
  {
    
    $closer = $this->tag_closer();
    
    $meta_http = array(
      'Content-Type'     => 'text/html; charset=utf-8',
      'Content-Language' => $this->lang(),
      'imagetoolbar'     => 'no'
    );
    $meta_name = array(
      'rating'                    => 'General',
      'MSSmartTagsPreventParsing' => 'true'
    );
    
    $meta_http = array_merge($meta_http, $this->get_data('meta_http', array()));
    $meta_name = array_merge($meta_name, $this->get_data('meta', array()));
    
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
  
  public function title_tag() 
  {
    return "  <title>".htmlspecialchars($this->title())."</title>\n";
  }

  // Sets class to active if active current page
  // or parent if current page is a child
  public function active_nav_class($link) 
  {
    $page = $this->page->get_page_by_href($link);
    return $page->get_active_class();
  }
  
  public function render_page($file) 
  {
    include(CONTENT_DIR . $file . '.page');
  }
  
  public function render_part($file) 
  {
    include(TEMPLATE_DIR . $file . '.part');
  }
  
  public function render_content($file) 
  {
    $this->render_page($file);
  }
  
  public function body_class() 
  {
    return str_replace('/', ' ', $this->page->get_key());
  }
  
  public function google_jsapi() 
  {
    if(!$libs = $this->get_data('jsapi')) return '';
    $jsapi  = "  <script type='text/javascript' src='http://www.google.com/jsapi'></script>\n";
    foreach($libs as $lib) {
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
  
  public function is_var($varname) 
  {
    return $this->page->is_var($varname);
  }
  
  public function var_set($varname) 
  {
    return $this->page->is_var_set($varname);
  }
  
  public function nav_data($for_page=false) 
  {
    $page = $for_page ? $this->page->get_page($for_page) : $page;
    $page->get_nav_data();
  }
  
  public function nav_list($depth=1000, $parent=false, $count=1) 
  {
    $pages = $parent ? $parent->get_subpages() : $this->page->site->root_pages;
    $list = '<ul class="nav">';
    foreach($pages as $page) {
      $page_data = $page->get_nav_data();
      if(!$page_data['exclude'] && $page_data['label']) {
        $rel = $page_data['external'] ? "rel='external'" : '';
        $list .= "<li class='{$page_data['active_class']} {$page_data['nav_class']}'>";
        if($page_data['href'] !== false) 
          $list .= "<a class='{$page_data['active_class']} {$page_data['nav_class']}' $rel href='{$page_data['href']}'>";
        $list .= $page_data['label'];
        if($page_data['href'])
          $list .= "</a>";
        if($page->get_subpages() && $count < $depth) {
          $list .= nav_list($depth, $page_data['page_path'], $count+1);
        }
        $list .= '</li>';
      }
    }
    $list .= '</ul>';
    return $list;
  }
    
  public function google_analytics() 
  {
    if(!$ga_id = $this->get_data('google_analytics_id'))
      return false;
    $tracking_code = "
      <script type=\"text/javascript\">
        var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
        document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
      </script>
      <script type=\"text/javascript\">
        var pageTracker = _gat._getTracker(\"$ga_id\");
        pageTracker._initData();
        pageTracker._trackPageview();
      </script>\n";
    return $tracking_code;
  }

}

?>