<?php
include 'config.php';
include('protect.php');
verificarAcesso();

date_default_timezone_set('America/Sao_Paulo');

function atualizarDataHora($id_vinculo, $acao, $conexao) {
    $campo = $acao === 'iniciar' ? 'data_prog_fim' : 'data_programacao';
    $dataHora = date("Y-m-d H:i:s");

    $sql = "UPDATE cliente_produto SET $campo = ? WHERE id_vinculo = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('si', $dataHora, $id_vinculo);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo ucfirst($acao) . " com sucesso!";
    } else {
        echo "Falha ao " . $acao . ".";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['id_vinculo'])) {
    $acao = $_POST['acao'];
    $id_vinculo= intval($_POST['id_vinculo']);
    atualizarDataHora($id_vinculo, $acao, $conexao);
}

function buscarClientes($conexao) {
    $sql = "SELECT 
                idcliente AS cliente_id,
                pedido AS pedido_cliente,
                cliente AS nome_cliente,           
                endereco AS endereco_cliente,      
                data_entrega AS entrega_cliente
            FROM cliente
            ORDER BY pedido DESC";
    
    $result = $conexao->query($sql);
    if (!$result) {
        error_log("Erro na consulta de clientes: " . $conexao->error);
        echo "Desculpe, ocorreu um erro ao processar sua solicitação.";
        exit;
    }

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        // Adiciona apenas se idcliente estiver definido
        if (isset($row['cliente_id'])) {
            $clientes[$row['cliente_id']] = [
                'idcliente' => $row['cliente_id'],
                'pedido' => $row['pedido_cliente'],
                'cliente' => $row['nome_cliente'],
                'endereco' => $row['endereco_cliente'],
                'data_entrega' => $row['entrega_cliente'],
                'pedidos' => [],
                'produtos' => []
            ];
        }
    }
    $result->free();

    return $clientes;
}

$sql_vinculos = "
    SELECT 
        c.idcliente, 
        c.cliente, 
        c.pedido, 
        C.endereco,
        e.equipamento_pai, 
        p.conjunto, 
        cp.id_vinculo, 
        cp.data_engenharia, 
        cp.data_prog_fim, 
        cp.data_programacao, 
        cp.quantidade_prod,
        cp.link_pastas, 
        cp.tag,
        cp.obs_detalhes, 
        cp.lote,
        cp.prioridade
    FROM 
        cliente_produto cp
    JOIN 
        cliente c ON cp.id_cliente = c.idcliente
    JOIN 
        equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    JOIN 
        equipamento e ON ep.idequipamento = e.idequipamento
    JOIN 
        produto p ON ep.idproduto = p.idproduto
    ORDER BY 
        cp.prioridade ASC, cp.data_engenharia DESC, cp.data_prog_fim DESC";


$result_vinculos = $conexao->query($sql_vinculos);

$clientes = buscarClientes($conexao);

$clientes = buscarClientes($conexao);
$clientes_data = array_values($clientes);
foreach ($clientes_data as $cliente) {
    if (!isset($cliente['idcliente'])) {
        continue; // Pula para o próximo cliente se 'idcliente' estiver ausente
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Programação Pendente</title>
    <style>
    :root {
        --primary-color: #4CAF50;
        --secondary-color: #45a049;
        --background-color: #f5f5f5;
        --card-background: #ffffff;
        --text-color: #333;
        --border-color: #ddd;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --hover-shadow: rgba(0, 0, 0, 0.15);
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background-color: var(--background-color);
        margin: 0;
        padding: 20px;
        min-height: 100vh;
    }

    .titulo {
        text-align: center;
        margin: 20px 0 40px;
        font-size: 28px;
        color: var(--text-color);
        font-weight: 600;
        padding: 15px;
        background-color: var(--card-background);
        border-radius: 10px;
        box-shadow: 0 2px 4px var(--shadow-color);
    }

    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .card {
        background-color: var(--card-background);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px var(--shadow-color);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px var(--hover-shadow);
    }

    .card h3 {
        margin: 0;
        font-size: 1.3em;
        color: var(--primary-color);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 8px;
    }

    .card p {
        margin: 8px 0;
        font-size: 1em;
        color: var(--text-color);
        line-height: 1.5;
    }

    .card label {
        display: block;
        margin: 8px 0 4px;
        font-weight: 500;
        color: var(--text-color);
    }

    .card input,
    .card select,
    .card textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95em;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .card input:focus,
    .card select:focus,
    .card textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    .button-play,
    .button-finalize,
    .btn-salvar {
        width: 100%;
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        font-size: 1em;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 8px;
    }

    .button-play {
        background-color: var(--primary-color);
        color: white;
    }

    .button-play:hover {
        background-color: var(--secondary-color);
    }

    .button-finalize {
        background-color: #ff5722;
        color: white;
    }

    .button-finalize:hover {
        background-color: #f4511e;
    }

    .btn-salvar {
        background-color: #2196F3;
        color: white;
    }

    .btn-salvar:hover {
        background-color: #1976D2;
    }

    .copy-text {
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        background-color: rgba(76, 175, 80, 0.1);
        transition: background-color 0.3s ease;
    }

    .copy-text:hover {
        background-color: rgba(76, 175, 80, 0.2);
    }

    .efeito-raios {
        position: relative;
        overflow: hidden;
    }

    .efeito-raios::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent);
        animation: shine 2s infinite;
    }

    @keyframes shine {
        to {
            left: 100%;
        }
    }

    #mostrarConcluidos {
        margin: 20px;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 20px 0;
        padding: 10px;
        background-color: var(--card-background);
        border-radius: 8px;
        box-shadow: 0 2px 4px var(--shadow-color);
    }

    .checkbox-container label {
        font-size: 1em;
        color: var(--text-color);
        cursor: pointer;
    }

    .checkbox-container input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .form-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .form-group label {
        min-width: 120px;
        font-weight: 500;
    }

    .form-group input {
        flex: 1;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95em;
    }

    .copy-button {
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .copy-button:hover {
        background-color: var(--secondary-color);
    }

    .copy-button i {
        font-size: 16px;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        .titulo {
            font-size: 24px;
            margin: 15px 0 30px;
        }

        .cards-container {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 10px;
        }

        .card {
            padding: 15px;
        }
    }

    @media (max-width: 480px) {
        .titulo {
            font-size: 20px;
            padding: 10px;
        }

        .card {
            padding: 12px;
        }

        .card h3 {
            font-size: 1.2em;
        }

        .card p {
            font-size: 0.95em;
        }
    }
    </style>
</head>

<body>
    <h1 class="titulo">Programação Pendente</h1>

    <div class="select-container" style="text-align: center; margin-bottom: 20px;">
        <?php
$mostrarConcluidos = isset($_POST['mostrarConcluidos']);

?>
        <form method="post" id="formMostrarConcluidos">
            <div>
                <input type="checkbox" id="mostrarConcluidos" name="mostrarConcluidos"
                    <?php if ($mostrarConcluidos) echo 'checked'; ?>>
                <label for="mostrarConcluidos">Mostrar concluídos</label>
            </div>
        </form>
        <select id="clienteSelect" onchange="selecionarCliente()">
            <option style="text-transform: uppercase;" value="all">Selecione o cliente</option>
            <?php foreach ($clientes_data as $cliente): ?>
            <option style="text-transform: uppercase;" value="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
                <?php echo htmlspecialchars($cliente['pedido']) . " - " . htmlspecialchars($cliente['cliente']); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <button onclick="window.location.href='obra.php'"
            style="margin-left: 10px; padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
            VOLTAR
        </button>
        <span id="contador" style="margin-left: 10px; font-weight: bold;"></span>
    </div>

    <div class="cards-container" id="cartoesClientes">
        <?php
            $mostrarConcluidos = false;
            if (isset($_POST['mostrarConcluidos'])) {
            $mostrarConcluidos = true;
            }
            ?>

        <?php while ($row = $result_vinculos->fetch_assoc()): ?>
        <?php
            $dataProgFimPreenchido = isset($row['data_prog_fim']) && $row['data_prog_fim'] !== '0000-00-00 00:00:00';
            $dataProgramacaoPreenchido = isset($row['data_programacao']) && $row['data_programacao'] !== '0000-00-00 00:00:00';

            if (!$mostrarConcluidos && $dataProgFimPreenchido && $dataProgramacaoPreenchido) {
                continue;
            }
            ?>
        <div class="card <?php echo (isset($row['data_prog_fim']) && $row['data_prog_fim'] !== '0000-00-00 00:00:00') ? 'efeito-raios' : ''; ?>"
            data-cliente-id="<?php echo htmlspecialchars($row['idcliente']); ?>"
            data-vinculo-id="<?php echo htmlspecialchars($row['id_vinculo']); ?>">
            <p><strong>PEDIDO:</strong>
    <span class="copy-text" onclick="copyToClipboard(this)">
        <?= htmlspecialchars($row['pedido']) ?> - <?= htmlspecialchars($row['cliente']) ?> - <?= htmlspecialchars($row['endereco']) ?>
    </span>
</p>
<p><strong>CONJUNTO:</strong>
    <span class="copy-text" onclick="copyToClipboard(this)">
        <?php 
        // Verifica se 'tag' tem valor antes de exibir
        $tag = !empty($row['tag']) ? htmlspecialchars($row['tag']) . ' - ' : '';
        echo htmlspecialchars($row['conjunto']) . ' (<b>' . $tag . str_pad($row['quantidade_prod'], 2, '0', STR_PAD_LEFT) . ' CJ</b>)'; 
        ?>
    </span>
</p>




            <div class="toggle-content" style="display: none;">

                <p><strong>Data Início:</strong> <span
                        class="data-prog-fim"><?php if (!isset($row['data_prog_fim']) || $row['data_prog_fim'] === '0000-00-00 00:00:00') { echo '<span style="color: #34a853;">Não iniciado</span>'; } else { echo '<span style="color: #34a853;">' . (new DateTime($row['data_prog_fim']))->format('d/m/Y H:i') . '</span>'; } ?></span>
                </p>
                <p><strong>Data Fim:</strong> <span
                        class="data-programacao"><?php if (!isset($row['data_programacao']) || $row['data_programacao'] === '0000-00-00 00:00:00') { echo '<span style="color: #980000;">N/A</span>'; } else { echo '<span style="color: #980000;">' . (new DateTime($row['data_programacao']))->format('d/m/Y H:i') . '</span>'; } ?></span>
                </p>




                <form action="salvar_dados.php" method="POST">
                    <div class="form-group">
                        <label>Prioridade:</label>
                        <input type="number" name="prioridade"
                            value="<?php echo isset($row['prioridade']) ? htmlspecialchars($row['prioridade']) : '0'; ?>"
                            min="1" max="10">
                    </div>

                    <div class="form-group">
                        <label>Link da Pasta:</label>
                        <input type="text" name="link_pastas"
                            id="link_pastas_<?php echo htmlspecialchars($row['id_vinculo']); ?>"
                            value="<?php echo isset($row['link_pastas']) ? htmlspecialchars($row['link_pastas']) : ''; ?>">
                        <button type="button" class="copy-button"
                            onclick="copyLinkPasta('<?php echo htmlspecialchars($row['id_vinculo']); ?>')">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Observações:</label>
                        <input type="text" name="obs_detalhes"
                            value="<?php echo isset($row['obs_detalhes']) ? htmlspecialchars($row['obs_detalhes']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Lote:</label>
                        <input type="text" name="lote"
                            value="<?php echo isset($row['lote']) ? htmlspecialchars($row['lote']) : ''; ?>">
                    </div>

                    <input type="hidden" name="id_vinculo"
                        value="<?php echo isset($row['id_vinculo']) ? htmlspecialchars($row['id_vinculo']) : ''; ?>">

                    <button type="submit" class="btn-salvar">Salvar Dados</button>
                </form>

                <?php if (!isset($row['data_prog_fim']) || $row['data_prog_fim'] === '0000-00-00 00:00:00'): ?>
                <form action="salvar_data.php" method="POST">
                    <input type="hidden" name="id_vinculo" value="<?php echo htmlspecialchars($row['id_vinculo']); ?>">
                    <input type="hidden" name="acao" value="iniciar">
                    <button type="submit" class="button-play">Iniciar</button>
                </form>
                <?php elseif (!isset($row['data_programacao']) || $row['data_programacao'] === '0000-00-00 00:00:00'): ?>
                <form action="salvar_data.php" method="POST"
                    onsubmit="return confirmarFinalizacao(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                    <input type="hidden" name="id_vinculo" value="<?php echo htmlspecialchars($row['id_vinculo']); ?>">
                    <input type="hidden" name="acao" value="finalizar">
                    <button type="submit" class="button-finalize" id="finalizarBtn">Finalizar</button>
                </form>
                <?php else: ?>
                <div style="display: flex; justify-content: center; margin-top: 10px;">
                    <img src="img/ko-street-fighter.gif" alt="KO Street Fighter" style="width: 30%; max-width: 300px;">
                </div>
                <?php endif; ?>
            </div>
            <button class="toggle-button" onclick="toggleCard(this)">
                <span class="arrow">➕</span>
            </button>
        </div>
        <?php endwhile; ?>
    </div>
    <script>
    function selecionarCliente() {
        const select = document.getElementById("clienteSelect");
        const selectedClientId = select.value;
        const cartoes = document.querySelectorAll(".card");

        cartoes.forEach(cartao => {
            const clienteId = cartao.getAttribute("data-cliente-id");
            cartao.style.display = selectedClientId === "all" || selectedClientId === clienteId ? "block" :
                "none";
        });
    }
    let tempoRestante = 60; // Tempo em segundos
    function atualizarContador() {
        document.getElementById('contador').textContent = ` ${tempoRestante} segundos...`;
        tempoRestante--;

        if (tempoRestante < 0) {
            // Atualiza a página
            window.location.reload();
        }
    }

    setInterval(atualizarContador, 1000);

    function toggleCard(button) {
        const card = button.closest('.card');
        const content = card.querySelector('.toggle-content');
        const arrow = button.querySelector('.arrow');
        const vinculoId = card.getAttribute('data-vinculo-id');

        const isExpanded = card.classList.contains('expanded');

        // Remove a classe expanded de todos os cartões
        const allCards = document.querySelectorAll('.card');
        allCards.forEach(c => {
            if (c !== card) {
                c.classList.remove('expanded');
                c.querySelector('.toggle-content').style.display = "none"; // Encolhe os outros cartões
                c.querySelector('.arrow').textContent = "➕"; // Reseta o ícone
            }
        });

        // Expandir ou encolher o cartão clicado
        if (isExpanded) {
            card.classList.remove('expanded');
            content.style.display = "none";
            arrow.textContent = "➕";
            localStorage.removeItem(`card-${vinculoId}`);
        } else {
            card.classList.add('expanded');
            content.style.display = "block"; // Mostra o conteúdo do cartão expandido
            arrow.textContent = "➖";
            localStorage.setItem(`card-${vinculoId}`, 'expanded');
        }
    }
    // Restaura o estado dos cartões ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            const vinculoId = card.getAttribute('data-vinculo-id');
            if (localStorage.getItem(`card-${vinculoId}`)) {
                card.querySelector('.toggle-content').style.display = 'block';
                card.querySelector('.arrow').textContent = "➖"; // seta para cima
            }
        });
    });

    function selecionarCliente() {
        const select = document.getElementById("clienteSelect");
        const selectedClientId = select.value;

        // Salva o cliente selecionado no localStorage
        localStorage.setItem("clienteSelecionado", selectedClientId);

        // Filtra os cartões com base na seleção
        const cartoes = document.querySelectorAll(".card");
        cartoes.forEach(cartao => {
            const clienteId = cartao.getAttribute("data-cliente-id");
            cartao.style.display = selectedClientId === "all" || selectedClientId === clienteId ? "block" :
                "none";
        });
    }

    // Função para carregar o cliente selecionado do localStorage quando a página for carregada
    function carregarClienteSelecionado() {
        const selectedClientId = localStorage.getItem("clienteSelecionado") || "all";
        const select = document.getElementById("clienteSelect");
        select.value = selectedClientId;

        // Filtra os cartões com base no valor salvo
        const cartoes = document.querySelectorAll(".card");
        cartoes.forEach(cartao => {
            const clienteId = cartao.getAttribute("data-cliente-id");
            cartao.style.display = selectedClientId === "all" || selectedClientId === clienteId ? "block" :
                "none";
        });
    }

    document.addEventListener("DOMContentLoaded", carregarClienteSelecionado);

    function copyToClipboard(element) {
        const text = element.textContent.trim();
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);

        // Add visual feedback
        const originalText = element.innerHTML;
        element.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        setTimeout(() => {
            element.innerHTML = originalText;
        }, 2000);
    }

    function confirmarFinalizacao(row) {
        // Obtém o valor do campo 'lote' do PHP
        var lote = row.lote;

        // Verifica se o lote está vazio
        if (!lote || lote.trim() === "") {
            return confirm("O campo 'Lote' está vazio. Deseja continuar?");
        }
        return true; // Continua com o envio do formulário se o lote não estiver vazio
    }

    function copyLinkPasta(id) {
        const input = document.getElementById('link_pastas_' + id);
        if (!input.value) {
            alert('Não há link para copiar!');
            return;
        }

        input.select();
        input.setSelectionRange(0, 99999); // For mobile devices

        try {
            navigator.clipboard.writeText(input.value).then(() => {
                // Visual feedback
                const button = input.nextElementSibling;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            });
        } catch (err) {
            alert('Erro ao copiar o link: ' + err);
        }
    }

    document.getElementById('mostrarConcluidos').addEventListener('change', function() {
        document.getElementById('formMostrarConcluidos').submit();
    });
    </script>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>

</html>