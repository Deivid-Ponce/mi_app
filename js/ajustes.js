async function inicializarAjustes() {
  const loginEl = document.getElementById("ajustesLogin");
  const nombreEl = document.getElementById("ajustesNombre");
  const emailEl = document.getElementById("ajustesEmail");
  const passwordEl = document.getElementById("ajustesPassword");
  const password2El = document.getElementById("ajustesPassword2");
  const form = document.getElementById("formAjustes");
  const mensaje = document.getElementById("mensajeAjustes");

  if (!form) return;

  let usuario;

  try {
    const respuestaSesion = await fetch("php/validar_sesion.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const dataSesion = await respuestaSesion.json();

    if (!respuestaSesion.ok || !dataSesion.success) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    usuario = dataSesion.usuario;
    localStorage.setItem("usuario", JSON.stringify(usuario));
  } catch (error) {
    console.error("Error validando sesión en ajustes:", error);
    localStorage.removeItem("usuario");
    window.location.href = "login.html";
    return;
  }

  loginEl.value = usuario.login || "";
  nombreEl.value = usuario.nombre || "";
  emailEl.value = usuario.correo || "";

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailEl.value.trim();
    const password = passwordEl.value.trim();
    const password2 = password2El.value.trim();

    if (password !== "" && password !== password2) {
      mensaje.textContent = "Las contraseñas no coinciden.";
      mensaje.className = "mensaje-ajustes error";
      return;
    }

    if (email === "" && password === "") {
      mensaje.textContent = "Debes capturar al menos un dato para actualizar.";
      mensaje.className = "mensaje-ajustes error";
      return;
    }

    try {
      const formData = new FormData();
      formData.append("email", email);
      formData.append("password", password);

      const respuesta = await fetch("php/actualizar_ajustes.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });

      const data = await respuesta.json();

      if (data.success) {
        mensaje.textContent = data.message;
        mensaje.className = "mensaje-ajustes success";

        localStorage.setItem("usuario", JSON.stringify(data.usuario));

        loginEl.value = data.usuario.login || "";
        nombreEl.value = data.usuario.nombre || "";
        emailEl.value = data.usuario.correo || "";

        passwordEl.value = "";
        password2El.value = "";
      } else {
        if (respuesta.status === 401) {
          localStorage.removeItem("usuario");
          window.location.href = "login.html";
          return;
        }

        mensaje.textContent = data.message || "No se pudo actualizar la información.";
        mensaje.className = "mensaje-ajustes error";
      }
    } catch (error) {
      console.error("Error al guardar ajustes:", error);
      mensaje.textContent = "Error al guardar cambios.";
      mensaje.className = "mensaje-ajustes error";
    }
  });
}