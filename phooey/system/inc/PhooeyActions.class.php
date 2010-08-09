<?php

/**
* Base class for Actions
*/
class PhooeyActions
{
  public $page;
  public $vars;
  
  function __construct($page)
  {
    $this->page = $page;
    $this->vars = $page->get_vars();
  }
  
}
