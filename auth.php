<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/google.php';

if (isset($_GET['error'])) {
    die('<p style="color:red">Login dibatalkan: ' . htmlspecialchars($_GET['error']) . ' <a href="index.php">Kembali</a></p>');
}

if (isset($_GET['code'])) {
    $token = exchangeCodeForToken($_GET['code']);
    
    if (isset($token['access_token'])) {
        $_SESSION['access_token']  = $token['access_token'];
        $_SESSION['refresh_token'] = $token['refresh_token'] ?? null;
        $_SESSION['token_expires'] = time() + ($token['expires_in'] ?? 3600);
        
        // Ambil info user
        $userInfo = getUserInfo($token['access_token']);
        $_SESSION['user'] = [
            'name'    => $userInfo['name']  ?? 'User',
            'email'   => $userInfo['email'] ?? '',
            'picture' => $userInfo['picture'] ?? '',
        ];
        
        header('Location: index.php');
        exit;
    }
    
    die('<p>Gagal login. Token tidak diterima. <a href="index.php">Coba lagi</a></p>');
}

header('Location: index.php');
exit;
