<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

try {
    $settings = db_fetch_all("SELECT * FROM settings");
    echo "<pre>";
    print_r($settings);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>