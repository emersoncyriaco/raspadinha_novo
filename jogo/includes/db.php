<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

$host = 'localhost';
$db = 'u228447457_raspa3';
$user = 'u228447457_raspa3';
$pass = 'RTN5sa^2';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>

