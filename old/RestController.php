<?php
require_once("RestHandler.php");

$table = "";
$col = "";

if (isset($_GET["table"]))
	$table = $_GET["table"];

if (isset($_GET["col"]))
	$col = $_GET["col"];
/*
controls the RESTful services
URL mapping
*/
$handler = new RestHandler();

if($col == "") {
	$handler->getTable($table);
} else {
	$handler->getTable($table, $col);
}
?>
