document.addEventListener("DOMContentLoaded", function () {
  // Verifique se o elemento existe antes de adicionar o event listener
  const openMenuButton = document.getElementById("openMenu");
  if (openMenuButton) {
    openMenuButton.addEventListener("click", function () {
      document.getElementById("menu").style.left = "0";
    });
  }

  const closeMenuButton = document.getElementById("closeMenu");
  if (closeMenuButton) {
    closeMenuButton.addEventListener("click", function () {
      document.getElementById("menu").style.left = "-250px";
    });
  }

  

  const clientSelect = document.getElementById("clientSelect");
  const clientBlocos = document.querySelectorAll(".cliente-bloco");
  let currentIndex = 0;
  const chartInstances = {};
  const devicePixelRatio = window.devicePixelRatio || 1;

  function showCliente(index) {
    clientBlocos.forEach((bloco, i) => {
      bloco.classList.toggle("active", i === index);
      bloco.style.display = i === index ? "block" : "none";
    });
    clientBlocos.forEach((bloco, i) => {
      const prevBtn = bloco.querySelector(".prev-btn");
      const nextBtn = bloco.querySelector(".next-btn");
      if (prevBtn && nextBtn) {
        // Verificar se os botões existem
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === clientBlocos.length - 1;
      }
    });
  }

  showCliente(currentIndex);

  clientBlocos.forEach((bloco, i) => {
    const prevBtn = bloco.querySelector(".prev-btn");
    const nextBtn = bloco.querySelector(".next-btn");

    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        if (currentIndex > 0) {
          currentIndex--;
          showCliente(currentIndex);
        }
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (currentIndex < clientBlocos.length - 1) {
          currentIndex++;
          showCliente(currentIndex);
        }
      });
    }
  });

  function atualizarBlocos() {
    clientBlocos.forEach((bloco) => {
      const blocoClientId = bloco.getAttribute("data-client-id");
      if (
        selectedClient === "all" ||
        String(blocoClientId) === String(selectedClient)
      ) {
        bloco.style.display = "block";
        const cliente = chartData.find(
          (c) => String(c.idcliente) === String(blocoClientId)
        );
        if (cliente) {
          // Destruir gráficos existentes, se houver
          if (chartInstances[`pedidos-${cliente.idcliente}`]) {
            chartInstances[`pedidos-${cliente.idcliente}`].destroy();
            delete chartInstances[`pedidos-${cliente.idcliente}`];
          }
          if (chartInstances[`produtos-${cliente.idcliente}`]) {
            chartInstances[`produtos-${cliente.idcliente}`].destroy();
            delete chartInstances[`produtos-${cliente.idcliente}`];
          }

          criarGraficoPedidos(cliente);
          criarGraficoProdutos(cliente);
        }
      } else {
        bloco.style.display = "none";

        const cliente = chartData.find(
          (c) => String(c.idcliente) === String(blocoClientId)
        );
        if (cliente) {
          if (chartInstances[`pedidos-${cliente.idcliente}`]) {
            chartInstances[`pedidos-${cliente.idcliente}`].destroy();
            delete chartInstances[`pedidos-${cliente.idcliente}`];
          }
          if (chartInstances[`produtos-${cliente.idcliente}`]) {
            chartInstances[`produtos-${cliente.idcliente}`].destroy();
            delete chartInstances[`produtos-${cliente.idcliente}`];
          }
        }
      }
    });
  }

  // Chame essa função onde necessário
  const primeiroVisivel = Array.from(clientBlocos).findIndex(
    (bloco) => bloco.style.display === "block"
  );
  if (primeiroVisivel !== -1) {
    currentIndex = primeiroVisivel;
    showCliente(currentIndex);
  } else {
    currentIndex = -1; // Nenhum cliente visível
  }
});

// Expansão do Cabeçalho
document.addEventListener("DOMContentLoaded", function () {
  const header = document.getElementById("header-expandable");
  const toggleButton = document.getElementById("toggleHeaderButton");

  toggleButton.addEventListener("click", function () {
    header.classList.toggle("expanded");
  });

  const toggleHeaderButton = document.getElementById("toggleHeaderButton");
  if (toggleHeaderButton) {
    toggleHeaderButton.onclick = function () {
      const sideMenu = document.getElementById("side-menu");
      if (sideMenu) {
        sideMenu.classList.toggle("open");
      }
    };
  }

  const closeButton = document.querySelector(".btn-close");
  if (closeButton) {
    closeButton.addEventListener("click", function () {
      const modal = new bootstrap.Modal(
        document.getElementById("modalRelatorio")
      );
      modal.hide();
    });
  }
});

let lastInteraction = Date.now();

function resetInteractionTimer() {
  lastInteraction = Date.now();
}

document.body.addEventListener("click", resetInteractionTimer);
document.body.addEventListener("keydown", resetInteractionTimer);
