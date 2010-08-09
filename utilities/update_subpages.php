#!/usr/bin/php
<?php

// This script changes nested subpages to new form.
// Run this file with './update_subpages.php /path/to/pages.yaml'

if($argc != 2) {
  echo "Usage: {$argv[0]} /path/to/pages.yaml\n";
  exit;
}

if(!file_exists($argv[1])) {
  echo "File does not exist: {$argv[1]}\n";
  exit;
}

require_once('../phooey/system/inc/spyc.php');

$old_pages = Spyc::YAMLLoad($argv[1]);
$GLOBALS['pages'] = array();

function flatten_subpages($key, $page)
{
  if(array_key_exists('subpages', $page)) {
    foreach($page['subpages'] as $subkey => $subpage) {
      $GLOBALS['pages'][$key.'/'.$subkey] = $subpage;
      flatten_subpages($subkey, $subpage);
    }
  }
}

foreach($old_pages as $key => $page) {
  $GLOBALS['pages'][$key] = $page;
  flatten_subpages($key, $page);
}

foreach($GLOBALS['pages'] as $key => $page) {
  if(array_key_exists('subpages', $page)) {
    unset($GLOBALS['pages'][$key]['subpages']);
  }
}

file_put_contents(dirname($argv[1])."/new_pages.yaml", Spyc::YAMLDump($GLOBALS['pages']));
