function inicializarInicio() {
  const usuario = JSON.parse(localStorage.getItem("usuario"));

  if (!usuario) {
    window.location.href = "login.html";
    return;
  }

  prepararSkeletonImagenesInicio();
}

function prepararSkeletonImagenesInicio() {
  const imagenes = document.querySelectorAll(".inicio-img-skeleton");

  imagenes.forEach((img) => {
    const card = img.closest(".inicio-bank-banner");

    if (!card) return;

    card.classList.remove("img-cargada");

    const marcarCargada = () => {
      requestAnimationFrame(() => {
        card.classList.add("img-cargada");
      });
    };

    if (img.complete && img.naturalWidth > 0) {
      marcarCargada();
    } else {
      img.addEventListener("load", marcarCargada, { once: true });

      img.addEventListener("error", () => {
        img.src = "img/avisos/suspeservicios.png";
        marcarCargada();
      }, { once: true });
    }
  });
}

function abrirDetalleInicio(tipo, clave) {
  const avisos = {
    aviso_dia_trabajo: {
      tipo: "aviso",
      titulo: "Dia del Trabajo",
      fecha: "1° de Mayo 2026",
      descripcion: "A todos los Servidores Públicos Sindicalizados se les informa que el día 1° de mayo de 2026, todas las Secretarías de esta Organización PERMANECERAN CERRADAS.<br>Las urgencias se atenderán en SUSPE de 9:00 a 24:00 horas y las que se presenten fuera",
      imagen: "img/avisos/dia_trabajo.png"
    },
    aviso_batalla_puebla: {
      tipo: "aviso",
      titulo: "COMUNICADO",
      fecha: "5 de Mayo 2026",
      descripcion: "A todos los Servidores Públicos Sindicalizados se les informa que el día 5 de mayo de 2026, todas las Secretarías de esta Organización PERMANECERAN CERRADAS.<br>Las urgencias se atenderán en SUSPE de 9:00 a 24:00 horas y las que se presenten fuera",
      imagen: "img/avisos/batalla_puebla.png"
    },
    aviso_prepa_abierta: {
      tipo: "aviso",
      titulo: "PREPARATORIA ABIERTA",
      fecha: "Inicio de clases: martes 26 de mayo de 2026",
      descripcion: "Plan de estudio de 22 materias <br><br> Clases martes y jueves <br><br> Horario: 4:00 p.m a 7:00 p.m <br><br> Lugar:Centro de Capacitación SUSPE<br>Washington No. 865 Ote, Centro Monterrey N.L",
      imagen: "img/avisos/preparatoria_abierta.png"
    },
     aviso_regalo_madres: {
      tipo: "aviso",
      titulo: "SELECCION DE REGALO MADRES",
      fecha: "Del 23 de Narzo al 17 de Abril 2026",
      descripcion: "Por este conducto, el Comité Ejecutivo 2026-2031, les hace una atenta invitación para la seleccion de regalo que con motivo del Dia de las Madres les hace llegar el C. Gobernador Constitucional del Estado, Dr. Samuel Alejandro Garcia Sepúlveda, acudiendo en forma personal a la Secretaria de Organización y Afiliación de la Institución, o bien, ingresando a la Plataforma suspeservicios.com",
      imagen: "img/avisos/seleccion_regalo.png"
    },
    aviso_academico: {
      tipo: "aviso",
      titulo: "EXCELENCIA ACADEMICA",
      fecha: "Del 25 de Narzo al 17 de Abril 2026",
      descripcion: "Reconocimiento a la Excelencia Académica: <br> Niños de primaria con un promedio de 9.5 a 10 <br> Inscríbelos para premiar su Esfuerzo y Dedicación <br><br> Acudir con;<br> Copia de las calificaciones del año que cursa con resultados a la fecha",
      imagen: "img/avisos/excelencia_academica.png"
    },
    aviso_lentes: {
      tipo: "aviso",
      titulo: "PRESTAMO PARA REMODELACION",
      fecha: "17 al 19 de Febrero 2026",
      descripcion: "Se convoca a los compañeros sindicalizados servidores públicos del Gobierno del Estado de Nuevo León con antigûedad igual o mayor de 5 años de cotizacion en el ISSSTELEON, a participar en el programa de Préstamos para Vivienda 2026",
      imagen: "img/avisos/Prestamo_remodelacion.png"
    },
    aviso_convenio: {
      tipo: "aviso",
      titulo: "PRESTAMO PARA VIVIENDA",
      fecha: "03 al 05 de Febrero",
      descripcion: "Se convoca a los compañeros sindicalizados servidores públicos del Gobierno del Estado de Nuevo León con antigûedad igual o mayor de 5 años de cotizacion en el ISSSTELEON, a participar en el programa de Préstamos para Vivienda 2026",
      imagen: "img/avisos/Prestamo_vivienda.png"
    },
    aviso_asamblea: {
      tipo: "aviso",
      titulo: "PLATAFORMA SUSPESERVICIOS",
      fecha: "2026",
      descripcion: "Se informa a todos nuestros afiliados que a través de la página web de https://www.suspeservicios.com/, ahora también podrán consultar y descargar las órdenes de los exámenes de laboratorio para BIOMED mediante la autenticación de usuario y contraseña.",
      imagen: "img/avisos/suspeservicios.png"
    },
    aviso_servicios: {
      tipo: "aviso",
      titulo: "ACTUALIZACION DE DOCUMENTOS",
      fecha: "Te invitamos a ahorrar con las siguientes ventajas:",
       descripcion: "Te invitamos a acudir a nuestras oficinas cuendo tengas que realizar algún cambio, es importante tener tu información al día",
       imagen: "img/avisos/actualizar_documentos.png"
    },
    aviso_caja_ahorro: {
      tipo: "aviso",
      titulo: "CAJA DE AHORRO",
      fecha: "SUSPE",
      descripcion: "1.- Fortalecer la Cultura del Ahorro para una mayor seguridad financiera <br><br> 2.- Obtener atractivos rendimientos. <br><br> 3.- Acceso a préstamos de hasta tres veces lo ahorrado, con tasa baja y preferencial. <br><br> 4.- Disposición de tu dinero de manera eficiente por imprevistos.",
      imagen: "img/avisos/caja_de_ahorro.png"
    },
     aviso_farmacia: {
      tipo: "aviso",
      titulo: "Farmacia",
      fecha: "2026",
      descripcion: "PROXIMAMENTE",
      imagen: "img/avisos/caja_de_ahorro.png"
    },
    aviso_mastografo: {
      tipo: "aviso",
      titulo: "MASTOGRAFO EN SUSPE",
      fecha: "Prevención y detección oportuna",
      descripcion: "Nos complace informarles que Servicios Médicos de nuestra Institución ya cuenta con Equipo de Mastografía, destinado a fortalecer las acciones de prevención y detección oportuna del cáncer de mama.",
      imagen: "img/avisos/mastografo.png"
    },
    aviso_oftalmologia: {
      tipo: "aviso",
      titulo: "ATENCION DE OFTALMOLOGIA",
      fecha: "Montemorelos",
      descripcion: "HOSPITAL LA CARLOTA <br><br> Camino al Vapor No. 209, Col. Zambrano, Montemorelos N.L. <br><br> De Lunes a Jueves de 8:00 a 17:00 horas y Viernes de 8:00 a 14:00 horas",
      imagen: "img/avisos/oftalmologia.png"
    }
  };

  const eventos = {
    bono_despensa: {
      tipo: "evento",
      titulo: "FESTEJO DE NIÑOS",
      fecha: "25 de Abril de 2026",
      descripcion: "",
      imagen: "img/avisos/festejo_niños.png"
    },
    vacaciones_marzo_1: {
      tipo: "evento",
      titulo: "FESTEJO MADRES",
      fecha: "08 de Mayo de 2026",
      descripcion: "",
      imagen: "img/avisos/festejo_madres.png"
    },
    vacaciones_marzo_2: {
      tipo: "evento",
      titulo: "FESTEJO PADRES",
      fecha: "19 de Junio de 2026",
      descripcion: "",
      imagen: "img/avisos/festejo_padres.png"
    },
    festejo_ninos: {
      tipo: "evento",
      titulo: "FESTEJO SECRETARIAS",
      fecha: "31 de Julio de 2026",
      descripcion: "Evento programado para celebración institucional del festejo de niños.",
      imagen: "img/avisos/festejo_secretarias.png"
    }
  };

  let item = null;

  if (tipo === "aviso") {
    item = avisos[clave];
  }

  if (tipo === "evento") {
    item = eventos[clave];
  }

  if (!item) return;

  localStorage.setItem("detalleInicio", JSON.stringify(item));
  cargarVista("detalleinicio");
}
function toggleAccesosInicio() {
  const grid = document.querySelector(".inicio-bank-grid");
  const btn = document.getElementById("btnVerAccesos");

  if (!grid || !btn) return;

  const expandido = grid.classList.toggle("accesos-expandido");

  btn.classList.toggle("activo", expandido);

  btn.innerHTML = expandido
    ? 'Ver menos <i class="fa-solid fa-chevron-down"></i>'
    : 'Ver todos <i class="fa-solid fa-chevron-down"></i>';
}


/*
function inicializarInicio() {
  const usuario = JSON.parse(localStorage.getItem("usuario"));

  if (!usuario) {
    window.location.href = "login.html";
    return;
  }

  const nombreEl = document.getElementById("inicioNombreUsuario");
  const loginEl = document.getElementById("inicioLoginUsuario");
  const fotoEl = document.getElementById("inicioFotoUsuario");
  const pasesTotalEl = document.getElementById("inicioPasesTotal");

  if (nombreEl) {
    nombreEl.textContent = usuario.nombre || usuario.login || "Usuario";
  }

  if (loginEl) {
    loginEl.textContent = `Credencial: ${usuario.login || ""}`;
  }

  if (fotoEl && usuario.idPersonal) {
    fotoEl.src = `fotos/${usuario.idPersonal}.jpg`;
    fotoEl.onerror = function () {
      this.src = "img/user.png";
    };
  }

  if (pasesTotalEl) {
    fetch("php/obtener_pases_medicos.php")
      .then(res => res.text())
      .then(texto => {
        try {
          const data = JSON.parse(texto);
          if (data.success && Array.isArray(data.data)) {
            pasesTotalEl.textContent = data.data.length;
          } else {
            pasesTotalEl.textContent = "0";
          }
        } catch {
          pasesTotalEl.textContent = "0";
        }
      })
      .catch(() => {
        pasesTotalEl.textContent = "0";
      });
  }
}
  */