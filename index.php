<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
include "autoload.php";

$f = new Facebook("15393026175@@","18183650@@");

var_dump($f->getMessages());

?>
