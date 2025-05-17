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
    
    // Detectar a página atual para determinar caminhos relativos corretos
    const isInSubdirectory = window.location.pathname.includes('/views/');
    const basePath = isInSubdirectory ? '../' : '';
    
    console.log('Caminho base detectado:', basePath);
    
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
        
        console.log('Alerta:', type, message);
        
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
    
    // Função auxiliar para analisar respostas do servidor
    function parseServerResponse(xhr) {
        try {
            return JSON.parse(xhr.responseText);
        } catch (e) {
            console.error('Erro ao processar JSON:', e);
            console.log('Resposta do servidor:', xhr.responseText);
            
            // Tentar extrair mensagem de erro de HTML caso o servidor retorne HTML
            let errorMessage = 'Erro ao processar resposta do servidor';
            
            if (xhr.responseText.includes('<br') || xhr.responseText.includes('<p')) {
                errorMessage += ': O servidor retornou HTML em vez de JSON. Verifique os logs do servidor.';
            } else if (xhr.responseText.trim() !== '') {
                errorMessage += ': ' + xhr.responseText.substring(0, 100);
                if (xhr.responseText.length > 100) errorMessage += '...';
            }
            
            throw new Error(errorMessage);
        }
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
                console.log('Resposta do servidor:', xhr.status, xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const response = parseServerResponse(xhr);
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
                        showAlert(e.message);
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
            xhr.addEventListener('error', function(e) {
                console.error('Erro de conexão:', e);
                showAlert('Erro de conexão ao enviar arquivo');
                if (progressContainer) {
                    progressBar.style.width = '0%';
                    progressContainer.style.display = 'none';
                }
                if (loader) {
                    loader.style.display = 'none';
                }
            });
            
            // Usar o caminho do formulário em vez de determinar programaticamente
            const uploadUrl = uploadForm.getAttribute('action');
            console.log('URL de upload:', uploadUrl);
            
            // Enviar requisição
            xhr.open('POST', uploadUrl, true);
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
                    const deleteUrl = basePath + 'delete.php';
                    console.log('URL de exclusão:', deleteUrl);
                    
                    xhr.open('POST', deleteUrl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    if (loader) {
                        loader.style.display = 'block';
                    }
                    
                    xhr.addEventListener('load', function() {
                        console.log('Resposta do servidor (exclusão):', xhr.status, xhr.responseText);
                        
                        if (xhr.status === 200) {
                            try {
                                const response = parseServerResponse(xhr);
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
                                showAlert(e.message);
                            }
                        } else {
                            showAlert('Erro ao excluir arquivo: ' + xhr.statusText);
                        }
                        
                        if (loader) {
                            loader.style.display = 'none';
                        }
                    });
                    
                    xhr.addEventListener('error', function(e) {
                        console.error('Erro de conexão (exclusão):', e);
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