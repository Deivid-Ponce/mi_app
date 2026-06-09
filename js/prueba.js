let streamPrueba = null;
let selfieSegmentation = null;

let ultimaImagenJpg = null;
let fotoCongeladaJpg = null;

let procesandoIA = false;
let frameIAActivo = false;
let modoCongelado = false;

function inicializarPrueba() {
  const video = document.getElementById("videoPrueba");
  const canvas = document.getElementById("canvasPreview");
  const btnTomar = document.getElementById("btnTomarFoto");
  const btnSubir = document.getElementById("btnSubirFoto");
  const btnCancelar = document.getElementById("btnCancelarFoto");
  const personaFotoInfo = document.getElementById("personaFotoInfo");

  if (!video || !canvas || !btnTomar || !btnSubir || !btnCancelar) {
    console.warn("No se encontraron elementos de prueba.");
    return;
  }

  const idPersonalFoto = sessionStorage.getItem("idPersonalFoto");
  const nombrePersonalFoto = sessionStorage.getItem("nombrePersonalFoto") || "Persona seleccionada";
  const relacionPersonalFoto = sessionStorage.getItem("relacionPersonalFoto") || "";

  if (personaFotoInfo) {
    personaFotoInfo.textContent = relacionPersonalFoto
      ? `${nombrePersonalFoto} - ${relacionPersonalFoto}`
      : nombrePersonalFoto;
  }

  if (!idPersonalFoto || !/^\d+$/.test(idPersonalFoto)) {
    if (personaFotoInfo) {
      personaFotoInfo.textContent = "No se recibió persona seleccionada.";
    }
    return;
  }

  const ctx = canvas.getContext("2d");

  const fondo = new Image();
  fondo.src = "img/fondo.jpg";

  function mostrarBotonesModoCamara() {
    btnTomar.style.display = "inline-block";
    btnSubir.style.display = "none";
    btnCancelar.style.display = "none";

    btnTomar.disabled = false;
    btnSubir.disabled = false;
    btnCancelar.disabled = false;
  }

  function mostrarBotonesModoFoto() {
    btnTomar.style.display = "none";
    btnSubir.style.display = "inline-block";
    btnCancelar.style.display = "inline-block";

    btnTomar.disabled = false;
    btnSubir.disabled = false;
    btnCancelar.disabled = false;
  }

  async function iniciarCamaraAutomatica() {
    try {
      modoCongelado = false;
      fotoCongeladaJpg = null;
      ultimaImagenJpg = null;
      procesandoIA = false;
      frameIAActivo = false;

      mostrarBotonesModoCamara();

      if (streamPrueba) {
        streamPrueba.getTracks().forEach(track => track.stop());
        streamPrueba = null;
      }

      streamPrueba = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: "user",
          width: { ideal: 720 },
          height: { ideal: 960 }
        },
        audio: false
      });

      video.srcObject = streamPrueba;
      await video.play();

      canvas.width = video.videoWidth || 720;
      canvas.height = video.videoHeight || 960;

      await iniciarSegmentacion();

    } catch (error) {
      console.error("Error al iniciar cámara:", error);
      if (personaFotoInfo) {
        personaFotoInfo.textContent = "No se pudo iniciar la cámara.";
      }
    }
  }

  async function iniciarSegmentacion() {
    if (typeof SelfieSegmentation === "undefined") {
      throw new Error("No cargó SelfieSegmentation.");
    }

    selfieSegmentation = new SelfieSegmentation({
      locateFile: file => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
    });

    selfieSegmentation.setOptions({
      modelSelection: 1
    });

    selfieSegmentation.onResults(results => {
      if (modoCongelado) return;

      ctx.clearRect(0, 0, canvas.width, canvas.height);

      ctx.drawImage(results.image, 0, 0, canvas.width, canvas.height);

      ctx.save();
      ctx.globalCompositeOperation = "destination-in";
      ctx.drawImage(results.segmentationMask, 0, 0, canvas.width, canvas.height);
      ctx.restore();

      ctx.save();
      ctx.globalCompositeOperation = "destination-over";

      if (fondo.complete && fondo.naturalWidth > 0) {
        ctx.drawImage(fondo, 0, 0, canvas.width, canvas.height);
      } else {
        ctx.fillStyle = "#1e3a8a";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
      }

      ctx.restore();

      ultimaImagenJpg = canvas.toDataURL("image/jpeg", 0.92);
    });

    procesandoIA = true;

    if (!frameIAActivo) {
      frameIAActivo = true;
      procesarFrame();
    }
  }

  async function procesarFrame() {
    if (!procesandoIA) {
      frameIAActivo = false;
      return;
    }

    if (!modoCongelado && selfieSegmentation && video.readyState >= 2) {
      try {
        await selfieSegmentation.send({ image: video });
      } catch (error) {
        console.error("Error procesando segmentación:", error);
      }
    }

    requestAnimationFrame(procesarFrame);
  }

  function tomarFoto() {
    if (!ultimaImagenJpg) {
      console.warn("Espera un momento a que la cámara procese la imagen.");
      return;
    }

    modoCongelado = true;
    fotoCongeladaJpg = ultimaImagenJpg;

    mostrarBotonesModoFoto();
  }

  function cancelarFoto() {
    modoCongelado = false;
    fotoCongeladaJpg = null;
    ultimaImagenJpg = null;

    procesandoIA = true;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    mostrarBotonesModoCamara();

    if (!frameIAActivo) {
      frameIAActivo = true;
      procesarFrame();
    }
  }

  async function subirFoto() {
    if (!fotoCongeladaJpg) {
      console.warn("Primero toma una foto.");
      return;
    }

    try {
      btnSubir.disabled = true;
      btnCancelar.disabled = true;

      const formData = new FormData();
      formData.append("imagen_base64", fotoCongeladaJpg);
      formData.append("idpersonal", idPersonalFoto);

      const response = await fetch("php/subir_foto_prueba.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        cache: "no-store"
      });

      const texto = await response.text();
      let data;

      try {
        data = JSON.parse(texto);
      } catch (error) {
        console.error("Respuesta no JSON:", texto);
        if (personaFotoInfo) {
          personaFotoInfo.textContent = "No se pudo guardar la foto.";
        }
        return;
      }

      if (response.status === 401) {
        localStorage.removeItem("usuario");
        window.location.href = "login.html";
        return;
      }

      if (!data.success) {
        if (personaFotoInfo) {
          personaFotoInfo.textContent = data.message || "No se pudo guardar la foto.";
        }
        return;
      }

      procesandoIA = false;
      frameIAActivo = false;

      if (streamPrueba) {
        streamPrueba.getTracks().forEach(track => track.stop());
        streamPrueba = null;
      }

      btnSubir.style.display = "none";
      btnCancelar.style.display = "inline-block";
      btnCancelar.textContent = "Tomar otra foto";

      if (personaFotoInfo) {
        personaFotoInfo.textContent = "Foto guardada correctamente.";
      }

      btnCancelar.onclick = function () {
        btnCancelar.textContent = "Cancelar";
        iniciarCamaraAutomatica();
      };

    } catch (error) {
      console.error("Error al subir foto:", error);

      if (personaFotoInfo) {
        personaFotoInfo.textContent = "No se pudo guardar la foto.";
      }
    } finally {
      btnSubir.disabled = false;
      btnCancelar.disabled = false;
    }
  }

  btnTomar.onclick = tomarFoto;
  btnSubir.onclick = subirFoto;
  btnCancelar.onclick = cancelarFoto;

  iniciarCamaraAutomatica();
}

function detenerCamaraPrueba() {
  procesandoIA = false;
  frameIAActivo = false;
  modoCongelado = false;

  if (streamPrueba) {
    streamPrueba.getTracks().forEach(track => track.stop());
    streamPrueba = null;
  }
}