<?php
/**
 * Hızlı çözüm - getClientIP fonksiyonunu değiştirmeden önce, burada direkt tanımlıyoruz.
 * Bu sayede login.php ve register.php'deki hatalar giderilecek.
 */

if (!function_exists('alternativeGetClientIP')) {
    function alternativeGetClientIP() {
        // CloudFlare kullanılıyorsa
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        // Proxy kullanılıyorsa
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        // Normal durum
        elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0'; // Varsayılan değer
    }
}
