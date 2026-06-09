document.addEventListener("DOMContentLoaded", () => {
  const usuario = JSON.parse(localStorage.getItem("usuario"));
  const menuBtn = document.getElementById("menuBtn");
  const dropdownMenu = document.getElementById("dropdownMenu");
  const cerrarSesion = document.getElementById("cerrarSesion");

  if (!usuario) {
    window.location.href = "login.html";
    return;
  }

  document.getElementById("nombreUsuario").textContent = usuario.nombre || "Usuario";
  document.getElementById("correoUsuario").textContent = usuario.correo || "";

  menuBtn.addEventListener("click", () => {
    dropdownMenu.classList.toggle("show");
  });

  document.addEventListener("click", (e) => {
    if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.classList.remove("show");
    }
  });

  cerrarSesion.addEventListener("click", async (e) => {
    e.preventDefault();

    try {
      await fetch("php/logout.php");
    } catch (error) {
      console.error("No se pudo cerrar sesión en servidor", error);
    }

    localStorage.removeItem("usuario");
    window.location.href = "login.html";
  });
});