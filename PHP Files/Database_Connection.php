<?php

$dbhost = "localhost";
$dbuser = "you_will_never_know";
$dbpass = "password";
$dbname = "you_will_never_know";

if(!$conn = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{
	die("failed to connect!");
}