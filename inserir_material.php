<?php
include_once('config.php');
include('protect.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

verificarAcesso();

// Consulta SQL para buscar os materiais
$sql_materiais = "SELECT * FROM materiais ORDER BY idmateriais DESC";
$result = $conexao->query($sql_materiais);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiais</title>
    <style>
        main {
            flex: 20 0 500px;
            flex-wrap: wrap;
            overflow: auto;
            height: calc(100vh - 150px);
            margin: 3px;
            padding: 10px;
            border-radius: 8px 8px 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn-inserir {
            margin-bottom: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }

        .btn-inserir:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <main>
        <h1>Lista de Materiais</h1>
        <a href="adicionar_material.php" class="btn-inserir">Inserir Material</a>
        <a href="material.php" class="btn-inserir">Voltar</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo do Material</th>
                    <th>Dimensão</th>
                    <th>Unidade de Medida</th>
                    <th>Quantidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Itera sobre os resultados
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['idmateriais']}</td>";
                        echo "<td>{$row['tipo_material']}</td>";
                        echo "<td>{$row['dimensão']}</td>";
                        echo "<td>{$row['un_medida']}</td>";
                        echo "<td>{$row['quant']}</td>";
                        echo "<td>
                                <a href='editar_material.php?id={$row['idmateriais']}'>Editar</a> |
                                <a href='excluir_material.php?id={$row['idmateriais']}' onclick='return confirm(\"Tem certeza que deseja excluir este material?\")'>Excluir</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Nenhum material encontrado.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </main>
</body>

</html>
