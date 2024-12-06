const buscarBox = document.querySelector('.buscar-box');
const inputBuscar = document.querySelector('.input-buscar');
const btnFechar = document.querySelector('.btn-fechar');
const lupaBuscar = document.querySelector('.lupa-buscar');

// Função para ativar a busca
function ativarBuscar() {
    buscarBox.classList.add('ativar');
    inputBuscar.style.display = 'flex'; // Mostra o input
    btnFechar.style.display = 'flex'; // Mostra o botão de fechar

    const circuloContainers = document.querySelectorAll('.circulo-container');
    circuloContainers.forEach(container => {
        const span = container.querySelector('span');
        span.style.display = 'none'; // Oculta o texto
    });
}

// Função para desativar a busca
function desativarBuscar() {
    buscarBox.classList.remove('ativar');
    inputBuscar.style.display = 'none'; // Oculta o input
    btnFechar.style.display = 'none'; // Oculta o botão de fechar

    const circuloContainers = document.querySelectorAll('.circulo-container');
    circuloContainers.forEach(container => {
        const span = container.querySelector('span');
        span.style.display = 'inline'; // Restaura a visibilidade do texto
    });
}

document.getElementById("searchInput").addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const pedidos = document.querySelectorAll(".pedido-detalhe");

    pedidos.forEach((pedido) => {
        const conteudo = pedido.textContent.toLowerCase();
        pedido.style.display = conteudo.includes(searchTerm) ? "block" : "none";
    });
});

function clearSearch() {
    document.getElementById("searchInput").value = "";
    document.querySelectorAll(".pedido-detalhe").forEach((pedido) => {
        pedido.style.display = "block";
    });
}

// Evento para limpar a busca ao clicar no botão de fechar
document.querySelector('.btn-fechar').addEventListener('click', function() {
    const searchInput = document.getElementById("searchInput");
    searchInput.value = ""; // Limpa o valor do input
    filterTable(""); // Chama a função de filtragem com uma string vazia
});

// Função para filtrar a tabela de pedidos
function filterTable(searchValue) {
    const rows = document.querySelectorAll(".pedido-detalhe"); // Seleciona todos os blocos de pedidos
    rows.forEach(row => {
        const textContent = row.textContent.toLowerCase();
        if (textContent.includes(searchValue)) {
            row.style.display = ""; // Exibe a linha
        } else {
            row.style.display = "none"; // Oculta a linha
        }
    });
}

// Eventos para abrir e fechar a busca
lupaBuscar.addEventListener('click', ativarBuscar);
btnFechar.addEventListener('click', desativarBuscar);

// **Nova Funcionalidade: Filtragem por Status**

// Adiciona evento de clique nos círculos de status para filtrar os blocos
