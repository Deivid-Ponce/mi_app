document.addEventListener("DOMContentLoaded", async () => {
  const form = document.getElementById("formLogin");
  const mensaje = document.getElementById("mensaje");
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const usuarioInput = document.getElementById("usuario");
  const overlay = document.getElementById("loginOverlay");
  const overlayTitle = document.getElementById("loginOverlayTitle");
  const recordarmeInput = document.getElementById("recordarme");
  const btnLogin = document.getElementById("btnLogin");

  let enviando = false;

  function obtenerDeviceId() {
    try {
      if (window.AndroidApp && typeof AndroidApp.getDeviceId === "function") {
        return AndroidApp.getDeviceId();
      }
    } catch (e) {}

    return "";
  }

  function obtenerNombreDispositivo() {
    try {
      if (window.AndroidApp && typeof AndroidApp.getDeviceName === "function") {
        return AndroidApp.getDeviceName();
      }
    } catch (e) {}

    return "Android WebView";
  }

  function mostrarError(msg) {
    if (!mensaje) return;
    mensaje.textContent = msg;
    mensaje.className = "premium-login-message error";
  }

  function limpiarMensaje() {
    if (!mensaje) return;
    mensaje.textContent = "";
    mensaje.className = "premium-login-message";
  }

  function bloquearBoton() {
    enviando = true;

    if (btnLogin) {
      btnLogin.disabled = true;
      btnLogin.style.opacity = "0.7";
    }
  }

  function desbloquearBoton() {
    enviando = false;

    if (btnLogin) {
      btnLogin.disabled = false;
      btnLogin.style.opacity = "1";
    }
  }

  const usuarioGuardado = localStorage.getItem("usuarioRecordado");
  const recordarmeActivo = localStorage.getItem("recordarmeActivo") === "1";
  const hash = window.location.hash || "";

  if (usuarioGuardado && usuarioInput) {
    usuarioInput.value = usuarioGuardado;
  }

  if (recordarmeInput) {
    recordarmeInput.checked = recordarmeActivo;
  }

  const deviceId = obtenerDeviceId();

  if (recordarmeActivo && deviceId) {
    try {
      const respuestaSesion = await fetch(
        "php/validar_sesion.php?v=" + Date.now() + "&device_id=" + encodeURIComponent(deviceId),
        {
          method: "GET",
          credentials: "same-origin",
          cache: "no-store"
        }
      );

      let dataSesion = null;

      try {
        dataSesion = await respuestaSesion.json();
      } catch (e) {
        dataSesion = null;
      }

      if (respuestaSesion.ok && dataSesion && dataSesion.success) {
        localStorage.setItem("usuario", JSON.stringify(dataSesion.usuario));

        if (overlayTitle) {
          overlayTitle.textContent =
            `Bienvenido, ${dataSesion.usuario.nombre || dataSesion.usuario.login || "Usuario"}`;
        }

        if (overlay) {
          overlay.classList.add("show");
        }

        setTimeout(() => {
          window.location.href = "app.html?v=" + Date.now() + hash;
        }, 1200);

        return;
      }
    } catch (e) {}

    localStorage.removeItem("usuario");
    localStorage.removeItem("usuarioRecordado");
    localStorage.removeItem("recordarmeActivo");
  }

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", () => {
      const tipo = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = tipo;

      togglePassword.innerHTML =
        tipo === "password"
          ? '<i class="fa-solid fa-eye"></i>'
          : '<i class="fa-solid fa-eye-slash"></i>';
    });
  }

  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (enviando) return;

    limpiarMensaje();

    const usuario = usuarioInput.value.trim();
    const password = passwordInput.value.trim();
    const recordarme = recordarmeInput ? recordarmeInput.checked : false;

    if (!usuario || !password) {
      mostrarError("Completa todos los campos.");
      return;
    }

    const deviceIdActual = obtenerDeviceId();
    const nombreDispositivoActual = obtenerNombreDispositivo();

    if (!deviceIdActual) {
      mostrarError("Acceso permitido solo desde la app móvil.");
      return;
    }

    bloquearBoton();

    try {
      const formData = new FormData();
      formData.append("usuario", usuario);
      formData.append("password", password);
      formData.append("recordarme", recordarme ? "1" : "0");
      formData.append("device_id", deviceIdActual);
      formData.append("nombre_dispositivo", nombreDispositivoActual);

      const respuesta = await fetch("php/login.php?v=" + Date.now(), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        cache: "no-store"
      });

      let data = null;

      try {
        data = await respuesta.json();
      } catch (e) {
        throw new Error("Respuesta inválida del servidor");
      }

      if (!respuesta.ok || !data.success) {
        mostrarError(data?.message || "Usuario o contraseña incorrectos.");
        passwordInput.value = "";
        passwordInput.focus();
        desbloquearBoton();
        return;
      }

      localStorage.setItem("usuario", JSON.stringify(data.usuario));

      if (recordarme) {
        localStorage.setItem("usuarioRecordado", usuario);
        localStorage.setItem("recordarmeActivo", "1");
      } else {
        localStorage.removeItem("usuarioRecordado");
        localStorage.removeItem("recordarmeActivo");
      }

      limpiarMensaje();

      if (overlayTitle) {
        overlayTitle.textContent =
          `Bienvenido, ${data.usuario.nombre || data.usuario.login || "Usuario"}`;
      }

      if (overlay) {
        overlay.classList.add("show");
      }

      setTimeout(() => {
        window.location.href = "app.html?v=" + Date.now() + hash;
      }, 1800);

    } catch (error) {
      mostrarError("Error al conectar con el servidor.");
      desbloquearBoton();
    }
  });
});