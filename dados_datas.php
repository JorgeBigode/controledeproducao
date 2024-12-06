<?php
include('config.php');
header('Content-Type: application/json');

// Ativa o modo de exceção no MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['produto_id'])) {
        echo json_encode([
            'error' => true,
            'message' => 'ID do produto ausente.'
        ]);
        exit();
    }

    $id_vinculo = $_POST['produto_id'];
    $data_pcp = isset($_POST['data_pcp']) ? $_POST['data_pcp'] : null;
    $data_producao = isset($_POST['data_producao']) ? $_POST['data_producao'] : null;
    $data_qualidade = isset($_POST['data_qualidade']) ? $_POST['data_qualidade'] : null;

    try {
        // Buscar valores atuais do banco de dados
        $query_select = "SELECT data_pcp, data_producao, data_qualidade FROM cliente_produto WHERE id_vinculo = ?";
        $stmt_select = $conexao->prepare($query_select);
        $stmt_select->bind_param("i", $id_vinculo);
        $stmt_select->execute();
        $stmt_select->bind_result($current_data_pcp, $current_data_producao, $current_data_qualidade);
        $stmt_select->fetch();
        $stmt_select->close();

        // Verificar se houve modificação em algum campo
        $fields = [];
        $params = [];
        $types = '';

        if ($data_pcp && $data_pcp !== $current_data_pcp) {
            $fields[] = "data_pcp = ?";
            $params[] = $data_pcp;
            $types .= 's';
        }

        if ($data_producao && $data_producao !== $current_data_producao) {
            $fields[] = "data_producao = ?";
            $params[] = $data_producao;
            $types .= 's';
        }

        if ($data_qualidade && $data_qualidade !== $current_data_qualidade) {
            $fields[] = "data_qualidade = ?";
            $params[] = $data_qualidade;
            $types .= 's';
        }

        if (count($fields) > 0) {
            // Campos foram modificados, preparar e executar a atualização
            $params[] = $id_vinculo;
            $types .= 'i';

            $query = "UPDATE cliente_produto SET " . implode(", ", $fields) . " WHERE id_vinculo = ?";
            $stmt = $conexao->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'id' => $id_vinculo,
                    'data_pcp' => $data_pcp,
                    'data_producao' => $data_producao,
                    'data_qualidade' => $data_qualidade
                ]);
            } else {
                echo json_encode([
                    'error' => true,
                    'message' => 'Falha ao atualizar os dados.'
                ]);
            }
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Nenhuma modificação detectada nos dados.'
            ]);
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Erro ao processar a solicitação: ' . $e->getMessage()
        ]);
    }
    exit();
}
?>
