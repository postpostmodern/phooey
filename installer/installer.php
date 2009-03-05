<?php
if($argc != 2) {
  echo "Usage: {$argv[0]} /path/to/installation\n";
  exit;
}

function install($src_path, $dest_dir, $overwrite)
{
  if(is_dir($src_path)) {
    install_dir($src_path, $dest_dir, $overwrite);
  } elseif(is_file($src_path)) {
    install_file($src_path, $dest_dir, $overwrite);
  }
}


function install_dir($src_dir, $dest_dir, $overwrite)
{
  $dest_path = "$dest_dir/$src_path";
  if(!is_dir($dest_path)) {
    echo("Creating dir $dest_path...\n");
    mkdir($dest_path, 0755, true);
  }
  $dir = dir($src_dir);
  while(($file = $dir->read()) !== false) {
    if($file == '.' || $file == '..') {
      continue;
    }
    $src_path = "$src_dir/$file";
    $dest_path = "$dest_dir/$file";
    install($src_path, $dest_dir, $overwrite);
  }
  $dir->close();
}


function install_file($src_path, $dest_dir, $overwrite)
{
  $dest_path = "$dest_dir/$src_path";
  $dir = dirname($dest_path);
  if(!is_dir($dir)) {
    echo("Creating dir $dir...\n");
    mkdir($dir, 0755, true);
  }
  if(is_file($src_path) && ($overwrite || !is_file($dest_path))) {
    echo("Copying file...\n    from: $src_path\n      to: $dest_path\n");
    copy($src_path, $dest_path);
  }
}

function core_install()
{
  global $files, $dest_dir;
  foreach($files['core'] as $user_path) {
    install($user_path, $dest_dir, true);
  }
}

function user_install()
{
  global $files, $dest_dir;
  foreach($files['user'] as $user_path) {
    install($user_path, $dest_dir, false);
  }
}


require_once('phooey/system/spyc.php');
$files = Spyc::YAMLLoad('installer/files.yaml');

$dest_dir = $argv[1];
