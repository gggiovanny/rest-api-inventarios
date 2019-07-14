<?php
class SQLController
{
	private $mysqli;

	function __construct($hostname, $user, $password, $schema)
	{
		//create conecction object
		$this->mysqli = new mysqli($hostname, $user, $password, $schema);

		//check connection status
		if ($this->mysqli->connect_errno)
		{
			echo "Fallo al conectar a MySQL: " . $this->mysqli->connect_error();
			exit();
		}

		//change charset to utf-8 for avoid possible encoding problems
		$this->mysqli->set_charset("utf8");
	}

	function __destruct()
	{
		$this->mysqli->close();
	}

	public function sqlExecuteQuery($query)
	{
		$resultArray = array();
		if ($result = $this->mysqli->query($query))
		{
			// Loop through each result
			while ($row = $result->fetch_object()) {
				// Add each result into the results array
				array_push($resultArray, $row);
			}
		}
		return $resultArray;
	}

	public function sqlSelectTable($sTabla)
	{
		$query = "SELECT * FROM $sTabla";
		return $this->sqlExecuteQuery($query);
	}
	
}