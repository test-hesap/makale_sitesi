<?php
/**
 * Admin panelinde kullanıcı banlamak için kullanılan form sayfası
 */

// Oturumu başlat
session_start();

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// Yetki kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login.php');
    exit;
}

// CSRF token oluştur
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Banla</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], 
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>Kullanıcı Banla</h1>
    
    <div id="banForm">
        <div class="form-group">
            <label for="user_id">Kullanıcı ID:</label>
            <input type="number" id="user_id" name="user_id" required>
        </div>
        
        <div class="form-group">
            <label for="reason">Ban Nedeni:</label>
            <textarea id="reason" name="reason" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="ban_type">Ban Tipi:</label>
            <select id="ban_type" name="ban_type">
                <option value="permanent">Süresiz</option>
                <option value="temporary">Geçici</option>
            </select>
        </div>
        
        <div class="form-group" id="expiryDateGroup" style="display: none;">
            <label for="expiry_date">Bitiş Tarihi:</label>
            <input type="datetime-local" id="expiry_date" name="expiry_date">
        </div>
        
        <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <button type="button" onclick="banUser()">Kullanıcıyı Banla</button>
    </div>
    
    <div id="result" class="result" style="display: none;"></div>
    
    <script>
        // Ban tipi seçildiğinde tarih alanını göster/gizle
        document.getElementById('ban_type').addEventListener('change', function() {
            const expiryDateGroup = document.getElementById('expiryDateGroup');
            if (this.value === 'temporary') {
                expiryDateGroup.style.display = 'block';
            } else {
                expiryDateGroup.style.display = 'none';
            }
        });
        
        // Kullanıcı banlama işlemi
        function banUser() {
            const userId = document.getElementById('user_id').value;
            const reason = document.getElementById('reason').value;
            const banType = document.getElementById('ban_type').value;
            const expiryDate = document.getElementById('expiry_date').value;
            const csrfToken = document.getElementById('csrf_token').value;
            
            if (!userId || !reason) {
                showResult('Kullanıcı ID ve ban nedeni zorunludur', false);
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('reason', reason);
            formData.append('ban_type', banType);
            formData.append('csrf_token', csrfToken);
            
            if (banType === 'temporary' && expiryDate) {
                formData.append('expiry_date', expiryDate);
            }
            
            fetch('/api/ban-user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) {
                    document.getElementById('banForm').reset();
                }
            })
            .catch(error => {
                showResult('İşlem sırasında bir hata oluştu: ' + error, false);
            });
        }
        
        // Sonucu göster
        function showResult(message, success) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = 'result ' + (success ? 'success' : 'error');
            resultDiv.style.display = 'block';
        }
    </script>
</body>
</html>
