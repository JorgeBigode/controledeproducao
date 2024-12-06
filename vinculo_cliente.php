<?php
include_once('config.php'); 
include('protect.php');
verificarAcesso();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ajuste na consulta SQL
$sql_produto_query = "
    SELECT 
        c.pedido AS pedido,
        e.equipamento_pai,
        p.conjunto
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
    WHERE 
        c.idcliente IS NOT NULL;  -- Corrigido para garantir uma condição válida
";

$result_produto = $conexao->query($sql_produto_query);

if (!$result_produto) {
    echo "Erro na consulta de produtos: " . $conexao->error;
    exit;
}

// Consulta SQL para clientes
$sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
$result_cliente = $conexao->query($sql_cliente_query);

$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente AS cliente, 
           c.endereco, 
           c.data_entrega AS data_entrega, 
           GROUP_CONCAT(cp.id_vinculo SEPARATOR '||') AS id_vinculos,  -- Agrupando id_vinculo
           GROUP_CONCAT(
               CONCAT_WS(
                   '||', 
                   COALESCE(cp.quantidade_prod, 'N/A'),
                   COALESCE(cp.lote, 'N/A'),
                   COALESCE(cp.data_engenharia, 'N/A'), 
                   COALESCE(cp.data_programacao, 'N/A'), 
                   COALESCE(cp.data_pcp, 'N/A'),
                   COALESCE(cp.data_producao, 'N/A'),
                   COALESCE(e.equipamento_pai, 'N/A'),  -- Adicionando equipamento
                   COALESCE(p.conjunto, 'N/A')           -- Adicionando conjunto
               ) SEPARATOR '%%'
           ) AS produtos
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    LEFT JOIN equipamento AS e ON ep.idequipamento = e.idequipamento
    LEFT JOIN produto AS p ON ep.idproduto = p.idproduto
    GROUP BY c.idcliente
    ORDER BY c.idcliente DESC
";

$result_lista_pedidos = $conexao->query($sql_lista_pedidos);

if (!$result_lista_pedidos) {
    echo "Erro ao carregar lista de pedidos: " . $conexao->error;
    exit;
}

// Consulta para buscar os vínculos entre clientes e equipamentos
$sql_vinculos = "
    SELECT c.idcliente, c.cliente, e.equipamento_pai, p.conjunto 
    FROM cliente_produto cp
    JOIN cliente c ON cp.id_cliente = c.idcliente
    JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    JOIN equipamento e ON ep.idequipamento = e.idequipamento
    JOIN produto p ON ep.idproduto = p.idproduto
";

$result_vinculos = $conexao->query($sql_vinculos);

if (!function_exists('validarData')) {
    function validarData($data) {
        if (!empty($data)) {
            $formato = 'Y-m-d'; // Ajuste o formato conforme necessário
            $d = DateTime::createFromFormat($formato, $data);
            return $d && $d->format($formato) === $data;
        }
        return false;
    }
}

$data_inserida = $_GET['data_inserida'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .form-control:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .btn {
        display: inline-block;
        padding: 10px 15px;
        color: #fff;
        background-color: #007bff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    #tabelaVinculos {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .vinculo-card {
        flex: 1;
        min-width: 300px;
        max-width: calc(50% - 10px);
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .vinculo-card table {
        width: 100%;
        margin: 0;
    }

    .vinculo-card th {
        background-color: #007bff;
        color: white;
        padding: 8px;
    }

    .vinculo-card td {
        padding: 8px;
    }
    </style>

    <title>Vincular Cliente com Equipamento</title>
</head>

<body>
    <div class="container">
        <h2>Vincular Cliente com Equipamento</h2>
        <form id="formVincularClienteProduto">
            <div class="form-group">
                <label for="selectCliente">Cliente</label>
                <select class="form-control" id="selectCliente" name="id_cliente" required>
                    <?php while ($cliente = $result_cliente->fetch_assoc()): ?>
                    <option value="<?php echo $cliente['idcliente']; ?>">
                        <?php echo $cliente['cliente']; ?> - <?php echo $cliente['pedido']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
    <label for="selectEquipamento">Equipamento</label>
    <select class="form-control" id="selectEquipamento" name="id_equipamento_produto[]" multiple required>
        <option value="">Selecione um ou mais equipamentos</option>
        <?php
        $sql_equipamento_query = "
            SELECT ep.id_equipamento_produto, e.equipamento_pai, p.conjunto 
            FROM equipamento_produto ep 
            JOIN equipamento e ON ep.idequipamento = e.idequipamento
            JOIN produto p ON ep.idproduto = p.idproduto
        ";
        $result_equipamento = $conexao->query($sql_equipamento_query);

        if ($result_equipamento) {
            if ($result_equipamento->num_rows > 0) {
                while ($equipamento = $result_equipamento->fetch_assoc()): ?>
                    <option value="<?php echo $equipamento['id_equipamento_produto']; ?>">
                        <?php echo htmlspecialchars($equipamento['equipamento_pai']); ?> - <?php echo htmlspecialchars($equipamento['conjunto']); ?>
                    </option>
                <?php endwhile;
            } else {
                echo '<option value="">Nenhum equipamento encontrado</option>';
            }
        } else {
            echo '<option value="">Erro ao buscar equipamentos</option>';
        }
        ?>
    </select>
</div>


            <!-- Campos adicionais -->
            <div class="form-group">
                <label for="dataEngenharia">Data Engenharia</label>
                <input type="datetime-local" class="form-control" id="dataEngenharia" name="data_engenharia">
            </div>
            <div class="form-group">
                <label for="dataProgramacao">Data Programação</label>
                <input type="datetime-local" class="form-control" id="dataProgramacao" name="data_programacao">
            </div>
            <div class="form-group">
                <label for="dataPcp">Data PCP</label>
                <input type="datetime-local" class="form-control" id="dataPcp" name="data_pcp">
            </div>
            <div class="form-group">
                <label for="dataProducao">Data Produção</label>
                <input type="datetime-local" class="form-control" id="dataProducao" name="data_producao">
            </div>
            <div class="form-group">
                <label for="lote">Lote</label>
                <input type="text" class="form-control" id="lote" name="lote">
            </div>
            <div class="form-group">
                <label for="quantidadeProd">Quantidade</label>
                <input type="text" class="form-control" id="quantidadeProd" name="quantidade_prod">
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
            
        </form>
    </div>

    <div id="tabelaVinculos">
        <!-- A tabela será gerada aqui -->
    </div>

    <script>
    $(document).ready(function() {
        // Recupera o último cliente selecionado do localStorage
        const lastSelectedClient = localStorage.getItem('lastSelectedClient');
        if (lastSelectedClient) {
            $('#selectCliente').val(lastSelectedClient).trigger('change');
        } else {
            // Se não houver cliente salvo, seleciona o primeiro da lista
            const firstClient = $('#selectCliente option:first').val();
            $('#selectCliente').val(firstClient).trigger('change');
        }

        $('#formVincularClienteProduto').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: 'processar_vinculo.php',
                data: $(this).serialize(),
                success: function(response) {
                    alert(response);
                    // Recarrega apenas a tabela de vínculos, não a página toda
                    $('#selectCliente').trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        // Inicializa o Select2
        $('#selectCliente, #selectEquipamento').select2({
            width: '100%',
            tags: true
        });

        // Ao selecionar um cliente/pedido, salva no localStorage e busca os dados vinculados
        $('#selectCliente').change(function() {
            var idCliente = $(this).val();
            localStorage.setItem('lastSelectedClient', idCliente);

            $.ajax({
                type: 'POST',
                url: 'buscar_vinculos.php', // Arquivo que busca os vínculos no servidor
                data: {
                    id_cliente: idCliente
                },
                success: function(response) {
                    $('#tabelaVinculos').html(response);
                },
                error: function(xhr, status, error) {
                    console.error("Erro ao buscar vínculos: " + xhr.responseText);
                }
            });
        });
    });
    </script>
</body>

</html>