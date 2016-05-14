<?PHP
session_start();
if($_SESSION['angemeldet'] != true) {
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	//header('Location: http://'.$hostname.'/index.php');
}
 
// default configuration
if(!isset($_SESSION['si'])) {
	$_SESSION['si']  = false;		// Show Infrastructure components (not only user devices)
	$_SESSION['so']  = false;		// Show Offline components (not only online)
	$_SESSION['slo'] = true;		// show local only components (with the local default GW)
}
?>
<!DOCTYPE HTML>
<html>
    <head>
		<title>phpArpScan</title>
    </head>
    <body>

<?php
// get all scans from the last 10 minutes ...
//		SELECT * FROM scans WHERE DATE_SUB(NOW(),INTERVAL 10 MINUTE) <= created ORDER BY `created` ASC  
//

?>




<?PHP



if($_SESSION['si'])  $config_si='on';
else				 $config_si='off';
if($_SESSION['so'])  $config_so='on';
else				 $config_so='off';
if($_SESSION['slo']) $config_slo='on';
else				 $config_slo='off';


// url with query parameter
$myurl = strlen($_SERVER['QUERY_STRING']) ? basename($_SERVER['PHP_SELF'])."?".$_SERVER['QUERY_STRING'] : basename($_SERVER['PHP_SELF']);
echo 'Hello <strong>' . $_SESSION['user'] . '</strong>. <a href="'.$myurl.'">refresh</a> | ' . 
	 '<a href="../logout.php">log out</a> | '.
	 'Infrastructure: <a href="config_si.php">'.$config_si.'</a> | ' .
	 'Offline: <a href="config_so.php">'.$config_so.'</a> | ' .
	 'Only Local: <a href="config_slo.php">'.$config_slo.'</a><br>';





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

function get_vendor($mac_address) {
	
	
	// echo "<h2>get_vendor($mac_address)</h2>";
	$url = "http://api.macvendors.com/" . urlencode($mac_address);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);


	if($response) {
		return  $response;
	} else {
		return "(Unknown)";
	}
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
		$_SESSION['default_gw_id'] = 62;
	}


}


echo "Default GW: " . $_SESSION['default_gw_id'];


//
// device_id is set. Therefore show all scans from this device
//
if(isset($_GET["device_id"])) {
	$device_id = $_GET["device_id"];

	if(!isset($_GET["date"])) {
		output_debug( "Error missing parameter date");
		exit;
	}
	$query_date = $_GET["date"];
	
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
	// show the scans in a graphical form
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
			// echo $row["created"]. " from " . $row["ip"]. "<br>";
			// echo "<pre>"; print_r($row);echo "</pre>";
			// echo "<pre>"; print_r($date);echo "</pre>";
			// date("Y-m-d H:i:s
			$second_of_the_day = datetimetime_to_sec($row["created"]);
			
			if(($second_of_the_day - $last) > $online_offline_threshold) {
			    $diff_seconds = $second_of_the_day - $last;
				
				if(strlen($last_created) == 0) {
					echo "START at " . substr($row["created"], 11) . '<br>';
				} else {
					// echo "LEFT at " . $last_created . ' - ' . $row["created"] . ' for ' . gmdate("H:i:s", $diff_seconds) . '<br>';
					// echo "LEFT at " . substr($last_created, 11) . ' for ' . gmdate("H:i:s", $diff_seconds) . ' hours. BACK at ' .  substr($row["created"],11) . '<br>';
					
					echo "AWAY FROM " . substr($last_created, 11) . ' UNTIL ' .  substr($row["created"],11) .'. DURATION: ' . gmdate("H:i:s", $diff_seconds) . ' hours.<br>';
					
					
				}
			}
			$last = $second_of_the_day;
			$last_created = $row["created"];
			
			$value  = array($second_of_the_day/3600, 1);
			$values[] = $value;
		}
		$end = gmdate("H:i:s", round($values[count($values)-1][0] * 3600));
		echo "END at $end";
		
		
		require_once("phpChart_Lite/conf.php");

		$l2 = array(array(0, 1.2), array(12, 1.2), array(24,1.2));

		$pc = new C_PhpChartX(array($values, $l2),'info1b');

		$pc->jqplot_show_plugins(true);
		//$pc->set_legend(array('show'=>false));
		//$pc->set_animate(false);

		//$pc->add_series(array('showLabel'=>true));
		//$pc->add_series(array('showLabel'=>true));
		//$pc->add_series(array('showLabel'=>false));
		
		$pc->draw(2000,100);   // 600,300
		
		
	} else {
		echo '<br><font color="red">0 scans found for device ' . "(id: $current_device_id) on the $query_date<br>device name: $current_device_name<br>Owner: $current_device_owner</font><br>";
	}
}







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

echo "<br>Rows: " . $result->num_rows . "<br>";

if ($result->num_rows > 0) {
	if(!isset($_GET["date"])) {
		// $query_date = date('Y-m-d',strtotime("-1 days"));  // yesterday
		$query_date = date('Y-m-d');  // today
	} else {
		$query_date = $_GET["date"];
	}
	
	// Show "calendar" to select date
	// A device MUST be selected
	if(isset($_GET["device_id"])) {
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
		echo '<a href="?device_id=' . $_GET["device_id"]  . '&date=' . $yesterday. '">-1day</a>  ';
		echo '<a href="?device_id=' . $_GET["device_id"]  . '&date=' . $query_date. '">' . $query_date . '</a>  ';
		echo "<b></b>";
		if($show_tomorrow) {
			echo '<a href="?device_id=' . $_GET["device_id"]  . '&date=' . $tomorrow. '">+1day</a>';
		}
		echo "<br>";
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
		// Update vendor information in the devices table
		//
		if(strlen($row["vendor"]) == 0) {
			//echo "updating"; 

			$vendor = get_vendor($row["mac"]);

			
			$sql_update = 	"UPDATE  arp_scan.devices " . 
							"SET  vendor =  '" . $conn->real_escape_string ($vendor) . "' " .
							"WHERE devices.id = " . $row["id"];
			

			if ($conn->query($sql_update) === FALSE) {
				output_debug( "Error update vendor of device $device_id in table devices: " . $conn->error);
				$conn->close();
				exit;
			}
		} else {
			$vendor = $row["vendor"];
		}
		

		
		//
		// Update hostname information in the devices table
		//
		if(strlen($row["hostname"]) == 0) {
			//echo "updating"; 
			if(strlen($ip) > 0) {
				$hostname = gethostbyaddr ( $ip );
				// echo "$hostname = gethostbyaddr ( $ip );<br>";
				$sql_update = 	"UPDATE  arp_scan.devices " . 
								"SET  hostname =  '" . $conn->real_escape_string($hostname) . "' " .
								"WHERE devices.id = " . $row["id"];
				
				if ($conn->query($sql_update) === FALSE) {
					output_debug( "Error update hostname of device $device_id in table devices: " . $conn->error);
					$conn->close();
					exit;
				}
			} else {
				$hostname = "";
			}
		} else {
			$hostname = $row["hostname"];
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
			$show_entry = false;
		}


		// Show Offline ... show all online and offline devices
		if(($_SESSION['so'] == false) && ($is_online == false))	$show_entry = false;
		
		
		
		
		if($show_entry) {		
			if($is_online) 	echo '<tr bgcolor="#99FF66">';
			else			echo '<tr>';
			echo '<td>' . $row["id"] . '</td>';
			echo '<td><a href="edit_device.php?device_id=' . $row['id']  . '">edit</a></td>';
			echo '<td><a href="?device_id=' . $row['id']  . '&date=' . $query_date. '">' . $row["name"]. '</a></td>';
			echo '<td>' . $row["owner"]. '</td>';
			
			
			
			echo '<td><a href="../owasp?ip=' . $ip  . '" target="_blank">' . $ip. '</a></td>';
			
			//echo '<td>' . $ip. '</td>';
			
			
			echo '<td>' . $row['created']. '</td>';
			
			echo '<td>' . $last_scanned . '</td>';
			
			echo '<td>' . $row['mac'] . '</td>';
			echo '<td>' . $row['defaultgw_id'] . '</td>';
			
			echo '<td>' . $hostname . '</td>';
			echo '<td>' . $vendor . '</td>';
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

</ul>

</body>
</html>


