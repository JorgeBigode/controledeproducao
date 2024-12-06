<?php
include_once('config.php');
include('protect.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

verificarAcesso();

// Função para converter número com vírgula para ponto
function convertToDecimal($number) {
    // Remove espaços em branco
    $number = trim($number);
    // Substitui vírgula por ponto
    return str_replace(',', '.', $number);
}

// Função para formatar número para exibição (com vírgula)
function formatDisplayNumber($number) {
    return number_format((float)$number, 2, ',', '.');
}

// Função para formatar número para o banco (com ponto)
function formatDecimal($number) {
    return number_format((float)$number, 2, '.', '');
}

// Função para buscar materiais vinculados
function getMaterialVinculados($idequipamento) {
    global $conexao;
    $query = "SELECT m.tipo_material, me.quantidade, me.quantidade_total, me.id, me.idmateriais 
              FROM materiais_equipamento me 
              JOIN materiais m ON me.idmateriais = m.idmateriais 
              WHERE me.idequipamento = ?";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $idequipamento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $materiais = array();
    while($row = $result->fetch_assoc()) {
        $row['quantidade'] = formatDisplayNumber($row['quantidade']);
        $row['quantidade_total'] = formatDisplayNumber($row['quantidade_total']);
        $materiais[] = $row;
    }
    return $materiais;
}

// Variáveis para manter a seleção
$selected_equipment = '';
$selected_equipment_id = '';
$selected_equipment_name = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coleta dos dados do formulário
    $idequipamento = $_POST['idequipamento'];
    $equipamento_pai = $_POST['equipamento_pai'];
    $idmateriais = $_POST['idmateriais'];
    $tipo_material = $_POST['tipo_material'];
    $quantidade = str_replace(',', '.', $_POST['quantidade']);
    $operacao = $_POST['operacao'];

    // Manter a seleção do equipamento após o POST
    $selected_equipment_id = $idequipamento;
    $selected_equipment_name = $equipamento_pai;

    // Busca o valor de 'quant' na tabela materiais
    $query_material = "SELECT quant FROM materiais WHERE idmateriais = ?";
    $stmt = $conexao->prepare($query_material);
    $stmt->bind_param("i", $idmateriais);
    $stmt->execute();
    $result = $stmt->get_result();
    $material = $result->fetch_assoc();

    if (!$material) {
        die("Material não encontrado.");
    }

    $quant = str_replace(',', '.', $material['quant']);
    
    // Converte para float para garantir cálculo preciso
    $quantidade_float = floatval($quantidade);
    $quant_float = floatval($quant);

    // Calcula baseado na operação selecionada
    if ($operacao === 'multiplicar') {
        $resultado = $quant_float * $quantidade_float;
        $quantidade_final = $quantidade_float;
        $quantidade_total = $resultado;
    } else {
        // Verifica divisão por zero
        if ($quant_float == 0) {
            die("Erro: Divisão por zero não é permitida.");
        }
        // Para divisão, o resultado vai para quantidade e o valor inserido vai para total
        $resultado = $quantidade_float / $quant_float;
        $quantidade_final = $resultado;
        $quantidade_total = $quantidade_float;
    }

    // Insere os dados na tabela materiais_equipamento
    $query_insert = "INSERT INTO materiais_equipamento (idequipamento, equipamento_pai, idmateriais, tipo_material, quantidade, quantidade_total) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($query_insert);
    $stmt_insert->bind_param("isisdd", $idequipamento, $equipamento_pai, $idmateriais, $tipo_material, $quantidade_final, $quantidade_total);

    if ($stmt_insert->execute()) {
        $mensagem = "<div class='alert alert-success'>Material vinculado com sucesso!</div>";
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao vincular material: " . $stmt_insert->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vincular Material ao Equipamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 0;
            height: 100vh;
            overflow: hidden;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            height: calc(100vh - 40px);
            overflow: hidden;
        }
        .page-container {
            display: flex;
            height: 100%;
            gap: 20px;
        }
        .linked-items {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .linked-items-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        .form-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        .form-title {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            color: #34495e;
        }
        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 5px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }
        .btn-submit {
            background-color: #2ecc71;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background-color: #27ae60;
        }
        .btn-voltar {
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-voltar:hover {
            background-color: #2980b9;
        }
        .buttons-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .alert {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .readonly-input {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .custom-input {
            position: relative;
        }
        .custom-input input {
            width: 100%;
        }
        .linked-items {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            max-height: 600px;
        }
        .linked-items-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: 600;
            flex-shrink: 0;
        }
        .linked-items-container {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 10px;
        }
        .linked-items-container::-webkit-scrollbar {
            width: 8px;
        }
        .linked-items-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .linked-items-container::-webkit-scrollbar-thumb {
            background: #95a5a6;
            border-radius: 4px;
        }
        .linked-items-container::-webkit-scrollbar-thumb:hover {
            background: #7f8c8d;
        }
        .linked-items-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .linked-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .linked-item:last-child {
            border-bottom: none;
        }
        .linked-item-content {
            flex-grow: 1;
        }
        .linked-item-name {
            font-weight: 500;
            color: #34495e;
        }
        .linked-item-qty {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 0.9em;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
        .delete-confirm {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        .delete-confirm.active {
            display: block;
        }
        .delete-confirm-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }
        .delete-confirm-buttons button {
            padding: 5px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn-cancel-delete {
            background-color: #95a5a6;
            color: white;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        .overlay.active {
            display: block;
        }
        .no-items {
            color: #7f8c8d;
            font-style: italic;
        }
        
        label.form-label {
    display: none;
}

input#equipamento_pai {
    display: none;
}

input#tipo_material {
    display: none;
}

.linked-item-actions {
    display: flex;
    gap: 10px;
}

.btn-update {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-update:hover {
    background-color: #0056b3;
}
    </style>
</head>

<body>
    <!-- Overlay para o modal de confirmação -->
    <div class="overlay" id="overlay"></div>

    <!-- Modal de confirmação de exclusão -->
    <div class="delete-confirm" id="deleteConfirm">
        <h4>Confirmar Exclusão</h4>
        <p>Tem certeza que deseja excluir este item?</p>
        <div class="delete-confirm-buttons">
            <button class="btn-cancel-delete" onclick="cancelDelete()">Cancelar</button>
            <button class="btn-confirm-delete" onclick="confirmDelete()">Excluir</button>
        </div>
    </div>

    <div class="container">
        <div class="page-container">
            <!-- Lista de itens vinculados -->
            <div class="linked-items">
                <h3 class="linked-items-title">Itens Vinculados</h3>
                <div id="linked-items-container" class="linked-items-container">
                    <?php if (isset($materiais_vinculados) && !empty($materiais_vinculados)): ?>
                        <ul class="linked-items-list">
                            <?php foreach ($materiais_vinculados as $material): ?>
                                <li class="linked-item">
                                    <div class="linked-item-content">
                                        <div class="linked-item-name"><?php echo htmlspecialchars($material['tipo_material']); ?></div>
                                        <div class="linked-item-qty">
                                            Quantidade: <?php echo htmlspecialchars($material['quantidade']); ?>
                                            <br>
                                            Total: <?php echo htmlspecialchars($material['quantidade_total']); ?>
                                        </div>
                                    </div>
                                    <div class="linked-item-actions">
                                        <button class="btn-update" onclick="abrirModalAtualizar('<?php echo $material['id']; ?>', '<?php echo htmlspecialchars($material['tipo_material']); ?>', '<?php echo $material['quantidade_total']; ?>', '<?php echo $material['idmateriais']; ?>')">
                                            Atualizar
                                        </button>
                                        <button class="btn-delete" onclick="showDeleteConfirm('<?php echo $idequipamento; ?>', '<?php echo $material['idmateriais']; ?>')">
                                            Excluir
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-items">Nenhum item vinculado</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário -->
            <div class="form-container">
                <h2 class="form-title">Vincular Material ao Equipamento</h2>
                
                <?php if (isset($mensagem)) echo $mensagem; ?>

                <form action="vincular_material_equipamento.php" method="POST" id="vinculoForm">
                    <div class="form-group">
                        <label class="form-label" for="idequipamento">Equipamento:</label>
                        <div class="custom-input">
                            <input class="form-control" list="equipamentos-list" id="equipamento-input" 
                                   placeholder="Selecione ou digite o equipamento" required
                                   value="<?php echo htmlspecialchars($selected_equipment_name); ?>">
                            <input type="hidden" name="idequipamento" id="idequipamento" 
                                   value="<?php echo htmlspecialchars($selected_equipment_id); ?>">
                            <datalist id="equipamentos-list">
                                <?php
                                $query_equipamentos = "SELECT idequipamento, equipamento_pai FROM equipamento ORDER BY equipamento_pai ASC";
                                $result_equipamentos = $conexao->query($query_equipamentos);

                                if ($result_equipamentos->num_rows > 0) {
                                    while ($equipamento = $result_equipamentos->fetch_assoc()) {
                                        echo "<option data-id='{$equipamento['idequipamento']}' value='{$equipamento['equipamento_pai']}'>";
                                    }
                                }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="equipamento_pai">Nome do Equipamento:</label>
                        <input type="text" class="form-control readonly-input" name="equipamento_pai" id="equipamento_pai" 
                               readonly value="<?php echo htmlspecialchars($selected_equipment_name); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="idmateriais">Material:</label>
                        <div class="custom-input">
                            <input class="form-control" list="materiais-list" id="material-input" placeholder="Selecione ou digite o material" required>
                            <input type="hidden" name="idmateriais" id="idmateriais">
                            <datalist id="materiais-list">
                                <?php
                                $query_materiais = "SELECT idmateriais, tipo_material FROM materiais ORDER BY tipo_material ASC";
                                $result_materiais = $conexao->query($query_materiais);

                                if ($result_materiais->num_rows > 0) {
                                    while ($material = $result_materiais->fetch_assoc()) {
                                        echo "<option data-id='{$material['idmateriais']}' value='{$material['tipo_material']}'>";
                                    }
                                }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tipo_material">Nome do Material:</label>
                        <input type="text" class="form-control readonly-input" name="tipo_material" id="tipo_material" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Operação:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operacao" id="multiplicar" value="multiplicar" checked>
                            <label class="form-check-label" for="multiplicar">
                                Multiplicar
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operacao" id="dividir" value="dividir">
                            <label class="form-check-label" for="dividir">
                                Dividir
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quantidade">Quantidade:</label>
                        <input type="number" 
                               class="form-control" 
                               name="quantidade" 
                               id="quantidade" 
                               required 
                               step="any"
                               min="0"
                               placeholder="Digite o valor">
                    </div>

                    <div class="buttons-container">
                        <a href="material.php" class="btn-voltar">Voltar</a>
                        <button type="submit" class="btn-submit" style="flex: 1;">Vincular Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Atualização -->
    <div class="modal fade" id="modalAtualizar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atualizar Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAtualizar" method="POST">
                        <input type="hidden" id="idmateriais_equipamento" name="idmateriais_equipamento">
                        <input type="hidden" id="idmateriais" name="idmateriais">
                        <div class="mb-3">
                            <label class="form-label">Material:</label>
                            <input type="text" class="form-control" id="material_nome" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nova Quantidade:</label>
                            <input type="number" step="any" class="form-control" id="nova_quantidade" name="nova_quantidade" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Operação:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="operacao_update" id="multiplicar_update" value="multiplicar" checked>
                                <label class="form-check-label" for="multiplicar_update">Multiplicar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="operacao_update" id="dividir_update" value="dividir">
                                <label class="form-check-label" for="dividir_update">Dividir</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="atualizarMaterial()">Atualizar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let deleteItemData = null;

    function showDeleteConfirm(idequipamento, idmateriais) {
        deleteItemData = { idequipamento, idmateriais };
        document.getElementById('overlay').classList.add('active');
        document.getElementById('deleteConfirm').classList.add('active');
    }

    function cancelDelete() {
        deleteItemData = null;
        document.getElementById('overlay').classList.remove('active');
        document.getElementById('deleteConfirm').classList.remove('active');
    }

    function confirmDelete() {
        if (!deleteItemData) return;

        fetch('delete_linked_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(deleteItemData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recarregar a lista de itens
                loadLinkedItems(deleteItemData.idequipamento);
                // Mostrar mensagem de sucesso
                const container = document.querySelector('.linked-items');
                const message = document.createElement('div');
                message.className = 'alert alert-success';
                message.textContent = 'Item excluído com sucesso!';
                container.insertBefore(message, container.firstChild);
                setTimeout(() => message.remove(), 3000);
            } else {
                alert('Erro ao excluir item: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir item');
        })
        .finally(() => {
            cancelDelete();
        });
    }

    // Carregar itens vinculados ao iniciar se houver equipamento selecionado
    document.addEventListener('DOMContentLoaded', function() {
        const equipamentoId = document.getElementById('idequipamento').value;
        if (equipamentoId) {
            loadLinkedItems(equipamentoId);
        }
    });

    // Função para carregar e exibir os itens vinculados
    function loadLinkedItems(equipamentoId) {
        if (!equipamentoId) {
            document.getElementById('linked-items-container').innerHTML = 
                '<p class="no-items">Selecione um equipamento para ver os itens vinculados</p>';
            return;
        }

        fetch(`get_linked_items.php?idequipamento=${equipamentoId}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('linked-items-container');
                if (data.length === 0) {
                    container.innerHTML = '<p class="no-items">Nenhum item vinculado</p>';
                    return;
                }

                let html = '<ul class="linked-items-list">';
                data.forEach(item => {
                    html += `
                        <li class="linked-item">
                            <div class="linked-item-content">
                                <div class="linked-item-name">${item.tipo_material}</div>
                                <div class="linked-item-qty">
                                    Quantidade: ${item.quantidade}
                                    <br>
                                    Total: ${item.quantidade_total}
                                </div>
                            </div>
                            <div class="linked-item-actions">
                                <button class="btn-update" onclick="abrirModalAtualizar('${item.id}', '${item.tipo_material}', '${item.quantidade_total}', '${item.idmateriais}')">
                                    Atualizar
                                </button>
                                <button class="btn-delete" onclick="showDeleteConfirm('${equipamentoId}', '${item.idmateriais}')">
                                    Excluir
                                </button>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Erro ao carregar itens:', error);
                document.getElementById('linked-items-container').innerHTML = 
                    '<p class="no-items">Erro ao carregar itens vinculados</p>';
            });
    }

    // Handle form submission
    document.getElementById('vinculoForm').addEventListener('submit', function(e) {
        // Store equipment selection in localStorage before submitting
        const equipamentoId = document.getElementById('idequipamento').value;
        const equipamentoNome = document.getElementById('equipamento-input').value;
        if (equipamentoId && equipamentoNome) {
            localStorage.setItem('selectedEquipmentId', equipamentoId);
            localStorage.setItem('selectedEquipmentName', equipamentoNome);
        }
    });

    // Restore equipment selection from localStorage
    window.addEventListener('load', function() {
        const savedId = localStorage.getItem('selectedEquipmentId');
        const savedName = localStorage.getItem('selectedEquipmentName');
        if (savedId && savedName) {
            document.getElementById('idequipamento').value = savedId;
            document.getElementById('equipamento-input').value = savedName;
            document.getElementById('equipamento_pai').value = savedName;
            loadLinkedItems(savedId);
        }
    });

    // Handle equipamento selection/input
    document.getElementById('equipamento-input').addEventListener('input', function(e) {
        const datalist = document.getElementById('equipamentos-list');
        const options = datalist.getElementsByTagName('option');
        let selectedId = '';
        let selectedText = this.value;

        // Check if the input matches any option
        for (let option of options) {
            if (option.value === this.value) {
                selectedId = option.getAttribute('data-id');
                break;
            }
        }

        // Update hidden input and display field
        document.getElementById('idequipamento').value = selectedId || '';
        document.getElementById('equipamento_pai').value = selectedText;

        // Save to localStorage
        if (selectedId && selectedText) {
            localStorage.setItem('selectedEquipmentId', selectedId);
            localStorage.setItem('selectedEquipmentName', selectedText);
        }

        // Load linked items when an equipment is selected
        loadLinkedItems(selectedId);
    });

    // Handle material selection/input
    document.getElementById('material-input').addEventListener('input', function(e) {
        const datalist = document.getElementById('materiais-list');
        const options = datalist.getElementsByTagName('option');
        let selectedId = '';
        let selectedText = this.value;

        // Check if the input matches any option
        for (let option of options) {
            if (option.value === this.value) {
                selectedId = option.getAttribute('data-id');
                break;
            }
        }

        // Update hidden input and display field
        document.getElementById('idmateriais').value = selectedId || '';
        document.getElementById('tipo_material').value = selectedText;
    });

    // Clear only material fields after successful submission
    <?php if (isset($mensagem) && strpos($mensagem, 'success') !== false): ?>
    document.getElementById('material-input').value = '';
    document.getElementById('idmateriais').value = '';
    document.getElementById('tipo_material').value = '';
    document.getElementById('quantidade').value = '';
    <?php endif; ?>

    function abrirModalAtualizar(id, material, quantidade_total, idmateriais) {
        document.getElementById('idmateriais_equipamento').value = id;
        document.getElementById('idmateriais').value = idmateriais;
        document.getElementById('material_nome').value = material;
        document.getElementById('nova_quantidade').value = quantidade_total;
        var modal = new bootstrap.Modal(document.getElementById('modalAtualizar'));
        modal.show();
    }

    function atualizarMaterial() {
        const idmateriais_equipamento = document.getElementById('idmateriais_equipamento').value;
        const idmateriais = document.getElementById('idmateriais').value;
        const novaQuantidade = document.getElementById('nova_quantidade').value;
        const operacao = document.querySelector('input[name="operacao_update"]:checked').value;
        const modalElement = document.getElementById('modalAtualizar');
        const modal = bootstrap.Modal.getInstance(modalElement);

        if (!idmateriais_equipamento || !idmateriais) {
            alert('Erro: Identificadores do material não encontrados');
            return;
        }

        // Disable the form while processing
        const form = modalElement.querySelector('form');
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Atualizando...';

        // Enviar requisição para atualizar
        fetch('atualizar_material_equipamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: idmateriais_equipamento,
                idmateriais: idmateriais,
                quantidade: novaQuantidade,
                operacao: operacao
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Recarregar a lista de itens antes de fechar o modal
                const equipamentoId = document.getElementById('idequipamento').value;
                loadLinkedItems(equipamentoId);
                
                // Fechar o modal após recarregar os itens
                modal.hide();

                // Mostrar mensagem de sucesso
                const container = document.querySelector('.linked-items');
                const message = document.createElement('div');
                message.className = 'alert alert-success';
                message.textContent = 'Material atualizado com sucesso!';
                container.insertBefore(message, container.firstChild);
                setTimeout(() => message.remove(), 3000);
            } else {
                throw new Error(data.error || 'Erro desconhecido ao atualizar material');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar material: ' + error.message);
        })
        .finally(() => {
            // Re-enable the form
            submitButton.disabled = false;
            submitButton.textContent = 'Atualizar';
        });
    }
    </script>
</body>

</html>