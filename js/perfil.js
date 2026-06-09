function inicializarPerfil() {
  const usuario = JSON.parse(localStorage.getItem("usuario"));

  if (!usuario) {
    window.location.href = "login.html";
    return;
  }

  const nombre = usuario.nombre || usuario.login || "SIN NOMBRE";
  const login = usuario.login || "";
  const idPersonal = usuario.idPersonal || "";
  const qrCanvas = document.getElementById("qrcodePerfil");

  const img = document.getElementById("perfilUserImage");
  const nameEl = document.getElementById("perfilUserName");
  const statusEl = document.getElementById("perfilMemberStatus");
  const numberEl = document.getElementById("perfilMemberNumber");
  const card = document.getElementById("credencialCard");
  const container = document.getElementById("credencialContainer");

  if (nameEl) nameEl.textContent = nombre;
  if (statusEl) statusEl.textContent = "MIEMBRO ACTIVO";
  if (numberEl) numberEl.textContent = login;

if (img && idPersonal) {
  const idSeguro = Number(idPersonal);

  if (Number.isInteger(idSeguro) && idSeguro > 0) {
    const rutaFoto = `/app/_lib/file/img/fotos/${idSeguro}.jpg?v=${Date.now()}`;

    const precarga = new Image();

    precarga.onload = function () {
      img.src = rutaFoto;
      img.classList.add("foto-cargada");
      img.parentElement.classList.add("cargada");
    };

    precarga.onerror = function () {
      img.src = "img/user.png";
      img.classList.add("foto-cargada");
      img.parentElement.classList.add("cargada");
    };

    precarga.src = rutaFoto;
  } else {
    img.classList.add("foto-cargada");
    img.parentElement.classList.add("cargada");
  }
}


   if (qrCanvas && typeof QRious !== "undefined") {
    const valorQR = String(idPersonal || login);

    new QRious({
      element: qrCanvas,
      value: valorQR,
      size: 150,
      level: "H"
    });
  }

  if (typeof QRious !== "undefined") {
    const qrCanvas = document.getElementById("qrcodePerfil");
    if (qrCanvas) {
      new QRious({
        element: qrCanvas,
        value: String(idPersonal || login),
        size: 150,
        level: "H"
      });
    }
  }

  if (container && card) {
    container.onclick = () => {
      card.classList.toggle("flipped");
    };
  }
}