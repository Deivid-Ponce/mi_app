function inicializarUsuario() {
  const contenedor = document.getElementById("usuarioContenido");
  if (!contenedor) return;

  fetch("php/obtener_usuario.php")
    .then(res => res.text())
    .then(texto => {
      let data;

      try {
        data = JSON.parse(texto);
      } catch (e) {
        contenedor.innerHTML = `
          <div class="usuario-empty-card">El servidor no devolvió JSON válido.</div>
        `;
        console.error("Respuesta inválida:", texto);
        return;
      }

      if (!data.success || !data.data) {
        contenedor.innerHTML = `
          <div class="usuario-empty-card">${data.message || "No se encontró información."}</div>
        `;
        return;
      }

      const u = data.data;
      const nombreCompleto = [
        u.Nombre || "",
        u.Appaterno || "",
        u.ApMaterno || ""
      ].join(" ").replace(/\s+/g, " ").trim();

      const fechaNacimiento = u.FechaNacimiento ? formatearFechaUsuario(u.FechaNacimiento) : "";

      contenedor.innerHTML = `
        <div class="usuario-ficha-card">
            <div class="usuario-ficha-top">
                <div class="usuario-avatar">
            <img id="usuarioFotoFicha" src="img/user.png" alt="Foto usuario">
        </div>

            <div class="usuario-top-info">
              <h3>${nombreCompleto}</h3>
              <p>Credencial: ${u.NumCredencial || ""}</p>
            </div>
          </div>

            <div class="usuario-dato-item">
              <span class="usuario-label">CURP</span>
              <strong>${u.CURP || ""}</strong>
            </div>

            <div class="usuario-dato-item">
              <span class="usuario-label">Lugar de nacimiento</span>
              <strong>${u.LugarNacimiento || ""}</strong>
            </div>

            <div class="usuario-dato-item">
              <span class="usuario-label">Fecha de nacimiento</span>
              <strong>${fechaNacimiento || ""}</strong>
            </div>
          </div>
        </div>
      `;

const img = document.getElementById("usuarioFotoFicha");

if (img && u.IdPersonal) {

    const idSeguro = Number(u.IdPersonal);

    if (Number.isInteger(idSeguro) && idSeguro > 0) {

        const rutaFoto = `/app/_lib/file/img/fotos/${idSeguro}.jpg?v=${Date.now()}`;

        const precarga = new Image();

        precarga.onload = function () {
            img.src = rutaFoto;
            img.classList.add("foto-cargada");
        };

        precarga.onerror = function () {
            img.src = "img/user.png";
            img.classList.add("foto-cargada");
        };

        precarga.src = rutaFoto;

    } else {
        img.classList.add("foto-cargada");
    }
}
    })
    .catch(error => {
      contenedor.innerHTML = `
        <div class="usuario-empty-card">Error al cargar la información del usuario.</div>
      `;
      console.error(error);
    });
}

function formatearFechaUsuario(fecha) {
  const f = new Date(fecha);
  if (isNaN(f.getTime())) return fecha;

  return f.toLocaleDateString("es-MX", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  });
}

/*           <div class="usuario-datos-grid">
            <div class="usuario-dato-item">
              <span class="usuario-label">Nombre completo</span>
              <strong>${nombreCompleto}</strong>
            </div>
*/