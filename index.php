<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
include "autoload.php";

$f = new Facebook("tutijapa@terra.com.br","ygen200h");

var_dump($f->processarMensagens());

?>
