<?php  
$dbserver = "localhost";  // Your database server
#$dbserver = "mysql310.phy.lolipop.lan";  // Uncomment this if using a remote server
$dbname = "salarytracker";  // Your database name
$dbuser = "LAA1619181";  // Your database username
$dbpasswd = "dbpasswd";  // Your database password

$opt = [  
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  
    PDO::ATTR_EMULATE_PREPARES => false,  
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,  
];  

// Changed $dbh to $pdo to match your existing code
$pdo = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbname, $dbuser, $dbpasswd, $opt);
