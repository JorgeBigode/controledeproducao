<?php
include_once('config.php');

if (!isset($_GET['equipment_id'])) {
    die('ID do equipamento não fornecido');
}

$equipment_id = intval($_GET['equipment_id']);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Lista de Materiais</h2>
    <button onclick="exportToExcel(<?php echo $equipment_id; ?>)" class="btn btn-success">
        <i class="fas fa-file-excel"></i> Exportar para Excel
    </button>
</div>

<table>
    <thead>
        <tr>
            <th>Material</th>
            <th>Quantidade</th>
            <th>UN/KG</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query_vinculos = "SELECT * FROM materiais_equipamento WHERE idequipamento = ? ORDER BY tipo_material ASC";
        $stmt = $conexao->prepare($query_vinculos);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $result_vinculos = $stmt->get_result();
        
        while ($row_vinculo = $result_vinculos->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row_vinculo['tipo_material'] . "</td>";
            echo "<td>" . $row_vinculo['quantidade'] . "</td>";
            echo "<td>" . $row_vinculo['quantidade_total'] . "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>
