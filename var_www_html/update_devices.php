<?PHP
session_start();

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
?>

<?PHP	

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





$sql =  "SELECT * FROM devices where devices.vendor IS NULL OR devices.vendor = '' ORDER BY devices.owner ASC ";
	
$result = $conn->query($sql);
if ( $result === FALSE) {
	output_debug( "Error selecting table devices: " . $conn->error);
	$conn->close();
	exit;
}

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		
		//
		// get the IP address of the device to retrieve the hostname
		//
		$sql = "SELECT created, ipv4 FROM scans WHERE device_id=" . $row["id"]  . " order by created desc LIMIT 1";
		$last_scan = $conn->query($sql);
		if ( $last_scan === FALSE) {
			output_debug( "Error selecting table devices: " . $conn->error);
			$conn->close();
			exit;
		}
		$row_last_scan = $last_scan->fetch_assoc();
		if($row_last_scan['ipv4'] != 0) {
			$ip = long2ip($row_last_scan['ipv4']);
		} else {
			$ip = "";
		}
		
		
		
		
		
		
		
		//
		// Update vendor information in the devices table
		//
		if(strlen($row["vendor"]) == 0) {
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
			if(strlen($ip) > 0) {
				$hostname = gethostbyaddr ( $ip );
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
		
		
	}
}

function get_vendor($mac_address) {
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





$hostname = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
header('Location: http://'.$hostname.'/index.php');
?>
