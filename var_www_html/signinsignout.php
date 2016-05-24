<?PHP
include "inc.header.php";
?>


<?PHP


function output_debug($str) {
	echo "DEBUG: $str\r\n<br>";
}
// 2014-11-02 21:25:03 --> ignores date and calculates seconds from 00:00:00
function datetimetime_to_sec($time) { 
	$hours = substr($time, -8, 2); 
	$minutes = substr($time, -5, 2); 
	$seconds = substr($time, -2); 
	
	return $hours * 3600 + $minutes * 60 + $seconds; 
}




$online_offline_threshold = 480;   // 480 sec means 8 minutes used in colouring table and also calculate the Pause duration 
$current_device_id="";
$current_device_name="";
$current_device_owner="";

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





if(isset($_GET["device_id"])) {

	$query_date = $_GET["date"];
	$device_id = $_GET["device_id"];


	echo "<h2>Select date</h2>";
	
	$cur_date = new DateTime($query_date);
	$cur_date->add(new DateInterval('P1D'));		// + 1 days
	$today = new DateTime();
	if($cur_date > $today) {
		$show_tomorrow = false;
	} else {
		$show_tomorrow = true;
	}
	$tomorrow = $cur_date->format('Y-m-d');
	$cur_date->sub(new DateInterval('P2D'));		// - 2 days
	$yesterday = $cur_date->format('Y-m-d');
	
	

	echo "Change date: ";
	echo '<a href="?device_id=' . $device_id  . '&date=' . $yesterday. '">-1day</a>  ';
	echo '<a href="?device_id=' . $device_id  . '&date=' . $query_date. '">' . $query_date . '</a>  ';
	echo "<b></b>";
	if($show_tomorrow) {
		echo '<a href="?device_id=' . $device_id  . '&date=' . $tomorrow. '">+1day</a>';
	}
	echo ' | <a href="?device_id=' . $device_id  . '&date=' . date('Y-m-d') . '">today</a>';
	echo "<br>";





	//
	// show all scans from the selected device
	//

	if(!isset($_GET["date"])) {
		output_debug( "Error missing parameter date");
		exit;
	}
	
	
	// get name, etc. from the device
	$sql = "SELECT * FROM devices WHERE id = $device_id;";
	$result = $conn->query($sql);
	if ( $result === FALSE) {
		output_debug( "Error selecting table scans: " . $conn->error);
		$conn->close();
		exit;
	}
	if ($result->num_rows > 0) {
		$device = $result->fetch_assoc();
		
		$current_device_id = $device['id'];
		$current_device_name = $device['name'];
		$current_device_owner = $device['owner'];
	}
	
	
	
	

	//
	// Select all scans on a certain day
	//
	$sql = "SELECT scans.created FROM scans WHERE scans.device_id = $device_id AND scans.created >= '$query_date' AND scans.created < '$query_date' + INTERVAL 1 DAY;";
	$result = $conn->query($sql);
	if ( $result === FALSE) {
		output_debug( "Error selecting table scans: " . $conn->error);
		$conn->close();
		exit;
	}

	$values = array();	
	$value  = array();
	
	
	
	if ($result->num_rows > 0) {
		$last = -1000000000;
		$last_created = "";
		echo "<h2>Results from $query_date for device (id: $current_device_id) $current_device_name from $current_device_owner</h2>";
		while($row = $result->fetch_assoc()) {
			
			
			$second_of_the_day = datetimetime_to_sec($row["created"]);
			
			if(($second_of_the_day - $last) > $online_offline_threshold) {
			    $diff_seconds = $second_of_the_day - $last;
				
				// First entry of the day
				if(strlen($last_created) == 0) {
					echo "Sign In: " . substr($row["created"], 11) . '<br>';
				} else {
					// echo "LEFT at " . $last_created . ' - ' . $row["created"] . ' for ' . gmdate("H:i:s", $diff_seconds) . '<br>';
					// echo "LEFT at " . substr($last_created, 11) . ' for ' . gmdate("H:i:s", $diff_seconds) . ' hours. BACK at ' .  substr($row["created"],11) . '<br>';
					
					echo "Sign Out: " . substr($last_created, 11) . '<br>';
					
					echo 'Sign In: ' .  substr($row["created"],11) . '<br>';
					
					// .'. DURATION: ' . gmdate("H:i:s", $diff_seconds) . ' hours.<br>';
					
					
				}
			}
			$last = $second_of_the_day;
			$last_created = $row["created"];
			
			$value  = array($second_of_the_day/3600, 1);
			$values[] = $value;
		}
		$end = gmdate("H:i:s", round($values[count($values)-1][0] * 3600));
		echo "Sign Out: $end";
		
		
	} else {
		echo '<br><font color="red">0 scans found for device ' . "(id: $current_device_id) on the $query_date<br>device name: $current_device_name<br>Owner: $current_device_owner</font><br>";
	}
}



$conn->close();
?>

</body>
</html>
