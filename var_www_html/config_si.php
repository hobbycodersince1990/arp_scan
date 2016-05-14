<?PHP
session_start();


// $_SESSION['si']=true / false		... show infrastructure
$_SESSION['si']=!$_SESSION['si'];
if($_SESSION['si'])
	echo "Show Infrastructure is now enabled<br>";
else
	echo "Show Infrastructure is now disabled<br>";
	

	
	
$hostname = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
?>