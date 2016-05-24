<?php 
/* netstat.conf.php example */


$title = "Our network status";
$headline = $title;

$checks = array(
    '10.10.60.83|ping| HP Printer',
    'www.wermescher.com  | 80 | wermescher.com (port 80)',
    'server1.pst.at         | 22 | server1 SSH (port 22)',
    'erp.pst.at|443| erp',
    '   Other checks   | headline',
    'www.example.com   | 80 | WWW server example.com' // no colon here!
)
// $ping_command = '/usr/bin/ping -l3 -c3 -w1 -q';


?>
