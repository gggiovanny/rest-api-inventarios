<?php
require_once("SimpleRest.php");
require_once("DB/SQLController.php");

class RestHandler extends SimpleRest {

	function getTable($table) {	

		$sql = new SQLController("localhost","android-test","123456","grupodic_activofijo");
		$rawData = $sql->sqlSelectTable($table);

		if(empty($rawData)) {
			$statusCode = 404;
			$rawData = array('error' => 'No table found!');		
		} else {
			$statusCode = 200;
		}

		$requestContentType = 'application/json';//$_POST['HTTP_ACCEPT'];
		$this ->setHttpHeaders($requestContentType, $statusCode);
		
		//$result["output"] = $rawData;
				
		if(strpos($requestContentType,'application/json') !== false){
			//$response = $this->encodeJson($result);
			$response = $this->encodeJson($rawData);
			echo $response;
		}
	}
	
	public function encodeJson($responseData) {
		$jsonResponse = json_encode($responseData);
		return $jsonResponse;		
	}
}
