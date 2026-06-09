async function inicializarCajaAhorro() {
  const contenedor = document.getElementById("cajaAhorroContenido");
  if (!contenedor) return;

  contenedor.innerHTML = `
    <div class="caja-empty-card">Cargando información...</div>
  `;

  const usuario = await validarSesion();
  if (!usuario) return;

  try {
    const res = await fetch("php/obtener_cajaahorro.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const texto = await res.text();
    let response;

    try {
      response = JSON.parse(texto);
    } catch (e) {
      contenedor.innerHTML = `
        <div class="caja-empty-card">El servidor no devolvió JSON válido.</div>
      `;
      console.error("Respuesta inválida:", texto);
      return;
    }

    if (res.status === 401) {
      localStorage.removeItem("usuario");
      window.location.href = "login.html";
      return;
    }

    if (!response.success || !response.data) {
      contenedor.innerHTML = `
        <div class="caja-empty-card">${response.message || "No se encontró información."}</div>
      `;
      return;
    }

    const d = response.data;

    const saldoCaja = formatearMoneda(d.SaldoCaja);
    const aportacion = formatearMoneda(d.Aportacion);
    const montoPrestamo = Number(d.MontoPrestamo || 0);
    const saldoPrestamo = Number(d.SaldoPrestamo || 0);
    const tienePrestamo = montoPrestamo > 0;

    const montoPrestamoTexto = tienePrestamo
      ? formatearMoneda(montoPrestamo)
      : "SIN PRÉSTAMO";

    const saldoPrestamoTexto = saldoPrestamo > 0
      ? formatearMoneda(saldoPrestamo)
      : "$0.00";

    const bloquePrestamo = tienePrestamo
      ? `
        <section class="caja-card caja-loan-card">
          <h3>Préstamo Vigente</h3>

          <div class="caja-loan-details">
            <span>Monto de préstamo<br><strong>${montoPrestamoTexto}</strong></span>
            <span>Plazo del préstamo<br><strong>${d.plazo || ""}</strong></span>
            <span>Quincena y año final<br><strong>${d.QuinFin || ""} - ${d.AnioFinal || ""}</strong></span>
            <span>Saldo préstamo<br><strong>${saldoPrestamoTexto}</strong></span>
          </div>
        </section>

        <section class="caja-card2" id="gaugeSection">
          <h3>Avance del Préstamo</h3>

          <div class="caja-gauge-container">
            <div class="caja-gauge">
              <svg width="150" height="75" viewBox="0 0 100 50">
                <path class="bg" d="M5,50 A45,45 0 0,1 95,50" fill="none" stroke="#eee" stroke-width="10"></path>
                <path class="fg" id="progressArcCaja" d="M5,50 A45,45 0 0,1 95,50" fill="none"
                  stroke="#782f40" stroke-width="10" stroke-linecap="round"></path>
              </svg>
              <div class="text" id="percentageCaja">0%</div>
            </div>

            <div class="caja-gauge">
              <svg width="150" height="75" viewBox="0 0 100 50">
                <path class="bg" d="M5,50 A45,45 0 0,1 95,50" fill="none" stroke="#eee" stroke-width="10"></path>
                <path class="fg" id="quincenaArcCaja" d="M5,50 A45,45 0 0,1 95,50" fill="none"
                  stroke="#2f6378" stroke-width="10" stroke-linecap="round"></path>
              </svg>
              <div class="text2" id="quincenaLabelCaja">0/0</div>
            </div>
          </div>
        </section>
      `
      : "";

    contenedor.innerHTML = `
      <div class="caja-dashboard">
        <section class="caja-card caja-balance-card">
          <p class="caja-greeting">${d.nombre || usuario.nombre || "Usuario"}</p>
          <h3>Tu saldo en caja SUSPE</h3>
          <h1><span class="caja-balance-amount">${saldoCaja}</span></h1>

          <div class="caja-balance-details">
            <span>
              Actualizado a la quincena <strong>${d.qui_saldo || ""}</strong>
              del año <strong>${d.anio_saldo || ""}</strong>
            </span><br>
            <span>
              Aportación Quincenal <strong>${aportacion}</strong>
            </span>
            <br><br>
            <span class="caja-leyenda">
              La <strong>quincena</strong> de actualización corresponde al pago efectuado
              por la tesorería de tu dependencia a la caja de ahorro.
            </span>
          </div>
        </section>

        ${bloquePrestamo}

        <div class="caja-consultar-movimientos">
          <button class="caja-btn-movimientos" onclick="cargarVista('estadocuenta')">
            Ver Estado de Cuenta
          </button>
        </div>

        <footer class="caja-footer">
          <p>SUSPE un Sindicato Diferente</p>
        </footer>
      </div>
    `;

    if (tienePrestamo) {
      inicializarGaugeCajaAhorro({
        quincenaInicio: d.QuinIni,
        quincenaActual: d.QuinAct,
        anioInicio: d.AnioIni,
        anioActual: d.AnioAct,
        plazo: d.plazo2,
        montoPrestamo: montoPrestamo
      });
    }

  } catch (error) {
    contenedor.innerHTML = `
      <div class="caja-empty-card">Error al cargar la información.</div>
    `;
    console.error("Error caja ahorro:", error);
  }
}

function inicializarGaugeCajaAhorro(data) {
  const gaugeSection = document.getElementById("gaugeSection");
  const montoPrestamo = Number(data.montoPrestamo || 0);

  if (!gaugeSection) return;

  if (montoPrestamo <= 0) {
    gaugeSection.style.display = "none";
    return;
  }

  const quincenaInicio = Number(data.quincenaInicio || 0);
  const quincenaActual = Number(data.quincenaActual || 0);
  const anioInicio = Number(data.anioInicio || 0);
  const anioActual = Number(data.anioActual || 0);
  const plazo = Number(data.plazo || 0);

  if (plazo <= 0) {
    gaugeSection.style.display = "none";
    return;
  }

  let transcurridas = 0;

  if (anioInicio === anioActual) {
    transcurridas = quincenaActual - quincenaInicio;
  } else {
    const restantesInicio = 24 - quincenaInicio;
    const transAnioActual = quincenaActual;
    const aniosIntermedios = anioActual - anioInicio - 1;
    const quincenasIntermedias = aniosIntermedios > 0 ? aniosIntermedios * 24 : 0;

    transcurridas = restantesInicio + transAnioActual + quincenasIntermedias;
  }

  if (transcurridas < 0) transcurridas = 0;
  if (transcurridas > plazo) transcurridas = plazo;

  const porcentaje = Math.round((transcurridas / plazo) * 100);

  const progressArc = document.getElementById("progressArcCaja");
  const percentageText = document.getElementById("percentageCaja");
  const quincenaArc = document.getElementById("quincenaArcCaja");
  const quincenaLabel = document.getElementById("quincenaLabelCaja");

  if (progressArc && percentageText) {
    const totalLength1 = progressArc.getTotalLength();
    progressArc.style.transition = "stroke-dashoffset 1s ease";
    progressArc.style.strokeDasharray = totalLength1;
    progressArc.style.strokeDashoffset = totalLength1 * (1 - porcentaje / 100);
    percentageText.textContent = porcentaje + "%";
  }

  if (quincenaArc && quincenaLabel) {
    const totalLength2 = quincenaArc.getTotalLength();
    quincenaArc.style.transition = "stroke-dashoffset 1s ease";
    quincenaArc.style.strokeDasharray = totalLength2;
    quincenaArc.style.strokeDashoffset = totalLength2 * (1 - transcurridas / plazo);
    quincenaLabel.textContent = `${transcurridas} de ${plazo} Quincenas`;
  }
}

function formatearMoneda(valor) {
  const numero = Number(valor || 0);
  return numero.toLocaleString("es-MX", {
    style: "currency",
    currency: "MXN",
    minimumFractionDigits: 2
  });
}