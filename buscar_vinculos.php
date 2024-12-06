<?php
// Conectar ao banco de dados
include_once('config.php');

// Verifica se o ID do cliente foi enviado via POST
if (isset($_POST['id_cliente'])) {
    $idCliente = $_POST['id_cliente'];

    // Consulta SQL para buscar os equipamentos e conjuntos do cliente selecionado
    $sql = "SELECT 
                cp.id_equipamento_produto, 
                eq.equipamento_pai, 
                pr.conjunto
            FROM 
                cliente_produto cp
            INNER JOIN 
                equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
            INNER JOIN 
                equipamento eq ON ep.idequipamento = eq.idequipamento
            INNER JOIN 
                produto pr ON ep.idproduto = pr.idproduto
            WHERE 
                cp.id_cliente = ? 
            ORDER BY 
                eq.equipamento_pai";

    // Preparar a consulta
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $result = $stmt->get_result();

    // Gera a tabela se houver resultados
    if ($result->num_rows > 0) {
        // Agrupar equipamentos
        $equipamentos = [];

        while ($row = $result->fetch_assoc()) {
            $equipamentos[$row['equipamento_pai']][] = [
                'conjunto' => htmlspecialchars($row['conjunto']),
                'id_equipamento_produto' => $row['id_equipamento_produto']
            ];
        }

        foreach ($equipamentos as $equipamento => $conjuntos) {
            echo "<div class='vinculo-card'>";
            echo "<h3>" . htmlspecialchars($equipamento) . "</h3>";
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Conjunto</th>";
            echo "<th>Ação</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            foreach ($conjuntos as $conjunto) {
                echo "<tr>";
                echo "<td>" . $conjunto['conjunto'] . "</td>";
                echo "<td>
                        <form method='POST' action='excluir_vinculo.php' onsubmit='return confirm(\"Tem certeza que deseja excluir este vínculo?\");'>
                            <input type='hidden' name='id_cliente' value='" . $idCliente . "'>
                            <input type='hidden' name='id_equipamento_produto' value='" . $conjunto['id_equipamento_produto'] . "'>
                            <button type='submit' class='btn btn-danger btn-sm'>Excluir</button>
                        </form>
                      </td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        }
    } else {
        echo "<p>Nenhum vínculo encontrado para este cliente.</p>";
    }

    $stmt->close();
}
?>
