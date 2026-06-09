function obtenerClaveQuincena() {
  const hoy = new Date();
  const anio = hoy.getFullYear();
  const mes = String(hoy.getMonth() + 1).padStart(2, "0");
  const dia = hoy.getDate();
  const quincena = dia <= 15 ? "Q1" : "Q2";

  return `notificacionPases_${anio}-${mes}_${quincena}`;
}

function enviarNotificacionNativa(titulo, mensaje, url = "") {
  // Android
  if (window.Android && typeof window.Android.showNotification === "function") {
    window.Android.showNotification(titulo, mensaje, url);
    return true;
  }

  // iOS
  if (
    window.webkit &&
    window.webkit.messageHandlers &&
    window.webkit.messageHandlers.showNotification
  ) {
    window.webkit.messageHandlers.showNotification.postMessage({
      titulo,
      mensaje,
      url
    });
    return true;
  }

  console.warn("No existe puente nativo de notificaciones.");
  return false;
}

async function revisarPasesYNotificar() {
  const usuario = await validarSesion();
  if (!usuario) return;

  try {
    const res = await fetch("php/obtener_pases_medicos.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const data = await res.json();

    if (res.status === 401) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    if (!data.success || !Array.isArray(data.data)) {
      return;
    }

    const pasesActuales = data.data.map(p => Number(p.idpase)).filter(Boolean);

    if (pasesActuales.length === 0) {
      return;
    }

    const claveQuincena = obtenerClaveQuincena();

    let ultimaQuincena = localStorage.getItem("ultimaQuincena");

    if (ultimaQuincena !== claveQuincena) {
      localStorage.setItem(claveQuincena, "0");
      localStorage.setItem("pasesNotificados", JSON.stringify([]));
      localStorage.setItem("ultimaQuincena", claveQuincena);
    }

    let pasesNotificados = localStorage.getItem("pasesNotificados");
    pasesNotificados = pasesNotificados ? JSON.parse(pasesNotificados) : [];

    let hayPaseNuevo = false;

    pasesActuales.forEach(id => {
      if (!pasesNotificados.includes(id)) {
        hayPaseNuevo = true;
      }
    });

    let contador = parseInt(localStorage.getItem(claveQuincena) || "0", 10);

    let debeNotificar = false;

    if (hayPaseNuevo) {
      debeNotificar = true;
    } else if (contador < 2) {
      debeNotificar = true;
    }

    if (!debeNotificar) {
      return;
    }

    const titulo = usuario.nombre || usuario.login || "Usuario";
    const mensaje = "Tienes pases especialistas disponibles";
    const url = "https://phpstack-1548792-5992674.cloudwaysapps.com/mi_app/login.html#pasemedico";

    const enviada = enviarNotificacionNativa(titulo, mensaje, url);

    if (!enviada) {
      console.log("No se envió notificación nativa porque no existe puente.");
      return;
    }

    pasesActuales.forEach(id => {
      if (!pasesNotificados.includes(id)) {
        pasesNotificados.push(id);
      }
    });

    localStorage.setItem("pasesNotificados", JSON.stringify(pasesNotificados));
    localStorage.setItem(claveQuincena, String(contador + 1));

  } catch (error) {
    console.error("Error al revisar pases médicos:", error);
  }
}