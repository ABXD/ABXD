<?php
error_reporting(~E_NOTICE);

if (file_exists('lib/database.php') || file_exists('../lib/database.php'))
	die("Board is already installed.");

$sqlServ = $_POST['dbhost'];
$sqlUser = $_POST['dbuser'];
$sqlPass = $_POST['dbpass'];
$sqlData = $_POST['dbname'];

$dblink = mysqli_init();
// 2 seconds timeout, will make errors noticed more quickly
$dblink->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
if (!@$dblink->real_connect($sqlServ, $sqlUser, $sqlPass, null))
	echo "Connect error ({$dblink->connect_errno}): {$dblink->connect_error}";
elseif (isset($_POST['create']))
{
	if(!preg_match("/[A-Za-z][A-Za-z0-9_]*/", $sqlData))
		die("Invalid database name entered.");
	if ($dblink->query("CREATE DATABASE $sqlData"))
		echo "Database was created successfully!<!--ABXD-->";
	else
		echo "Failed to create database...";
}
elseif(!preg_match("/[A-Za-z][A-Za-z0-9_]*/", $sqlData))
		die("Invalid database name entered.");
elseif (!$dblink->select_db($sqlData))
	echo "The database was not found. <a href='javascript:create()'>Would you like to create one?</a>";
else
	echo "<!--ABXD-->";
