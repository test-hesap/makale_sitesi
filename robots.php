<?php
header('Content-Type: text/plain; charset=utf-8');

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";

// Yasaklı dizinler
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /database/\n";
echo "Disallow: /api/\n";
echo "Disallow: /auth/\n";
echo "Disallow: /admin/\n";
echo "Disallow: /payment/\n";
echo "Disallow: /cache/\n";
echo "Disallow: /logs/\n";
echo "\n";

// Yasaklı dosya türleri
echo "Disallow: /*.sql$\n";
echo "Disallow: /*.log$\n";
echo "Disallow: /*.env$\n";
echo "Disallow: /*.md$\n";
echo "Disallow: /*.json$\n";
echo "\n";

// Sitemap lokasyonu
echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";
?> 