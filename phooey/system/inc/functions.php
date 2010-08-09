<?php

// This is how empty() should work anyway (accepts any value; not just variables)!
function is_empty($val)
{
  return empty($val);
}

// Shorthand for using everywhere
function h($string) 
{
  echo htmlspecialchars($string);
}

// Deep stripslashes
function fix_magic_quotes($data)
{
  $data = is_array($data) ? array_map('fix_magic_quotes', $data) : stripslashes($data);
  return $data;
}

// Returns true if the filename is not . or ..
function real_file($filename) 
{
  return $filename !== '.' && $filename !== '..';
}

// Returns a multi-dimensional array for a MySQL query
function mysql_select_rows($query)
{
  if($result = @mysql_query($query)) {
    $rows = array();
    while($row = @mysql_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return $rows;
  } else {
    return false;
  }
}

// Returns the first row as an array for a MySQL query
function mysql_select_row($query)
{
  if($result = @mysql_query($query)) {
    return @mysql_fetch_assoc($result);
  } else {
    return false;
  }
}

// Returns the first row as an array for a MySQL query
function mysql_select_value($query)
{
  if($result = @mysql_query($query)) {
    $row = @mysql_fetch_row($result);
    return $row[0];
  } else {
    return false;
  }
}

/**
 * Merges any number of arrays / parameters recursively, replacing
 * entries with string keys with values from latter arrays.
 * If the entry or the next value to be assigned is an array, then it
 * automagically treats both arguments as an array.
 * Numeric entries are appended, not replaced, but only if they are
 * unique
 *
 * calling: result = array_merge_recursive_distinct(a1, a2, ... aN)
**/
function array_merge_recursive_distinct () {
  $arrays = func_get_args();
  $base = array_shift($arrays);
  if(!is_array($base)) $base = empty($base) ? array() : array($base);
  foreach($arrays as $append) {
    if(!is_array($append)) $append = array($append);
    foreach($append as $key => $value) {
      if(!array_key_exists($key, $base) and !is_numeric($key)) {
        $base[$key] = $append[$key];
        continue;
      }
      if(is_array($value) or is_array($base[$key])) {
        $base[$key] = array_merge_recursive_distinct($base[$key], $append[$key]);
      } else if(is_numeric($key)) {
        if(!in_array($value, $base)) $base[] = $value;
      } else {
        $base[$key] = $value;
      }
    }
  }
  return $base;
}