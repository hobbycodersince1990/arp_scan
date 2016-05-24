<?PHP
include "inc.header.php";
?>
<?php
// get all scans from the last 10 minutes ...
//		SELECT * FROM scans WHERE DATE_SUB(NOW(),INTERVAL 10 MINUTE) <= created ORDER BY `created` ASC  
//

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



function get_default_gw() {
	exec('/sbin/route -n', $output);
	$default_gw = "";
	if(count($output) > 0) {
		foreach($output as $line) {
			if(strpos($line, '0.0.0.0') === 0) {
				$tmp = trim(substr($line, 8));
				$default_gw = substr($tmp, 0, strpos($tmp, ' '));
				break;
			}
		}
	}
	return $default_gw;
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



if(!isset($_SESSION['default_gw_id'])) {
// get id of degault GW
			
	$default_gw_ip = get_default_gw();
	$default_gw_ip_long = ip2long($default_gw_ip);


	// e.g. 167772298 = 10.0.0.138
	$sql = "SELECT devices.id FROM devices,scans WHERE scans.ipv4 = $default_gw_ip_long AND scans.device_id = devices.id AND DATE_SUB(NOW(),INTERVAL 2 MINUTE) <= scans.created";
	$defgw_result = $conn->query($sql);
	$defgw_info = null;
	if ( $defgw_result !== FALSE) {
	
		if ($defgw_result->num_rows > 0) {
			$defgw_info = $defgw_result->fetch_assoc();
			
			$_SESSION['default_gw_id'] = $defgw_info['id'];		// ??? error handling !!! --> also when showing the possibility to select and wehn showing results
		}
	}
	if(!isset($_SESSION['default_gw_id'])) {
		echo "<h1>Could not retrieve default GW ID</h1>";
		echo "<pre>IP: $default_gw_ip\r\nIP: $default_gw_ip_long\r\n</pre>";
		echo "<br><br><br>Query: $sql<br>Result:<br>";
		echo "<pre>";print_r($defgw_result); echo "</pre>";
		if($defgw_info != null) {
			echo "<pre>";print_r($defgw_info); echo "</pre>";		
		}
		$_SESSION['default_gw_id'] = 0; // not valid value
	}
}


echo "Default GW: " . $_SESSION['default_gw_id'];




$sql =  "SELECT * FROM devices " . 
		"WHERE devices.owner <> 'Infrastructure' AND devices.owner <> 'Multimedia' " .
		"ORDER BY devices.owner ASC ";
if(isset($_SESSION['si']) && ($_SESSION['si'] == true)) {
	$sql =  "SELECT * FROM devices " . 
			"ORDER BY devices.owner ASC ";
}

		
$result = $conn->query($sql);
if ( $result === FALSE) {
	output_debug( "Error selecting table devices: " . $conn->error);
	$conn->close();
	exit;
}

//echo "<br>Rows: " . $result->num_rows . "<br>";

if ($result->num_rows > 0) {
	if(!isset($_GET["date"])) {
		// $query_date = date('Y-m-d',strtotime("-1 days"));  // yesterday
		$query_date = date('Y-m-d');  // today
	} else {
		$query_date = $_GET["date"];
	}
	

	
	echo "<h2>Select device</h2>";
	echo '<table border="1"><tr><th>ID.</th><th>tasks</th><th>device name</th><th>owner</th>'.
	     '<th>IP</th><th>first scanned</th><th>recent scan</th><th>mac address</th>'.
		 '<th>Default GW</th><th>hostname</th><th>vendor</th></tr>';



		 
	// run through all devices that are not infrastructure or multimedia
	while($row = $result->fetch_assoc()) {
		$is_online = false;
		// ask DB for the last time the device was scanned & its last IP address
		$sql = "SELECT created, ipv4 FROM scans WHERE device_id=" . $row["id"]  . " order by created desc LIMIT 1";
		$last_scan = $conn->query($sql);
		if ( $last_scan === FALSE) {
			output_debug( "Error selecting table devices: " . $conn->error);
			$conn->close();
			exit;
		}
		$row_last_scan = $last_scan->fetch_assoc();
		//echo "<pre>";print_r($row_last_scan); echo "</pre>";
		
		if($row_last_scan['ipv4'] != 0) {
			$ip = long2ip($row_last_scan['ipv4']);
		} else {
			$ip = "";
		}
		


		$last_scan = $row_last_scan['created'];
		//echo $last_scan . '<br>';
		if(strlen($last_scan) > 0) {
			//
			// Format last_scanned
			// last scan of the device older than one our?
			$dateFromDatabase = new DateTime($last_scan);
			$now   = new DateTime;
			
			$difference = $now->diff($dateFromDatabase);
			// $last_scanned = $difference->format('%a days, %h hours, %i minutes');
			if($difference->d > 0) {
				$last_scanned = $difference->format('%ad%hh%Im');
			} else {
				if($difference->h > 0) {
					$last_scanned = $difference->format('%hh%Im');
				} else {
					$last_scanned = $difference->format('%Im%Ss');
				}
			}
			
			//
			// calculate if the device is online or offline
			//
			$now->sub(new DateInterval('PT'.$online_offline_threshold.'S'));
			if($dateFromDatabase > $now) {
				$is_online = true;
			} else {
				$ip="";				// ip address is currently not valid --> set it to empty
				$is_online = false;
			}			
		} else {
			$last_scanned="never";
		}
		
		
		


		//
		// Update defaultgw_id information in the devices table
		//
		// device must be online to set the default GW
		if(($is_online == true) & ($row["defaultgw_id"] == 0)) {
		
			if(isset($_SESSION['default_gw_id'])) {
				$sql_update = 	'UPDATE arp_scan.devices ' . 
								'SET  defaultgw_id =  ' . $_SESSION['default_gw_id'] . ' ' .
								'WHERE devices.id = ' . $row["id"];
				
				if ($conn->query($sql_update) === FALSE) {
					output_debug( "Error update defaultgw_id of device $device_id in table devices: " . $conn->error);
					$conn->close();
					exit;
				}
			}
		}
		
		
//		Show Offline		Show Infrastructure 		Show Local Only		
//							in the select statement
//		
		
		$show_entry = true;


		if(($_SESSION['slo'] == true) && ($row['defaultgw_id'] != $_SESSION['default_gw_id'])) {
			$show_entry = true; // should be false but local default GW still has some errors.
		}


		// Show Offline ... show all online and offline devices
		if(($_SESSION['so'] == false) && ($is_online == false))	$show_entry = false;
		
		
		
		
		if($show_entry) {		
			if($is_online) 	echo '<tr bgcolor="#99FF66">';
			else			echo '<tr>';
			echo '<td>' . $row["id"] . '</td>';
			
			echo '<td><a href="edit_device.php?device_id=' . $row['id']  . '">edit</a>'.
			' | <a href="http://' . $ip . '/" target="_blank">http</a>'.
			' | attendance <a href="device.php?device_id=' . $row['id']  . '&date=' . $query_date. '">1</a>' . ' <a href="signinsignout.php?device_id=' . $row['id']  . '&date=' . $query_date. '">2</a>'.
			'</td>';
			echo '<td>'.$row["name"]. '</td>';
			echo '<td>' . $row["owner"]. '</td>';
			
			
			
			// echo '<td><a href="../owasp?ip=' . $ip  . '" target="_blank">' . $ip. '</a></td>';
			echo '<td>' . $ip. '</td>';
			
			
			echo '<td>' . $row['created']. '</td>';
			
			echo '<td>' . $last_scanned . '</td>';
			
			echo '<td>' . $row['mac'] . '</td>';
			echo '<td>' . $row['defaultgw_id'] . '</td>';
			
			echo '<td>' . $row['hostname'] . '</td>';
			echo '<td>' . $row['vendor'] . '</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
} else {
	echo "0 devices found";
}




$conn->close();
?>

<br>
<br>
<h2>Todo</h2>
The following things would make sense:
<ul>
<li>What todo if arp_scan_log.txt is not empty</li>
<li>less info per device. + link to show more info (mainly for discovery of the owner)</li>
<li>icons for edit, more info, port scanner</li>

<li>If device was not scanned on a certain day: tell when this device was scanned the last time before this day ... or maybe NEVER ...</li>
<li>arp-scan may send all data on a hourly basis to a central server. On this central server all data is stored in the DB and all webpage and visibility aspects can be programmed there ...</li>
<li>etc</li>
<li><a href="http://www.macvendors.com/" target="_blank">API scanner</a></li>




</ul>

</body>
</html>


