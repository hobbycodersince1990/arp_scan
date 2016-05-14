<?PHP
session_start();
if($_SESSION['angemeldet'] != true) {
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	header('Location: http://'.$hostname.'/index.php');
}
// $_SESSION['si']=true / false		... show infrastructure
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<title>Logfile</title>
	</head>
	<body>
	<pre>
<?PHP


echo "--- log_reboot_on_notconnect ------------------------------------\r\n";
$file = "/wa/01_log_reboot_on_notconnect.php.log";
$handle = fopen($file, "r");
while ($line = fgets($handle, 1000)) {
	echo $line;
}
fclose($handle);

echo "\r\n\r\n--- log_arp_scan_log ------------------------------------\r\n";

$file = "/wa/01_log_arp_scan.php.log";
$handle = fopen($file, "r");
while ($line = fgets($handle, 1000)) {
	echo $line;
}
fclose($handle);
  
  
  
  
?>
	</pre>
	</body>
</html>
