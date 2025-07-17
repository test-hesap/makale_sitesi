// Dil değiştirme fonksiyonu
function setLanguage(lang) {
    fetch('/api/set-language.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ language: lang })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Dil değiştirilirken bir hata oluştu:', error);
    });
}
