


<?php





// JSON website status: https://gist.github.com/k0nsl/733955a3c3093832de49
// https://fam.tuwien.ac.at/~schamane/_/netstat_php










function pingICMP($host)
{
	// LINUX!!!
	// -c 1 ... ping only once (count = 1)
	// -W 5 ... timeout = 5
	//
	exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
	return $rval === 0;
}


function pingHTTP($host) {
	return pingPort($host, 80);
}


function pingPort($host,$port=80,$timeout=6)
{
	$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
	if ( ! $fsock )	{
		return FALSE;
	} else {
		return TRUE;
	}
}



// USER MUST BE ROOT!!
function ping($host, $timeout = 1) {
    // ICMP ping packet with a pre-calculated checksum
    $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    $socket  = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
    socket_connect($socket, $host, null);
    $ts = microtime(true);
    socket_send($socket, $package, strLen($package), 0);
    if (socket_read($socket, 255)) {
        $result = microtime(true) - $ts;
    } else {
        $result = false;
    }
    socket_close($socket);
    return $result;
}




//check if the host is up $host can also be an ip address 


		

		
		
$host = 'www.wermescher.com';
$up = pingICMP($host);

if ($up) {
    echo 'Your site is up!';
} else {
    echo 'Your site is down!';
}


















// TEST DIFFERENT PORTS

$wait = 1; // wait Timeout In Seconds
$host = '10.10.60.60';
$ports = [
    'http'  => 80,
    'https' => 443,
    'ftp'   => 21,
];

foreach ($ports as $key => $port) {
    $fp = @fsockopen($host, $port, $errCode, $errStr, $wait);
    echo "Ping $host:$port ($key) ==> ";
    if ($fp) {
        echo 'SUCCESS';
        fclose($fp);
    } else {
        echo "ERROR: $errCode - $errStr";
    }
    echo PHP_EOL;
}


/*
for ($port = 1; $port <= 1000; $port++) {
    $fp = @fsockopen($host, $port, $errCode, $errStr, $wait);
    echo "Ping $host:$port  ==> ";
    if ($fp) {
        echo 'SUCCESS';
        fclose($fp);
    } else {
        echo "ERROR: $errCode - $errStr";
    }
    echo PHP_EOL;
	flush();
}
*/



?>






