<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$isLoggedIn = isset($_SESSION["username"]);
$isAdmin    = $isLoggedIn && ($_SESSION["role"] ?? "member") === "admin";
$username = $isLoggedIn ? $_SESSION['username'] : '';

$initials = '';
if ($isLoggedIn) {
    $nameParts = explode(' ', $username);
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
include 'Landing_page.html';
?>