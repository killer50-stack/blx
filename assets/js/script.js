document.addEventListener('DOMContentLoaded', function() {
    // File input change handler
    const fileInput = document.getElementById('file');
    const fileNameDisplay = document.getElementById('file-name');
    
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                fileNameDisplay.textContent = file.name;
                
                // Check file size
                const maxSize = 29 * 1024 * 1024 * 1024; // 29 GB in bytes
                if (file.size > maxSize) {
                    alert('O arquivo excede o limite de tamanho de 29 GB.');
                    fileInput.value = '';
                    fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                }
            } else {
                fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
            }
        });
    }
    
    // Delete file button handlers
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este arquivo?')) {
                e.preventDefault();
            }
        });
    });
    
    // Hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500); // Wait for fade-out transition
            });
        }, 5000);
    }
}); 