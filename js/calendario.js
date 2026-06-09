function inicializarCalendario() {
  const contenedor = document.getElementById("contenedorCalendario");
  if (!contenedor) return;

  const year = 2026;

  const meses = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
  ];

  const diasSemana = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];

  const eventos = {
    0: [
      { dias: [1], tipo: "asueto", texto: "1 Asueto" },
      { dias: [2, 5, 6], tipo: "vacacional", texto: "2, 5, 6 Periodo vacacional" },
      { dias: [23], tipo: "bono", texto: "23 Bono de despensa" }
    ],
    1: [
      { dias: [2], tipo: "asueto", texto: "2 Asueto" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" }
    ],
    2: [
      { dias: [16], tipo: "asueto", texto: "16 Asueto" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" },
      { dias: [30, 31], tipo: "vacacional", texto: "30, 31 Periodo vacacional" }
    ],
    3: [
      { dias: [1, 2, 3, 6, 7, 8, 9, 10], tipo: "vacacional", texto: "1, 2, 3, 6, 7, 8, 9, 10 Periodo vacacional" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" },
      { dias: [25], tipo: "festejo", texto: "25 Festejo de niños" }
    ],
    4: [
      { dias: [1, 5], tipo: "asueto", texto: "1, 5 Asueto" },
      { dias: [8], tipo: "festejo", texto: "8 Festejo Madres" },
      { dias: [22], tipo: "bono", texto: "22 Bono de despensa" }
    ],
    5: [
      { dias: [19], tipo: "festejo", texto: "19 Festejo Padres" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" }
    ],
    6: [
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" },
      { dias: [31], tipo: "festejo", texto: "31 Festejo Secretarias" }
    ],
    7: [
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" }
    ],
    8: [
      { dias: [16], tipo: "asueto", texto: "16 Asueto" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" }
    ],
    9: [
      { dias: [12], tipo: "asueto", texto: "12 Asueto" },
      { dias: [23], tipo: "bono", texto: "23 Bono de despensa" }
    ],
    10: [
      { dias: [16], tipo: "asueto", texto: "16 Asueto" },
      { dias: [17], tipo: "asamblea", texto: "17 Asamblea anual" },
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" }
    ],
    11: [
      { dias: [24], tipo: "bono", texto: "24 Bono de despensa" },
      { dias: [25], tipo: "asueto", texto: "25 Asueto" }
    ]
  };

  const mapaEventos = {};

  Object.keys(eventos).forEach((mesIndex) => {
    mapaEventos[mesIndex] = {};
    eventos[mesIndex].forEach((evento) => {
      evento.dias.forEach((dia) => {
        mapaEventos[mesIndex][dia] = evento.tipo;
      });
    });
  });

  const hoyReal = new Date();
  const hoy = {
    year: hoyReal.getFullYear(),
    month: hoyReal.getMonth(),
    day: hoyReal.getDate()
  };

  contenedor.innerHTML = "";

  for (let mes = 0; mes < 12; mes++) {
    const primerDiaSemana = new Date(year, mes, 1).getDay();
    const diasDelMes = new Date(year, mes + 1, 0).getDate();

    const card = document.createElement("div");
    card.className = "mes-card";

    const monthHeader = document.createElement("div");
    monthHeader.className = "mes-card-header";
    monthHeader.innerHTML = `<h3>${meses[mes]} ${year}</h3>`;
    card.appendChild(monthHeader);

    const weekRow = document.createElement("div");
    weekRow.className = "dias-semana";
    diasSemana.forEach((dia) => {
      const cell = document.createElement("div");
      cell.className = "dia-semana";
      cell.textContent = dia;
      weekRow.appendChild(cell);
    });
    card.appendChild(weekRow);

    const daysGrid = document.createElement("div");
    daysGrid.className = "dias-grid";

    for (let i = 0; i < primerDiaSemana; i++) {
      const empty = document.createElement("div");
      empty.className = "dia-cell empty";
      daysGrid.appendChild(empty);
    }

    for (let dia = 1; dia <= diasDelMes; dia++) {
      const dayCell = document.createElement("div");
      dayCell.className = "dia-cell";
      dayCell.textContent = dia;

      const tipoEvento = mapaEventos[mes]?.[dia];
      if (tipoEvento) {
        dayCell.classList.add("evento", tipoEvento);
      }

      if (hoy.year === year && hoy.month === mes && hoy.day === dia) {
        dayCell.classList.add("hoy");
      }

      daysGrid.appendChild(dayCell);
    }

    card.appendChild(daysGrid);

    const listaEventos = document.createElement("div");
    listaEventos.className = "mes-eventos";

    const tituloEventos = document.createElement("h4");
    tituloEventos.textContent = "Fechas del mes";
    listaEventos.appendChild(tituloEventos);

    if (eventos[mes] && eventos[mes].length > 0) {
      eventos[mes].forEach((evento) => {
        const item = document.createElement("div");
        item.className = `evento-item ${evento.tipo}`;
        item.textContent = evento.texto;
        listaEventos.appendChild(item);
      });
    } else {
      const sinEventos = document.createElement("div");
      sinEventos.className = "evento-item";
      sinEventos.textContent = "Sin eventos registrados";
      listaEventos.appendChild(sinEventos);
    }

    card.appendChild(listaEventos);
    contenedor.appendChild(card);
  }
}