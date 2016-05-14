<?PHP
session_start();
 

// $_SESSION['so']=true / false		... show infrastructure
$_SESSION['so']=!$_SESSION['so'];
if($_SESSION['so'])
	echo "Show Offline is now enabled<br>";
else
	echo "Show Offline is now disabled<br>";
	

	
	
$hostname = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
?>