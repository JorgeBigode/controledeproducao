<?php
include_once('config.php');
include('protect.php');
include('menu.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

verificarAcesso();

// Consulta SQL para selecionar os equipamentos únicos da tabela materiais_equipamento
$query = "SELECT DISTINCT idequipamento as id FROM materiais_equipamento ORDER BY idequipamento";
$result = $conexao->query($query);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material</title>
    <style>
        main {
            margin: 20px;
            font-family: Arial, sans-serif;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .equipment-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .equipment-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .equipment-card.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .equipment-card img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <main>
        <div class="equipment-grid">
            <?php
            $equipment_images = [
                2 => ['img' => 'Tr-080.png', 'nome' => 'TR-80'],
                22 => ['img' => 'Tr-0100.png', 'nome' => 'TR-100'],
                21 => ['img' => 'Tr-060.png', 'nome' => 'TR-60']
            ];

            foreach ($equipment_images as $id => $equipment) {
                echo "<div class='equipment-card' onclick='selectEquipment(" . $id . ")' id='card_" . $id . "'>";
                echo "<img src='img/" . $equipment['img'] . "' alt='" . $equipment['nome'] . "'>";
                echo "<h3>" . $equipment['nome'] . "</h3>";
                echo "</div>";
            }
            ?>
        </div>

        <div id="materials-container"></div>

        <script>
            let selectedEquipment = null;

            function selectEquipment(equipmentId) {
                // Remove seleção anterior
                if (selectedEquipment) {
                    document.getElementById('card_' + selectedEquipment).classList.remove('selected');
                }

                // Adiciona nova seleção
                document.getElementById('card_' + equipmentId).classList.add('selected');
                selectedEquipment = equipmentId;

                // Carrega os materiais
                fetch('get_materials.php?equipment_id=' + equipmentId)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('materials-container').innerHTML = html;
                    });
            }

            function exportToExcel(equipmentId) {
                window.location.href = 'export_materials.php?equipment_id=' + equipmentId;
            }
        </script>
    </main>
</body>
</html>