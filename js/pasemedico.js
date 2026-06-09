function inicializarPaseMedico() {
  const tbody = document.getElementById("tablaPasesMedicos");
  const usuario = JSON.parse(localStorage.getItem("usuario"));

  if (!tbody || !usuario) {
    return;
  }

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="pase-empty">Cargando información...</td>
    </tr>
  `;

  fetch("php/obtener_pases_medicos.php")
    .then(res => res.text())
    .then(texto => {
      let data;

      try {
        data = JSON.parse(texto);
      } catch (e) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="pase-empty">El servidor no devolvió JSON válido.</td>
          </tr>
        `;
        console.error("Respuesta inválida:", texto);
        return;
      }

      if (!data.success) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="pase-empty">
              ${data.message || "No se pudo obtener la información."}
            </td>
          </tr>
        `;
        return;
      }

      if (!data.data || data.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="pase-empty">No hay pases médicos disponibles.</td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = "";

      data.data.forEach((item) => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
          <td data-label="Nombre">${item.nombre || ""}</td>
          <td data-label="Médico">${item.medico || ""}</td>
          <td data-label="Especialidad">${item.especialidad || ""}</td>
          <td data-label="Acción">
            <button class="btn-pdf-pase" type="button">
              <i class="fa-solid fa-file-pdf"></i> PDF
            </button>
          </td>
        `;

        const btn = tr.querySelector(".btn-pdf-pase");

        btn.addEventListener("click", () => {
          generarPdfPaseMedico(item.idpase);
        });

        tbody.appendChild(tr);
      });
    })
    .catch(error => {
      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="pase-empty">Error al cargar los pases médicos.</td>
        </tr>
      `;
      console.error("Error fetch:", error);
    });
}

/* ======================================
   GENERAR PDF
====================================== */
function generarPdfPaseMedico(idpase) {
  if (!idpase) {
    alert("No se encontró el identificador del pase.");
    return;
  }

  const url = `php/generar_pase_medico.php?idpase=${encodeURIComponent(idpase)}`;

  // Para navegador normal
  // window.open(url, "_blank");

  // Para WebView / Android / iOS (mejor opción)
  window.location.href = url;
}