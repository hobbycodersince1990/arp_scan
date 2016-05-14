<?PHP


/*
Install CURL to request mac - vendor from international database
sudo apt-get install php5-curl

Start manually:
sudo /usr/bin/php -f /wa/arp_scan.php

mysql -u root -p
After login create the database (please change the password before you paste):

CREATE USER arp_scan@localhost IDENTIFIED BY "oluhznkjhIh78ghJZgjhfHdtriuzJgvht";
create database arp_scan;
GRANT ALL ON arp_scan.* TO arp_scan@localhost;
FLUSH PRIVILEGES;
exit
*/


$dont_insert_infrastructure_scans = true;	// does not yet work
$dont_insert_multimedia_scans = true;		// does not yet work




/*
output_debug_file("---------");
output_debug_file(php_uname() );
output_debug_file( PHP_OS);
output_debug_file("---------");


/* Some possible outputs:
Linux mozart 3.16.0-24-generic #32-Ubuntu SMP Tue Oct 28 13:07:32 UTC 2014 x86_64
Linux

Windows NT XN1 5.1 build 2600
WINNT
*/


function isWindows() {
	// WIN32
	// WINNT
	// Windows
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// output_debug_file('This is a server running Windows!');
		return true;
	} else {
		return false;
	}
}
function isUbuntu() {

	if (strpos(strtoupper(php_uname()), "UBUNTU") !== false) {
		// output_debug_file('This is a server running Ubuntu Linux!');
		return true;
	} else {
		return false;
	}
}





/*


Edit crontab

nano /etc/cron.d/arp-scan

and paste the following:
* * * * * root /usr/bin/php -f /wa/arp_scan.php >> /wa/01_log_arp_scan.php.log 2>&1



DEBUG:
Check when cronjob ran the last time:
cat /var/log/syslog



phpMyAdmin:
SELECT * FROM scans,devices WHERE scans.device_id=devices.id

Show all scans for one certain device:
SELECT scans.id, scans.created, devices.name, devices.owner 
FROM scans,devices 
WHERE scans.device_id=devices.id AND scans.device_id = 5
ORDER BY  scans.id DESC 
LIMIT 0,1000



Show all scans for all devices with a certain owner:
----------------------------------------------------
SELECT scans.id, scans.created, devices.name, devices.owner 
FROM scans,devices 
WHERE scans.device_id=devices.id AND devices.owner = 'Multimedia'
ORDER BY  scans.id DESC 
LIMIT 0,1000


DELETE ALL INFRASTRUCTURE and MULTIMEDIA SCANS:
-----------------------------------------------
DELETE FROM arp_scan.scans 
WHERE scans.device_id IN (
SELECT id FROM devices
WHERE devices.owner = 'Infrastructure' OR devices.owner = 'Multimedia'
)



Optimize database:
get stringlenght of the longest entry in a field (e.g. vendor or name) of a table (e.g. devices)
SELECT MAX(LENGTH(vendor)) FROM devices
SELECT MAX(LENGTH(name)) FROM devices



See more: http://www.coffer.com/mac_find/
Chromecast: 6c:ad:f8
LG: 		c0:41:f6
LG:     	10:68:3f
Netgear: 	28:c6:8e

*/



function output_debug($str) {
	echo date('Y-m-d H:i:s') . ": $str\r\n";
}
function output_debug_file($str) {
	$filename = dirname(__FILE__) . "/01_log_" . basename(__FILE__) . '.log'; 
	
	file_put_contents($filename, date('Y-m-d H:i:s') . ": $str\r\n", FILE_APPEND | LOCK_EX);
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




class ArpEntry {
	public $ip = null;
    public $mac = null;
	public $type = null;
	public $if_addr = null;
	public $name = null;
    
    public function __construct($ip, $mac, $type, $if_addr, $name) {
        $this->ip = $ip;
        $this->mac = strtolower($mac);
        $this->type = $type;
        $this->if_addr = $if_addr;
    }
}

function isIPAddress($input) {
	$input = trim($input, "()");
	return filter_var($input, FILTER_VALIDATE_IP);
}
function isMACAddress($input) {
	// Five times the delimiter ('-' in Windows and ':' in Linux)
	return ((substr_count($input, '-') == 5) || (substr_count($input, ':') == 5));
}
function isType($input) {
	// windows: dynamic or static
	return ((strcmp($input, 'dynamic') == 0) || (strcmp($input, 'static') == 0));
}






// linux: wlan or eth0 (no --interface --> first configured but not lo)
// sudo arp-scan --interface=wlan0 --localnet


exec('arp-scan -l -r 3', $output);
if(count($output) < 1) {
	output_debug("scan failed.");
    exit;
}
// output_debug("--- OUTPUT START ---------------------------------------------------------------------");
// print_r($output);
// output_debug("--- OUTPUT END ---------------------------------------------------------------------");



////////////////////////////////////////////////////
//
// Scan all arp-entries into the array: $ArpArray[] 
//

$if_addr = "";
foreach($output as $ov) {
	// output_debug( "Current Line: " . $ov );

	// scan the interface
	// in Windows: Interface: 192.168.70.15 --- 0x2
	// in Linux: Interface: eth0, datalink type: EN10MB (Ethernet)
	
	
	if(strpos($ov, 'Starting arp-scan') === 0) {
		// second line in linux
		// Interface: eth0, datalink type: EN10MB (Ethernet)
		// Starting arp-scan 1.8.1 with 256 hosts (http://www.nta-monitor.com/tools/arp-scan/)

	} else {
		if(strpos($ov, 'Interface: ') === 0) {
			// First line in  Linux
			$ov = str_replace(',', ' ', $ov);
			$entries=explode(" ", $ov);
			$if_addr = $entries[1];

			// output_debug("------------------------------------------------------------------------");
			// print_r($entries);
			// output_debug("------------------------------------------------------------------------");
		} else {
			// scan a new arp entry
			
			
			// $entries=explode(" ", $ov);   // windows
			$entries=explode("\t", $ov);	// linux
			$ip = null;
			$mac = null;
			$type = null;
			$name = "";

			// output_debug("--- ARP ENTRY START ---------------------------------------------------------------------");
			// print_r($entries);
			// output_debug("--- ARP ENTRY END ---------------------------------------------------------------------");
			
			
			foreach($entries as $entry) {
				if(strlen($entry) != 0) {
					if (isIPAddress($entry)) {
						$ip = trim($entry, "()");
						// output_debug( "IP:  $ip");
					} else {
						if (isMACAddress($entry)) {
							$mac = $entry;
							// output_debug("MAC: $mac");
						} else {
							if(isType($entry)) {
								$type = $entry;
								// output_debug("Type: $type");
							} else {
								$name = $entry;
								// output_debug("Name: $entry");
							}
						}
					}
				}
			}
			if(strlen($mac) > 0) {
				$ArpArray[] = new ArpEntry($ip, $mac, $type, $if_addr, $name);
			}
		}
	}
	// output_debug("");
}

// output_debug("--------------------------------------------------------------------------------------------");
// print_r($ArpArray);
// output_debug("--------------------------------------------------------------------------------------------");






$servername = "localhost";
$database = "arp_scan";
$username = "arp_scan";
$password = "oluhznkjhIh78ghJZgjhfHdtriuzJgvht";

// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    output_debug("Connection failed: " . $conn->connect_error);
	exit;
} 

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === FALSE) {
    output_debug( "Error creating database: " . $conn->error);
	$conn->close();
	exit;
}
if ($conn->select_db($database) === FALSE) {
    output_debug( "Error selecting database $database: " . $conn->error);
	$conn->close();
	exit;
}
$sql = "CREATE TABLE IF NOT EXISTS devices ( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "name VARCHAR(100) NOT NULL, ".
	   "owner VARCHAR( 50 ) NOT NULL, ".
       "mac VARCHAR(18) NOT NULL, ".					// 00:09:34:1c:01:62
       "hostname VARCHAR(100) NOT NULL, ".
	   "vendor VARCHAR( 255 ) NOT NULL, ".
	   "defaultgw_id INT, ".
       "created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ".
       "PRIMARY KEY ( id )); ";
	   
if ($conn->query($sql) === FALSE) {
    output_debug( "Error creating table devices: " . $conn->error);
	$conn->close();
	exit;
}
$sql = "CREATE TABLE IF NOT EXISTS scans ( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "device_id INT NOT NULL, ".
	   "ipv4 INT UNSIGNED NOT NULL, " .				// only 4 bytes instead of VARCHAR(15)
       "created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ".
       "PRIMARY KEY ( id )); ";
if ($conn->query($sql) === FALSE) {
    output_debug( "Error creating table scans: " . $conn->error);
	$conn->close();
	exit;
}





// how to insert and select an IP address:
// 		INSERT INTO `table` (`ipv4`) VALUES (INET_ATON("127.0.0.1"));
// 		Use result of SELECT with the function "long2ip(ipv4_from_database)"



//
//	Only when the arp-scan was successful
//
if(isset($ArpArray) > 0) {
	//
	//	Insert all not yet existing mac addresses into the database
	//
	foreach($ArpArray as $ArpEntry) {
		// output_debug("$ArpEntry->mac $ArpEntry->ip $ArpEntry->name ");
		// output_debug("-------------------------------------------");
		
		//
		// Insert all devices (where not already exists) into the devices table
		//
		$sql = 	"INSERT INTO devices (mac, name) " .
				"SELECT * FROM (SELECT '$ArpEntry->mac', '$ArpEntry->mac ') AS tmp " .
				"WHERE NOT EXISTS ( " .
				"    SELECT mac FROM devices WHERE mac = '$ArpEntry->mac' " .
				") LIMIT 1;";
				
		if ($conn->query($sql) === FALSE) {
			output_debug( "Error insert $ArpEntry->mac into devices: " . $conn->error);
			$conn->close();
			exit;
		}
	}

	//
	//	Insert all scans into the scans table
	//
	foreach($ArpArray as $ArpEntry) {
		$sql = 	"SELECT id,owner FROM devices WHERE devices.mac = '$ArpEntry->mac' LIMIT 1";

		$result = $conn->query($sql);
		
		if($result->num_rows != 1) {
			output_debug("Error fetching device id");
			print_r($result);
			$conn->close();
			exit;
		}
		$row = $result->fetch_assoc();
		
		$device_id = $row['id'];
		$owner = $row['owner'];

		$sql = 	"INSERT INTO scans (device_id, ipv4) " .
				"VALUES ('$device_id', INET_ATON('$ArpEntry->ip'))";
		if ($conn->query($sql) === FALSE) {
			output_debug( "Error insert scan $ArpEntry->mac into devices: " . $conn->error);
			$conn->close();
			exit;
		}	
	}
} else {
	output_debug( "Error incorrect arp-scan");
	print_r ($output);
}




//
// remove all scans where the device owner is "Infrastructure" or "Multimedia"
//
//  dont do this. otherwise we cannot determine the id of the default router (it is infrastructure!!!
//

/*
$sql = 	"DELETE FROM arp_scan.scans " .
		"WHERE scans.device_id IN ( " .
		"SELECT id FROM devices " .
		"WHERE devices.owner = 'Infrastructure'" .  //  OR devices.owner = 'Multimedia' 
		" )";
if ($conn->query($sql) === FALSE) {
	output_debug( "Error delete Infrastructure and Multimedia scans: " . $conn->error);
	$conn->close();
	exit;
}
*/


$conn->close();
?>
