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
  
  public function charset()
  {
    return $this->get_data('charset', 'utf-8');
  }
  
  public function doctype_tag()
  {
    $charset = $this->charset();
    $doctype_tags = array(
      'HTML 4.01 Strict'        => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>",
      'HTML 4.01 Transitional'  => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>",
      'HTML 4.01 Frameset'      => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/html4/frameset.dtd'>",
      'XHTML 1.0 Strict'        => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>",
      'XHTML 1.0 Transitional'  => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>",
      'XHTML 1.0 Frameset'      => "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Frameset//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd'>",
      'XHTML 1.1'               => "<?xml version='1.0' encoding='$charset'?>\n<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.1//EN' 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'>",
      'HTML 5'                  => "<!DOCTYPE html>"
    );
    return $doctype_tags[$this->doctype()]."\n";
  }
  
  public function html_tag()
  {
    $lang = $this->lang();
    $tag = "<html";
    $tag .= $this->is_xhtml() ? " xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$lang\"" : '';
    $tag .= strpos($this->doctype(), "XHTML 1.1") === false ? " lang=\"$lang\"" : '';
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
        $css_file = strpos($css[0], '/') === false ? $this->page->site->root.'css/'.$css[0] : $css[0];
        $css_tags .= '  <link href="'.$css_file.'" rel="stylesheet" type="text/css" media="'.$css['1'].'"'.$this->tag_closer().'>'."\n";
      } else {
        $css_file = strpos($css, '/') === false ? $this->page->site->root.'css/'.$css : $css;
        $css_tags .= '  <link href="'.$css_file.'" rel="stylesheet" type="text/css" media="all" '.$this->tag_closer().'>'."\n";
      }
    }
    foreach($ie_css_files as $css => $condition) {
      $css_file = strpos($css, '/') === false ? $this->page->site->root.'css/'.$css : $css;
      $css_tags .= '  <!--[if '.$condition.']><link href="'.$css_file.'" rel="stylesheet" type="text/css" media="all" '.$this->tag_closer().'><![endif]-->'."\n";
    }
    return $css_tags;
  }
  
  public function link_tags() 
  {
    $link_tags = '';
    $links = $this->get_data('links', array());

    $favicon = $this->get_data('favicon', false);
    if($favicon) {
      $links[] = array(
        'rel'  => "shortcut icon",
        'type' => "image/ico",
        'href' => ($favicon === true ? $this->page->site->root."favicon.ico" : $favicon)
      );
    }

    foreach($links as $link) {
      $rel   = '';
      $href  = '';
      $type  = '';
      $rev   = '';
      $media = '';
      if(is_array($link)) {
        if(count($link) == 2) {
          $rel   = 'rel="';
          $rel  .= array_key_exists('rel', $link)  ? $link['rel']  : $link[0];
          $rel  .= '" ';
          $href  = 'href="';
          $href .= array_key_exists('href', $link) ? $link['href'] : $link[1];
          $href .= '" ';
        } else {
          $rel    = array_key_exists('rel',   $link) ? 'rel="'   .$link['rel'].  '" ' : '';
          $href   = array_key_exists('href',  $link) ? 'href="'  .$link['href']. '" ' : '';
          $media  = array_key_exists('media', $link) ? 'media="' .$link['media'].'" ' : '';
          $rev    = array_key_exists('rev',   $link) ? 'rev="'   .$link['rev'].  '" ' : '';
          $type   = array_key_exists('type',  $link) ? 'type="'  .$link['type']. '" ' : '';
        }
      }
      $link_tags .= '  <link '.$href.$media.$rel.$rev.$type.$this->tag_closer().'>'."\n";
    }
    return $link_tags;
  }
  
  public function js_tags() 
  {
    $charset = $this->charset();
    $js_tags = $this->google_jsapi();
    $js_files = $this->get_data('js', array());
    foreach($js_files as $js) {
      $js_file = strpos($js, '/') === false ? $this->page->site->root.'js/'.$js : $js;
      $js_tags .= '  <script src="'.$js_file.'" type="text/javascript" charset="'.$charset.'"></script>'."\n";
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
      'Content-Language' => $this->lang(),
      'imagetoolbar'     => 'no'
    );
    if($this->doctype() != 'HTML 5') {
      $meta_http['Content-Type'] = 'text/html; charset='.$this->charset();
    }
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
        $meta .= "  <meta http-equiv=\"$equiv\" content=\"$content\"$closer>\n";
    }
    
    foreach($meta_name as $key => $val) {
      $name    = trim(htmlspecialchars($key));
      $content = trim(htmlspecialchars($val));
      if(!empty($content))
        $meta .= "  <meta name=\"$name\" content=\"$content\"$closer>\n";
    }
    
    return $meta;
  }
  
  public function meta_charset_tag()
  {
    $charset = $this->charset();
    $closer = $this->tag_closer();
    if($this->doctype() == 'HTML 5') {
      return "  <meta charset=\"$charset\"$closer>\n";
    } else {
      return '';
    }
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
    include($this->page->site->template_dir . $file . '.page');
  }
  
  public function render_part($file) 
  {
    include($this->page->site->template_dir . $file . '.part');
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
    $jsapi  = "  <script type=\"text/javascript\" src=\"http://www.google.com/jsapi\"></script>\n";
    foreach($libs as $lib) {
      if(is_array($lib) && count($lib) == 2) {
        $library = $lib[0];
        $version = $lib[1];
        $jsapi .= "  <script type=\"text/javascript\">google.load(\"$library\", \"$version\");</script>\n";
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
    $parent = $this->page->get_page($parent);
    $pages = $parent ? $parent->get_children() : $this->page->site->get_root_pages();
    if(!$pages) return false;
    $list = '<ul class="nav">';
    foreach($pages as $page) {
      if(!$page->get_nav_exclude() && $page->get_nav_label()) {
        
        $rel          = $page->is_external_redirect() ? "rel='external'" : '';
        $active_class = htmlspecialchars($page->get_active_class());
        $nav_class    = htmlspecialchars($page->get_nav_class());
        $href         = $page->get_href();
        $label        = htmlspecialchars($page->get_nav_label());
        
        $class = trim("$active_class $nav_class");
        $list .= "<li class='$class'>";
        if($href !== false) 
          $list .= "<a class='$class' $rel href='$href'>";
        $list .= $label;
        if($href !== false)
          $list .= "</a>";
        if($page->get_children() && $count < $depth)
          $list .= $this->nav_list($depth, $page, $count+1);
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
  try {
  var pageTracker = _gat._getTracker(\"$ga_id\");
  pageTracker._trackPageview();
  } catch(err) {}
</script>\n";
    return $tracking_code;
  }

}

?>