<?php
class SQLController
{
	private $mysqli;
	private $hostname = "";
	private $user = "";
	private $password = "";
	private $schema = "";

	function __construct($hostname, $user, $password, $schema)
	{
		$this->hostname = $hostname;
		$this->user = $user;
		$this->password = $password;
		$this->schema = $schema;

		$this->mysqli = new mysqli();
	}

	function connect()
	{
		//create conecction object
		$this->mysqli->connect($this->hostname, $this->user, $this->password, $this->schema, ini_get('mysqli.default_port'), ini_get('mysqli.default_socket'));

		//check connection status
		if ($this->mysqli->connect_errno)
		{
			echo "Fallo al conectar a MySQL: " . $this->mysqli->connect_error();
			exit();
		}

		//change charset to utf-8 for avoid possible encoding problems
		$this->mysqli->set_charset("utf8");
	}

	public function sqlExecuteQuery($query)
	{
		$this->connect();

		$resultArray = array();
		if ($result = $this->mysqli->query($query))
		{
			// Loop through each result
			while ($row = $result->fetch_object()) {
				// Add each result into the results array
				array_push($resultArray, $row);
			}
		}

		$this->mysqli->close();
		return $resultArray;
	}

	public function sqlSelectTable($sTabla)
	{
		$query = "SELECT * FROM $sTabla";
		return $this->sqlExecuteQuery($query);
	}

	public function sqlSelectTableColumn($tabla, $col)
	{
		$query = "SELECT $col FROM $tabla";
		return $this->sqlExecuteQuery($query);
	}

	
	
}