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
