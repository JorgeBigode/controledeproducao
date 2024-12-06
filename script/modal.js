document.addEventListener('DOMContentLoaded', function() {
    // Funções e código que manipulam o DOM
    function abrirEditarDatasModal(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao) {
        document.getElementById('equipamentoInput').value = equipamento;
        document.getElementById('loteInput').value = lote;
        document.getElementById('conjuntoInput').value = conjunto;
        document.getElementById('dataProgramacaoInput').value = dataProgramacao;
        document.getElementById('dataPCPInput').value = dataPCP;
        document.getElementById('dataProducaoInput').value = dataProducao;
    
        // Exibe o modal
        document.getElementById('editarDatasModal').style.display = 'block';
    }
    

    function editarDatas(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao) {
        var modal = document.getElementById('editarDatasModal');
        if (modal) {
            modal.style.display = 'block';
            preencherModal(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao);
        } else {
            console.error("O modal 'editarDatasForm' não foi encontrado.");
        }
    }

    // Função que preenche o modal com os valores fornecidos
    function preencherModal(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao) {
        document.getElementById('equipamentoInput').value = equipamento;
        document.getElementById('loteInput').value = lote;
        document.getElementById('conjuntoInput').value = conjunto;

        // Verifica se as datas estão no formato correto, senão define como vazio
        document.getElementById('dataProgramacaoInput').value = isValidDate(dataProgramacao) ? dataProgramacao : '';
        document.getElementById('dataPCPInput').value = isValidDate(dataPCP) ? dataPCP : '';
        document.getElementById('dataProducaoInput').value = isValidDate(dataProducao) ? dataProducao : '';
    }

    // Função para validar o formato da data (YYYY-MM-DD)
    function isValidDate(dateString) {
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        return regex.test(dateString);
    }

    // Exportar as funções se necessário
    window.abrirEditarDatasModal = abrirEditarDatasModal;
    window.editarDatas = editarDatas;
});

function fecharEditarDatasModal() {
    document.getElementById('editarDatasModal').style.display = 'none';
}

function salvarDatasEspecifico() {
    var equipamento = document.getElementById('equipamentoInput').value;
    var lote = document.getElementById('loteInput').value;
    var conjunto = document.getElementById('conjuntoInput').value;
    var dataProgramacao = document.getElementById('dataProgramacaoInput').value;
    var dataPCP = document.getElementById('dataPCPInput').value;
    var dataProducao = document.getElementById('dataProducaoInput').value;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'atualizar_datas.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            location.reload(); // Recarrega a página após o sucesso
        } else {
            alert('Erro ao atualizar datas.');
        }
    };
    xhr.send('equipamento=' + encodeURIComponent(equipamento) +
             '&lote=' + encodeURIComponent(lote) +
             '&conjunto=' + encodeURIComponent(conjunto) +
             '&data_programacao=' + encodeURIComponent(dataProgramacao) +
             '&data_pcp=' + encodeURIComponent(dataPCP) +
             '&data_producao=' + encodeURIComponent(dataProducao));
}