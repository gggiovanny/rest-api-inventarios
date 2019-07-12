<?php 
header('Content-Type: charset=utf-8;application/json');

// Create connection
$mysqli = new mysqli("localhost","android-test","123456","test");

/* check connection */
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}

/* change character set to utf8 */
if (!$mysqli->set_charset("utf8")) {
	//printf("Error loading character set utf8: %s\n", $mysqli->error);
} else {
	//printf("Current character set: %s\n", $mysqli->character_set_name());
}

// Select all of our stocks from table 'stock_tracker'
$sql = "SELECT * FROM `catalogo_estados` WHERE cpaiIndice = 1";

// Confirm there are results
if ($result = mysqli_query($mysqli, $sql))
{
	// We have results, create an array to hold the results
        // and an array to hold the data
	$resultArray = array();
	$tempArray = array();

	// Loop through each result
	while($row = $result->fetch_object())
	{
		// Add each result into the results array
		$tempArray = $row;
	    array_push($resultArray, $tempArray);
	}
	
	// Encode the array to JSON and output the results
	$json = json_encode($resultArray);

	if($json)
	{
		echo $json;
	}
	else
	{
		echo "Error de conversion a json!";
	}
}
// Close connections
$mysqli->close();
?>