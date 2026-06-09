let estudioSeleccionadoLaboratorio = null;
let personaSeleccionadaLaboratorio = null;
let nombrePersonaSeleccionadaLaboratorio = "";

function inicializarEstudiosClinicos() {
  const lista = document.getElementById("listaPersonasClinicos");
  const tabla = document.getElementById("tablaEstudiosClinicos");
  const titulo = document.getElementById("tituloTablaClinicos");

  if (!lista || !tabla) return;

  fetch("php/obtener_personas_estudios.php")
    .then(res => res.text())
    .then(texto => {
      let response;

      try {
        response = JSON.parse(texto);
      } catch (e) {
        lista.innerHTML = `<div class="estudios-empty">El servidor no devolvió JSON válido.</div>`;
        console.error("Respuesta inválida:", texto);
        return;
      }

      if (!response.success) {
        lista.innerHTML = `<div class="estudios-empty">${response.message || "No se pudo obtener la información."}</div>`;
        return;
      }

      if (!response.data || response.data.length === 0) {
        lista.innerHTML = `<div class="estudios-empty">No hay personas relacionadas activas.</div>`;
        return;
      }

      lista.innerHTML = "";

      response.data.forEach(persona => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "persona-estudio-btn";

        const nombreCompleto = [
          persona.Nombre || "",
          persona.Appaterno || "",
          persona.ApMaterno || ""
        ].join(" ").replace(/\s+/g, " ").trim();

        btn.innerHTML = `
          <span class="persona-estudio-nombre">${escaparHtml(nombreCompleto)}</span>
          <span class="persona-estudio-relacion">${escaparHtml(persona.Relacion || "")}</span>
        `;

        btn.addEventListener("click", () => {
          document.querySelectorAll(".persona-estudio-btn").forEach(el => {
            el.classList.remove("active");
          });

          btn.classList.add("active");

          if (titulo) {
            titulo.textContent = `Estudios clínicos de ${nombreCompleto}`;
          }

          nombrePersonaSeleccionadaLaboratorio = nombreCompleto;
          cargarEstudiosClinicosPersona(persona.IdPersonal, nombreCompleto);
        });

        lista.appendChild(btn);
      });
    })
    .catch(error => {
      lista.innerHTML = `<div class="estudios-empty">Error al cargar personas relacionadas.</div>`;
      console.error(error);
    });
}

function cargarEstudiosClinicosPersona(idPersonal, nombreCompleto) {
  const tabla = document.getElementById("tablaEstudiosClinicos");

  if (!tabla || !idPersonal) return;

  if (typeof nombreCompleto === "string" && nombreCompleto.trim() !== "") {
    nombrePersonaSeleccionadaLaboratorio = nombreCompleto;
  }

  tabla.innerHTML = `
    <tr>
      <td colspan="6" class="estudios-empty">Cargando estudios clínicos...</td>
    </tr>
  `;

  fetch(`php/obtener_estudios_clinicos_persona.php?idpersonal=${encodeURIComponent(idPersonal)}`)
    .then(res => res.text())
    .then(texto => {
      let response;

      try {
        response = JSON.parse(texto);
      } catch (e) {
        tabla.innerHTML = `
          <tr>
            <td colspan="6" class="estudios-empty">El servidor no devolvió JSON válido.</td>
          </tr>
        `;
        console.error("Respuesta inválida:", texto);
        return;
      }

      if (!response.success) {
        tabla.innerHTML = `
          <tr>
            <td colspan="6" class="estudios-empty">${response.message || "No se pudo obtener la información."}</td>
          </tr>
        `;
        return;
      }

      if (!response.data || response.data.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="6" class="estudios-empty">No hay estudios clínicos para ${escaparHtml(nombreCompleto || "")}.</td>
          </tr>
        `;
        return;
      }

      tabla.innerHTML = "";

      response.data.forEach(item => {
        const tr = document.createElement("tr");

        const textoBoton = item.accion_boton || "PDF";
        const hint = item.hint || "";

        const esSinLaboratorio = item.accion_boton === "SinLaboratorio";
        const puedeDescargar = item.puede_descargar === true || item.puede_descargar === 1 || item.puede_descargar === "1";
        const botonInteractivo = puedeDescargar || esSinLaboratorio;

        tr.innerHTML = `
          <td data-label="Estudio">${escaparHtml(item.estudio || "")}</td>
          <td data-label="Médico">${escaparHtml(item.medico || "")}</td>
          <td data-label="Laboratorio">${escaparHtml(item.Laboratorio || "")}</td>
          <td data-label="Fecha estudio">${formatearFechaEstudio(item.fechaestudio)}</td>
          <td data-label="Vigencia">${formatearFechaEstudio(item.FechaFinvigencia)}</td>
          <td data-label="Acción">
            <button
              class="btn-pdf-estudio ${!botonInteractivo ? "bloqueado" : ""} ${esSinLaboratorio ? "sin-laboratorio" : ""}"
              type="button"
              title="${escaparHtml(hint)}"
              ${!botonInteractivo ? "disabled" : ""}
            >
              ${escaparHtml(textoBoton)}
            </button>
            <div class="accion-hint">${escaparHtml(hint)}</div>
          </td>
        `;

        const btnPdf = tr.querySelector(".btn-pdf-estudio");

        if (esSinLaboratorio) {
          btnPdf.addEventListener("click", () => {
            if (!item.idestudio || !item.idpersonal) {
              alert("No se encontró la información del estudio.");
              return;
            }

            abrirModalLaboratorio(item.idestudio, item.idpersonal, item.estudio || "");
          });
        } else if (puedeDescargar) {
          btnPdf.addEventListener("click", () => {
            if (!item.idestudio) {
              alert("No se encontró el identificador del estudio.");
              return;
            }

            window.location.href = `php/generar_pase_clinico.php?idestudio=${encodeURIComponent(item.idestudio)}&idpersonal=${encodeURIComponent(item.idpersonal)}`;
          });
        }

        tabla.appendChild(tr);
      });
    })
    .catch(error => {
      tabla.innerHTML = `
        <tr>
          <td colspan="6" class="estudios-empty">Error al cargar los estudios clínicos.</td>
        </tr>
      `;
      console.error(error);
    });
}

function abrirModalLaboratorio(idestudio, idpersonal, estudioNombre) {
  estudioSeleccionadoLaboratorio = idestudio;
  personaSeleccionadaLaboratorio = idpersonal;

  const modal = document.getElementById("modalLaboratorio");
  const texto = document.getElementById("modalLaboratorioTexto");

  if (texto) {
    texto.textContent = `El estudio "${estudioNombre || "seleccionado"}" no tiene laboratorio asignado. Seleccione uno para continuar.`;
  }

  if (modal) {
    modal.classList.add("activo");
  }
}

function cerrarModalLaboratorio() {
  const modal = document.getElementById("modalLaboratorio");

  if (modal) {
    modal.classList.remove("activo");
  }

  estudioSeleccionadoLaboratorio = null;
  personaSeleccionadaLaboratorio = null;
}

function seleccionarLaboratorio(nombreLaboratorio) {
  if (!estudioSeleccionadoLaboratorio || !personaSeleccionadaLaboratorio) {
    alert("No se encontró el estudio a actualizar.");
    return;
  }

  const idPersonalRecargar = personaSeleccionadaLaboratorio;

  fetch("php/guardar_laboratorio_estudio.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      idestudio: estudioSeleccionadoLaboratorio,
      idpersonal: personaSeleccionadaLaboratorio,
      laboratorio: nombreLaboratorio
    })
  })
    .then(res => res.text())
    .then(texto => {
      let response;

      try {
        response = JSON.parse(texto);
      } catch (e) {
        alert("El servidor no devolvió JSON válido al guardar el laboratorio.");
        console.error("Respuesta inválida:", texto);
        return;
      }

      if (!response.success) {
        alert(response.message || "No fue posible guardar el laboratorio.");
        return;
      }

      cerrarModalLaboratorio();
      cargarEstudiosClinicosPersona(idPersonalRecargar, nombrePersonaSeleccionadaLaboratorio);
    })
    .catch(error => {
      alert("Error al guardar el laboratorio.");
      console.error(error);
    });
}

function formatearFechaEstudio(fecha) {
  if (!fecha) return "";

  const f = new Date(fecha);
  if (isNaN(f.getTime())) return fecha;

  return f.toLocaleDateString("es-MX");
}

function escaparHtml(texto) {
  return String(texto)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

document.addEventListener("click", function (e) {
  const modal = document.getElementById("modalLaboratorio");
  if (!modal) return;

  if (e.target === modal) {
    cerrarModalLaboratorio();
  }
});