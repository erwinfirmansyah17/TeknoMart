<?php
// Development-only read-only page smoke testing. Never route production through this file.
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
$root=realpath(__DIR__.'/../TeknoMart-main');
$file=realpath($root.($path==='/'?'/index.php':$path));
if (!$file || strpos($file,$root.DIRECTORY_SEPARATOR)!==0) { http_response_code(404); exit; }
if (pathinfo($file,PATHINFO_EXTENSION)!=='php') return false;
$_SERVER['SCRIPT_NAME']=$path==='/'?'/index.php':$path;
require_once $root.'/includes/bootstrap.php';
if (($_SERVER['HTTP_X_TEST_FIXTURE']??'')==='commerce') require __DIR__.'/fixtures.php';
$role=$_SERVER['HTTP_X_TEST_ROLE']??'';
unset($_SESSION['id_admin'],$_SESSION['id_user']);
if ($role==='admin') $_SESSION['id_admin']=(int)(one('SELECT id_admin FROM tb_admin ORDER BY id_admin LIMIT 1')['id_admin']??0);
if ($role==='user') $_SESSION['id_user']=(int)(one('SELECT id_user FROM tb_user ORDER BY id_user LIMIT 1')['id_user']??0);
require $file;
