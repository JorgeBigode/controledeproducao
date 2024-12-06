<?php
session_start();

$permissoes = [
    'admin' => ['abrir_pasta.php', 'executar_comando.php', 'vincular_material_equipamento.php', 'adicionar_material.php', 'producao_programacao.php','programacao.php','inicio.php','adimim.php','gestao_setores.php','obra.php', 'registro_usuario.php', 'vinculo_cliente.php', 'pedido.php', 'edit_datas.php', 'relatorio_status.php', 'cadastro.php', 'vincular.php', 'material.php', 'editar_tr.php', 'trilhadeira.php', 'grafico.php', 'inserir_tag.php', 'excluir_vinculo.php', 'producao.php', 'inserir_material.php', 'excluir_material.php', 'editar_material.php'],
    'editor' => ['abrir_pasta.php', 'executar_comando.php', 'producao_programacao.php','programacao.php','inicio.php','obra.php', 'pedido.php', 'relatorio_status.php', 'cadastro.php', 'material.php', 'trilhadeira.php', 'grafico.php', 'edit_datas.php'],
    'viewer' => ['gestao_setores.php']
];

if (!function_exists('verificarAcesso')) {
    function verificarAcesso() {
        global $permissoes;

        // Verifica se é uma requisição AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if (!isset($_SESSION['id'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Você deve estar logado para acessar esta página.'
                ]);
            } else {
                echo "Você deve estar logado para acessar esta página.";
                echo '<a href="index.php">Voltar</a>';
            }
            exit;
        }

        // Obtém o papel do usuário e a página atual
        $role = $_SESSION['role'] ?? 'guest';
        $paginaAtual = basename($_SERVER['PHP_SELF']);

        // Verifica se o papel do usuário tem permissão para acessar a página atual
        if (!isset($permissoes[$role]) || !in_array($paginaAtual, $permissoes[$role])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Você não tem permissão para acessar esta página.'
                ]);
            } else {
                echo "Você não tem permissão para acessar esta página.";
                echo '<a href="inicio.php">Voltar</a>';
            }
            exit;
        }
    }
}

// Verifica se é uma requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Se não for AJAX, inclui o HTML
if (!$isAjax) {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['id'])) {
        header("Location: index.php");
        exit;
    }

    // Obtém informações do usuário
    $id = $_SESSION['id'];
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <style>
    .notification {
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        margin: 10px;
        border-radius: 5px;
        opacity: 1;
        transition: opacity 0.5s ease-out;
    }

    .notification.fade-out {
        opacity: 0;
    }
    </style>
</head>
<body>
    <div id="notifications" style="position: fixed; bottom: 20px; right: 20px;"></div>

    <script>
    // Conectar ao servidor WebSocket
    const socket = new WebSocket('ws://localhost:8080'); // Ajuste a porta conforme necessário

    // Função para adicionar uma nova notificação ao frontend
    function addNotification(message) {
        const notificationDiv = document.createElement('div');
        notificationDiv.classList.add('notification');
        notificationDiv.textContent = message;

        // Adiciona a notificação ao DOM
        document.getElementById('notifications').appendChild(notificationDiv);

        // Após 5 segundos, a notificação desaparece com uma animação de fade
        setTimeout(() => {
            notificationDiv.classList.add('fade-out');
            setTimeout(() => {
                notificationDiv.remove(); // Remove a notificação do DOM
            }, 500); // Tempo de animação
        }, 5000); // Notificação desaparecerá após 5 segundos
    }

    socket.onopen = function(event) {
        console.log("Conexão WebSocket estabelecida.");
    };

    socket.onmessage = function(event) {
        const dados = JSON.parse(event.data);
        if (dados.id_registro && dados.status_producao) {
            const message = `Status do cliente ${dados.id_registro}: ${dados.status_producao}`;
            addNotification(message);
        } else {
            console.log("Dados inválidos recebidos do servidor");
        }
    };

    // Quando houver erro na conexão
    socket.onerror = function(error) {
        console.log("Erro na conexão WebSocket:", error);
    };

    // Quando a conexão for fechada
    socket.onclose = function(event) {
        console.log("Conexão WebSocket fechada.");
    };
    </script>
</body>
</html>
<?php } ?>