function inicializarDocumentos() {
  const inputDocumento = document.getElementById("inputDocumento");
  const btnSubirDocumento = document.getElementById("btnSubirDocumento");
  const nombreDocumento = document.getElementById("nombreDocumento");
  const mensajeDocumento = document.getElementById("mensajeDocumento");

  if (!inputDocumento || !btnSubirDocumento || !nombreDocumento || !mensajeDocumento) {
    console.warn("No se encontraron elementos de documentos.");
    return;
  }

  function mostrarMensaje(texto, color = "#111827") {
    mensajeDocumento.style.color = color;
    mensajeDocumento.textContent = texto;
  }

  inputDocumento.addEventListener("change", function () {
    const archivo = this.files[0];

    if (!archivo) {
      nombreDocumento.textContent = "";
      return;
    }

    nombreDocumento.textContent = "Archivo seleccionado: " + archivo.name;
  });

  btnSubirDocumento.addEventListener("click", async function () {
    const archivo = inputDocumento.files[0];

    if (!archivo) {
      mostrarMensaje("Selecciona un documento primero.", "red");
      return;
    }

    try {
      btnSubirDocumento.disabled = true;
      mostrarMensaje("Subiendo documento...", "#111827");

      const formData = new FormData();
      formData.append("documento", archivo);

      const response = await fetch("php/subir_documento.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });

      const texto = await response.text();
      let data;

      try {
        data = JSON.parse(texto);
      } catch (e) {
        console.error("Respuesta no JSON:", texto);
        mostrarMensaje("El servidor no devolvió JSON válido.", "red");
        return;
      }

      console.log("Respuesta documento:", data);

      if (!data.success) {
        mostrarMensaje(data.message || "No se pudo subir el documento.", "red");
        return;
      }

      mostrarMensaje("Documento guardado correctamente.", "green");

      inputDocumento.value = "";
      nombreDocumento.textContent = "";

    } catch (error) {
      console.error(error);
      mostrarMensaje("Error al subir documento: " + error.message, "red");
    } finally {
      btnSubirDocumento.disabled = false;
    }
  });
}