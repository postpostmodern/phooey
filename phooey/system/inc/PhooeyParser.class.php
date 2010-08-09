<?php
require_once('stp.php');
# Uses a modified verson of the STP
class PhooeyParser extends Parser2 {
  function splitTemplate($str) {
    $str = preg_replace('/\[#\s*(.*?)\s*#\]/e', "'<#\\1#>'", $str);
    parent::splitTemplate($str);
  }
}
