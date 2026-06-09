async function inicializarDetalleInicio() {
  const contenedor = document.getElementById("detalleInicioContenido");
  if (!contenedor) return;

  try {
    const respuestaSesion = await fetch("php/validar_sesion.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const dataSesion = await respuestaSesion.json();

    if (!respuestaSesion.ok || !dataSesion.success) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    localStorage.setItem("usuario", JSON.stringify(dataSesion.usuario));
  } catch (error) {
    console.error("Error validando sesión en detalle de inicio:", error);
    localStorage.removeItem("usuario");
    window.location.href = "login.html";
    return;
  }

  const detalle = JSON.parse(localStorage.getItem("detalleInicio"));

  if (!detalle) {
    contenedor.innerHTML = `
      <div class="detalleinicio-empty-card">No se encontró información para mostrar.</div>
    `;
    return;
  }

  contenedor.innerHTML = `
    <div class="detalleinicio-card">
      <div class="detalleinicio-imagen-wrapper">
        <img id="detalleInicioImagen" src="${detalle.imagen}" alt="${detalle.titulo}">
      </div>

      <div class="detalleinicio-info">
        <span class="detalleinicio-tipo">${detalle.tipo === "evento" ? "Próximo evento" : "Aviso"}</span>
        <h2>${detalle.titulo}</h2>
        <p class="detalleinicio-fecha">${detalle.fecha}</p>
        <p class="detalleinicio-descripcion">${detalle.descripcion}</p>

        <button class="detalleinicio-btn" onclick="cargarVista('inicio')">
          Regresar
        </button>
      </div>
    </div>
  `;

  const img = document.getElementById("detalleInicioImagen");
  if (img) {
    img.onerror = function () {
      this.src = "img/user.png";
    };
  }
}