/**
 * Barra de búsqueda tipo "autocompletar" para los filtros de Producto y
 * Centro de salud en la pantalla de Kárdex.
 *
 * Cómo funciona:
 *  - El campo visible es un <input type="text"> normal, no un <select>.
 *  - Al escribir, se filtra en el navegador (sin ir al servidor) la lista
 *    de opciones ya cargada en la página, comparando por "contiene" y sin
 *    importar mayúsculas ni acentos, así "mante" encuentra "Mantequilla".
 *  - Cada opción que coincide aparece en una lista desplegable debajo del
 *    campo. Al hacer clic en una, se guarda su ID en un <input hidden> que
 *    es el que realmente viaja al servidor con el filtro.
 *  - Al presionar Enter: si el usuario navegó con las flechas hasta una
 *    opción, se usa esa. Si no, se busca primero una coincidencia EXACTA
 *    con el nombre escrito (el "nombre literal" tal cual se pidió); si no
 *    hay una exacta, se usa la primera coincidencia parcial de la lista.
 *    Elegida la opción, se envía el formulario de una vez.
 */
(function () {
  "use strict";

  function normalizar(texto) {
    return (texto || "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function inicializarCombo(root) {
    var input = root.querySelector("[data-sc-combo-input]");
    var hidden = root.querySelector("[data-sc-combo-hidden]");
    var resultsBox = root.querySelector("[data-sc-combo-results]");
    // Solo los buscadores de FILTRO (Kárdex/Inventario) deben enviar el
    // formulario apenas se elige una opción con Enter. En formularios con
    // varios campos (como Agendar/Editar cita) eso enviaría la cita a
    // medio llenar, así que ahí NO se agrega este atributo y Enter solo
    // selecciona la opción sin enviar nada.
    var enviarAlPresionarEnter = root.hasAttribute("data-sc-combo-submit-on-enter");

    if (!input || !hidden || !resultsBox) {
      return;
    }

    var opciones = [];
    try {
      opciones = JSON.parse(input.getAttribute("data-options") || "[]");
    } catch (e) {
      opciones = [];
    }

    var indiceActivo = -1;
    var coincidenciasActuales = [];

    function cerrarResultados() {
      resultsBox.hidden = true;
      resultsBox.innerHTML = "";
      indiceActivo = -1;
      coincidenciasActuales = [];
    }

    function elegirOpcion(opcion) {
      input.value = opcion.nombre;
      hidden.value = opcion.id;
      cerrarResultados();
      // Evento para que otros scripts de la pantalla (por ejemplo, mostrar
      // el teléfono de un paciente elegido) reaccionen a la selección sin
      // tener que conocer los detalles internos de este componente.
      root.dispatchEvent(
        new CustomEvent("sc-combo:select", { detail: opcion, bubbles: true })
      );
    }

    function limpiarSeleccion() {
      hidden.value = "";
      root.dispatchEvent(new CustomEvent("sc-combo:clear", { bubbles: true }));
    }

    function resaltar(indice) {
      var items = resultsBox.querySelectorAll(".sc-combo-option");
      items.forEach(function (item, i) {
        item.classList.toggle("is-active", i === indice);
      });
      indiceActivo = indice;
    }

    function pintarResultados(coincidencias) {
      coincidenciasActuales = coincidencias;
      indiceActivo = -1;

      if (coincidencias.length === 0) {
        resultsBox.innerHTML = '<div class="sc-combo-empty">Sin resultados</div>';
        resultsBox.hidden = false;
        return;
      }

      resultsBox.innerHTML = coincidencias
        .slice(0, 8)
        .map(function (opcion, indice) {
          var nombreSeguro = String(opcion.nombre).replace(/</g, "&lt;");
          return (
            '<div class="sc-combo-option" data-index="' + indice + '">' +
            nombreSeguro +
            "</div>"
          );
        })
        .join("");
      resultsBox.hidden = false;
    }

    function buscarCoincidencias(query) {
      var q = normalizar(query);
      return opciones.filter(function (opcion) {
        return normalizar(opcion.nombre).indexOf(q) !== -1;
      });
    }

    input.addEventListener("input", function () {
      limpiarSeleccion();
      var query = input.value;

      if (normalizar(query) === "") {
        cerrarResultados();
        return;
      }

      pintarResultados(buscarCoincidencias(query));
    });

    input.addEventListener("focus", function () {
      if (normalizar(input.value) !== "") {
        pintarResultados(buscarCoincidencias(input.value));
      }
    });

    input.addEventListener("keydown", function (event) {
      if (event.key === "ArrowDown") {
        if (!coincidenciasActuales.length) {
          return;
        }
        event.preventDefault();
        resaltar((indiceActivo + 1) % coincidenciasActuales.length);
        return;
      }

      if (event.key === "ArrowUp") {
        if (!coincidenciasActuales.length) {
          return;
        }
        event.preventDefault();
        resaltar(
          (indiceActivo - 1 + coincidenciasActuales.length) %
            coincidenciasActuales.length
        );
        return;
      }

      if (event.key === "Escape") {
        cerrarResultados();
        return;
      }

      if (event.key !== "Enter") {
        return;
      }

      var query = input.value;
      if (normalizar(query) === "") {
        // Campo vacío + Enter = "todos". El hidden ya quedó vacío, así que
        // se deja pasar el envío normal del formulario.
        cerrarResultados();
        return;
      }

      var elegida = null;

      if (indiceActivo >= 0 && coincidenciasActuales[indiceActivo]) {
        // El usuario navegó con las flechas hasta una opción puntual.
        elegida = coincidenciasActuales[indiceActivo];
      } else {
        var qNorm = normalizar(query);
        var exacta = opciones.filter(function (opcion) {
          return normalizar(opcion.nombre) === qNorm;
        })[0];

        if (exacta) {
          elegida = exacta;
        } else {
          var parciales = buscarCoincidencias(query);
          elegida = parciales[0] || null;
        }
      }

      if (elegida) {
        event.preventDefault();
        elegirOpcion(elegida);
        if (enviarAlPresionarEnter) {
          var form = root.closest("form");
          if (form) {
            form.submit();
          }
        }
      }
      // Si no hubo ninguna coincidencia, se deja que Enter envíe el
      // formulario tal cual (con el filtro vacío) en vez de bloquear al
      // usuario sin explicación.
    });

    resultsBox.addEventListener("mousedown", function (event) {
      var optionEl = event.target.closest(".sc-combo-option");
      if (!optionEl) {
        return;
      }
      var indice = parseInt(optionEl.getAttribute("data-index"), 10);
      var opcion = coincidenciasActuales[indice];
      if (opcion) {
        elegirOpcion(opcion);
      }
    });

    document.addEventListener("click", function (event) {
      if (!root.contains(event.target)) {
        cerrarResultados();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var combos = document.querySelectorAll("[data-sc-combo]");
    combos.forEach(inicializarCombo);
  });
})();
