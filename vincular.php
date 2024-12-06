<?php
include_once('config.php');
include('protect.php');
verificarAcesso();

// Processar a vinculação se o formulário for enviado
if (isset($_POST['submit'])) {
    $idequipamento = $_POST['idequipamento'] ?? null;
    $idprodutos = $_POST['idproduto'] ?? []; // Receber um array de produtos

    // Inserir o vínculo na tabela equipamento_produto
    $sql_insert = "INSERT INTO equipamento_produto (idequipamento, idproduto) VALUES (?, ?)";
    
    if ($stmt = $conexao->prepare($sql_insert)) {
        // Iterar sobre cada produto selecionado
        foreach ($idprodutos as $idproduto) {
            $stmt->bind_param("ii", $idequipamento, $idproduto);
            if (!$stmt->execute()) {
                echo "Erro ao vincular: " . $stmt->error;
            }
        }
        header("Location: vincular.php?status=success");
        exit();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Vincular Equipamento a Produto</title>

    <!-- Incluir CSS do Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
    /* Estilo opcional para melhorar a aparência */
    .select2-container {
        width: 100% !important;
        /* Faz com que o Select2 ocupe toda a largura disponível */
    }

    .button {
        display: inline-block;
        padding: 10px 15px;
        background-color: #007bff;
        color: white;
        text-align: center;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .button:hover {
        background-color: #0056b3;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .equipment-row {
        font-weight: bold;
        background-color: #f2f2f2;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 800px;
        margin: 20px auto;
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #f8f8f8;
    }
    .btn {
        background-color: #ff4c4c;
        color: white;
        padding: 10px 15px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        margin: 4px 2px;
        cursor: pointer;
        border: none;
        border-radius: 4px;
    }
    .btn-danger:hover {
        background-color: #ff1a1a;
    }
    </style>
</head>

<body>
    <div class="container">
    <a href="cadastro.php" class="button">Voltar</a>

    <h1>Vincular Equipamento a Produto</h1>

    <!-- Formulário para vincular produtos e equipamentos -->
    <form method="POST" action="">
        <label for="idequipamento">Selecione o Equipamento:</label>
        <select name="idequipamento" id="idequipamento" required>
            <?php
        $sql = "SELECT * FROM equipamento";
        $result = $conexao->query($sql);
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['idequipamento']}'>{$row['equipamento_pai']}</option>";
        }
        ?>
        </select>

        <label for="idproduto">Selecione os Produtos:</label>
<select name="idproduto[]" id="idproduto" multiple required>
    <?php
    $sql = "SELECT * FROM produto";
    $result = $conexao->query($sql);
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['idproduto']}'>{$row['conjunto']}</option>";
    }
    ?>
</select>


        <button type="submit" name="submit" class="btn">Vincular</button>
    </form>

    <h2>Vínculos Existentes</h2>

    <label for="equipamento_pai">Filtrar por Equipamento:</label>
    <select name="equipamento_pai" id="equipamento_pai" required>
        <?php
        $sql = "SELECT * FROM equipamento";
        $result = $conexao->query($sql);
        echo "<option value=''>Todos</option>";
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['equipamento_pai']}'>{$row['equipamento_pai']}</option>";
        }
        ?>
    </select>

    <!-- Visualizar os vínculos existentes -->
    <?php
// Consulta para obter os vínculos
$sql = "SELECT ep.id_equipamento_produto, e.idequipamento, e.equipamento_pai, p.conjunto 
        FROM equipamento_produto ep 
        JOIN equipamento e ON ep.idequipamento = e.idequipamento 
        JOIN produto p ON ep.idproduto = p.idproduto
        ORDER BY e.equipamento_pai"; // Ordena os resultados pelo equipamento
$result = $conexao->query($sql);

if ($result->num_rows > 0) {
    $currentEquipment = null;
    echo "<table>";
    echo "<tr><th>ID</th><th>Equipamento</th><th>Produto</th><th>Ação</th></tr>";

    while ($row = $result->fetch_assoc()) {
        if ($currentEquipment !== $row['equipamento_pai']) {
            if ($currentEquipment !== null) {
                echo "<tr><td colspan='4'></td></tr>"; // Linha vazia entre grupos
            }
            $currentEquipment = $row['equipamento_pai'];
            echo "<tr class='equipment-row'><td colspan='4'>{$row['equipamento_pai']}</td></tr>";
        }
        echo "<tr data-equipamento='{$row['equipamento_pai']}'>";
        echo "<td></td>"; // Célula vazia para o ID (se precisar, pode preencher aqui)
        echo "<td></td>"; // Célula vazia para o Equipamento
        echo "<td>{$row['conjunto']}</td>";
        echo "<td><form method='POST' action='excluir_vinculo_equipamento.php' style='display:inline;'>
                <input type='hidden' name='id_equipamento_produto' value='{$row['id_equipamento_produto']}'>
                <button type='submit' class='btn btn-danger'>Excluir</button>
              </form></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Nenhum vínculo encontrado.";
}

?>

    </div>

    <!-- Incluir jQuery e JS do Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    // Inicializa o Select2 para os elementos <select>
    $(document).ready(function() {
        $('#idequipamento').select2();
        $('#idproduto').select2();
    });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const equipamentoSelect = document.getElementById('equipamento_pai');
            const tableRows = document.querySelectorAll('table tr');

            equipamentoSelect.addEventListener('change', function() {
                const selectedEquipamento = equipamentoSelect.value;
                let showRow = false;

                tableRows.forEach((row, index) => {
                    if (index === 0) return; // Skip header row

                    if (row.classList.contains('equipment-row')) {
                        const equipamentoId = row.textContent.trim();
                        showRow = (equipamentoId === selectedEquipamento || selectedEquipamento === '');
                        row.style.display = showRow ? '' : 'none';
                    } else {
                        const equipamento = row.getAttribute('data-equipamento');
                        row.style.display = (equipamento === selectedEquipamento || selectedEquipamento === '') ? '' : 'none';
                    }
                });
            });
        });
    </script>

</body>

</html>

<?php
$conexao->close(); // Fechar a conexão com o banco de dados
?>
