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
if($_SESSION['si'])  $config_si='on';
else				 $config_si='off';
if($_SESSION['so'])  $config_so='on';
else				 $config_so='off';
if($_SESSION['slo']) $config_slo='on';
else				 $config_slo='off';


error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
?>

<!DOCTYPE HTML>
<html>
    <head>
		<title>Device</title>
    </head>
    <body>
<?PHP	
// url with query parameter for the refresh function
$myurl = strlen($_SERVER['QUERY_STRING']) ? basename($_SERVER['PHP_SELF'])."?".$_SERVER['QUERY_STRING'] : basename($_SERVER['PHP_SELF']);
echo 
	 '<a href="index.php">home</a> | ' . 
	 '<a href="'.$myurl.'">refresh</a> | ' . 
	 'Infrastructure: <a href="config_si.php">'.$config_si.'</a> | ' .
	 'Offline: <a href="config_so.php">'.$config_so.'</a> | ' .
	 'Only Local: <a href="config_slo.php">'.$config_slo.'</a> | ' .
	 
	 'Read <a href="readlogfile.php">logfile</a> | ' .
	 '<a href="update_devices.php">autofill device data</a> | ' .
	 
	 
	'<br>';	
?>
