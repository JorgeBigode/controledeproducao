<?php
include_once('config.php'); 
include('protect.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicializando as permissões de edição
$permissoes_edicao = [];
if (isset($_SESSION['username'])) {
    $id_usuario_logado = $_SESSION['id'];
    $sql_permissoes_query = "SELECT campo FROM permissao_edicao WHERE id_usuario = ?";
    $stmt_permissoes = $conexao->prepare($sql_permissoes_query);
    $stmt_permissoes->bind_param("i", $id_usuario_logado);
    $stmt_permissoes->execute();
    
    // Recupera o resultado corretamente
    $result_permissoes = $stmt_permissoes->get_result();  

    if ($result_permissoes->num_rows > 0) {
        // Preenche o array com os campos que o usuário tem permissão para editar
        $permissoes_edicao = array_column($result_permissoes->fetch_all(MYSQLI_ASSOC), 'campo');
    } else {
        echo "Nenhuma permissão encontrada para o usuário.";
    }

    $stmt_permissoes->close();
}

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
    $result->free();

    return $clientes;
}

// Consultando os produtos vinculados aos clientes
$sql_produto_query = "
    SELECT 
        c.pedido AS pedido,
        e.equipamento_pai,
        p.conjunto
    FROM cliente_produto cp
    JOIN cliente c ON cp.id_cliente = c.idcliente
    JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    JOIN equipamento e ON ep.idequipamento = e.idequipamento
    JOIN produto p ON ep.idproduto = p.idproduto
    WHERE c.idcliente IS NOT NULL;  
";

$result_produto = $conexao->query($sql_produto_query);

if (!$result_produto) {
    echo "Erro na consulta de produtos: " . $conexao->error;
    exit;
}

// Consultando os dados dos pedidos
$sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
$result_cliente = $conexao->query($sql_cliente_query);

// Lista de pedidos com produtos vinculados
$sql_base = "
    SELECT DISTINCT c.idcliente, 
           c.pedido, 
           c.cliente AS cliente, 
           c.endereco, 
           c.data_entrega AS data_entrega, 
           c.data_insercao,
           GROUP_CONCAT(cp.id_vinculo SEPARATOR '||') AS id_vinculos,  
           GROUP_CONCAT(
               CONCAT_WS(
                   '||', 
                   COALESCE(cp.quantidade_prod, 'N/A'),
                   COALESCE(cp.lote, 'N/A'),
                   COALESCE(cp.data_engenharia, 'N/A'), 
                   COALESCE(
                       IF(
                           cp.data_prog_fim IS NOT NULL 
                           AND cp.data_prog_fim != '0000-00-00 00:00:00', 
                           'Em programação', 
                           IF(
                               cp.data_programacao IS NOT NULL 
                               AND cp.data_programacao != '0000-00-00 00:00:00' 
                               AND cp.data_programacao != '0000-00-00', 
                               DATE_FORMAT(cp.data_programacao, '%d/%m/%Y'),
                               'N/A'
                           )
                       ), 'N/A'
                   ), 
                   COALESCE(cp.data_prog_fim, 'N/A'),
                   COALESCE(cp.data_pcp, 'N/A'),
                   COALESCE(cp.data_producao, 'N/A'),
                   COALESCE(cp.data_qualidade, 'N/A'),
                   COALESCE(cp.link_pastas, 'N/A'),
                   COALESCE(cp.obs_detalhes, 'N/A'),
                   COALESCE(e.equipamento_pai, 'N/A'),  
                   COALESCE(p.conjunto, 'N/A')           
               ) SEPARATOR '%%'
           ) AS produtos
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    LEFT JOIN equipamento AS e ON ep.idequipamento = e.idequipamento
    LEFT JOIN produto AS p ON ep.idproduto = p.idproduto";

// Buscar o ano selecionado
$ano_selecionado = isset($_GET['ano']) ? $_GET['ano'] : '';

// Adicionar WHERE clause para filtro de ano se necessário
if (!empty($ano_selecionado)) {
    $sql_base .= " WHERE YEAR(c.data_insercao) = ?";
}

$sql_base .= " GROUP BY c.idcliente, c.pedido, c.cliente, c.endereco, c.data_entrega, c.data_insercao 
               ORDER BY c.idcliente DESC";

// Executar a consulta com ou sem o filtro de ano
if (!empty($ano_selecionado)) {
    $stmt = $conexao->prepare($sql_base);
    $stmt->bind_param("i", $ano_selecionado);
    $stmt->execute();
    $result_lista_pedidos = $stmt->get_result();
} else {
    $result_lista_pedidos = $conexao->query($sql_base);
}

if (!$result_lista_pedidos) {
    echo "Erro ao carregar lista de pedidos: " . $conexao->error;
    exit;
}

// Armazenar os resultados da consulta em $clientes_data
if ($result_lista_pedidos) {
    $clientes_data = array();
    while ($row = $result_lista_pedidos->fetch_assoc()) {
        $clientes_data[] = $row;
    }
}

if (!$result_lista_pedidos) {
    echo "Erro ao carregar lista de pedidos: " . $conexao->error;
}

// Usar o mesmo conjunto de dados para o select do menu
$clientes_menu = $clientes_data;

// Consulta de vinculos de produtos
$sql_vinculos = "
    SELECT c.idcliente, c.cliente, e.equipamento_pai, p.conjunto 
    FROM cliente_produto cp
    JOIN cliente c ON cp.id_cliente = c.idcliente
    JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    JOIN equipamento e ON ep.idequipamento = e.idequipamento
    JOIN produto p ON ep.idproduto = p.idproduto
";

$result_vinculos = $conexao->query($sql_vinculos);

// Recebe a data inserida por GET
$data_inserida = $_GET['data_inserida'] ?? '';

$clientes = buscarClientes($conexao);
foreach ($clientes as $cliente_id => &$cliente_data) {
    // Adicionar pedidos
    if (isset($pedidos[$cliente_id])) {
        $cliente_data['pedidos'] = $pedidos[$cliente_id];
    }

    if (isset($produtos[$cliente_id])) {
        $cliente_data['produtos'] = $produtos[$cliente_id];
    }
}
unset($cliente_data); // Remover referência

$clientes_data = array_values($clientes);

// Processamento de atualização por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($conexao) {
        // Recebe os dados do formulário
        $produto_id = $_POST['produto_id'];
        $data_pcp = $_POST['data_pcp'];
        $data_producao = $_POST['data_producao'];
        $data_qualidade = $_POST['data_qualidade'];

        // Prepara a consulta de atualização
        $sql = "UPDATE cliente_produto SET 
                    data_pcp = ?, 
                    data_producao = ?, 
                    data_qualidade = ? 
                WHERE id_vinculo = ?";

        // Preparando a consulta
        $stmt = $conexao->prepare($sql);

        // Verificando se a preparação foi bem-sucedida
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Erro ao preparar a consulta: " . $conexao->error]);
            exit;
        }

        // Vincula os parâmetros e executa a consulta
        $stmt->bind_param("sssi", $data_pcp, $data_producao, $data_qualidade, $produto_id);
        $stmt->execute();

        // Verificando se a atualização foi bem-sucedida
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Datas atualizadas com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Nenhum dado foi atualizado."]);
        }

        // Fecha o statement
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Erro na conexão com o banco de dados."]);
    }
    exit;
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style_obra.css">
    <link rel="stylesheet" href="css/obra_style.css">
    <link rel="stylesheet" href="css/obra.css">
    <title>Obras</title>
    <style>
    .seta-cliente {
        position: sticky;
        top: 0;
        z-index: 100;
        background-color: white;
    }
    /* Select2 custom styles */
    .select2-container {
        width: 300px !important; /* Fixed width */
    }
    
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        padding-left: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    #side-menu1 {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 10px;
    }
    
    #side-menu1 .button {
        white-space: nowrap;
    }

    /* Dropdown width */
    .select2-dropdown {
        width: 300px !important;
    }
    /* Modal Indicadores */
    #modalIndicadores {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content-indicadores {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 5px;
        position: relative;
    }

    .modal-content-indicadores h2 {
        color: #000;
        text-align: center;
        margin-bottom: 20px;
    }

    .modal-content-indicadores .status-indicators {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .modal-content-indicadores .status-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }

    .modal-content-indicadores .status-item span {
        color: #000;
        font-weight: 500;
    }

    .close-modal-indicadores {
        position: absolute;
        right: 10px;
        top: 5px;
        font-size: 28px;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
    }

    .close-modal-indicadores:hover {
        color: #000;
    }

    .circle {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 10px;
    }
    /* Estilos para tornar o header responsivo */
    #header-expandable {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px;
    }
    
    #header-expandable a#logo {
        margin-bottom: 10px;
    }

    @media (min-width: 600px) {
        #header-expandable {
            flex-direction: row;
            justify-content: space-between;
        }
        #header-expandable a#logo {
            margin-bottom: 0;
        }
    }
    /* Ocultar filtros por padrão em telas menores */
    @media (max-width: 600px) {
        #side-menu1,
        #ano,
        #btnIndicadores {
            display: none;
        }
    }
    /* Estilos para centralizar o logo e alinhar os botões lado a lado em telas menores */
    @media (max-width: 600px) {
        #header-expandable {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #header-expandable a#logo {
            margin-right: auto;
            margin-left: auto;
        }

        #openMenu,
        #toggleHeaderButton {
            display: inline-block;
            margin: 0 5px;
        }
    }
    </style>

</head>

<body>
    <header id="header-expandable" class="collapsed">
        <a href="pedido.php" id="logo"><img src="./img/logoSMA.png" alt="Logo" style="max-width: 100px;"></a>
        <button id="btnIndicadores" onclick="abrirIndicadores()" 
                style="padding: 8px 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 10px;">
        Indicadores
        </button>
        <div id="side-menu1">
            <a class="button" href="vinculo_cliente.php">Inserir</a>
            <select id="clienteSelectSideMenu" class="form-control select2-single" style="width: 100%;">
                <option></option>
                <option value="all">Todos os clientes</option>
                <?php foreach ($clientes_menu as $cliente): ?>
                <option value="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
                    <?php echo htmlspecialchars($cliente['pedido']) . " - " . htmlspecialchars($cliente['cliente']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin: 20px;">
        <form method="GET" style="display: inline-block;">
            <select name="ano" id="ano" style="padding: 5px; margin-right: 10px;">
                <option value="">Todos os Anos</option>
                <?php
                $anos_query = "SELECT DISTINCT YEAR(data_insercao) as ano FROM cliente ORDER BY ano DESC";
                $anos_result = $conexao->query($anos_query);
                while($ano = $anos_result->fetch_assoc()) {
                    $selected = (isset($_GET['ano']) && $_GET['ano'] == $ano['ano']) ? 'selected' : '';
                    echo "<option value='".$ano['ano']."' $selected>".$ano['ano']."</option>";
                }
                ?>
            </select>
            <button type="submit" style="padding: 5px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer;">Filtrar</button>
            <?php if(isset($_GET['ano']) && $_GET['ano'] != ''): ?>
                <a href="obra.php" style="padding: 5px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 3px;">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
        <button id="openMenu">&#9776;</button>

        <button id="toggleHeaderButton">&#9662;</button>
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
            <a href="producao.php">PRODUÇÃO</a>
            <div class="submenu">
                <a href="programacao.php">PROGRAMAÇÃO</a>
            </div>
        </div>
        <div class="submenu">
            <a href="FERRAMENTAS/aproveitamento_barras.php">CORTE DE BARRAS</a>
        </div>
        <div class="submenu">
            <a href="logout.php">SAIR</a>
        </div>
        <form method="GET" action="relatorio_status.php">
            <label for="dataMin">Data a partir de:</label>
            <input type="date" name="dataMin" id="dataMin" class="form-control" required>
            <label for="statusFilter">Selecione o Status:</label>
            <select name="status" id="statusFilter" class="form-control">
                <option value="all">Todos</option>
                <option value="engenharia">Engenharia</option>
                <option value="programacao">Programação</option>
                <option value="pcp">PCP</option>
                <option value="producao">Produção</option>
                <option value="qualidade">Qualidade</option>
            </select>
            <button type="submit">STATUS</button>
        </form>
        <button type="button" class="btn btn-primary" onclick="abrirModalRelatorio()">RELATORIO</button>

        <div class="modal fade" id="modalRelatorio" tabindex="-1" role="dialog" aria-labelledby="modalRelatorioLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRelatorioLabel">Selecione o Cliente</h5>
                        <!-- Botão de Fechar -->
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formRelatorio">
                            <div class="form-group">
                                <label for="clienteSelect" class="form-label">Cliente</label>
                                <select class="form-control" id="clienteSelectModal" name="cliente"
                                    aria-label="Selecione um cliente">
                                    <option value="todos">Todos</option>
                                    <!-- Opções de clientes -->
                                    <?php
                                $clientes = mysqli_query($conexao, "SELECT idcliente, cliente, pedido FROM cliente");
                                if (mysqli_num_rows($clientes) > 0) {
                                    while ($cliente = mysqli_fetch_assoc($clientes)) {
                                        echo "<option value='" . $cliente['idcliente'] . "'>" . $cliente['pedido'] . '-' . $cliente['cliente'] . "</option>";
                                    }
                                } else {
                                    echo "<option value='nenhum'>Nenhum cliente encontrado</option>";
                                }
                            ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="gerarRelatorio()">Gerar
                            Relatório</button>
                    </div>
                </div>
            </div>
        </div>

        <a href="adimim.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill"
                viewBox="0 0 16 16">
                <path
                    d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l-.17.31c-.698-1.283-.705-2.686 1.987-1.987l.311.169a1.464 1.464 0 0 1 2.105.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
            </svg>
        </a>
    </nav>
    <main style="overflow-y: auto; max-height: calc(120vh - 50px);">
        <?php foreach ($clientes_data as $index => $cliente): ?>
        <div class="cliente-bloco <?php echo $index === 0 ? 'active' : ''; ?>"
            id="cliente-<?php echo htmlspecialchars($cliente['idcliente']); ?>"
            data-client-id="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
            <div class="seta-cliente">
                <div class="dados-cliente">
                    <h2><?php echo htmlspecialchars($cliente['cliente']); ?></h2>
                    <h2><a href="baixar_pdf.php?id=<?php echo htmlspecialchars($cliente['idcliente']); ?>" target='_blank'>Pedido:</a>
                        <?php echo htmlspecialchars($cliente['pedido']); ?></h2>
                    <h2><a>Localidade:</a> <?php echo htmlspecialchars($cliente['endereco']); ?></h2>
                    <h2><a>Data de Entrega:</a> <?php echo date('d/m/Y', strtotime($cliente['data_entrega'])); ?></h2>
                </div>
                <!-- Botões de Navegação -->
                <div class="navigation-buttons">
                    <button class="prev-btn" data-index="<?php echo $index; ?>" id="prev-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-left-fill" viewBox="0 0 16 16">
                            <path
                                d="M3.86 8.753 5.482 10.548c.646.566 1.658.106 1.658-.753V3.204a1 1 0 0 0-1.659-.753l-5.48 4.796a1 1 0 0 0 0 1.506z" />
                        </svg>
                    </button>
                    <button class="next-btn" data-index="<?php echo $index; ?>" id="next-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-right-fill" viewBox="0 0 16 16">
                            <path
                                d="M12.14 8.753 10.723 10.548c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z" />
                        </svg>
                    </button>
                </div>
            </div>
            <?php
                // Seu código de consulta e exibição de produtos permanece o mesmo
                $query_produtos = "SELECT cp.id_vinculo, cp.quantidade_prod, cp.lote, cp.data_engenharia, cp.data_programacao, cp.data_pcp, cp.data_producao, cp.data_qualidade, cp.tag, cp.data_prog_fim, cp.obs_detalhes, eq.equipamento_pai AS nome_equipamento, pr.conjunto
                   FROM cliente_produto cp
                   JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
                   JOIN equipamento eq ON ep.idequipamento = eq.idequipamento
                   JOIN produto pr ON ep.idproduto = pr.idproduto
                   WHERE cp.id_cliente = ? 
                    ORDER BY  eq.equipamento_pai, cp.data_engenharia ASC";
                    $stmt_produtos = $conexao->prepare($query_produtos);
                    $stmt_produtos->bind_param("i", $cliente['idcliente']);
                    $stmt_produtos->execute();
                    $result_produtos = $stmt_produtos->get_result();
                    $equipamento_pai_anterior = "";
                    $tag_anterior = "";

                if ($result_produtos->num_rows > 0):
                    while ($produto = $result_produtos->fetch_assoc()):
                        if ($produto['nome_equipamento'] !== $equipamento_pai_anterior):
                            if ($equipamento_pai_anterior !== "") {
                                echo "</tbody></table></div>";
                            }

                            $equipamento_pai_anterior = $produto['nome_equipamento'];
                            $tag_anterior = $produto['tag'] ?? '';
                            
                            echo "<div class='equipamento-block'>";
echo "<h5 style='display: flex; align-items: center; gap: 8px;'>
        Equipamento Pai: 
        <a href='buscar_pdf.php?equipamento=" . urlencode($produto['nome_equipamento']) . "' target='_blank'>" . 
        htmlspecialchars($produto['nome_equipamento']) . " -</a>
        <a class='btn btn-sm btn-secondary' style='margin-left: 8px;' onclick='showTagModal(" . htmlspecialchars($produto['id_vinculo']) . ")'>Obs:</a>
        <span>" . htmlspecialchars($tag_anterior) . "</span>
        <a href='print_etiqueta.php?id_vinculo=" . htmlspecialchars($produto['id_vinculo']) . 
           "&cliente_id=" . htmlspecialchars($cliente['idcliente']) . 
           "&equipamento=" . urlencode($produto['nome_equipamento']) . 
           "&obs=" . urlencode($produto['obs_detalhes'] ?? '??') . "' 
           target='_blank'
           class='btn btn-sm btn-secondary' 
           style='display: flex; align-items: center;'>
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-printer' viewBox='0 0 16 16'>
                <path d='M2 1a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2v4.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V9a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm1 4V3h10v2H3z'/>
                <path d='M1 9a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v4H1V9z'/>
            </svg>
            Imprimir Etiqueta
        </a>";
echo "</h5>";
                            echo "<table class='table table-striped'>";
                            echo "<thead>
                                    <tr>
                                        <th>Conjunto</th>
                                        <th>Qtd</th>
                                        <th>Lote</th>
                                        <th>Engenharia</th>
                                        <th>Programação</th>
                                        <th>PCP</th>
                                        <th>Produção</th>
                                        <th>Qualidade</th>
                                        <th>Obs</th>
                                        <th>Ações</th>
                                    </tr>
                                  </thead>
                                  <tbody>";
                            $equipamento_pai_anterior = $produto['nome_equipamento'];
                        endif;
                        $data_qualidade = $produto['data_qualidade'];
                        $data_producao = $produto['data_producao'];
                        $data_pcp = $produto['data_pcp'];
                        $data_programacao = $produto['data_programacao'];
                        $data_engenharia = $produto['data_engenharia'];
                        $classe_status = '';
                        if (!empty($data_qualidade) && $data_qualidade !== '0000-00-00 00:00:00') {
                            $classe_status = 'linha-qualidade';
                        } elseif (!empty($data_producao) && $data_producao !== '0000-00-00 00:00:00') {
                            $classe_status = 'linha-producao';
                        } elseif (!empty($data_pcp) && $data_pcp !== '0000-00-00 00:00:00') {
                            $classe_status = 'linha-pcp';
                        } elseif (!empty($data_programacao) && $data_programacao !== '0000-00-00 00:00:00') {
                            $classe_status = 'linha-programacao';
                        } elseif (!empty($data_engenharia) && $data_engenharia !== '0000-00-00 00:00:00') {
                            $classe_status= 'linha-engenharia';
                        }
                ?>
            <tr class="<?php echo $classe_status; ?>">
                <td class="single-line" title="<?php echo htmlspecialchars($produto['conjunto'] ?? ''); ?>">
                    <a href="buscar_pdf.php?conjunto=<?php echo urlencode($produto['conjunto'] ?? ''); ?>"
                        target="_blank">
                        <?php echo htmlspecialchars($produto['conjunto'] ?? ''); ?>
                    </a>
                </td>
                <td class="single-line" title="<?php echo htmlspecialchars($produto['quantidade_prod'] ?? ''); ?>">
                    <?php echo htmlspecialchars($produto['quantidade_prod'] ?? ''); ?>
                </td>
                <td class="single-line" title="<?php echo htmlspecialchars($produto['lote'] ?? ''); ?>">
                    <?php echo htmlspecialchars($produto['lote'] ?? ''); ?>
                </td>
                <td class="single-line"
                    title="<?php echo !empty($produto['data_engenharia']) && $produto['data_engenharia'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_engenharia'])) : 'N/A'; ?>">
                    <?php echo !empty($produto['data_engenharia']) && $produto['data_engenharia'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_engenharia'])) : 'N/A'; ?>
                </td>
                <td class="single-line" title="<?php 
        $dataProgFim = $produto['data_prog_fim'] ?? null;
        $dataProgramacao = $produto['data_programacao'] ?? null;
        echo !empty($dataProgramacao) ? date('d/m/Y H:i', strtotime($dataProgramacao)) : (!empty($dataProgFim) ? 'Em programação' : 'N/A');
    ?>">
                    <?php 
            if (!empty($dataProgramacao) && 
                $dataProgramacao !== '0000-00-00' && 
                $dataProgramacao !== '0000-00-00 00:00:00' && 
                $dataProgramacao !== '30/11/-0001') {
                echo date('d/m/Y H:i', strtotime($dataProgramacao));
            } elseif (!empty($dataProgFim) && $dataProgFim !== '0000-00-00 00:00:00') {
                echo '<span style="color: white;">Em programação</span>';
            } else {
                echo 'N/A';
            }
        ?>
                </td>
                <td class="single-line"
                    title="<?php echo !empty($produto['data_pcp']) && $produto['data_pcp'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_pcp'])) : 'N/A'; ?>">
                    <?php echo !empty($produto['data_pcp']) && $produto['data_pcp'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_pcp'])) : 'N/A'; ?>
                </td>
                <td class="single-line"
                    title="<?php echo !empty($produto['data_producao']) && $produto['data_producao'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_producao'])) : 'N/A'; ?>">
                    <?php echo !empty($produto['data_producao']) && $produto['data_producao'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_producao'])) : 'N/A'; ?>
                </td>
                <td class="single-line"
                    title="<?php echo !empty($produto['data_qualidade']) && $produto['data_qualidade'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_qualidade'])) : 'N/A'; ?>">
                    <?php echo !empty($produto['data_qualidade']) && $produto['data_qualidade'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($produto['data_qualidade'])) : 'N/A'; ?>
                </td>
                <td class="single-line"
                    title="<?php echo !empty($produto['obs_detalhes']) ? htmlspecialchars($produto['obs_detalhes']) : 'N/A'; ?>">
                    <?php echo htmlspecialchars($produto['obs_detalhes'] ?? ''); ?>
                </td>
                <td>
                    <a href="#" class="btn btn-sm btn-primary btn-edit-data"
                        data-id="<?php echo $produto['id_vinculo']; ?>"
                        data-data-pcp="<?php echo $produto['data_pcp']; ?>"
                        data-data-producao="<?php echo $produto['data_producao']; ?>"
                        data-data-qualidade="<?php echo $produto['data_qualidade']; ?>">
                        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor'
                            class='bi bi-pencil-square' viewBox='0 0 16 16'>
                            <path
                                d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293z' />
                            <path fill-rule='evenodd'
                                d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z' />
                        </svg>
                    </a>
                </td>


            </tr>
            <?php endwhile; ?>
            <?php echo "</tbody></table></div>"; ?>
            <?php endif; ?>
        </div>
        </div>
        </div>
        <div class="modal" id="tagModal">
            <div class="modal-content">
                <span class="close" onclick="closeTagModal()" style="color: black;">&times;</span>
                <h2>Inserir Obs</h2>
                <form id="tagForm" method="post" action="inserir_tag.php">
                    <input type="hidden" id="vinculoId" name="id_vinculo" value="">
                    <label for="tag">Obs:</label>
                    <input type="text" id="tag" name="tag" required>
                    <button type="submit">Salvar</button>
                </form>
            </div>
        </div>
        <div id="editarDatasModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editarDatasLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarDatasLabel">Editar Datas</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form-editar-datas" method="post">
                            <input type="hidden" id="produto-id" name="produto_id">

                            <?php if (in_array('data_pcp', $permissoes_edicao)): ?>
                            <label for="data_pcp">Data PCP:</label>
                            <input type="datetime-local" id="data_pcp" name="data_pcp"
                                value="<?= htmlspecialchars($data_pcp) ?>" onchange="validarDatas()">
                            <br><br>
                            <?php endif; ?>

                            <?php if (in_array('data_producao', $permissoes_edicao)): ?>
                            <label for="data_producao">Data de Produção:</label>
                            <input type="date" id="data_producao" name="data_producao"
                                value="<?= htmlspecialchars($data_producao) ?>" onchange="validarDatas()">
                            <br><br>
                            <?php endif; ?>

                            <?php if (in_array('data_qualidade', $permissoes_edicao)): ?>
                            <label for="data_qualidade">Data de Qualidade:</label>
                            <input type="datetime-local" id="data_qualidade" name="data_qualidade"
                                value="<?= htmlspecialchars($data_qualidade) ?>" onchange="validarDatas()">
                            <br><br>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <?php endforeach; ?>
    </main>

    <!-- Modal Indicadores -->
    <div id="modalIndicadores" style="display: none;">
        <div class="modal-content-indicadores">
            <span class="close-modal-indicadores">&times;</span>
            <h2 style="color: #000;">Status do Pedido</h2>
            <div class="status-indicators" style="list-style: none; padding: 0;">
                <div class="status-item" style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <div class="circle status1"></div>
                    <span style="color: #000; font-weight: 500;">AGUARDANDO PROGRAMAÇÃO</span>
                </div>
                <div class="status-item" style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <div class="circle status2"></div>
                    <span style="color: #000; font-weight: 500;">PROGRAMADO</span>
                </div>
                <div class="status-item" style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <div class="circle status3"></div>
                    <span style="color: #000; font-weight: 500;">EM PRODUÇÃO</span>
                </div>
                <div class="status-item" style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <div class="circle status4"></div>
                    <span style="color: #000; font-weight: 500;">FINALIZADO</span>
                </div>
                <div class="status-item" style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <div class="circle status5"></div>
                    <span style="color: #000; font-weight: 500;">QUALIDADE - ENTREGA</span>
                </div>
            </div>
        </div>
    </div>

    <script src="script/scr_obra.js" defer></script>
    <script>
    const chartData = <?php echo json_encode($clientes_data); ?>;

    // Função para formatar a data para o formato de input
    function formatDateForInput(dateStr) {
        var date = new Date(dateStr);

        if (isNaN(date.getTime())) {
            console.error("Data inválida:", dateStr);
            return null; // ou um valor padrão, como uma string vazia
        }

        var yyyy = date.getFullYear();
        var mm = (date.getMonth() + 1).toString().padStart(2, '0');
        var dd = date.getDate().toString().padStart(2, '0');
        var hh = date.getHours().toString().padStart(2, '0');
        var min = date.getMinutes().toString().padStart(2, '0');

        return yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + min;
    }

    $(document).ready(function() {
        // Initialize Select2 for client dropdown
        $('#clienteSelectSideMenu').select2({
            placeholder: 'Selecione o cliente',
            allowClear: true,
            width: '300px',
            language: {
                noResults: function() {
                    return "Nenhum resultado encontrado";
                }
            },
            dropdownAutoWidth: false
        });

        // Preserve the original onchange functionality
        $('#clienteSelectSideMenu').on('select2:select', function (e) {
            selecionarCliente();
        });

        // Quando o modal for mostrado, preenche os campos com os dados do produto
        $('.btn-edit-data').on('click', function(e) {
            e.preventDefault();

            var id_vinculo = $(this).data('id');
            var data_pcp = $(this).data('data-pcp');
            var data_producao = $(this).data('data-producao');
            var data_qualidade = $(this).data('data-qualidade');

            // Preenche o modal com as informações
            $('#produto-id').val(id_vinculo);
            $('#data_pcp').val(data_pcp);
            $('#data_producao').val(data_producao);
            $('#data_qualidade').val(data_qualidade);

            // Exibe o modal
            $('#editarDatasModal').modal('show');
        });

        $('#form-editar-datas').on('submit', function(e) {
            e.preventDefault();

            var produtoId = $('#produto-id').val();
            var dataPcp = $('#data_pcp').val();
            var dataProducao = $('#data_producao').val();
            var dataQualidade = $('#data_qualidade').val();

            // Função para verificar se o campo data foi modificado
            function isModified(currentValue, field) {
                return currentValue !== $('#' + field).data('original-value');
            }

            // Coleta os dados a serem enviados, incluindo apenas os campos modificados
            var formData = {
                produto_id: produtoId
            };

            if (dataPcp && isModified(dataPcp, 'data_pcp')) {
                formData.data_pcp = dataPcp;
            }
            if (dataProducao && isModified(dataProducao, 'data_producao')) {
                formData.data_producao = dataProducao;
            }
            if (dataQualidade && isModified(dataQualidade, 'data_qualidade')) {
                formData.data_qualidade = dataQualidade;
            }

            // Verificação final: se nenhum dado foi alterado, exibir uma mensagem e interromper o processo
            if (Object.keys(formData).length <= 1) {
                alert('Nenhuma data foi modificada ou as datas estão com valores inválidos.');
                return;
            }

            // Realiza a chamada AJAX para o backend
            $.ajax({
                url: 'dados_datas.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    if (response && response.success) {
                        // Atualiza os campos no frontend com os novos valores
                        if (response.data_pcp) {
                            $('#data_pcp_' + produtoId).text(response.data_pcp);
                            $('#data_pcp').val(response.data_pcp);
                        }
                        if (response.data_producao) {
                            $('#data_producao_' + produtoId).text(response.data_producao);
                            $('#data_producao').val(response.data_producao);
                        }
                        if (response.data_qualidade) {
                            $('#data_qualidade_' + produtoId).text(response.data_qualidade);
                            $('#data_qualidade').val(response.data_qualidade);
                        }

                        $('#editarDatasModal').modal('hide');
                        alert('Dados atualizados com sucesso!');
                        location.reload();
                    } else if (response && response.error) {
                        alert(response.message);
                    } else {
                        alert(
                            'Erro ao atualizar os dados. Resposta do servidor inesperada.'
                        );
                    }
                },
                error: function() {
                    alert('Erro ao salvar os dados.');
                }
            });
        });


    });
    window.addEventListener('load', () => {
        // Usando setTimeout para garantir que o DOM tenha sido completamente carregado
        setTimeout(() => {
            var clienteId = localStorage.getItem("clienteSelecionado");
            console.log("Cliente ID recuperado do localStorage: " +
                clienteId); // Verifique se o valor está sendo recuperado corretamente

            if (clienteId) {
                // Atribui o valor ao select com id 'clienteSelectSideMenu'
                document.getElementById('clienteSelectSideMenu').value = clienteId;
                console.log("Valor do select clienteSelectSideMenu após atribuição: " +
                    document
                    .getElementById('clienteSelectSideMenu').value);

                // Chama a função para mostrar o bloco correto
                selecionarCliente();
            }
        }, 0); // Delay 0 é para garantir que o código será executado após o carregamento do DOM
    });


    function selecionarCliente() {
        var clienteId = clienteSelectSideMenu ? clienteSelectSideMenu.value : null;
        $(".cliente-bloco").hide();
        if (clienteId === "all") {
            $(".cliente-bloco").show();
        } else if (clienteId) {
            $("#cliente-" + clienteId).show();
        }
        localStorage.setItem("clienteSelecionado", clienteId);
    }

    function logout() {
        localStorage.removeItem("clienteSelecionado");
        console.log("Cliente removido do localStorage");
    }

    function abrirModalRelatorio() {
        // Código para abrir o modal
        const modal = new bootstrap.Modal(document.getElementById('modalRelatorio'));
        modal.show();
    }

    function gerarRelatorio() {
        // Obter o valor do cliente selecionado no select
        const clienteSelect = document.getElementById('clienteSelectModal');
        const clienteId = clienteSelect.value;

        // Verificar se o cliente foi selecionado
        if (clienteId === 'nenhum') {
            alert('Por favor, selecione um cliente válido!');
            return;
        }

        // Redirecionar para gerar_relatorio.php com o clienteId como parâmetro
        if (clienteId === 'todos') {
            window.location.href = 'gerar_relatorio.php';
        } else {
            window.location.href = `gerar_relatorio.php?cliente_id=${clienteId}`;
        }
    }

    function showTagModal(idVinculo) {
        document.getElementById('vinculoId').value = idVinculo;
        $('#tagModal').modal('show');
    }

    function closeTagModal() {
        $('#tagModal').modal('hide');
    }

    function imprimirEtiquetaDireta(idVinculo, idCliente, equipamentoPai) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'imprimir_etiqueta.php?id=' + idVinculo + '&id_cliente=' + idCliente + '&equipamento_pai=' + encodeURIComponent(equipamentoPai), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert(xhr.responseText);
            } else {
                alert('Erro ao imprimir etiqueta');
            }
        };
        xhr.onerror = function() {
            alert('Erro ao imprimir etiqueta');
        };
        xhr.send();
    }

    function validarDatas() {
        const dataPcp = document.getElementById('data_pcp');
        const dataProducao = document.getElementById('data_producao');
        const dataQualidade = document.getElementById('data_qualidade');

        // Habilita/desabilita data_producao baseado na data_pcp
        if (dataPcp && dataProducao) {
            if (dataPcp.value) {
                dataProducao.disabled = false;
            } else {
                dataProducao.disabled = true;
                dataProducao.value = '';
            }
        }

        // Habilita/desabilita data_qualidade baseado na data_producao
        if (dataProducao && dataQualidade) {
            if (dataProducao.value) {
                dataQualidade.disabled = false;
            } else {
                dataQualidade.disabled = true;
                dataQualidade.value = '';
            }
        }
    }

    // Executa a validação quando o modal é aberto
    $('#editarDatasModal').on('show.bs.modal', function () {
        validarDatas();
    });

    // Modal Indicadores
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalIndicadores');
        const closeBtn = document.querySelector('.close-modal-indicadores');

        // Abrir modal com duplo clique no menu de status
        document.getElementById('btnIndicadores').addEventListener('click', function() {
            modal.style.display = 'block';
        });

        // Fechar com o botão X
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        // Fechar clicando fora do modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggleHeaderButton');
        const sideMenu = document.getElementById('side-menu1');
        const anoFilter = document.getElementById('ano');
        const btnIndicadores = document.getElementById('btnIndicadores');

        toggleButton.addEventListener('click', function() {
            if (window.innerWidth <= 600) { // Somente para telas menores
                const isHidden = sideMenu.style.display === 'none';
                sideMenu.style.display = isHidden ? 'block' : 'none';
                anoFilter.style.display = isHidden ? 'block' : 'none';
                btnIndicadores.style.display = isHidden ? 'block' : 'none';
            }
        });
    });
    </script>


</body>

</html>