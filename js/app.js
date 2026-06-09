document.addEventListener("DOMContentLoaded", async () => {
  const usuario = await validarSesionApp();

  if (!usuario) {
    return;
  }

  const nombreUsuario = document.getElementById("nombreUsuario");
  const correoUsuario = document.getElementById("correoUsuario");

  if (nombreUsuario) {
    nombreUsuario.textContent = usuario.nombre || usuario.login || "Usuario";
  }

  if (correoUsuario) {
    correoUsuario.textContent = usuario.login || "";
  }

  const sideNombreUsuario = document.getElementById("sideNombreUsuario");
  const sideCorreoUsuario = document.getElementById("sideCorreoUsuario");

  if (sideNombreUsuario) {
    sideNombreUsuario.textContent = usuario.nombre || usuario.login || "Usuario";
  }

  if (sideCorreoUsuario) {
    sideCorreoUsuario.textContent = usuario.login || "";
  }

  if (usuario.idPersonal) {
    actualizarFotosUsuario(usuario.idPersonal);
  }

  const hash = window.location.hash;

  if (hash === "#pasemedico") {
    cargarVista("pasemedico");
    history.replaceState(null, null, window.location.pathname);
  } else {
    cargarVista("inicio");
  }

  setTimeout(() => {
    if (typeof revisarPasesYNotificar === "function") {
      revisarPasesYNotificar();
    }
  }, 1200);

  const menuBtn = document.getElementById("menuBtn");
  const closeMenuBtn = document.getElementById("closeMenuBtn");
  const sideMenu = document.getElementById("sideMenu");
  const menuOverlay = document.getElementById("menuOverlay");
  const sideMenuItems = document.querySelectorAll(".side-menu-item[data-vista]");
  const sideMenuToggles = document.querySelectorAll(".side-menu-toggle");
  const sideSubmenuItems = document.querySelectorAll(".side-submenu-item");
  const logoutMenuItem = document.getElementById("logoutMenuItem");

  function abrirMenu() {
    if (sideMenu) sideMenu.classList.add("open");
    if (menuOverlay) menuOverlay.classList.add("show");
    document.body.classList.add("menu-open");
  }

  function cerrarMenu() {
    if (sideMenu) sideMenu.classList.remove("open");
    if (menuOverlay) menuOverlay.classList.remove("show");
    document.body.classList.remove("menu-open");
  }

  if (menuBtn) {
    menuBtn.addEventListener("click", abrirMenu);
  }

  if (closeMenuBtn) {
    closeMenuBtn.addEventListener("click", cerrarMenu);
  }

  if (menuOverlay) {
    menuOverlay.addEventListener("click", cerrarMenu);
  }

  sideMenuItems.forEach(item => {
    item.addEventListener("click", e => {
      e.preventDefault();

      const vista = item.getAttribute("data-vista");

      if (vista) {
        cargarVista(vista);
        cerrarMenu();
      }
    });
  });

  sideMenuToggles.forEach(toggle => {
    toggle.addEventListener("click", () => {
      const group = toggle.closest(".side-menu-group");

      if (group) {
        group.classList.toggle("open");
      }
    });
  });

  sideSubmenuItems.forEach(item => {
    item.addEventListener("click", e => {
      e.preventDefault();

      const vista = item.getAttribute("data-vista");

      if (vista) {
        cargarVista(vista);
        cerrarMenu();
      }
    });
  });

  if (logoutMenuItem) {
    logoutMenuItem.addEventListener("click", e => {
      e.preventDefault();
      cerrarMenu();
      logout(e);
    });
  }

  document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
      cerrarMenu();
    }
  });
});

async function validarSesionApp() {
  try {
    const respuesta = await fetch("php/validar_sesion.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        "Accept": "application/json"
      }
    });

    const texto = await respuesta.text();
    let data;

    try {
      data = JSON.parse(texto);
    } catch (error) {
      console.error("Respuesta no JSON en validar_sesion.php:", texto);
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return null;
    }

    if (!respuesta.ok || !data.success || !data.usuario) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return null;
    }

    localStorage.setItem("usuario", JSON.stringify(data.usuario));
    return data.usuario;

  } catch (error) {
    console.error("Error validando sesión:", error);
    localStorage.removeItem("usuario");
    window.location.href = "login.html";
    return null;
  }
}

function actualizarFotosUsuario(idPersonal) {
  const id = Number(idPersonal);

  if (!Number.isInteger(id) || id <= 0) {
    return;
  }

  const version = Date.now();
  const rutaFoto = `/app/_lib/file/img/fotos/${id}.jpg?v=${version}`;

  const fotoUsuario = document.getElementById("fotoUsuario");
  const sideFotoUsuario = document.getElementById("sideFotoUsuario");

  if (fotoUsuario) {
    fotoUsuario.src = rutaFoto;
    fotoUsuario.onerror = function () {
      this.onerror = null;
      this.src = "img/user.png";
    };
  }

  if (sideFotoUsuario) {
    sideFotoUsuario.src = rutaFoto;
    sideFotoUsuario.onerror = function () {
      this.onerror = null;
      this.src = "img/user.png";
    };
  }
}

function activarNav(vista) {
  const items = document.querySelectorAll(".nav-item");

  items.forEach(item => {
    item.classList.remove("active");

    if (item.getAttribute("data-vista") === vista) {
      item.classList.add("active");
    }
  });

  const sideItems = document.querySelectorAll(".side-menu-item[data-vista]");

  sideItems.forEach(item => {
    item.classList.remove("active");

    if (item.getAttribute("data-vista") === vista) {
      item.classList.add("active");
    }
  });

  const sideSubItems = document.querySelectorAll(".side-submenu-item");
  const sideGroups = document.querySelectorAll(".side-menu-group");

  sideSubItems.forEach(item => {
    item.classList.remove("active");
  });

  sideGroups.forEach(group => {
    group.classList.remove("open");
  });

  sideSubItems.forEach(item => {
    if (item.getAttribute("data-vista") === vista) {
      item.classList.add("active");

      const group = item.closest(".side-menu-group");

      if (group) {
        group.classList.add("open");
      }
    }
  });
}

function cargarVista(vista) {
  const vistasPermitidas = [
    "inicio",
    "detalleinicio",
    "perfil",
    "cajaahorro",
    "ajustes",
    "calendario",
    "contacto",
    "usuario",
    "estudiosclinicos",
    "pasemedico",
    "estadocuenta",
    "recetasmedicas",
    "prueba",
    "recetadetalle",
    "documentos",
    "organizacion",
    "accesos",
    "avisos"
  ];

  if (!vistasPermitidas.includes(vista)) {
    console.warn("Vista no permitida:", vista);
    vista = "inicio";
  }

  const contenedor = document.getElementById("contenido");

  if (!contenedor) {
    return;
  }

  if (typeof detenerCamaraPrueba === "function") {
    detenerCamaraPrueba();
  }

  const vistaActual = contenedor.firstElementChild;

  const cargarNuevaVista = () => {
    fetch(`vistas/${encodeURIComponent(vista)}.html?v=${Date.now()}`, {
      method: "GET",
      cache: "no-store",
      credentials: "same-origin",
      headers: {
        "Accept": "text/html"
      }
    })
      .then(res => {
        if (!res.ok) {
          throw new Error(`No se pudo cargar la vista: ${vista}`);
        }

        return res.text();
      })
      .then(html => {
        contenedor.scrollTop = 0;
        contenedor.innerHTML = `<div class="vista-animada vista-entrando">${html}</div>`;

        activarNav(vista);

        const inicializadores = {
          inicio: "inicializarInicio",
          detalleinicio: "inicializarDetalleInicio",
          perfil: "inicializarPerfil",
          cajaahorro: "inicializarCajaAhorro",
          ajustes: "inicializarAjustes",
          calendario: "inicializarCalendario",
          usuario: "inicializarUsuario",
          estudiosclinicos: "inicializarEstudiosClinicos",
          pasemedico: "inicializarPaseMedico",
          estadocuenta: "inicializarEstadoCuenta",
          recetasmedicas: "inicializarRecetasMedicas",
          prueba: "inicializarPrueba",
          recetadetalle: "inicializarRecetaDetalle",
          documentos: "inicializarDocumentos",
          organizacion: "inicializarOrganizacion",
          accesos: "inicializarAccesos",
          avisos: "inicializarAvisos"
        };

        const nombreFuncion = inicializadores[vista];

        if (nombreFuncion && typeof window[nombreFuncion] === "function") {
          setTimeout(() => {
            window[nombreFuncion]();
          }, 80);
        }
      })
      .catch(error => {
        console.error("Error al cargar la vista:", error);
        contenedor.innerHTML = `
          <div class="vista-animada vista-entrando">
            <div class="organizacion-empty">No se pudo cargar la vista.</div>
          </div>
        `;
      });
  };

  if (vistaActual) {
    vistaActual.classList.remove("vista-entrando");
    vistaActual.classList.add("vista-animada", "vista-saliendo");

    setTimeout(() => {
      cargarNuevaVista();
    }, 180);
  } else {
    cargarNuevaVista();
  }
}

function logout(e) {
  if (e) e.preventDefault();

  const sideMenu = document.getElementById("sideMenu");
  const menuOverlay = document.getElementById("menuOverlay");
  const modal = document.getElementById("logoutModal");

  if (sideMenu) sideMenu.classList.remove("open");
  if (menuOverlay) menuOverlay.classList.remove("show");

  document.body.classList.remove("menu-open");

  if (modal) {
    modal.classList.add("show");
  }
}

function cerrarModalLogout() {
  const modal = document.getElementById("logoutModal");

  if (modal) {
    modal.classList.remove("show");
  }
}

async function confirmarLogout() {
  const modal = document.getElementById("logoutModal");
  const overlay = document.getElementById("logoutOverlay");
  const overlayTitle = document.getElementById("logoutOverlayTitle");
  const overlayText = document.getElementById("logoutOverlayText");
  const appBody = document.querySelector(".home-body");

  const usuario = JSON.parse(localStorage.getItem("usuario") || "{}");
  const nombreUsuario = usuario?.nombre || usuario?.login || "Usuario";

  if (modal) {
    modal.classList.remove("show");
  }

  if (overlayTitle) {
    overlayTitle.textContent = `Hasta pronto, ${nombreUsuario}`;
  }

  if (overlayText) {
    overlayText.textContent = "Cerrando sesión...";
  }

  if (overlay) {
    overlay.classList.add("show");
  }

  if (appBody) {
    appBody.classList.add("app-logout-animating");
  }

  if (navigator.vibrate) {
    navigator.vibrate(35);
  }

  try {
    await fetch("php/logout.php", {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        "Accept": "application/json"
      }
    });
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  } finally {
    setTimeout(() => {
      localStorage.removeItem("usuario");
      localStorage.removeItem("recordarmeActivo");
      sessionStorage.clear();
      window.location.href = "login.html";
    }, 1250);
  }
}

function inicializarAccesos() {
  // Inicializador reservado para accesos.
}

function inicializarAvisos() {
  if (typeof prepararSkeletonImagenesInicio === "function") {
    prepararSkeletonImagenesInicio();
  }
}