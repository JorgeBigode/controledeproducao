<?php
include_once('config.php');
include('protect.php');
include('menu.php');
verificarAcesso();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['idcliente'])) {
    header('Content-Type: application/json');

    $idcliente = intval($_GET['idcliente']);
    
    // Verifica a conexão
    if ($conexao->connect_error) {
        echo json_encode(['error' => 'Erro de conexão: ' . $conexao->connect_error]);
        exit;
    }

    $stmt = $conexao->prepare("SELECT * FROM cliente WHERE idcliente = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Erro ao preparar consulta: ' . $conexao->error]);
        exit;
    }

    $stmt->bind_param("i", $idcliente);
    $stmt->execute();
    $result_pedido = $stmt->get_result();

    if ($result_pedido->num_rows > 0) {
        echo json_encode($result_pedido->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Pedido não encontrado']);
    }
    exit;
}
   

$sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
$result_cliente = $conexao->query($sql_cliente_query);

if (!$result_cliente) {
    echo json_encode(['error' => 'Erro ao buscar clientes: ' . $conexao->error]);
    exit;
}

$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente AS cliente, 
           c.endereco, 
           c.data_entrega AS data_entrega,
           pt.idpedidos_tr,
           pt.status,
           GROUP_CONCAT(
               CONCAT_WS(
                   '||', 
                   COALESCE(pt.modelo, 'N/A'), 
                   COALESCE(pt.montagem, 'N/A'), 
                   COALESCE(pt.frete, 'N/A'), 
                   COALESCE(pt.status, 'N/A'), 
                   COALESCE(pt.frequencia, 'N/A'), 
                   COALESCE(pt.bica, 'N/A'),
                   COALESCE(pt.n_serie, 'N/A'),
                   COALESCE(pt.observacao, 'N/A')
               ) SEPARATOR '%%'
           ) AS detalhes_pedido
    FROM cliente AS c
    JOIN pedidos_tr AS pt ON c.idcliente = pt.idtr_pedido
    GROUP BY c.idcliente, pt.idpedidos_tr, pt.status
    ORDER BY c.idcliente DESC";

$result_lista_pedidos = $conexao->query($sql_lista_pedidos);

if (!$result_lista_pedidos) {
    echo json_encode(['error' => 'Erro ao carregar lista de pedidos: ' . $conexao->error]);
    exit;
}

if (isset($_POST['idtr_pedido']) && isset($_POST['modelo'])) {
    $idtr_pedido = intval($_POST['idtr_pedido']);
    $modelo = $_POST['modelo'];
    // Preparar a consulta SQL para atualizar o modelo
    $stmt = $conexao->prepare("UPDATE pedidos_tr SET modelo = ? WHERE idpedidos_tr = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Erro ao preparar consulta: ' . $conexao->error]);
        exit;
    }

    $stmt->bind_param("si", $modelo, $idtr_pedido);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Modelo atualizado com sucesso!']);
    } else {
        echo json_encode(['error' => 'Erro ao atualizar o modelo: ' . $stmt->error]);
    }

    $stmt->close();
}

if ($statusSelecionado !== 'todos') {
    $sql_lista_pedidos .= " WHERE pt.status = ?";
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style_tr.css">
    <title>TRILHADEIRA</title>
    <style>
    .count {
        font-size: 20px;
        color: black;
    }

    #modal-overlay {
        display: none;
        /* Inicia o modal oculto */
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        /* Fundo escuro semitransparente */
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        /* Certifique-se de que está acima de outros elementos */
    }

    #modal {
        background: white;
        padding: 20px;
        width: 80%;
        /* Ajuste conforme necessário */
        max-width: 500px;
        /* Define um limite de largura */
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        /* Sombra para dar destaque */
        border-radius: 8px;
        text-align: center;
    }  

@media (max-width: 768px) {
    .buttons-e-circulos {
        display: none;  /* Os botões começam ocultos em telas pequenas */
    }

    .seta-toggle {
        cursor: pointer;
        display: block;  /* Exibe a seta como um bloco */
        padding: 10px;
        text-align: center;
    }

    .seta-toggle button {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color:white;
    }

    /* Adiciona rolagem horizontal no conteúdo do pedido em telas pequenas */
    .pedido-detalhe.bloco-cliente {
        overflow-x: auto;  /* Adiciona rolagem horizontal */
        white-space: nowrap;  /* Garante que o conteúdo não quebre linha */
    }

    .pedido-detalhe.bloco-cliente table {
        min-width: 800px;  /* Ajuste conforme necessário */
    }

    /* Classe para mostrar os botões quando a seta for clicada */
    .buttons-e-circulos.show {
        display: flex;  /* Exibe os botões quando a classe .show é aplicada */
        gap: 10px;
        justify-content: flex-start;
        align-items: center;
    }
}

/* Estilo para telas maiores (mínimo 769px) */
@media (min-width: 769px) {
    .seta-toggle {
        display: none;  /* A seta é escondida em telas maiores */
    }

    .buttons-e-circulos {
        display: flex;  /* Exibe os botões em telas maiores */
        gap: 10px;  /* Espaçamento entre os botões */
        justify-content: flex-start;  /* Alinha os botões à esquerda */
        align-items: center;  /* Alinha os botões verticalmente */
    }
}


    </style>
</head>

<body>
    <div id="modal-overlay" style="display:none;">
        <div id="modal">
            <h2>Vincular Modelo ao Cliente</h2>
            <form id="formAdicionar">
                <input type="hidden" id="idtr_pedido_adicionar" name="idtr_pedido">
                <select id="clienteSelect" name="cliente">
                    <option value="">Selecione um Cliente</option>
                    <?php
                            while ($cliente = $result_cliente->fetch_assoc()) {
                                echo "<option value=\"" . htmlspecialchars($cliente['idcliente']) . "\">" . htmlspecialchars($cliente['pedido']) . "</option>";
                            }
                            ?>
                </select>
                <select id="modeloSelect" name="modelo">
                    <option value="">Selecione um Modelo</option>
                    <option value="TR-60">TR-60</option>
                    <option value="TR-80">TR-80</option>
                    <option value="TR-80 BD">TR-80 BD</option>
                    <option value="TR-100">TR-100</option>
                    <option value="TR-120">TR-120</option>
                </select>
                <button type="button" onclick="salvarVinculo()">Salvar</button>
                <button type="button" onclick="fecharModal()">Cancelar</button>
            </form>
        </div>
    </div>
    <main id="pedidoDetails">
        <div id="listaPedidos">
            <?php 
            if(isset($_GET['ano']) && $_GET['ano'] != '') {
                $ano = $_GET['ano'];
                $sql_lista_pedidos = "
                    SELECT c.idcliente, 
                           c.pedido, 
                           c.cliente AS cliente, 
                           c.endereco, 
                           c.data_entrega AS data_entrega,
                           c.data_insercao,
                           pt.idpedidos_tr,
                           pt.status,
                           GROUP_CONCAT(
                               CONCAT_WS(
                                   '||', 
                                   COALESCE(pt.modelo, 'N/A'), 
                                   COALESCE(pt.montagem, 'N/A'), 
                                   COALESCE(pt.frete, 'N/A'), 
                                   COALESCE(pt.status, 'N/A'), 
                                   COALESCE(pt.frequencia, 'N/A'), 
                                   COALESCE(pt.bica, 'N/A'),
                                   COALESCE(pt.n_serie, 'N/A'),
                                   COALESCE(pt.observacao, 'N/A')
                               ) SEPARATOR '%%'
                           ) AS detalhes_pedido
                    FROM cliente AS c
                    JOIN pedidos_tr AS pt ON c.idcliente = pt.idtr_pedido
                    WHERE YEAR(c.data_insercao) = ?
                    GROUP BY c.idcliente, pt.idpedidos_tr, pt.status
                    ORDER BY c.idcliente DESC";
                
                $stmt = $conexao->prepare($sql_lista_pedidos);
                $stmt->bind_param("i", $ano);
                $stmt->execute();
                $result_lista_pedidos = $stmt->get_result();
            } else {
                $result_lista_pedidos = $conexao->query($sql_lista_pedidos);
            }

            if ($result_lista_pedidos->num_rows > 0) {
                while ($row = $result_lista_pedidos->fetch_assoc()) {
                    $mostrar_pedido = true;

                    // Verifica se a busca única corresponde a algum campo
                    if (!empty($busca_unica)) {
                        if (
                            stripos($row['pedido'], $busca_unica) === false &&
                            stripos($row['cliente'], $busca_unica) === false &&
                            stripos($row['endereco'], $busca_unica) === false
                        ) {
                            $mostrar_pedido = false;
                        }
                    }

                    if (!empty($busca_data) && $row['data_entrega'] != $busca_data) {
                        $mostrar_pedido = false;
                    }

                    if ($mostrar_pedido) {
                        // Incluindo o data-status com o status do pedido em minúsculas e sem acentos
                        $status_normalizado = strtolower($row['status']);
                        $status_normalizado = iconv('UTF-8', 'ASCII//TRANSLIT', $status_normalizado); // Remove acentos
                        $status_normalizado = htmlspecialchars($status_normalizado);
            
                        echo "<div class='pedido-detalhe bloco-cliente' data-status='" . $status_normalizado . "'>
                                <p><strong>Pedido: </strong>" . htmlspecialchars($row['pedido']) . 
                                " <strong>Nome do Cliente: </strong>" . htmlspecialchars($row['cliente']) . 
                                " <strong>Localidade: </strong>" . htmlspecialchars($row['endereco']) . "</p>";                 

                        $data_entrega = htmlspecialchars($row['data_entrega']);
                        if ($data_entrega !== '0000-00-00' && !empty($data_entrega)) {
                            $data_formatada = (new DateTime($data_entrega))->format('d/m/Y');
                            echo "<p><strong>Data de Entrega: </strong>" . $data_formatada . "</p>";
                               }

                        if (!empty($row['detalhes_pedido'])) {
                            echo "<h3>Detalhes do Pedido:</h3>";
                            // Usar ID único para a tabela
                            echo "<table id='tabela-produtos-" . htmlspecialchars($row['idpedidos_tr']) . "'>
                                <tr>
                                    <th>Modelo</th>
                                    <th>Montagem</th>
                                    <th>Frete</th>
                                    <th>Status</th>
                                    <th>Frequência</th>
                                    <th>Bica</th>
                                    <th>N° de Série</th>
                                    <th>Observação</th>
                                    <th>Ações</th>
                                </tr>";

                                foreach (explode('%%', $row['detalhes_pedido']) as $pedido) {
                                    if (!empty($pedido)) {
                                        $dados = explode('||', $pedido);
                                        list($modelo, $montagem, $frete, $status, $frequencia, $bica, $n_serie, $observacao) = array_map('htmlspecialchars', $dados + array_fill(0, 8, 'N/A'));
    
                                         echo "<tr data-idpedidos_tr='" . htmlspecialchars($row['idpedidos_tr']) . "'>
                                                <td>$modelo</td>
                                                <td>$montagem</td>
                                                <td>$frete</td>
                                                <td>$status</td>
                                                <td>$frequencia</td>
                                                <td>$bica</td>
                                                <td>$n_serie</td>
                                                <td>$observacao</td>
                                                <td>
                                                    <a class='editar' href='editar_tr.php?id=" . htmlspecialchars($row['idpedidos_tr']) . "'>Editar</a>
                                                </td>
                                              </tr>";
                            }
                        }
                            echo "</table>";
                        }
                        
                        echo "</div>";
                    }
                }
            } else {
                echo "<p>Nenhum pedido encontrado.</p>";
            }
            ?>
        </div>

    </main>
    <script src="script/buscar.js"></script>
    <script>
function salvarVinculo() {
    const cliente = document.getElementById('clienteSelect').value;
    const modelo = document.getElementById('modeloSelect').value;
    
    console.log('Cliente:', cliente);
    console.log('Modelo:', modelo);
    
    if (!cliente || !modelo) {
        alert('Por favor, selecione um cliente e um modelo.');
        return;
    }

    const formData = new FormData();
    formData.append('cliente', cliente);
    formData.append('modelo', modelo);

    console.log('Enviando requisição para salvar_vinculo.php');
    
    fetch('salvar_vinculo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('Vínculo salvo com sucesso!');
            fecharModal();
            location.reload();
        } else {
            alert('Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro detalhado:', error);
        alert('Erro ao salvar o vínculo. Por favor, tente novamente.');
    });
}

function fecharModal() {
    document.getElementById('modal-overlay').style.display = 'none';
}

function abrirModal() {
    document.getElementById('modal-overlay').style.display = 'block';
}
</script>
</body>

</html>