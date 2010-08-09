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

