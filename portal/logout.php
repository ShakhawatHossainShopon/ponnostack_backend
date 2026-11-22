<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy server session
unset($_SESSION['customer_uuid'], $_SESSION['tenant_uuid'], $_SESSION['tenant_auth_token'], $_SESSION['tenant_email'], $_SESSION['tenant_name']);
session_destroy();

// Clear persistent cookies
setcookie('ponnostack_tenant_uuid', '', time() - 3600, '/');
setcookie('ponnostack_tenant_token', '', time() - 3600, '/');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLive = (strpos($host, 'ponnostack.shop') !== false);
$loginUrl = $isLive ? 'https://ponnostack.shop/portal/login.php' : ($protocol . $host . '/Ponnostack/portal/login.php');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
</head>
<body style="background-color: #F8FAFC; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div style="text-align: center; color: #64748B;">
        <p style="font-weight: bold; font-size: 14px;">লগআউট হচ্ছে...</p>
    </div>
    <script>
        try {
            localStorage.removeItem('ponnostack_auth_token');
            localStorage.removeItem('ponnostack_tenant');
        } catch (e) {}
        window.location.href = <?= json_encode($loginUrl) ?>;
    </script>
</body>
</html>
