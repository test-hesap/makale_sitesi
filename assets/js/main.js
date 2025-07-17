// Ana JavaScript Dosyası
document.addEventListener('DOMContentLoaded', function() {
    
    // Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            fetch('/api/set-theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ theme: newTheme })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.documentElement.setAttribute('data-theme', newTheme);
                    updateThemeIcon(newTheme);
                }
            });
        });
    }
    
    // Language Toggle
    const languageToggle = document.getElementById('language-toggle');
    if (languageToggle) {
        languageToggle.addEventListener('click', function() {
            const currentLang = this.getAttribute('data-current-lang') || 'tr';
            const newLang = currentLang === 'tr' ? 'en' : 'tr';
            
            fetch('/api/set-language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ language: newLang })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    }
    
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('open');
            
            // ARIA accessibility
            const isOpen = mobileMenu.classList.contains('open');
            mobileMenuToggle.setAttribute('aria-expanded', isOpen);
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mobileMenu.classList.remove('open');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Search Form Enhancement
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault();
                showToast('Lütfen arama terimi girin', 'warning');
                searchInput.focus();
            }
        });
    }
    
    // Scroll to Top Button
    const scrollTopBtn = document.getElementById('scroll-top');
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.remove('hidden');
            } else {
                scrollTopBtn.classList.add('hidden');
            }
        });
        
        scrollTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Form Validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Image Lazy Loading
    const images = document.querySelectorAll('img[data-src]');
    if (images.length > 0 && 'IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, delay);
    });
    
    // Çerez bildirimi
    setTimeout(() => {
        initCookieConsent();
    }, 1000); // 1 saniye gecikme ile başlat
});

// Utility Functions
function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
}

function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function validateForm(form) {
    let isValid = true;
    
    // Email validation
    const emailInputs = form.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (input.value && !emailRegex.test(input.value)) {
            showError(input, 'Geçerli bir e-posta adresi girin');
            isValid = false;
        }
    });
    
    // Password validation
    const passwordInputs = form.querySelectorAll('input[type="password"][data-min-length]');
    passwordInputs.forEach(input => {
        const minLength = parseInt(input.dataset.minLength);
        if (input.value.length < minLength) {
            showError(input, `Şifre en az ${minLength} karakter olmalıdır`);
            isValid = false;
        }
    });
    
    // Required fields
    const requiredInputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            showError(input, 'Bu alan zorunludur');
            isValid = false;
        }
    });
    
    return isValid;
}

function showError(input, message) {
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    input.parentNode.appendChild(errorDiv);
    input.classList.add('border-red-500');
    
    input.addEventListener('input', function() {
        this.classList.remove('border-red-500');
        const error = this.parentNode.querySelector('.error-message');
        if (error) error.remove();
    }, { once: true });
}

// AJAX Helper
function makeRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaults, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Bir hata oluştu', 'error');
            throw error;
        });
}

// Copy to Clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Panoya kopyalandı', 'success');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Panoya kopyalandı', 'success');
    }
}

// Social Share
function socialShare(platform, url, title) {
    const shareUrls = {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
        whatsapp: `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`
    };
    
    if (shareUrls[platform]) {
        window.open(shareUrls[platform], '_blank', 'width=600,height=400');
    }
}

// Çerez bildirimi
function initCookieConsent() {
    console.log('Çerez bildirimi başlatılıyor...');
    
    // Ayarlar yüklenmediyse bekle
    if (!window.siteSettings) {
        fetch('/api/get-settings.php?settings=cookie_consent_enabled,cookie_consent_text,cookie_consent_button_text,cookie_consent_position,cookie_consent_theme,cookie_consent_show_link,cookie_consent_link_text,cookie_analytics_enabled,cookie_marketing_enabled')
            .then(response => response.json())
            .then(data => {
                console.log('Ayarlar alındı:', data);
                window.siteSettings = data;
                initCookieConsent(); // Ayarlar yüklendikten sonra tekrar dene
            })
            .catch(error => {
                console.error('Ayarlar alınamadı:', error);
            });
        return;
    }
    
    // Çerez ayarlarını al
    const cookieConsentEnabled = getSetting('cookie_consent_enabled', '1') === '1';
    console.log('Çerez bildirimi aktif mi:', cookieConsentEnabled);
    
    // Çerez bildirimi aktif değilse çıkış yap
    if (!cookieConsentEnabled) return;
    
    // Kullanıcı daha önce kabul ettiyse çıkış yap
    if (getCookie('cookie_consent_accepted') === 'true') {
        console.log('Kullanıcı daha önce çerezleri kabul etmiş');
        return;
    }
    
    // Varsa mevcut bildirimi kaldır
    const existingConsent = document.getElementById('cookie-consent');
    if (existingConsent) {
        existingConsent.remove();
    }
    
    // Çerez ayarlarını al
    const cookieConsentText = getSetting('cookie_consent_text', 'Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanır.');
    const cookieConsentButtonText = getSetting('cookie_consent_button_text', 'Kabul Et');
    const cookieConsentPosition = getSetting('cookie_consent_position', 'bottom');
    const cookieConsentTheme = getSetting('cookie_consent_theme', 'dark');
    const cookieConsentShowLink = getSetting('cookie_consent_show_link', '1') === '1';
    const cookieConsentLinkText = getSetting('cookie_consent_link_text', 'Daha fazla bilgi');
    
    console.log('Çerez bildirimi ayarları:', {
        text: cookieConsentText,
        button: cookieConsentButtonText,
        position: cookieConsentPosition,
        theme: cookieConsentTheme,
        showLink: cookieConsentShowLink,
        linkText: cookieConsentLinkText
    });
    
    // Çerez bildirimi oluştur
    const cookieConsent = document.createElement('div');
    cookieConsent.id = 'cookie-consent';
    
    // Pozisyona göre sınıf ekle
    let positionClass = '';
    switch(cookieConsentPosition) {
        case 'top':
            positionClass = 'top-0 left-0 right-0';
            break;
        case 'bottom-left':
            positionClass = 'bottom-0 left-0 max-w-sm m-4';
            break;
        case 'bottom-right':
            positionClass = 'bottom-0 right-0 max-w-sm m-4';
            break;
        default:
            positionClass = 'bottom-0 left-0 right-0';
    }
    
    // Temaya göre sınıf ekle
    const themeClass = cookieConsentTheme === 'dark' 
        ? 'bg-gray-800 text-white' 
        : 'bg-white text-gray-800 border-t border-gray-200';
    
    // HTML oluştur
    cookieConsent.className = `fixed ${positionClass} ${themeClass} p-4 shadow-lg z-50 flex flex-col sm:flex-row items-center justify-between transition-opacity duration-300`;
    
    let consentHTML = `
        <div class="text-sm mr-4 mb-3 sm:mb-0">
            ${cookieConsentText}
            ${cookieConsentShowLink ? `<a href="/cerezler" class="text-blue-400 hover:underline ml-1">${cookieConsentLinkText}</a>` : ''}
        </div>
        <button id="accept-cookies" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
            ${cookieConsentButtonText}
        </button>
    `;
    
    cookieConsent.innerHTML = consentHTML;
    document.body.appendChild(cookieConsent);
    
    console.log('Çerez bildirimi eklendi');
    
    // Kabul et butonuna tıklama olayı ekle
    document.getElementById('accept-cookies').addEventListener('click', function() {
        console.log('Çerez kabul edildi');
        
        // Çerez ayarla (1 yıl geçerli)
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = `cookie_consent_accepted=true; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
        
        // Bildirimi kaldır
        cookieConsent.style.opacity = '0';
        setTimeout(() => {
            cookieConsent.remove();
        }, 300);
    });
}

// Çerez alma fonksiyonu
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Ayar alma fonksiyonu
function getSetting(key, defaultValue) {
    // Statik ayarlar nesnesi
    if (!window.siteSettings) {
        window.siteSettings = {};
        
        // Ayarları API'den al
        fetch('/api/get-settings.php?settings=cookie_consent_enabled,cookie_consent_text,cookie_consent_button_text,cookie_consent_position,cookie_consent_theme,cookie_consent_show_link,cookie_consent_link_text,cookie_analytics_enabled,cookie_marketing_enabled')
            .then(response => response.json())
            .then(data => {
                console.log('Ayarlar alındı:', data);
                window.siteSettings = data;
            })
            .catch(error => {
                console.error('Ayarlar alınamadı:', error);
            });
            
        // Ayarlar alınana kadar varsayılan değerleri kullan
        return defaultValue;
    }
    
    // Ayar varsa döndür, yoksa varsayılan değeri döndür
    return window.siteSettings[key] !== undefined ? window.siteSettings[key] : defaultValue;
} 