<?php
$dbhost = 'localhost';
$dbuser = 'dima';
$dbpass = 'Dukartdu8';
$dbname = 'dima_erep';
$conn = mysqli_connect($dbhost,$dbuser,$dbpass)
			or die ('Error connecting to mysql');
mysqli_select_db($conn, $dbname);