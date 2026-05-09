<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
// Get initials
$initials = '';
if ($isLoggedIn) {
    $nameParts = explode(' ', $username);
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
include 'Landing_page.html';
?>