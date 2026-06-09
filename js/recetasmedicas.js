async function inicializarRecetasMedicas() {
  const contenedor = document.getElementById("recetasContenido");
  if (!contenedor) {
    console.error("No existe #recetasContenido");
    return;
  }

  contenedor.innerHTML = `
    <div class="recetas-loading-card">
      Cargando recetas médicas...
    </div>
  `;

  const usuario = await validarSesion();
  if (!usuario) return;

  try {
    const res = await fetch("php/obtener_recetas_medicas.php", {
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
        <div class="recetas-empty-card">
          ${response.message || "No fue posible obtener las recetas médicas."}
        </div>
      `;
      return;
    }

    const recetas = response.data || [];

    if (recetas.length === 0) {
      contenedor.innerHTML = `
        <div class="recetas-empty-card">
          No se encontraron recetas médicas para este usuario.
        </div>
      `;
      return;
    }

    const filas = recetas.map(r => `
      <div class="receta-row-card">
        <div class="receta-col receta-col-principal">
          <span class="receta-label">Receta</span>
          <strong>#${r.idreceta}</strong>
        </div>

        <div class="receta-col receta-col-principal">
          <span class="receta-label">Médico / Paciente</span>
          <strong>${r.medico ?? "Sin médico"}</strong>
          <small>${r.nombre ?? ""}</small>
        </div>

        <div class="receta-col">
          <span class="receta-label">Especialidad</span>
          <strong>${r.especialidad ?? "Sin especialidad"}</strong>
        </div>

        <div class="receta-col receta-col-btn">
          <button class="receta-btn-ver" onclick="verRecetaDetalle(${r.idreceta})">
            Ver receta
          </button>
        </div>
      </div>
    `).join("");

    contenedor.innerHTML = `
      <div class="recetas-lista">
        ${filas}
      </div>
    `;
  } catch (error) {
    contenedor.innerHTML = `
      <div class="recetas-empty-card">
        Error al cargar las recetas médicas.
      </div>
    `;
    console.error("Error recetas:", error);
  }
}

function verRecetaDetalle(idreceta) {
  sessionStorage.setItem("idrecetaSeleccionada", idreceta);
  cargarVista("recetadetalle");
}