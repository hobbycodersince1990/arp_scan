<?PHP



function ping($host)
{
	exec(sprintf('/bin/ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
	return $rval === 0;
}
function get_nw_interfaces() {
	exec('ls /sys/class/net', $interfaces);
	// echo "<pre>"; 	print_r($interfaces);	echo "</pre>";
	return $interfaces;
}


class InterfaceEntry {
	
    public $interface = null;	// lo, wlan0, eth0
    public $mac = null;			// HWaddr
	public $ip = null;		// inet addr
    
    public function __construct($interface, $mac, $ip) {
        $this->interface = $interface;
        $this->mac = strtolower($mac);
        $this->ip = $ip;
    }
}

function get_interface($interface_name)
{
    $mac = null;
	$ip = null;
	
    exec("/sbin/ifconfig $interface_name",$output);
	// echo "<pre>"; 	print_r($output);	echo "</pre>";
	$output = implode(",", $output);
	// echo "-------------------------------------------------------<br>" . $output . "<br><br>";
	// echo "Interface: " . $interface_name . "<br>";
	
	if(strpos($output,'HWaddr ') === false) {
		$mac = ""; // no mac address like for interface lo
	} else {
		$mac = trim(substr($output, strpos($output,'HWaddr ')+7, 17));
	}
	// echo "MAC: '" . $mac . "'<br>";
	
	if(strpos($output,'inet addr:') === false) {
		$ip = "";	// no ip address
	} else {
		$tmp_ip = trim(substr($output, strpos($output,'inet addr:')+10, 15));	// min len is 15 but may be shorter!
		$ip = substr($tmp_ip, 0, strpos($tmp_ip, ' '));
	}
	// echo "IP: '" . $ip . "'<br>";
	
	$ie = new InterfaceEntry($interface_name, $mac, $ip);
	
	return $ie;
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



function getIp($interface) { 
	exec('/sbin/ifconfig', $resultArray); 
	// echo "<pre>" . print_r($resultArray) . "</pre>"; 
	$result = implode(",", $resultArray);
	echo "<pre>" . $result . "</pre>"; 
	
	$ip = preg_match("/$interface.*?inet\saddr:([\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})\s/", $result, $matches);
	
	return isset($matches[1])?$matches[1]:false; 
}


function hexdump ($data, $htmloutput = true, $uppercase = false, $return = false)  {
    // Init
    $hexi   = '';
    $ascii  = '';
    $dump   = ($htmloutput === true) ? '<pre>' : '';
    $offset = 0;
    $len    = strlen($data);
  
    // Upper or lower case hexadecimal
    $x = ($uppercase === false) ? 'x' : 'X';
  
    // Iterate string
    for ($i = $j = 0; $i < $len; $i++)
    {
        // Convert to hexidecimal
        $hexi .= sprintf("%02$x ", ord($data[$i]));
  
        // Replace non-viewable bytes with '.'
        if (ord($data[$i]) >= 32) {
            $ascii .= ($htmloutput === true) ?
                            htmlentities($data[$i]) :
                            $data[$i];
        } else {
            $ascii .= '.';
        }
  
        // Add extra column spacing
        if ($j === 7) {
            $hexi  .= ' ';
            $ascii .= ' ';
        }
  
        // Add row
        if (++$j === 16 || $i === $len - 1) {
            // Join the hexi / ascii output
            $dump .= sprintf("%04$x  %-49s  %s", $offset, $hexi, $ascii);
             
            // Reset vars
            $hexi   = $ascii = '';
            $offset += 16;
            $j      = 0;
             
            // Add newline            
            if ($i !== $len - 1) {
                $dump .= "\n";
            }
        }
    }
  
    // Finish dump
    $dump .= $htmloutput === true ?
                '</pre>' :
                '';
    $dump .= "\n";
  
    // Output method
    if ($return === false) {
        echo $dump;
    } else {
        return $dump;
    }
}


$interfaces = get_nw_interfaces();
foreach($interfaces as $interface)  {
	$i = get_interface($interface);
	echo "IP for <strong>$i->interface</strong> ($i->mac): $i->ip<br>";
}


echo "<h1>-get_default_gw- " . get_default_gw() . " -</h1>";


 
 
 
 if(ping('8.8.8.8')) {
	echo "ping ok<br>";
} else {
	echo "ping nok<br>";
}
 
 



/*

RUN NMAP


exec("/sbin/ifconfig", $netinfo); 
$netregex='/inet addr:(\d+\.\d+\.\d+\.\d+)/';
print "$netinfo[1] <br><br><br>";
preg_match($netregex, $netinfo[1], $matches);
$ips=$matches[1];
$ips=preg_replace('/(\d+\.\d+\.\d+\.)\d+/', '${1}*', $ips);
print "$ips <br><br><br>";
$nmapresults=system("nmap -sT $ips");
print "$nmapresults";
*/

?>