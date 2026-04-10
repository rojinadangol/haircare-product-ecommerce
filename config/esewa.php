<?php
// config/esewa.php
// 🔑 Get keys from: https://admin.khalti.com/ (Test/Live)
define('ESEEWA_MERCHANT_CODE', 'EPAYTEST'); // Replace with LIVE key
define('ESEEWA_SECRET_KEY',    '8gBm/:&EnhH.1/q'); // Replace with LIVE key

// 🔗 API Endpoints
define('ESEEWA_API_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'); // Sandbox

// 🔄 Callback URLs (MUST match your XAMPP port exactly)
define('ESEEWA_SUCCESS_URL', 'http://localhost/haircare/user/esewa_success.php');
define('ESEEWA_FAILURE_URL', 'http://localhost/haircare/user/esewa_failure.php');
?>