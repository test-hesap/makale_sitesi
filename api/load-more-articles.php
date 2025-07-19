<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

try {
    $type = $_GET['type'] ?? '';
    $page = intval($_GET['page'] ?? 1);
    
    if (!in_array($type, ['featured', 'recent', 'popular'])) {
        throw new Exception('Geçersiz makale tipi');
    }
    
    $database = new Database();
    $db = $database->pdo;
    
    // Sayfa başına makale sayısını al
    switch ($type) {
        case 'featured':
            $perPage = getSiteSetting('featured_articles_per_page', 6);
            break;
        case 'recent':
            $perPage = getSiteSetting('recent_articles_per_page', 6);
            break;
        case 'popular':
            $perPage = getSiteSetting('popular_articles_per_page', 6);
            break;
    }
    
    $offset = ($page - 1) * $perPage;
    
    // Sorguyu hazırla
    switch ($type) {
        case 'featured':
            // Öne çıkan makaleler
            $totalQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND is_featured = 1";
            $totalStmt = $db->prepare($totalQuery);
            $totalStmt->execute();
            $total = $totalStmt->fetch()['total'];
            
            if ($total == 0) {
                // Öne çıkan makale yoksa normal makalelerden al
                $totalQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
                $totalStmt = $db->prepare($totalQuery);
                $totalStmt->execute();
                $total = $totalStmt->fetch()['total'];
                
                $query = "SELECT a.*, c.name as category_name, u.username 
                         FROM articles a 
                         LEFT JOIN categories c ON a.category_id = c.id 
                         LEFT JOIN users u ON a.user_id = u.id 
                         WHERE a.status = 'published' 
                         ORDER BY a.published_at DESC, a.created_at DESC 
                         LIMIT $perPage OFFSET $offset";
            } else {
                $query = "SELECT a.*, c.name as category_name, u.username 
                         FROM articles a 
                         LEFT JOIN categories c ON a.category_id = c.id 
                         LEFT JOIN users u ON a.user_id = u.id 
                         WHERE a.status = 'published' AND a.is_featured = 1
                         ORDER BY a.published_at DESC, a.created_at DESC 
                         LIMIT $perPage OFFSET $offset";
            }
            break;
            
        case 'recent':
            // Son eklenen makaleler
            $totalQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
            $totalStmt = $db->prepare($totalQuery);
            $totalStmt->execute();
            $total = $totalStmt->fetch()['total'];
            
            $query = "SELECT a.*, c.name as category_name, u.username 
                     FROM articles a 
                     LEFT JOIN categories c ON a.category_id = c.id 
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE a.status = 'published' 
                     ORDER BY a.created_at DESC 
                     LIMIT $perPage OFFSET $offset";
            break;
            
        case 'popular':
            // Popüler makaleler
            $totalQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
            $totalStmt = $db->prepare($totalQuery);
            $totalStmt->execute();
            $total = $totalStmt->fetch()['total'];
            
            $query = "SELECT a.*, c.name as category_name, u.username 
                     FROM articles a 
                     LEFT JOIN categories c ON a.category_id = c.id 
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE a.status = 'published' 
                     ORDER BY a.views_count DESC, a.created_at DESC 
                     LIMIT $perPage OFFSET $offset";
            break;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    $totalPages = ceil($total / $perPage);
    $hasMore = $page < $totalPages;
    
    // HTML oluştur
    $html = '';
    foreach ($articles as $article) {
        if ($type === 'featured') {
            // Öne çıkan makaleler için 3 sütunlu grid layout
            $html .= '<article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">';
            $html .= '<div class="relative">';
            
            if ($article['featured_image']) {
                $html .= '<img src="' . htmlspecialchars($article['featured_image']) . '" alt="' . htmlspecialchars($article['title']) . '" class="w-full h-48 object-contain object-center">';
            } else {
                $html .= '<div class="w-full h-48 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center"><i class="fas fa-image text-3xl text-gray-400"></i></div>';
            }
            
            $html .= '<span class="absolute top-2 left-2 px-2 py-1 bg-primary-600 text-white text-xs rounded-full">' . htmlspecialchars($article['category_name'] ?? 'Genel') . '</span>';
            $html .= '</div>';
            
            $html .= '<div class="p-6">';
            $html .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">';
            $html .= '<a href="/makale/' . htmlspecialchars($article['slug']) . '" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">' . htmlspecialchars($article['title']) . '</a>';
            $html .= '</h3>';
            
            $excerpt = $article['excerpt'] ? substr($article['excerpt'], 0, 100) . '...' : 'Bu bir örnek makale açıklamasıdır...';
            $html .= '<p class="text-gray-600 dark:text-gray-300 text-sm mb-4">' . htmlspecialchars($excerpt) . '</p>';
            
            $html .= '<div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">';
            $html .= '<div class="flex items-center space-x-3">';
            $html .= '<span><i class="fas fa-user mr-1"></i>' . htmlspecialchars($article['username']) . '</span>';
            $html .= '<span><i class="fas fa-eye mr-1"></i>' . number_format($article['views_count']) . '</span>';
            $html .= '</div>';
            $html .= '<span>' . timeAgo($article['created_at']) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</article>';
        } else {
            // Son eklenen ve popüler makaleler için 2 sütunlu compact layout
            $html .= '<article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">';
            $html .= '<div class="flex">';
            $html .= '<div class="flex-shrink-0">';
            
            if ($article['featured_image']) {
                $html .= '<img src="' . htmlspecialchars($article['featured_image']) . '" alt="' . htmlspecialchars($article['title']) . '" class="w-24 h-24 md:w-32 md:h-24 object-contain object-center">';
            } else {
                $html .= '<div class="w-24 h-24 md:w-32 md:h-24 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>';
            }
            
            $html .= '</div>';
            $html .= '<div class="flex-1 p-4">';
            $html .= '<div class="flex items-center space-x-2 mb-2">';
            $html .= '<span class="px-2 py-1 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs rounded-full">' . htmlspecialchars($article['category_name'] ?? 'Genel') . '</span>';
            $html .= '</div>';
            
            $html .= '<h3 class="text-sm md:text-base font-semibold text-gray-900 dark:text-white mb-2">';
            $html .= '<a href="/makale/' . htmlspecialchars($article['slug']) . '" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">' . htmlspecialchars($article['title']) . '</a>';
            $html .= '</h3>';
            
            $html .= '<div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">';
            if ($type === 'popular') {
                $html .= '<span><i class="fas fa-eye mr-1"></i>' . number_format($article['views_count']) . ' görüntüleme</span>';
            } else {
                $html .= '<span><i class="fas fa-user mr-1"></i>' . htmlspecialchars($article['username']) . '</span>';
            }
            $html .= '<span>' . timeAgo($article['created_at']) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</article>';
        }
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => $hasMore,
        'currentPage' => $page,
        'totalPages' => $totalPages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
