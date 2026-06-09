async function inicializarOrganizacion() {
  const grid = document.getElementById("organizacionGrid");

  if (!grid) {
    return;
  }

  const usuario = await validarSesion();

  if (!usuario) {
    return;
  }

  try {
    const res = await fetch("php/obtener_organizacion.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        "Accept": "application/json"
      }
    });

    const texto = await res.text();
    let data;

    try {
      data = JSON.parse(texto);
    } catch (e) {
      grid.innerHTML = `<div class="organizacion-empty">No se pudo cargar la información.</div>`;
      console.error("Respuesta inválida:", texto);
      return;
    }

    if (res.status === 401) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    if (!res.ok || !data.success) {
      grid.innerHTML = `
        <div class="organizacion-empty">
          ${escaparHTML(data.message || "No se pudo obtener la información.")}
        </div>
      `;
      return;
    }

    if (!Array.isArray(data.data) || data.data.length === 0) {
      grid.innerHTML = `<div class="organizacion-empty">No se encontraron personas relacionadas.</div>`;
      return;
    }

    grid.innerHTML = "";

    data.data.forEach(persona => {
      const idPersonal = Number(persona.IdPersonal || persona.idpersonal || 0);

      if (!Number.isInteger(idPersonal) || idPersonal <= 0) {
        return;
      }

      const nombreCompleto = [
        persona.Nombre || "",
        persona.Appaterno || "",
        persona.ApMaterno || ""
      ].join(" ").replace(/\s+/g, " ").trim();

      const relacion = persona.Relacion || "";
      const fechaVigencia = persona.FechaVigencia ? formatearFecha(persona.FechaVigencia) : "";

      const card = document.createElement("div");
      card.className = "organizacion-card";

      const fotoWrapper = document.createElement("div");
      fotoWrapper.className = "organizacion-foto-wrapper";

      const img = document.createElement("img");
      img.className = "organizacion-foto";
      img.src = `/app/_lib/file/img/fotos/${idPersonal}.jpg?v=${Date.now()}`;
      img.alt = nombreCompleto;
      img.title = "Actualizar foto";
      img.style.cursor = "pointer";

      img.onerror = function () {
        this.onerror = null;
        this.src = "img/user.png";
      };

      img.addEventListener("click", () => {
        sessionStorage.setItem("idPersonalFoto", String(idPersonal));
        sessionStorage.setItem("nombrePersonalFoto", nombreCompleto);
        sessionStorage.setItem("relacionPersonalFoto", relacion);

        cargarVista("prueba");
      });

      fotoWrapper.appendChild(img);

      const info = document.createElement("div");
      info.className = "organizacion-info";

      const h3 = document.createElement("h3");
      h3.className = "organizacion-nombre";
      h3.textContent = nombreCompleto;

      const pRelacion = document.createElement("p");
      pRelacion.className = "organizacion-relacion";
      pRelacion.textContent = relacion;

      const pVigencia = document.createElement("p");
      pVigencia.className = "organizacion-vigencia";
      pVigencia.textContent = fechaVigencia ? `Vigencia: ${fechaVigencia}` : "";

      info.appendChild(h3);
      info.appendChild(pRelacion);
      info.appendChild(pVigencia);

      card.appendChild(fotoWrapper);
      card.appendChild(info);

      grid.appendChild(card);
    });

  } catch (error) {
    grid.innerHTML = `<div class="organizacion-empty">Error al cargar la información.</div>`;
    console.error(error);
  }
}

function formatearFecha(fecha) {
  const f = new Date(fecha);

  if (isNaN(f.getTime())) {
    return fecha;
  }

  return f.toLocaleDateString("es-MX", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  });
}

function escaparHTML(valor) {
  return String(valor ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}