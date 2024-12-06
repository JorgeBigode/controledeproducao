<?php
include_once('config.php');
include('protect.php');
verificarAcesso();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$permissoes_edicao = [];

// Verifica as permissões específicas de edição para o usuário logado
if (isset($_SESSION['username'])) {
    $id_usuario_logado = $_SESSION['id'];

    // Consulta as permissões específicas para o usuário logado
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
$id_vinculo = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id_vinculo) {
    $sql_cliente_equip = "
    SELECT 
        c.idcliente, 
        c.pedido, 
        c.cliente AS cliente, 
        c.endereco, 
        c.data_entrega AS data_entrega, 
        GROUP_CONCAT(cp.id_vinculo SEPARATOR '||') AS id_vinculos,
        GROUP_CONCAT(
            COALESCE(p.conjunto, 'N/A') SEPARATOR '%%'
        ) AS conjunto,
        GROUP_CONCAT(
            COALESCE(cp.quantidade_prod, 'N/A') SEPARATOR '%%'
        ) AS quantidade
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    LEFT JOIN produto AS p ON ep.idproduto = p.idproduto
    WHERE cp.id_vinculo = ?
    GROUP BY c.idcliente
    ORDER BY c.idcliente DESC
";
    $stmt_cliente_equip = $conexao->prepare($sql_cliente_equip);
    $stmt_cliente_equip->bind_param("i", $id_vinculo);
    
    if ($stmt_cliente_equip->execute()) {
        $result_cliente_equip = $stmt_cliente_equip->get_result();
        $dados_cliente_equip = $result_cliente_equip->fetch_assoc();
    }
}

// Conta o número de edições do usuário para o registro específico
if (isset($_SESSION['username'])) {
    $sql_contar_edicoes = "SELECT COUNT(*) AS num_edicoes FROM historico_edicoes WHERE id_usuario = ? AND id_vinculo = ?";
    $stmt_contar_edicoes = $conexao->prepare($sql_contar_edicoes);
    $stmt_contar_edicoes->bind_param("ii", $id_usuario_logado, $id_vinculo);
    $stmt_contar_edicoes->execute();
    $result_edicoes = $stmt_contar_edicoes->get_result();
    $row = $result_edicoes->fetch_assoc();
    $num_edicoes = $row['num_edicoes'];
    $stmt_contar_edicoes->close();
}

if ($id_vinculo) {
    $sql_get_dados = "SELECT lote, data_engenharia, data_programacao, data_pcp, data_producao, data_qualidade, status_producao, data_prog_fim, link_pastas, obs_detalhes FROM cliente_produto WHERE id_vinculo = ?";
    $stmt_get_dados = $conexao->prepare($sql_get_dados);
    $stmt_get_dados->bind_param("i", $id_vinculo);
    $stmt_get_dados->execute();
    $result = $stmt_get_dados->get_result();
    $dados_produto = $result->fetch_assoc();
    
    $lote = $dados_produto['lote'] ?? '';
    $data_engenharia = $dados_produto['data_engenharia'] ?? '';
    $data_programacao = $dados_produto['data_programacao'] ?? '';
    $data_prog_fim = $dados_produto['data_prog_fim'] ?? '';
    $data_pcp = $dados_produto['data_pcp'] ?? '';
    $data_producao = $dados_produto['data_producao'] ?? '';
    $data_qualidade = $dados_produto['qualidade'] ?? '';
    $link_pastas = $dados_produto['link_pastas'] ?? '';
    $obs_detalhes = $dados_produto['obs_detalhes'] ?? '';
    $status_producao = $dados_produto['status_producao'] ?? ''; // Obter o status de produção

    $stmt_get_dados->close();
} else {
    echo "ID de vínculo não fornecido.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos_a_atualizar = [];
    $valores_a_atualizar = [];
    
    if (in_array('lote', $permissoes_edicao)) {
        $lote = $_POST['lote'] ?? null;
        $campos_a_atualizar[] = "lote = ?";
        $valores_a_atualizar[] = $lote;
    }

    if (in_array('data_engenharia', $permissoes_edicao)) {
        $data_engenharia = $_POST['data_engenharia'] ?? null;
        $campos_a_atualizar[] = "data_engenharia = ?";
        $valores_a_atualizar[] = $data_engenharia;
    }

    if (in_array('data_programacao', $permissoes_edicao)) {
        $data_programacao = $_POST['data_programacao'] ?? null;
        $campos_a_atualizar[] = "data_programacao = ?";
        $valores_a_atualizar[] = $data_programacao;
    }

    if (in_array('data_prog_fim', $permissoes_edicao)) {
        $data_prog_fim = $_POST['data_prog_fim'] ?? null;
        $campos_a_atualizar[] = "data_prog_fim = ?";
        $valores_a_atualizar[] = $data_prog_fim;
    }

    if (in_array('data_pcp', $permissoes_edicao)) {
        $data_pcp = $_POST['data_pcp'] ?? null;
        $campos_a_atualizar[] = "data_pcp = ?";
        $valores_a_atualizar[] = $data_pcp;
    }

    if (in_array('data_producao', $permissoes_edicao)) {
        $data_producao = $_POST['data_producao'] ?? null;
        $campos_a_atualizar[] = "data_producao = ?";
        $valores_a_atualizar[] = $data_producao;
    }

    if (in_array('data_qualidade', $permissoes_edicao)) {
        $data_qualidade = $_POST['data_qualidade'] ?? null;
        $campos_a_atualizar[] = "data_qualidade = ?";
        $valores_a_atualizar[] = $data_qualidade;
    }

    if (in_array('link_pastas', $permissoes_edicao)) {
        $link_pastas = $_POST['link_pastas'] ?? null;
        $campos_a_atualizar[] = "link_pastas= ?";
        $valores_a_atualizar[] = $link_pastas;
    }

    if (in_array('obs_detalhes', $permissoes_edicao)) {
        $obs_detalhes = $_POST['obs_detalhes'] ?? null;
        $campos_a_atualizar[] = "obs_detalhes = ?";
        $valores_a_atualizar[] = $obs_detalhes;
    }
    

    if (!empty($campos_a_atualizar)) {
        // Constrói a query dinamicamente com base nos campos que o usuário pode editar
        $sql_upsert = "UPDATE cliente_produto SET " . implode(", ", $campos_a_atualizar) . " WHERE id_vinculo = ?";
        $valores_a_atualizar[] = $id_vinculo;

        $stmt_upsert = $conexao->prepare($sql_upsert);
        $stmt_upsert->bind_param(str_repeat('s', count($valores_a_atualizar) - 1) . 'i', ...$valores_a_atualizar);

        try {
            if ($stmt_upsert->execute()) {
                // Registra a edição na tabela de histórico
                $sql_registrar_edicao = "INSERT INTO historico_edicoes (id_usuario, id_vinculo) VALUES (?, ?)";
                $stmt_registrar_edicao = $conexao->prepare($sql_registrar_edicao);
                $stmt_registrar_edicao->bind_param("ii", $id_usuario_logado, $id_vinculo);
                $stmt_registrar_edicao->execute();
                $stmt_registrar_edicao->close();

                echo "<script>alert('Data inserida com sucesso!'); window.location.href='obra.php?data_inserida=sucesso';</script>";
                exit;
            } else {
                echo "Erro ao salvar as datas: " . htmlspecialchars($stmt_upsert->error) . "<br>";
            }
        } catch (mysqli_sql_exception $e) {
            echo "Erro ao salvar as datas: " . htmlspecialchars($e->getMessage()) . "<br>";
        }

        $stmt_upsert->close();
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/datas.css">
    <title>Editar Dados do Produto</title>
    <style>
    span {
        text-transform: uppercase;
    }
    </style>
</head>

<body>
    <?php if (!empty($dados_cliente_equip)): ?>
    <div>
        <p><strong>Cliente:</strong>
            <span class="copy-text"
                onclick="copyToClipboard(this)"><?= htmlspecialchars($dados_cliente_equip['cliente']) ?></span>
        </p>
        <p><strong>Endereço:</strong>
            <span class="copy-text"
                onclick="copyToClipboard(this)"><?= htmlspecialchars($dados_cliente_equip['endereco']) ?></span>
        </p>
        <p><strong>Conjunto:</strong>
            <span class="copy-text" onclick="copyToClipboard(this)">
                <?= isset($dados_cliente_equip['conjunto']) ? htmlspecialchars($dados_cliente_equip['conjunto']) : 'N/A' ?>
            </span>
        </p>
        <p><strong>Quantidade:</strong>
            <span class="copy-text" onclick="copyToClipboard(this)">
                <?= isset($dados_cliente_equip['quantidade']) ? htmlspecialchars($dados_cliente_equip['quantidade']) : 'N/A' ?>
            </span>
        </p>
    </div>
    <?php endif; ?>

    <h1>Editar Dados</h1>

    <form action="edit_datas.php?id=<?= htmlspecialchars($id_vinculo) ?>" method="POST" oninput="verificarDatas()">
        <?php if (in_array('lote', $permissoes_edicao)): ?>
        <label for="lote">Lote de Produção:</label>
        <input type="text" id="lote" name="lote" value="<?= htmlspecialchars($lote) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_engenharia', $permissoes_edicao)): ?>
        <label for="data_engenharia">Data de Engenharia:</label>
        <input type="datetime-local" id="data_engenharia" name="data_engenharia"
            value="<?= htmlspecialchars($data_engenharia) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_prog_fim', $permissoes_edicao)): ?>
        <label for="data_prog_fim">Data de Inicio da Programação:</label>
        <input type="datetime-local" id="data_prog_fim" name="data_prog_fim"
            value="<?= htmlspecialchars($data_prog_fim) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_programacao', $permissoes_edicao)): ?>
        <label for="data_programacao">Data de Fim da Programação:</label>
        <input type="datetime-local" id="data_programacao" name="data_programacao"
            value="<?= htmlspecialchars($data_programacao) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_pcp', $permissoes_edicao)): ?>
        <label for="data_pcp">Data de PCP:</label>
        <input type="datetime-local" id="data_pcp" name="data_pcp" value="<?= htmlspecialchars($data_pcp) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_producao', $permissoes_edicao)): ?>
        <label for="data_producao">Data de Produção:</label>
        <input type="datetime-local" id="data_producao" name="data_producao"
            value="<?= htmlspecialchars($data_producao) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('data_qualidade', $permissoes_edicao)): ?>
        <label for="data_qualidade">Data de Qualidade:</label>
        <input type="datetime-local" id="data_qualidade" name="data_qualidade"
            value="<?= htmlspecialchars($data_qualidade) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('obs_detalhes', $permissoes_edicao)): ?>
        <label for="obs_detalhes">Obs:</label>
        <input type="text" id="obs_detalhes" name="obs_detalhes" value="<?= htmlspecialchars($obs_detalhes) ?>">
        <br><br>
        <?php endif; ?>
        <?php if (in_array('link_pastas', $permissoes_edicao)): ?>
        <label for="link_pastas">Link da Pasta:</label>
        <input type="text" id="link_pastas" name="link_pastas" value="<?= htmlspecialchars($link_pastas) ?>">
        <br><br>
        <?php endif; ?>
        <button type="submit">Salvar</button>
        <a href="javascript:void(0);" onclick="window.history.back();" class="link-voltar">Voltar</a>
    </form>

    <?php
include 'config.php';

$id_vinculo_url = $_GET['id']; // Captura o id_vinculo da URL

// Consulta para obter o lote correspondente ao id_vinculo
$queryLote = "SELECT lote FROM cliente_produto WHERE id_vinculo = '$id_vinculo_url'";
$resultLote = mysqli_query($conexao, $queryLote);

if ($rowLote = mysqli_fetch_assoc($resultLote)) {
    $lote = $rowLote['lote']; // Armazena o lote correspondente
} else {
    $lote = "Lote não encontrado"; // Caso não encontre, exibe mensagem de erro
}
?>
    <div id="modalInserirProducao" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h2>Inserir Produção</h2>
            <form id="formInserirProducao" action="inserir_producao.php" method="POST">


                <p>Lote: <?php echo htmlspecialchars($lote); ?></p>
                <input type="hidden" name="id_vinculo" value="<?php echo htmlspecialchars($id_vinculo_url); ?>">

                <!-- Checkboxes para Selecionar Múltiplos Setores -->
                <label for="id_setor">Selecione os Setores:</label>
                <div>
                    <?php
                // Consulta para obter os valores de id_setor e nome_setor da tabela setores
                include 'config.php';
                $querySetor = "SELECT id_setor, nome_setor FROM setores";
                $resultSetor = mysqli_query($conexao, $querySetor);
                while ($rowSetor = mysqli_fetch_assoc($resultSetor)) {
                    echo "<label>";
                    echo "<input type='checkbox' name='id_setor[]' value='" . $rowSetor['id_setor'] . "'>";
                    echo $rowSetor['nome_setor'];
                    echo "</label><br>";
                }
                ?>
                </div>

                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <?php if (in_array('data_programacao', $permissoes_edicao)): ?>
    <button onclick="abrirModal()">Selecionar Setores</button>
    <?php endif; ?>
    <script>
    window.onload = function() {
        var dataAtual = new Date();
        var ano = dataAtual.getFullYear();
        var mes = String(dataAtual.getMonth() + 1).padStart(2, '0'); // Adiciona 1 ao mês (0-11)
        var dia = String(dataAtual.getDate()).padStart(2, '0');
        var horas = String(dataAtual.getHours()).padStart(2, '0');
        var minutos = String(dataAtual.getMinutes()).padStart(2, '0');

        var dataFormatada = `${ano}-${mes}-${dia}T${horas}:${minutos}`;
    };

    function abrirModal() {
        var modal = document.getElementById("modalInserirProducao");
        modal.style.display = "block";
    }

    function fecharModal() {
        var modal = document.getElementById("modalInserirProducao");
        modal.style.display = "none";
    }

    function copyToClipboard(element) {
        // Cria um elemento de input temporário para selecionar e copiar o texto
        const tempInput = document.createElement('input');
        tempInput.value = element.innerText;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    }
    </script>
</body>

</html>