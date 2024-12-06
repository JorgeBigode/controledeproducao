document.addEventListener('DOMContentLoaded', function() {
    // Função para copiar texto
    document.querySelectorAll('.copy-text').forEach(element => {
        element.addEventListener('click', function() {
            const text = this.textContent;
            navigator.clipboard.writeText(text).then(() => {
                this.classList.add('copied');
                setTimeout(() => {
                    this.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Erro ao copiar texto:', err);
                alert('Não foi possível copiar o texto. Por favor, tente novamente.');
            });
        });
    });

    // Função para sanitizar o caminho da rede
    function sanitizarCaminho(caminho) {
        return caminho
            .trim()
            .replace(/[<>"|?*]/g, '') // Remove caracteres inválidos
            .replace(/\//g, '\\') // Normaliza barras
            .replace(/\s+/g, ' ') // Normaliza espaços
            .replace(/\\+/g, '\\') // Remove barras duplicadas
            .replace(/^\\+/, ''); // Remove barras do início
    }

    // Função para processar resposta JSON
    async function processarResposta(response, origem) {
        const text = await response.text();
        console.log(`Resposta de ${origem} (texto):`, text);

        if (!text.trim()) {
            throw new Error('Resposta vazia do servidor');
        }

        if (text.includes('<!DOCTYPE html>')) {
            throw new Error('Resposta inválida do servidor (HTML detectado)');
        }

        try {
            const data = JSON.parse(text);
            if (!data.success) {
                const erro = data.error || 'Erro desconhecido';
                console.log(`Detalhes do erro de ${origem}:`, data.details || {});
                
                // Se for erro de autenticação, redireciona para o login
                if (data.details?.statusCode === 401) {
                    window.location.href = 'index.php';
                    throw new Error('Sessão expirada. Redirecionando para login...');
                }
                
                throw new Error(erro);
            }
            return data;
        } catch (jsonError) {
            if (jsonError.message.includes('Sessão expirada')) throw jsonError;
            console.error(`Erro ao parsear JSON de ${origem}:`, jsonError);
            throw new Error(`Erro ao processar resposta do servidor: ${text.substring(0, 100)}`);
        }
    }

    // Função para tentar abrir via PHP
    async function tentarAbrirViaPhp(link) {
        try {
            const response = await fetch('abrir_pasta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'caminho=' + encodeURIComponent(link)
            });

            return await processarResposta(response, 'PHP');
        } catch (error) {
            console.error('Erro na tentativa via PHP:', error);
            throw error;
        }
    }

    // Função para tentar abrir via Explorer
    async function tentarAbrirViaExplorer(link) {
        try {
            const response = await fetch('executar_comando.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                },
                body: 'comando=' + encodeURIComponent(`explorer /select,"${link}"`)
            });

            return await processarResposta(response, 'Explorer');
        } catch (error) {
            console.error('Erro na tentativa via explorer:', error);
            throw error;
        }
    }

    // Função para mostrar feedback visual
    function mostrarFeedback(elemento, sucesso, mensagem) {
        const classe = sucesso ? 'efeito-sucesso' : 'efeito-erro';
        elemento.classList.add(classe);
        
        if (mensagem) {
            // Remove qualquer tooltip existente
            const tooltipExistente = elemento.querySelector('.tooltip-feedback');
            if (tooltipExistente) {
                tooltipExistente.remove();
            }
            
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-feedback';
            tooltip.textContent = mensagem;
            elemento.appendChild(tooltip);
            
            setTimeout(() => {
                if (tooltip.parentNode === elemento) {
                    tooltip.remove();
                }
            }, 3000);
        }
        
        setTimeout(() => {
            elemento.classList.remove(classe);
        }, 2000);
    }
    

    // Função para abrir pasta
    window.abrirPasta = async function(link, event) {
        // Elemento que receberá o feedback visual
        const linkElement = event.target.closest('a') || event.target;

        try {
            if (!link) {
                throw new Error('Link inválido');
            }

            // Previne o comportamento padrão do link
            event.preventDefault();

            // Se a pasta já está sendo aberta, não tenta abrir novamente
            if (linkElement.dataset.opening === 'true') {
                console.log('Pasta já está sendo aberta...');
                return false;
            }

            // Marca que está tentando abrir
            linkElement.dataset.opening = 'true';
            
            // Sanitiza e normaliza o caminho
            link = sanitizarCaminho(link);
            console.log('Link sanitizado:', link);

            // Abre diretamente via explorer.exe
            console.log('Abrindo via explorer.exe...');
            await tentarAbrirViaExplorer(link);
            mostrarFeedback(linkElement, true, 'Pasta aberta com sucesso');

        } catch (error) {
            console.error('Erro ao abrir pasta:', error);
            mostrarFeedback(linkElement, false, error.message);
        } finally {
            // Sempre limpa o flag de abertura
            linkElement.dataset.opening = 'false';
        }

        return false;
    };

    // Adiciona estilos para os efeitos visuais
    const style = document.createElement('style');
    style.textContent = `
        .efeito-sucesso {
            animation: pulse-success 0.5s;
        }
        .efeito-erro {
            animation: pulse-error 0.5s;
        }
        @keyframes pulse-success {
            0% { background-color: transparent; }
            50% { background-color: rgba(0, 255, 0, 0.2); }
            100% { background-color: transparent; }
        }
        @keyframes pulse-error {
            0% { background-color: transparent; }
            50% { background-color: rgba(255, 0, 0, 0.2); }
            100% { background-color: transparent; }
        }
        .tooltip-feedback {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            margin-top: 5px;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);

    // Função para atualizar status
    window.atualizarStatus = async function(id, novoStatus) {
        const button = event.target;
        const originalText = button.textContent;
        
        try {
            // Adiciona loading
            button.innerHTML = '<span class="loading"></span>';
            button.disabled = true;

            const response = await fetch('atualizar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${id}&status=${novoStatus}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Erro ao atualizar status');
            }

            location.reload();

        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao atualizar status: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    };

    // Função para filtrar cards
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.card').forEach(card => {
                const cardText = card.textContent.toLowerCase();
                card.style.display = cardText.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Função para alternar visualização
    const checkboxes = document.querySelectorAll('.custom-checkbox');
    const cards = document.querySelectorAll('.card');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const status = this.dataset.status;
            cards.forEach(card => {
                if (card.dataset.status === status) {
                    card.style.display = this.checked ? '' : 'none';
                }
            });
        });
    });

    // Adiciona evento de clique para links de pasta
    const links = document.querySelectorAll('.link-pasta');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const caminho = this.getAttribute('data-caminho');
            window.open(caminho, '_blank');
        });
    });
});
