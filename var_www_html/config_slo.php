<?PHP
session_start();
 

// Show Local Only (show only devices with the local default gw (plus devices that are online independent of local GW)
$_SESSION['slo']=!$_SESSION['slo'];
if($_SESSION['slo'])
	echo "Show Local Only is now enabled<br>";
else
	echo "Show Local Only is now disabled<br>";
	

	
	
$hostname = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
?>