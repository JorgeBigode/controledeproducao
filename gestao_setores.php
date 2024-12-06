<?php
include_once('config.php'); 
include('protect.php');
verificarAcesso();

date_default_timezone_set('America/Sao_Paulo');
if (!isset($_SESSION['id'])) {
    header("Location: index.php"); // Redireciona para a página de login se não estiver logado
    exit;
}
$id_usuario = $_SESSION['id'];

// Consulta para obter todos os setores do usuário logado
$sqlSetor = "SELECT id_setor FROM usuario_setor WHERE id_usuario = ?";
$stmtSetor = $conexao->prepare($sqlSetor);
$stmtSetor->bind_param('i', $id_usuario);
$stmtSetor->execute();
$resultSetor = $stmtSetor->get_result();

$id_setores_usuario = [];
while ($rowSetor = $resultSetor->fetch_assoc()) {
    $id_setores_usuario[] = $rowSetor['id_setor'];
}

$stmtSetor->close();

if (empty($id_setores_usuario)) {
    echo "Setores do usuário não encontrados.";
    exit;
}

// Convertendo o array para uma string para uso na consulta SQL
$id_setores_usuario_str = implode(',', $id_setores_usuario);

$limitesPorSetor = [
    'guilhotina' => 1,
    'laser' => 2,
    'dobra' => 2,
    'puncionadeira' => 1,
];

// Função para atualizar data e hora
function atualizarDataHora($idProducao, $acao, $conexao) {
    $campo = $acao === 'iniciar' ? 'data_hora_inicio' : 'data_hora_fim';
    $dataHora = date("Y-m-d H:i:s");

    $sql = "UPDATE producao SET $campo = ? WHERE id_producao = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('si', $dataHora, $idProducao);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "$acao com sucesso!";
    } else {
        echo "Falha ao $acao.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['id_producao'])) {
    $acao = $_POST['acao'];
    $idProducao = intval($_POST['id_producao']);
    atualizarDataHora($idProducao, $acao, $conexao);
}

function buscarCartoesPorSetor($conexao, $id_setores_usuario_str, $limitesPorSetor) {
    $sql = "
        SELECT 
            p.id_producao,
            cp.lote,
            p.data_hora_inicio,
            p.data_hora_fim,
            s.nome_setor,
            p.prioridade
        FROM producao p
        JOIN setores s ON p.id_setor = s.id_setor
        JOIN cliente_produto cp ON p.id_vinculo = cp.id_vinculo
        WHERE p.id_setor IN ($id_setores_usuario_str)
        AND p.data_hora_fim IS NULL
        ORDER BY s.nome_setor, p.prioridade ASC, p.data_hora_inicio DESC
    ";
    $stmt = $conexao->prepare($sql);
    $stmt->execute();
    return $stmt->get_result();
}

// Busque os cartões inicialmente
$result = buscarCartoesPorSetor($conexao, $id_setores_usuario_str, $limitesPorSetor);
$contagemPorSetor = [];

echo '<button class="button-back" onclick="window.history.back();">Voltar</button>';
echo '<span id="contador" style="margin-left: 10px; font-weight: bold;"></span>';
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Gestão de Setores</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        padding: 20px;
    }

    .card {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 8px;
        width: 300px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .card h3 {
        margin: 0;
        font-size: 1.2em;
        color: #4CAF50;
    }

    .card p {
        margin: 8px 0;
        font-size: 1em;
        color: #333;
    }

    .button-play,
    .button-finalize {
        width: 100%;
        margin-top: 10px;
        padding: 10px;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .button-play {
        background-color: #4CAF50;
    }

    .button-finalize {
        background-color: #f44336;
    }

    .button-back {
        position: fixed;
        /* Fixa o botão na tela */
        top: 20px;
        /* Espaçamento superior */
        left: 20px;
        /* Espaçamento esquerdo */
        background-color: #007BFF;
        /* Cor de fundo */
        color: white;
        /* Cor do texto */
        padding: 10px 15px;
        /* Espaçamento interno reduzido */
        border: none;
        /* Remove borda */
        border-radius: 5px;
        /* Arredondamento */
        cursor: pointer;
        /* Cursor em formato de mão */
        font-size: 16px;
        /* Tamanho da fonte */
        font-weight: bold;
        /* Negrito */
        transition: background-color 0.3s;
        /* Transição suave */
    }

    .button-back:hover {
        background-color: #0056b3;
        /* Cor ao passar o mouse */
    }
    </style>
</head>

<body>

    <h2 style="width: 100%; text-align: center;">Gestão de Setores</h2>
    <div id="cards-container">
        <?php while ($row = $result->fetch_assoc()): 
        $setor = strtolower($row['nome_setor']);

        // Inicializa a contagem para o setor, se não existir ainda
        if (!isset($contagemPorSetor[$setor])) {
            $contagemPorSetor[$setor] = 0;
        }

        // Verifica se o setor atingiu o limite de cartões
        if (isset($limitesPorSetor[$setor]) && $contagemPorSetor[$setor] >= $limitesPorSetor[$setor]) {
            continue;
        }

        // Incrementa a contagem para o setor
        $contagemPorSetor[$setor]++;
        ?>

        <div class="card">
            <h3>Lote: <?php echo htmlspecialchars($row['lote']); ?></h3>
            <p><strong>Setor:</strong> <?php echo htmlspecialchars($row['nome_setor']); ?></p>
            <p><strong>Prioridade:</strong> <?php echo $row['prioridade']; ?></p>
            <p><strong>Data e Hora Início:</strong>
                <?php 
                    echo $row['data_hora_inicio'] 
                        ? (new DateTime($row['data_hora_inicio']))->format('d/m/Y H:i:s') 
                        : 'Não iniciado'; 
                ?>
            </p>
            <p><strong>Data e Hora Fim:</strong>
                <?php 
                echo $row['data_hora_fim'] 
                    ? (new DateTime($row['data_hora_fim']))->format('d/m/Y H:i:s') 
                    : 'Não finalizado'; 
            ?>
            </p>
            <div>
                <!-- Botões para iniciar/finalizar produção, conforme o estado do lote -->
                <?php if (!$row['data_hora_inicio']): ?>
                <form method="POST">
                    <input type="hidden" name="id_producao" value="<?php echo $row['id_producao']; ?>">
                    <input type="hidden" name="acao" value="iniciar">
                    <button type="submit" class="button-play">Iniciar</button>
                </form>
                <?php elseif (!$row['data_hora_fim']): ?>
                <form method="POST">
                    <input type="hidden" name="id_producao" value="<?php echo $row['id_producao']; ?>">
                    <input type="hidden" name="acao" value="finalizar">
                    <button type="submit" class="button-finalize">Finalizar</button>
                </form>
                <?php else: ?>
                <p style="color: #4CAF50;"><strong>Concluído</strong></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <script>
    function finalizarCartao(id_producao, setor) {
        // Envio de requisição AJAX para finalizar o cartão
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'finalizar_cartao.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                atualizarCartoes(); // Atualiza a lista de cartões
            }
        };
        xhr.send('id_producao=' + id_producao + '&setor=' + setor);
    }

    function atualizarCartoes() {
        // Atualiza os cartões via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'gestao_setores.php', true); // Substitua para apenas buscar os cartões via AJAX
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('cards-container').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }
    let tempoRestante = 20; // Tempo em segundos

    function atualizarContador() {
        document.getElementById('contador').textContent = `Atualizando em ${tempoRestante} segundos...`;
        tempoRestante--;

        if (tempoRestante < 0) {
            // Atualiza a página
            window.location.reload();
        }
    }

    setInterval(atualizarContador, 1000);
    </script>

</body>

</html>