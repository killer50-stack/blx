document.addEventListener('DOMContentLoaded', function() {
    // Variáveis globais
    const uploadForm = document.getElementById('upload-form');
    const fileInput = document.getElementById('file-input');
    const progressBar = document.querySelector('.progress');
    const progressContainer = document.querySelector('.progress-bar');
    const storageBar = document.querySelector('.storage-used');
    const deleteButtons = document.querySelectorAll('.delete-file');
    const alertContainer = document.querySelector('.alert-container');
    const loader = document.querySelector('.loader');
    
    // Atualizar a barra de armazenamento
    function updateStorageBar(percentage) {
        if (storageBar) {
            storageBar.style.width = percentage + '%';
            
            // Mudar cor baseado na porcentagem utilizada
            if (percentage > 90) {
                storageBar.style.backgroundColor = 'var(--error-color)';
            } else if (percentage > 70) {
                storageBar.style.backgroundColor = 'orange';
            } else {
                storageBar.style.backgroundColor = 'var(--primary-color)';
            }
        }
    }
    
    // Inicializar barra de armazenamento se existir no DOM
    if (storageBar) {
        const storagePercentage = storageBar.getAttribute('data-percentage');
        updateStorageBar(storagePercentage);
    }
    
    // Mostrar mensagens de alerta
    function showAlert(message, type = 'error') {
        if (!alertContainer) return;
        
        // Limpar alertas anteriores
        alertContainer.innerHTML = '';
        
        // Criar novo alerta
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        // Adicionar botão de fechar
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.float = 'right';
        closeBtn.style.cursor = 'pointer';
        closeBtn.onclick = function() {
            alertContainer.removeChild(alert);
        };
        
        alert.appendChild(closeBtn);
        alertContainer.appendChild(alert);
        
        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
        }, 5000);
    }
    
    // Gerenciar upload de arquivos
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) {
                showAlert('Por favor, selecione um arquivo para upload');
                return;
            }
            
            // Verificar tamanho máximo (29 GB)
            const maxSize = 31457280000; // 29 GB em bytes
            if (file.size > maxSize) {
                showAlert(`O arquivo excede o tamanho máximo permitido (29 GB). Tamanho atual: ${formatFileSize(file.size)}`);
                return;
            }
            
            // Verificar tipo de arquivo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('Tipo de arquivo não permitido. Use apenas imagens, vídeos ou PDFs.');
                return;
            }
            
            // Preparar formulário para envio
            const formData = new FormData(uploadForm);
            const xhr = new XMLHttpRequest();
            
            // Mostrar progresso
            if (progressContainer) {
                progressContainer.style.display = 'block';
            }
            if (loader) {
                loader.style.display = 'block';
            }
            
            // Configurar evento de progresso
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable && progressBar) {
                    const percentage = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentage + '%';
                }
            });
            
            // Configurar evento de conclusão
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Limpar formulário
                            uploadForm.reset();
                            
                            // Mostrar mensagem de sucesso
                            showAlert(response.message, 'success');
                            
                            // Recarregar página para mostrar o novo arquivo
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showAlert(response.message);
                        }
                    } catch (e) {
                        showAlert('Erro ao processar resposta do servidor');
                    }
                } else {
                    showAlert('Erro ao enviar arquivo: ' + xhr.statusText);
                }
                
                // Ocultar progresso
                if (progressContainer) {
                    setTimeout(() => {
                        progressBar.style.width = '0%';
                        progressContainer.style.display = 'none';
                    }, 1000);
                }
                if (loader) {
                    loader.style.display = 'none';
                }
            });
            
            // Configurar evento de erro
            xhr.addEventListener('error', function() {
                showAlert('Erro de conexão ao enviar arquivo');
                if (progressContainer) {
                    progressBar.style.width = '0%';
                    progressContainer.style.display = 'none';
                }
                if (loader) {
                    loader.style.display = 'none';
                }
            });
            
            // Enviar requisição
            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);
        });
    }
    
    // Manipular botões de exclusão
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (confirm('Tem certeza que deseja excluir este arquivo?')) {
                    const fileId = this.getAttribute('data-id');
                    
                    // Enviar requisição para excluir arquivo
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'delete.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    if (loader) {
                        loader.style.display = 'block';
                    }
                    
                    xhr.addEventListener('load', function() {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    // Remover elemento da interface
                                    const fileCard = button.closest('.file-card');
                                    if (fileCard) {
                                        fileCard.remove();
                                    }
                                    
                                    // Mostrar mensagem de sucesso
                                    showAlert(response.message, 'success');
                                    
                                    // Atualizar barra de armazenamento
                                    if (response.storagePercentage !== undefined) {
                                        updateStorageBar(response.storagePercentage);
                                    }
                                } else {
                                    showAlert(response.message);
                                }
                            } catch (e) {
                                showAlert('Erro ao processar resposta do servidor');
                            }
                        } else {
                            showAlert('Erro ao excluir arquivo: ' + xhr.statusText);
                        }
                        
                        if (loader) {
                            loader.style.display = 'none';
                        }
                    });
                    
                    xhr.addEventListener('error', function() {
                        showAlert('Erro de conexão ao excluir arquivo');
                        if (loader) {
                            loader.style.display = 'none';
                        }
                    });
                    
                    xhr.send('file_id=' + encodeURIComponent(fileId));
                }
            });
        });
    }
    
    // Função para formatar tamanho de arquivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}); 