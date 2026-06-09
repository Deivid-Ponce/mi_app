function getBasePath() {
  const path = window.location.pathname.toLowerCase();
  return path.includes("/vistas/") ? "../" : "";
}

function obtenerDeviceId() {
  try {
    if (window.AndroidApp && typeof AndroidApp.getDeviceId === "function") {
      return AndroidApp.getDeviceId();
    }
  } catch (e) {}

  return "";
}

function limpiarDatosLocales() {
  localStorage.removeItem("usuario");
  sessionStorage.clear();
}

function limpiarTodoLogin() {
  localStorage.removeItem("usuario");
  localStorage.removeItem("usuarioRecordado");
  localStorage.removeItem("recordarmeActivo");
  sessionStorage.clear();
}

function redirigirLogin(base) {
  window.location.replace(`${base}login.html?v=${Date.now()}`);
}

async function validarSesion() {
  const base = getBasePath();
  const deviceId = obtenerDeviceId();

  if (!deviceId) {
    limpiarDatosLocales();
    redirigirLogin(base);
    return null;
  }

  try {
    const respuesta = await fetch(
      `${base}php/validar_sesion.php?v=${Date.now()}&device_id=${encodeURIComponent(deviceId)}`,
      {
        method: "GET",
        credentials: "same-origin",
        cache: "no-store"
      }
    );

    let data = null;

    try {
      data = await respuesta.json();
    } catch (e) {
      limpiarDatosLocales();
      redirigirLogin(base);
      return null;
    }

    if (!respuesta.ok || !data.success || !data.usuario) {
      limpiarDatosLocales();
      redirigirLogin(base);
      return null;
    }

    localStorage.setItem("usuario", JSON.stringify(data.usuario));
    return data.usuario;

  } catch (error) {
    limpiarDatosLocales();
    redirigirLogin(base);
    return null;
  }
}

async function cerrarSesion() {
  const base = getBasePath();

  try {
    await fetch(`${base}php/logout.php?v=${Date.now()}`, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store"
    });
  } catch (error) {
  } finally {
    limpiarTodoLogin();
    redirigirLogin(base);
  }
}