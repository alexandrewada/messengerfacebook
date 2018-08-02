<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
include "autoload.php";

$f = new Facebook("myka_sk8_@hotmail.com","");

var_dump($f->processarMensagens());

?>
