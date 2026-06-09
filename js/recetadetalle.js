function inicializarRecetaDetalle() {
  const contenedor = document.getElementById("recetaDetalleContenido");
  if (!contenedor) {
    console.error("No existe #recetaDetalleContenido");
    return;
  }

  const idreceta = sessionStorage.getItem("idrecetaSeleccionada");

  if (!idreceta) {
    contenedor.innerHTML = `
      <div class="recetas-empty-card">
        No se encontró la receta seleccionada.
      </div>
    `;
    return;
  }

  contenedor.innerHTML = `
    <div class="recetas-loading-card">
      Cargando receta...
    </div>
  `;

  fetch(`php/obtener_receta_detalle.php?idreceta=${encodeURIComponent(idreceta)}`)
    .then(res => res.json())
    .then(response => {
      if (!response.success) {
        contenedor.innerHTML = `
          <div class="recetas-empty-card">
            ${response.message || "No fue posible obtener la receta."}
          </div>
        `;
        return;
      }

      const items = response.data || [];

      if (items.length === 0) {
        contenedor.innerHTML = `
          <div class="recetas-empty-card">
            No se encontraron datos para esta receta.
          </div>
        `;
        return;
      }

      const cabecera = items[0];

      const medicamentos = items.map(item => `
        <div class="medicamento-card">
          <div class="medicamento-header">
          <span class="medicamento-cantidad">Cant. ${item.cantidad ?? 0}</span>
            <br>
            <br>
            <h4>${item.medicamento ?? "Medicamento sin nombre"}</h4>
          </div>

          <div class="medicamento-grid">
           <!-- <div>
              <span>Presentación</span>
              <strong>${item.presentacion ?? "No especificada"}</strong>
            </div> -->
            <div>
              <span>Indicaciones</span>
              <strong>${item.posologia ?? "No especificada"}</strong>
            </div>
          </div>
        </div>
      `).join("");

      contenedor.innerHTML = `
        <div class="receta-premium-card">
          <div class="receta-premium-top">
            <div class="receta-folio">Receta #${cabecera.idreceta}</div>
            <button class="recetas-btn-regresar" onclick="cargarVista('recetasmedicas')">
              ← Regresar
            </button>
          </div>

          <div class="receta-premium-header">
              
            <h2>${cabecera.nombre ?? "Paciente"}</h2>
            <p>${formatearFechaReceta(cabecera.fechareceta)}</p>
          </div>

          <div class="receta-info-grid">
            <div class="receta-info-item">
              <span>Médico</span>
              <strong>${cabecera.medico ?? "No especificado"}</strong>
            </div>
            <div class="receta-info-item">
              <span>Especialidad</span>
              <strong>${cabecera.especialidad ?? "No especificada"}</strong>
            </div>
          </div>

          <div class="receta-diagnostico-card">
            <span>Diagnóstico</span>
            <p>${cabecera.diagnostico ?? "No especificado"}</p>
          </div>

          <div class="receta-medicamentos-wrap">
            <h3>Medicamentos recetados</h3>
            ${medicamentos}
          </div>
        </div>
      `;
    })
    .catch(error => {
      contenedor.innerHTML = `
        <div class="recetas-empty-card">
          Error al cargar la receta.
        </div>
      `;
      console.error("Error detalle receta:", error);
    });
}

function formatearFechaReceta(fecha) {
  if (!fecha) return "Fecha no disponible";
  const soloFecha = fecha.split(" ")[0];
  const partes = soloFecha.split("-");
  if (partes.length !== 3) return fecha;
  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}
/* <button class="recetas-btn-regresar" onclick="cargarVista('recetasmedicas')">
              ← Regresar
            </button>*/