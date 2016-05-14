<html>
<head>
<meta content="en-gb" http-equiv="Content-Language" />
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<title>Install</title>
</head>
<body>
<!-- Title -->
<h1>Create Database</h1>

<form action="<?php $currentFile = $_SERVER["PHP_SELF"];$parts = Explode('/', $currentFile);echo $parts[count($parts) - 1];?>" method="post">
Database and user name: <input name="dbname" value="arp_scan" type="text" />
<br>
DB Pass: <input name="dbpass" value="oluhznkjhIh78ghJZgjhfHdtriuzJgvht" type="text" />
<br>
MySQL Admin Pass: <input name="mysqlrootpass" type="password" />
<input name="Submit" type="submit" value="submit" />
</form>

</body>
</html>



<?php
if(isset($_POST["mysqlrootpass"]) && ($_POST["dbname"])){
	$dbname = $_POST["dbname"];
	$dbpass = $_POST["dbpass"];
	$mysqlRootPass = $_POST["mysqlrootpass"];
} else { 
	exit;
}
$connection=mysqli_connect("localhost","root",$mysqlRootPass);
if (mysqli_connect_errno())   {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit;
}


// Create database
$sql="CREATE DATABASE $dbname";
if (mysqli_query($connection,$sql))
{
	echo "<p>Database <b>$dbname</b> created successfully!</p>";
} else {
	echo "Error creating database: " . mysqli_error($con);
	exit;
}


// Create user
$sql='GRANT usage on *.* to ' . $dbname . '@localhost identified by ' . "'" . "$dbpass" . "'";
if (mysqli_query($connection,$sql)) {
	echo "<p>User <b>$dbname</b> created successfully!</p>";
} else {
	echo "Error creating database user: " . mysqli_error($con);
	exit;
}


// Create user permissions

$sql="GRANT all privileges on $dbname.* to $dbname@localhost";
if (mysqli_query($connection,$sql)) {
	echo "<p>User permissions for database <b>$dbname</b> created successfully!</p>";
} else {
	echo "Error creating database user: " . mysqli_error($con);
	exit;
}

echo "<p>Database Name: $dbname</p>";
echo "<p>Database Username: $dbname</p>";
echo "<p>Database Password: $dbpass</p>";
echo "<p>Database Host: localhost</p>";
?>