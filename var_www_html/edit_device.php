<?PHP
session_start();
if($_SESSION['angemeldet'] != true) {
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	//header('Location: http://'.$hostname.'/index.php');
}
?>
<!DOCTYPE HTML>
<html>
    <head>
		<title>phpArpScan Edit</title>
    <body>

	<pre>
<?php
$servername = "localhost";
$database = "arp_scan";
$username = "arp_scan";
$password = "oluhznkjhIh78ghJZgjhfHdtriuzJgvht";
$table1   = "devices";
$table2   = "scans";
// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} 

if ($conn->select_db($database) === FALSE) {
	output_debug( "Error selecting database $database: " . $conn->error);
	$conn->close();
	exit;
}





if(!isset($_GET["device_id"])) {
	echo "No device id available";
	exit;
}
$device_id = $conn->real_escape_string($_GET["device_id"]);


if(isset($_GET["name"])) {
	$name = $conn->real_escape_string($_GET["name"]);
} else {
	$name = "";
}
if(isset($_GET["owner"])) {
	$owner = $conn->real_escape_string($_GET["owner"]);
} else {
	$owner = "";
}
?>
</pre>




<?PHP


function output_debug($str) {
	echo "$str\r\n";
}
// 2014-11-02 21:25:03 --> ignores date and calculates seconds from 00:00:00
function datetimetime_to_sec($time) { 
	$hours = substr($time, -8, 2); 
	$minutes = substr($time, -5, 2); 
	$seconds = substr($time, -2); 
	
	return $hours * 3600 + $minutes * 60 + $seconds; 
}





if(strlen($name) == 0 || strlen($owner) == 0) {
	$sql = "SELECT * FROM devices WHERE id='$device_id';";
	
echo "Line: " . __LINE__ . " - $sql<br>";
	
	$result = $conn->query($sql);
	if ( $result === FALSE) {
		output_debug( "Error selecting table devices: " . $conn->error);
		$conn->close();
		exit;
	}
	if ($result->num_rows != 1) {
		output_debug( "Error incorrect number of results: " . $result->num_rows);
		$conn->close();
		exit;
	}


	$row = $result->fetch_assoc();

	$id =  $row["id"];
	$created =  $row["created"];
	$name = $row["name"];
	$owner = $row["owner"];

	$conn->close();
?>
<form action="" method="get">
<h2>Device <?= $id?> created on <?= $created?></h2>
 <p>Name: <input type="text" name="name" value="<?= $name?>" /></p>
 <p>Owner: <input type="text" name="owner" value="<?= $owner?>" /> (name or Infrastructure or Multimedia)</p>
  <input type="hidden" name="device_id" value="<?= $id?>" />
 <p><input type="submit" /></p>
</form>
<?PHP
} else {


	$sql = 	"UPDATE  arp_scan.devices " . 
			"SET  name =  '$name', owner = '$owner' " .
			"WHERE devices.id = $device_id;";
			
	if ($conn->query($sql) === FALSE) {
		output_debug( "Error update device $device_id in table devices: " . $conn->error);
		$conn->close();
		exit;
	}	

	// Redirect to the index page
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
}

?>



    </body>
</html>


