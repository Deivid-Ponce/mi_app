async function inicializarEstadoCuenta() {
  const contenedor = document.getElementById("estadoCuentaContenido");
  if (!contenedor) {
    console.error("No existe #estadoCuentaContenido");
    return;
  }

  contenedor.innerHTML = `<div class="estado-loading-card">Cargando estado de cuenta...</div>`;

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
    console.error("Error validando sesión en estado de cuenta:", error);
    localStorage.removeItem("usuario");
    window.location.href = "login.html";
    return;
  }

  try {
    const res = await fetch("php/obtener_estadocuenta.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const response = await res.json();

    if (res.status === 401) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    if (!response.success) {
      contenedor.innerHTML = `
        <div class="estado-empty-card">
          ${response.message || "No fue posible obtener el estado de cuenta."}
        </div>
      `;
      return;
    }

    const movimientos = response.data || [];

    if (movimientos.length === 0) {
      contenedor.innerHTML = `
        <div class="estado-empty-card">
          No se encontraron movimientos para este usuario.
        </div>
      `;
      return;
    }

    const bloques = movimientos.map(m => `
      <div class="estado-item-card">
        <div class="estado-item-row">
          <div class="estado-item-col">
            <span class="estado-label">Fecha</span>
            <strong>${formatearFecha(m.Fechaaplicacion)}</strong>
          </div>
          <div class="estado-item-col">
            <span class="estado-label">Quincena</span>
            <strong>${m.quincena ?? ""}</strong>
          </div>
        </div>

        <div class="estado-item-row">
          <div class="estado-item-col">
            <span class="estado-label">Cargo</span>
            <strong class="monto-cargo">${formatearMoneda(m.cargo)}</strong>
          </div>
        </div>

        <div class="estado-item-row">
          <div class="estado-item-col">
            <span class="estado-label">Abono</span>
            <strong class="monto-abono">${formatearMoneda(m.abono)}</strong>
          </div>
          <div class="estado-item-col">
            <span class="estado-label">Saldo</span>
            <strong class="monto-saldo">${formatearMoneda(m.saldo)}</strong>
          </div>
        </div>
      </div>
    `).join("");

    contenedor.innerHTML = `
      <div class="estado-lista-cards">
        ${bloques}
      </div>
    `;
  } catch (error) {
    contenedor.innerHTML = `
      <div class="estado-empty-card">
        Error al cargar el estado de cuenta.
      </div>
    `;
    console.error("Error en estado de cuenta:", error);
  }
}

function formatearMoneda(valor) {
  const numero = Number(valor || 0);
  return numero.toLocaleString("es-MX", {
    style: "currency",
    currency: "MXN",
    minimumFractionDigits: 2
  });
}

function formatearFecha(fecha) {
  if (!fecha) return "";
  const soloFecha = fecha.split(" ")[0];
  const partes = soloFecha.split("-");
  if (partes.length !== 3) return fecha;
  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}