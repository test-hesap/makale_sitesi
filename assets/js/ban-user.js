/**
 * Ban User Form Handler
 * Admin panelinden kullanıcı banlama için JavaScript kodu
 */

document.addEventListener('DOMContentLoaded', function() {
    const banUserForms = document.querySelectorAll('form.ban-user-form');
    
    banUserForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            
            // Butonu devre dışı bırak ve yükleniyor göster
            submitButton.disabled = true;
            submitButton.textContent = 'İşleniyor...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Başarılı işlem
                    Swal.fire({
                        title: 'Başarılı!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        // Eğer yönlendirme varsa, sayfaya git
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        } else {
                            // Yoksa sayfayı yenile
                            window.location.reload();
                        }
                    });
                } else {
                    // Hata durumu
                    Swal.fire({
                        title: 'Hata!',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'Tamam'
                    });
                }
            } catch (error) {
                console.error('Ban işlemi hatası:', error);
                Swal.fire({
                    title: 'Sistem Hatası!',
                    text: 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
            } finally {
                // Butonu normal haline getir
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    });
});
