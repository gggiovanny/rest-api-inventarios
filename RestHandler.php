<?php
require_once("SimpleRest.php");
require_once("DB/SQLController.php");

class RestHandler extends SimpleRest {

	private $sql;

	function __construct()
	{
		$this->sql = new SQLController("localhost","root","","grupodic_activofijo");
	}

	function getTable($table, $col="*")
	{	
		if($col == "*") {
			$rawData = $this->sql->sqlSelectTable($table);
		} else {
			$rawData = $this->sql->sqlSelectTableColumn($table, $col);
		}
			

		if(empty($rawData)) {
			$statusCode = 404;
			$rawData = array('error' => 'No table found!');		
		} else {
			$statusCode = 200;
		}

		$requestContentType = 'application/json';//$_POST['HTTP_ACCEPT'];
		$this ->setHttpHeaders($requestContentType, $statusCode);
		
		$result["output"] = $rawData;
				
		if(strpos($requestContentType,'application/json') !== false){
			$response = $this->encodeJson($result);
			//$response = $this->encodeJson($rawData);
			echo $response;
		}
	}
	
	public function encodeJson($responseData) {
		$jsonResponse = json_encode($responseData, JSON_UNESCAPED_UNICODE);
		return $jsonResponse;		
	}
}

?>
