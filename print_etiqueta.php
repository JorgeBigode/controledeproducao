<?php
include_once('config.php');
include('protect.php');

// Configurar fuso horário para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a tabela existe e criar se necessário
$check_table = "SHOW TABLES LIKE 'config_impressao'";
$table_exists = $conexao->query($check_table)->num_rows > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE config_impressao (
        id INT PRIMARY KEY,
        margem_topo DECIMAL(5,2) DEFAULT 4.5,
        margem_esquerda DECIMAL(5,2) DEFAULT 0,
        largura_etiqueta DECIMAL(5,2) DEFAULT 101,
        altura_etiqueta DECIMAL(5,2) DEFAULT 75,
        rotacao INT DEFAULT 0,
        temperatura INT DEFAULT 0,
        velocidade INT DEFAULT 0,
        densidade INT DEFAULT 0
    )";
    
    $conexao->query($create_table);
    
    // Inserir configuração padrão
    $insert_default = "INSERT INTO config_impressao (id, margem_topo, margem_esquerda, largura_etiqueta, altura_etiqueta, rotacao, temperatura, velocidade, densidade)
                      VALUES (1, 4.5, 0, 101, 75, 0, 0, 0, 0)";
    $conexao->query($insert_default);
} else {
    // Verificar se a coluna rotacao existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'rotacao'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna rotacao
        $add_column = "ALTER TABLE config_impressao ADD COLUMN rotacao INT DEFAULT 0";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna temperatura existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'temperatura'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna temperatura
        $add_column = "ALTER TABLE config_impressao ADD COLUMN temperatura INT DEFAULT 0";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna velocidade existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'velocidade'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna velocidade
        $add_column = "ALTER TABLE config_impressao ADD COLUMN velocidade INT DEFAULT 0";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna densidade existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'densidade'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna densidade
        $add_column = "ALTER TABLE config_impressao ADD COLUMN densidade INT DEFAULT 0";
        $conexao->query($add_column);
    }
}

// Buscar configurações de impressão
$sql = "SELECT * FROM config_impressao WHERE id = 1";
$result = $conexao->query($sql);
$config = $result->fetch_assoc();

if (!$config) {
    $config = [
        'margem_topo' => 4.5,
        'margem_esquerda' => 0,
        'largura_etiqueta' => 101,
        'altura_etiqueta' => 75,
        'rotacao' => 0,
        'temperatura' => 0,
        'velocidade' => 0,
        'densidade' => 0
    ];
}

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['id_vinculo']) && isset($_GET['cliente_id']) && isset($_GET['equipamento'])) {
    $id_vinculo = $_GET['id_vinculo'];
    $cliente_id = $_GET['cliente_id'];
    $equipamento = $_GET['equipamento'];
    $observacao = isset($_GET['obs']) ? $_GET['obs'] : '??';

    // Buscar informações do cliente
    $query_cliente = "SELECT pedido, cliente, endereco FROM cliente WHERE idcliente = ?";
    $stmt_cliente = $conexao->prepare($query_cliente);
    $stmt_cliente->bind_param("i", $cliente_id);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    $cliente = $result_cliente->fetch_assoc();

    // Buscar informações dos conjuntos
    $query_conjuntos = "SELECT pr.conjunto, cp.quantidade_prod, cp.lote, cp.tag
                        FROM cliente_produto cp
                        JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
                        JOIN equipamento eq ON ep.idequipamento = eq.idequipamento
                        JOIN produto pr ON ep.idproduto = pr.idproduto
                        WHERE cp.id_cliente = ? AND eq.equipamento_pai = ?
                        ORDER BY pr.conjunto";

    $stmt_conjuntos = $conexao->prepare($query_conjuntos);
    $stmt_conjuntos->bind_param("is", $cliente_id, $equipamento);
    $stmt_conjuntos->execute();
    $result_conjuntos = $stmt_conjuntos->get_result();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Etiqueta - <?php echo htmlspecialchars($equipamento); ?></title>
        <style>
            @page {
                size: 105mm 84mm;
                margin: 0;
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                font-weight: bold;
            }
            .etiqueta-container {
                position: relative;
                margin-bottom: 20px;
                padding: 10px;
            }
            .select-etiqueta {
                position: absolute;
                left: 30px;
                font-size: 14px;
                font-weight: normal;
                display: flex;
                align-items: center;
                gap: 3px;
                z-index: 1000;
            }
            .etiqueta {
                background: white;
                padding: 5px;
                border: 2px solid black;
                width: <?php echo $config['largura_etiqueta']; ?>mm;
                height: <?php echo $config['altura_etiqueta']; ?>mm;
                margin-top: <?php echo $config['margem_topo']; ?>mm;
                margin-left: <?php echo $config['margem_esquerda']; ?>mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
                transform: rotate(<?php echo $config['rotacao']; ?>deg);
            }
            .box {
                border: 1px solid black;
                padding: 3px;
                margin-bottom: 2px;
                text-align: center;
                overflow: hidden;
                word-wrap: break-word;
                line-height: 1.2;
            }
            .pedido {
                min-height: 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: relative;
            }
            .cliente {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .equipamento-info {
                min-height: 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .conjunto-info {
                min-height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .quantidade {
                min-height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .data {
                position: absolute;
                top: 2px;
                right: 2px;
                font-size: 12px;
                border: 1px solid black;
                padding: 1px 3px;
            }
            .controls {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: white;
                padding: 10px;
                border: 1px solid black;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 1000;
                display: flex;
                gap: 10px;
            }
            @media print {
                @page {
                    size: 105mm 84mm;
                    margin: 0;
                }
                html, body {
                    width: 105mm;
                    height: 84mm;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .etiqueta-container {
                    width: 105mm;
                    height: 84mm;
                    margin: 0 !important;
                    padding: 0 !important;
                    page-break-after: avoid !important;
                    page-break-before: always !important;
                    page-break-inside: avoid !important;
                    position: relative !important;
                }
                .etiqueta-container:first-of-type {
                    page-break-before: avoid !important;
                }
                .etiqueta-container:last-of-type {
                    page-break-after: avoid !important;
                }
                .etiqueta {
                    background: white !important;
                    padding: 5px !important;
                    border: 2px solid black !important;
                    width: <?php echo $config['largura_etiqueta']; ?>mm !important;
                    height: <?php echo $config['altura_etiqueta']; ?>mm !important;
                    margin-top: <?php echo $config['margem_topo']; ?>mm !important;
                    margin-left: <?php echo $config['margem_esquerda']; ?>mm !important;
                    box-sizing: border-box !important;
                    transform: rotate(<?php echo $config['rotacao']; ?>deg) !important;
                    position: absolute !important;
                }
                .box {
                    page-break-inside: avoid !important;
                }
                .no-print, 
                .select-etiqueta, 
                .controls {
                    display: none !important;
                }
                .etiqueta[data-selected="false"],
                .etiqueta-container:has(.etiqueta[data-selected="false"]) {
                    display: none !important;
                }
                /* Esconder elementos não selecionados */
                .etiqueta-container:has(.etiqueta[data-selected="false"]) {
                    display: none !important;
                    page-break-after: avoid !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="controls">
            <button onclick="selecionarTodas()">Selecionar Todas</button>
            <button onclick="deselecionarTodas()">Deselecionar Todas</button>
            <button onclick="imprimirSelecionadas()">Imprimir Selecionadas</button>
            <button onclick="window.close()">Fechar</button>
            <a href="config_impressao.php"><button type="button">Configurar Impressão</button></a>
        </div>
        <?php 
        $index = 0;
        while ($conjunto = $result_conjuntos->fetch_assoc()) { 
        ?>
            <div class="etiqueta-container">
                <label class="select-etiqueta">
                    <input type="checkbox" class="etiqueta-checkbox" checked data-index="<?php echo $index; ?>">
                    Selecionar esta etiqueta
                </label>
                <div class="etiqueta" data-selected="true" data-index="<?php echo $index; ?>">
                    <div class="box pedido">
                        <span>PED: <?php echo htmlspecialchars($cliente['pedido']); ?></span>
                        <span class="data"><?php echo date('d/m/Y H:i'); ?></span>
                    </div>
                    <div class="box cliente">
                        <?php echo htmlspecialchars($cliente['cliente']) . ' - ' . htmlspecialchars($cliente['endereco']); ?>
                    </div>
                    <div class="box equipamento-info">
                        <div class="medida"><?php echo htmlspecialchars($equipamento); ?></div>
                    </div>
                    <div class="box conjunto-info">
                        <?php echo htmlspecialchars($conjunto['conjunto']); ?>
                    </div>
                    <div class="box quantidade">QTDE: <?php echo htmlspecialchars($conjunto['quantidade_prod']); ?></div>
                </div>
            </div>
        <?php 
            $index++;
        } 
        ?>
        <script>
            function ajustarFonte(elemento, tamanhoMaximo, tamanhoMinimo) {
                let tamanho = tamanhoMaximo;
                elemento.style.fontSize = tamanho + 'px';
                
                while ((elemento.scrollHeight > elemento.offsetHeight || elemento.scrollWidth > elemento.offsetWidth) && tamanho > tamanhoMinimo) {
                    tamanho--;
                    elemento.style.fontSize = tamanho + 'px';
                }
                
                // Se ainda houver overflow, forçar duas linhas
                if (elemento.scrollHeight > elemento.offsetHeight) {
                    elemento.style.display = '-webkit-box';
                    elemento.style.webkitLineClamp = '2';
                    elemento.style.webkitBoxOrient = 'vertical';
                } else {
                    elemento.style.display = 'flex';
                    elemento.style.webkitLineClamp = 'none';
                }
            }

            function ajustarTodasFontes() {
                document.querySelectorAll('.pedido span:first-child').forEach(el => ajustarFonte(el, 24, 12));
                document.querySelectorAll('.cliente').forEach(el => ajustarFonte(el, 30, 12));
                document.querySelectorAll('.equipamento-info').forEach(el => ajustarFonte(el, 24, 12));
                document.querySelectorAll('.conjunto-info').forEach(el => ajustarFonte(el, 24, 12));
                document.querySelectorAll('.quantidade').forEach(el => ajustarFonte(el, 42, 16));
            }

            // Ajustar fontes inicialmente
            ajustarTodasFontes();

            // Ajustar fontes quando a janela for redimensionada
            window.addEventListener('resize', ajustarTodasFontes);

            function selecionarTodas() {
                document.querySelectorAll('.etiqueta-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                    atualizarEtiqueta(checkbox);
                });
            }

            function deselecionarTodas() {
                document.querySelectorAll('.etiqueta-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                    atualizarEtiqueta(checkbox);
                });
            }

            function atualizarEtiqueta(checkbox) {
                const index = checkbox.dataset.index;
                const etiqueta = document.querySelector(`.etiqueta[data-index="${index}"]`);
                etiqueta.dataset.selected = checkbox.checked;
            }

            document.querySelectorAll('.etiqueta-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', () => atualizarEtiqueta(checkbox));
            });

            function imprimirSelecionadas() {
                // Configurar preferências de impressão para Argox
                const configurarImpressora = `
                    ^Q<?php echo $config['altura_etiqueta']; ?>,0,0
                    ^W<?php echo $config['largura_etiqueta']; ?>
                    ^H<?php echo $config['temperatura']; ?>
                    ^P<?php echo $config['velocidade']; ?>
                    ^D<?php echo $config['densidade']; ?>
                    ^L
                `;
                
                // Criar elemento oculto com configurações
                const configElement = document.createElement('div');
                configElement.style.display = 'none';
                configElement.innerHTML = configurarImpressora;
                document.body.appendChild(configElement);
                
                window.print();
                
                // Remover elemento após impressão
                document.body.removeChild(configElement);
            }
        </script>
    </body>
    </html>
    <?php
}
?>
