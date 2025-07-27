<?php
// Admin paneli için yardımcı fonksiyonlar

/**
 * Admin panelinde gösterilecek uyarı mesajları için
 * 
 * @param string $type Uyarı tipi ('success', 'error', 'warning', 'info')
 * @param string $message Gösterilecek mesaj
 * @return void
 */
function addAlert($type, $message) {
    if (!isset($_SESSION['admin_alerts'])) {
        $_SESSION['admin_alerts'] = [];
    }
    
    $_SESSION['admin_alerts'][] = [
        'type' => $type, 
        'message' => $message
    ];
}

/**
 * Admin panelindeki uyarı mesajlarını gösterir ve temizler
 * 
 * @return string HTML çıktısı
 */
function showAlerts() {
    $output = '';
    
    if (isset($_SESSION['admin_alerts']) && !empty($_SESSION['admin_alerts'])) {
        foreach ($_SESSION['admin_alerts'] as $alert) {
            $alertClass = '';
            $iconClass = '';
            
            switch ($alert['type']) {
                case 'success':
                    $alertClass = 'bg-green-100 border-green-400 text-green-700';
                    $iconClass = 'fa-check-circle text-green-500';
                    break;
                case 'error':
                    $alertClass = 'bg-red-100 border-red-400 text-red-700';
                    $iconClass = 'fa-times-circle text-red-500';
                    break;
                case 'warning':
                    $alertClass = 'bg-yellow-100 border-yellow-400 text-yellow-700';
                    $iconClass = 'fa-exclamation-circle text-yellow-500';
                    break;
                case 'info':
                default:
                    $alertClass = 'bg-blue-100 border-blue-400 text-blue-700';
                    $iconClass = 'fa-info-circle text-blue-500';
                    break;
            }
            
            $output .= '<div class="mb-4 border px-4 py-3 rounded relative ' . $alertClass . '" role="alert">';
            $output .= '<div class="flex items-center">';
            $output .= '<i class="fas ' . $iconClass . ' mr-2"></i>';
            $output .= '<span>' . htmlspecialchars($alert['message']) . '</span>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Uyarıları temizle
        $_SESSION['admin_alerts'] = [];
    }
    
    return $output;
}
