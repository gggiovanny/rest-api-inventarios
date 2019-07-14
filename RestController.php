<?php
require_once("RestHandler.php");

$table = "";
if(isset($_GET["table"]))
	$table = $_GET["table"];
/*
controls the RESTful services
URL mapping
*/
$handler = new RestHandler();
$handler->getTable($table);


?>