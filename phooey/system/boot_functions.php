<?php

// Function for flattening nested page array
function extract_pages($nested_pages, $parent_path = '') {
  global $pages;
  foreach($nested_pages as $page_path => $page_data) {
    $pages[$parent_path.$page_path] = $page_data;
    if(array_key_exists('subpages', $page_data)) {
      extract_pages($page_data['subpages'], $parent_path.$page_path.'/');
    }
  }
}


?>