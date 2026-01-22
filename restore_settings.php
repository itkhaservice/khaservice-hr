<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$settings = [
    'company_name' => 'CÔNG TY TNHH KHASERVICE',
    'company_address' => 'Tầng 1, Khu Thương Mại, 360C Bến Vân Đồn, Phường 1, Quận 4, TP.HCM',
    'admin_email' => 'admin@khaservice.vn',
    'company_phone' => '02838253041',
    'company_website' => 'https://khaservice.com.vn/'
];

foreach ($settings as $key => $val) {
    db_query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $val, $val]);
}

echo "Settings restored successfully.\n";
?>