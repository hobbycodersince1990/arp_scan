<?PHP
include "inc.header.php";
?>
<pre>
<?PHP

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
