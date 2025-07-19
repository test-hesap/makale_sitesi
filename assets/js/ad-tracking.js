// Reklam izleme fonksiyonları
const trackAd = async (adId, type) => {
    try {
        const response = await fetch('/api/track-ad.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ad_id: adId, type }),
        });
        
        if (!response.ok) {
            console.error('Ad tracking failed:', await response.json());
        }
    } catch (error) {
        console.error('Ad tracking error:', error);
    }
};

// Reklam gösterimini kaydet
const trackImpression = (adId) => {
    trackAd(adId, 'impression');
};

// Reklam tıklamasını kaydet
const trackClick = (adId) => {
    trackAd(adId, 'click');
};

// Görünürlük API'sini kullanarak reklam gösterimlerini otomatik kaydet
document.addEventListener('DOMContentLoaded', () => {
    const adElements = document.querySelectorAll('[data-ad-id]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const adId = entry.target.dataset.adId;
                    trackImpression(adId);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        adElements.forEach(ad => observer.observe(ad));
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        adElements.forEach(ad => trackImpression(ad.dataset.adId));
    }
    
    // Tıklama izleme
    document.addEventListener('click', (e) => {
        const adElement = e.target.closest('[data-ad-id]');
        if (adElement) {
            trackClick(adElement.dataset.adId);
        }
    });
}); 