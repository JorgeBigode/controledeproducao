<?php

include_once('config.php'); 
include('protect.php');
verificarAcesso();

$sql = "
    SELECT 
        p.id_producao, 
        p.id_vinculo, 
        p.id_setor, 
        p.data_hora_inicio, 
        p.data_hora_fim, 
        CASE 
            WHEN p.data_hora_fim IS NOT NULL THEN 'Concluído' 
            WHEN p.data_hora_inicio IS NOT NULL THEN 'Em Andamento' 
            ELSE 'Iniciar' 
        END AS status,
        p.observacao,
        s.nome_setor,
        c.idcliente,
        c.cliente,
        c.pedido,
        c.endereco,
        cp.lote,
        cp.data_prog_fim,
        cp.data_programacao,
        p.prioridade
    FROM producao p
    JOIN setores s ON p.id_setor = s.id_setor
    JOIN cliente_produto cp ON p.id_vinculo = cp.id_vinculo
    JOIN cliente c ON cp.id_cliente = c.idcliente
    ORDER BY p.prioridade DESC, p.data_hora_inicio ASC;
";

$result = $conexao->query($sql);

$sql_vinculos = "
    SELECT c.idcliente, c.cliente, e.equipamento_pai, p.conjunto 
    FROM cliente_produto cp
    JOIN cliente c ON cp.id_cliente = c.idcliente
    JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    JOIN equipamento e ON ep.idequipamento = e.idequipamento
    JOIN produto p ON ep.idproduto = p.idproduto
";

$result_vinculos = $conexao->query($sql_vinculos);

$data_inserida = $_GET['data_inserida'] ?? '';

function buscarClientes($conexao) {
    $sql = "SELECT 
                idcliente AS cliente_id,
                pedido AS pedido_cliente,
                cliente AS nome_cliente,           
                endereco AS endereco_cliente,      
                data_entrega AS entrega_cliente
            FROM cliente
            ORDER BY idcliente DESC";
    
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

$clientes = buscarClientes($conexao);
$clientes_data = array_values($clientes);
foreach ($clientes_data as $cliente) {
    if (!isset($cliente['idcliente'])) {
        continue; // Pula para o próximo cliente se 'idcliente' estiver ausente
    }
}

if (isset($_POST['prioridade']) && isset($_POST['id_producao'])) {
    $prioridade = $_POST['prioridade'];
    $idProducao = $_POST['id_producao'];

    $sqlUpdatePrioridade = "UPDATE producao SET prioridade = ? WHERE id_producao = ?";
    $stmt = $conexao->prepare($sqlUpdatePrioridade);
    $stmt->bind_param('ii', $prioridade, $idProducao);
    $stmt->execute();
    $stmt->close();
}
$conexao->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Produção</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background-image: linear-gradient(to right, rgb(20, 147, 22), rgb(17, 54, 7));
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #333;
        padding: 10px;
        color: white;
    }

    a {
        color: inherit;
    }

    main {
        background-color: rgba(0, 0, 0, 0.6);
        flex: 20 0 500px;
        flex-wrap: wrap;
        overflow: auto;
        height: calc(100vh - 115px);
        margin: 3px;
        padding: 10px;
        border-radius: 8px 8px 8px;
    }


    #logo img {
        max-width: 100px;
    }

    #openMenu {
        font-size: 24px;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
    }

    #menu {
        height: 100%;
        width: 250px;
        position: fixed;
        top: 0;
        left: -250px;
        background-color: #111;
        overflow-x: hidden;
        transition: 0.3s;
        padding-top: 60px;
        z-index: 2000;
    }

    #menu a {
        padding: 10px 15px;
        text-decoration: none;
        font-size: 18px;
        color: white;
        display: block;
        transition: 0.3s;
    }

    #menu a:hover {
        background-color: #575757;
    }

    #closeMenu {
        position: absolute;
        top: 0;
        right: 15px;
        font-size: 36px;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
    }

    .submenu {
        padding: 10px;
    }

    .submenu-content {
        padding-left: 20px;
    }

    .cliente-bloco {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .cliente-bloco table {
        width: 100%;
        border-collapse: collapse;
    }

    .cliente-bloco table th,
    .cliente-bloco table td {
        width: 20%;
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .cliente-bloco table th {
        background-color: #009879;
        color: white;
    }
    </style>
</head>

<body>
    <header>

        <a href="pedido.php" id="logo"><img src="./img/logoSMA.png"></a>
        <div class="select-container">
            <select id="clienteSelect" onchange="selecionarCliente()">
                <option value="all">Selecione o cliente</option>
                <?php foreach ($clientes_data as $cliente): ?>
                <option value="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
                    <?php echo htmlspecialchars($cliente['pedido']) . " - " . htmlspecialchars($cliente['cliente']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="select-container">
            <label for="tipoTabela">Escolha a tabela:</label>
            <select id="tipoTabela" onchange="alterarTabela()">
                <option value="producao.php">Fabrica</option>
                <option value="producao_programacao.php">Programação</option>
            </select>
        </div>
        <button id="openMenu">&#9776;</button>
    </header>

    <nav id="menu">
        <button id="closeMenu">X</button>
        <div class="submenu">
            <a href="inicio.php">INICIO</a>
        </div>
        <div>
            <a href="status_producao.php">STATUS PRODUÇÃO</a>
        </div>
        <div class="submenu">
            <a href="pedido.php">PEDIDOS</a>
            <div class="submenu-content">
                <a href="graficos.php">GRÁFICOS</a>
            </div>
        </div>
        <div class="submenu">
            <a href="trilhadeira.php">TRILHADEIRA</a>
            <div class="submenu-content">
                <a href="material.php">MATERIAL</a>
            </div>
        </div>
        <div class="submenu">
            <a href="cadastro.php">CADASTRO</a>
        </div>
        <div class="submenu">
            <a href="obra.php">OBRAS</a>
        </div>
        <div class="submenu">
            <a href="logout.php">SAIR</a>
        </div>
    </nav>

    <main>
        <?php 
        $ultimo_cliente = null;
        $ultimo_lote = null;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["status"] !== "Concluído") { // Adiciona essa condição
                    if ($ultimo_cliente !== $row["cliente"]) {
                        if ($ultimo_cliente !== null) {
                            echo "</table></div>";
                        }
                        echo "<div class='cliente-bloco' data-cliente-id='" . htmlspecialchars($row["idcliente"]) . "'>";
                        echo "<h2>Pedido: " . htmlspecialchars($row["pedido"]) . " - Cliente: " . htmlspecialchars($row["cliente"]) . "</h2>";
                        echo "<p>Localidade: " . htmlspecialchars($row["endereco"]) . "</p>";
                        $ultimo_cliente = $row["cliente"];
                        $ultimo_lote = null;
                    }
                    if ($ultimo_lote !== $row["lote"]) {
                        if ($ultimo_lote !== null) {
                            echo "</table>";
                        }
                        echo "<h3>Lote: " . htmlspecialchars($row["lote"]) . "</h3>";
                        echo "<table> 
                        <tr> 
                        <th>Setor</th> 
                        <th>Data e Hora Início</th> 
                        <th>Data e Hora Fim</th> 
                        <th>Status</th> 
                        <th>Observação</th> 
                        <th>Prioridade</th> 
                        </tr>";
                        $ultimo_lote = $row["lote"];
                    }
                    $inicio_formatado = $row["data_hora_inicio"] ? (new DateTime($row["data_hora_inicio"]))->format('d/m/Y H:i:s') : 'Não iniciado';
                    $fim_formatado = $row["data_hora_fim"] ? (new DateTime($row["data_hora_fim"]))->format('d/m/Y H:i:s') : 'Não concluído';
                    echo "<tr> 
                    <td>" . htmlspecialchars($row["nome_setor"]) . "</td> 
                    <td>" . $inicio_formatado . "</td> 
                    <td>" . $fim_formatado . "</td> 
                    <td>" . htmlspecialchars($row["status"]) . "</td> 
                    <td>" . ($row["observacao"] ? htmlspecialchars($row["observacao"]) : 'Nenhuma observação') . "</td> 
                    <td> 
                    <input type='number' value='" . htmlspecialchars($row["prioridade"]) . "' onchange='atualizarPrioridade(" . htmlspecialchars($row["id_producao"]) . ", this.value)'> 
                    </td> 
                    </tr>";
                }
            }
            echo "</table></div>";
        } else {
            echo "Nenhum resultado encontrado.";
        }
        ?>
    </main>
    <script>
    function atualizarPrioridade(idProducao, prioridade) {
        fetch('atualizar_prioridade_producao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_producao: idProducao,
                    prioridade: prioridade
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Prioridade atualizada com sucesso!");
                    location.reload(); // Recarregar para aplicar a nova ordem
                } else {
                    alert("Erro ao atualizar prioridade.");
                }
            })
            .catch(error => console.error('Erro:', error));
    }

    function selecionarCliente() {
        const clienteId = document.getElementById("clienteSelect").value;
        const blocosClientes = document.querySelectorAll(".cliente-bloco");
        blocosClientes.forEach(bloco => {
            bloco.style.display = clienteId === 'all' || bloco.dataset.clienteId === clienteId ? 'block' :
                'none';
        });
    }

    document.getElementById("openMenu").addEventListener("click", function() {
        document.getElementById("menu").style.left = "0";
    });

    document.getElementById("closeMenu").addEventListener("click", function() {
        document.getElementById("menu").style.left = "-250px";
    });

    function alterarTabela() {
        var url = document.getElementById("tipoTabela").value;
        window.location.href = url;
    }

    function salvarScroll() {
        const main = document.querySelector("main");
        localStorage.setItem("scrollPos", main.scrollTop);
    }

    // Função para restaurar o scroll ao carregar a página
    function restaurarScroll() {
        const main = document.querySelector("main");
        const scrollPos = localStorage.getItem("scrollPos");
        if (scrollPos) {
            main.scrollTop = scrollPos;
        }
    }

    // Chama restaurarScroll ao carregar a página
    document.addEventListener("DOMContentLoaded", restaurarScroll);

    // Salva o scroll ao sair da página ou recarregá-la
    window.addEventListener("beforeunload", salvarScroll);
    
    </script>
</body>

</html>