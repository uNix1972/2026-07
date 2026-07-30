<div class="container section-pad">
  <div class="form-card" style="max-width:700px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.5rem;">Agendar una cita</h2>
      <p style="color:#636366;">Selecciona paciente, médico y horario para registrar una nueva cita.</p>
    </div>

    {{if error}}
    <div style="background:#FEE2E2; border:1px solid #FCA5A5; border-radius:12px; padding:1rem; margin-bottom:1.5rem; color:#991B1B;">
      {{error}}
    </div>
    {{endif error}}

    <form id="appointment_form" method="POST" action="index.php?page=CitasController&action=agendar" novalidate>
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <input id="notify_patient" type="hidden" name="notify_patient" value="0">
      <div class="form-group">
        <label for="paciente_search">Paciente</label>
        <div class="sc-combo" id="paciente_combo" data-sc-combo>
          <input type="text" id="paciente_search" class="sc-combo-input" autocomplete="off" placeholder="Buscar paciente por nombre o identidad..." value="{{pacienteNombreSeleccionado}}" data-sc-combo-input data-options="{{~pacientesJsonAttr}}" required>
          <input type="hidden" id="paciente_id" name="paciente_id" data-sc-combo-hidden data-telefono-inicial="{{pacienteTelefonoSeleccionado}}" value="{{pacienteIdSeleccionadoValue}}">
          <div class="sc-combo-results" data-sc-combo-results hidden></div>
        </div>
        <div style="margin-top:8px;padding:10px 12px;background:#F8FAFC;border-left:3px solid #0260CB;color:#334155;">
          Teléfono para notificación:
          <strong id="patient_notification_phone">Selecciona un paciente</strong>
        </div>
      </div>

      <div class="form-group">
        <label for="medico_search">Médico</label>
        <div class="sc-combo" id="medico_combo" data-sc-combo>
          <input type="text" id="medico_search" class="sc-combo-input" autocomplete="off" placeholder="Buscar médico por nombre o especialidad..." value="{{medicoNombreSeleccionado}}" data-sc-combo-input data-options="{{~medicosJsonAttr}}" required>
          <input type="hidden" id="medico_id" name="medico_id" data-sc-combo-hidden value="{{medicoIdSeleccionadoValue}}">
          <div class="sc-combo-results" data-sc-combo-results hidden></div>
        </div>
      </div>

      <div class="form-group">
        <label for="centro_salud_id">Centro de salud y consultorio</label>
        <select id="centro_salud_id" name="centro_salud_id" required>
          <option value="">-- Selecciona un centro --</option>
          {{foreach centros}}
          <option value="{{centro_salud_id}}" {{if selected}}selected{{endif selected}}>{{centro_nombre}} - Consultorio {{consultorio}}</option>
          {{endfor centros}}
        </select>
      </div>

      <div class="form-group">
        <label for="fecha">Fecha de la cita</label>
        <input id="fecha" type="date" name="fecha" value="{{fecha}}" min="{{minDate}}" max="{{maxDate}}" required>
      </div>

      <div class="form-group">
        <label for="hora">Hora de la cita</label>
        <select id="hora" name="hora" required>
          <option value="">-- Selecciona una hora --</option>
          {{foreach timeOptions}}
          <option value="{{value}}" {{if selected}}selected{{endif selected}}>{{label}}</option>
          {{endfor timeOptions}}
        </select>
        <small style="display:block; margin-top:0.5rem; color:#475569;">Solo horarios de 30 minutos: 00 o 30, sin almuerzo de 12:00 a 13:00.</small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Agendar cita</button>
        <a href="index.php?page=CitasController&action=index" class="btn btn--outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<dialog id="appointment_confirmation" aria-labelledby="appointment_confirmation_title" style="width:min(92vw,520px);border:0;border-radius:8px;padding:0;box-shadow:0 20px 50px rgba(15,23,42,.28);">
  <div style="padding:24px;">
    <h3 id="appointment_confirmation_title" style="margin:0 0 10px;color:#0F172A;font-size:1.35rem;">¿Está seguro que los datos son correctos?</h3>
    <p style="margin:0 0 20px;color:#475569;">La cita se guardará con la información seleccionada.</p>

    <label style="display:flex;align-items:flex-start;gap:10px;padding:14px;border:1px solid #CBD5E1;border-radius:6px;color:#0F172A;font-weight:600;">
      <input id="notify_patient_choice" type="checkbox" {{if notify_patient}}checked{{endif notify_patient}}>
      <span>
        ¿Desea notificar al paciente inmediatamente después de crear la cita?
        <small style="display:block;margin-top:6px;color:#475569;font-weight:400;">
          El mensaje se enviará al teléfono <strong id="confirmation_patient_phone">sin teléfono registrado</strong>.
        </small>
      </span>
    </label>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px;">
      <button id="cancel_appointment_confirmation" type="button" class="btn btn--outline">Revisar datos</button>
      <button id="confirm_appointment_creation" type="button" class="btn btn--primary">Confirmar y agendar</button>
    </div>
  </div>
</dialog>

<style>
  #appointment_confirmation::backdrop {
    background: rgba(15, 23, 42, 0.55);
  }

  /* Barra de búsqueda con autocompletar (Paciente / Médico), mismo
     componente que ya se usa en Inventario/Kárdex.
     Ver public/js/kardex-autocomplete.js para el comportamiento. */
  .sc-combo { position: relative; }
  .sc-combo-input {
    width: 100%;
    padding: 0.95rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    font: inherit;
    box-sizing: border-box;
  }
  .sc-combo-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    max-height: 220px;
    overflow-y: auto;
    z-index: 20;
  }
  .sc-combo-option {
    padding: 10px 14px;
    cursor: pointer;
    font-size: .95rem;
    color: #111827;
  }
  .sc-combo-option:hover,
  .sc-combo-option.is-active {
    background: #EAF5FD;
  }
  .sc-combo-empty {
    padding: 10px 14px;
    color: #64748b;
    font-size: .9rem;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var appointmentForm = document.getElementById('appointment_form');
    var pacienteCombo = document.getElementById('paciente_combo');
    var pacienteSearch = document.getElementById('paciente_search');
    var pacienteIdInput = document.getElementById('paciente_id');
    var patientPhone = document.getElementById('patient_notification_phone');
    var notifyInput = document.getElementById('notify_patient');
    var confirmationDialog = document.getElementById('appointment_confirmation');
    var confirmationPhone = document.getElementById('confirmation_patient_phone');
    var notifyChoice = document.getElementById('notify_patient_choice');
    var cancelConfirmation = document.getElementById('cancel_appointment_confirmation');
    var confirmCreation = document.getElementById('confirm_appointment_creation');
    var medicoCombo = document.getElementById('medico_combo');
    var medicoSearch = document.getElementById('medico_search');
    var medicoIdInput = document.getElementById('medico_id');
    var centroSelect = document.querySelector('select[name="centro_salud_id"]');
    var fechaInput = document.querySelector('input[name="fecha"]');
    var horaSelect = document.querySelector('select[name="hora"]');

    function refreshPatientPhone(telefono) {
      if (!patientPhone || !notifyChoice) return;

      if (typeof telefono === 'undefined') {
        telefono = pacienteIdInput && pacienteIdInput.value
          ? (pacienteIdInput.getAttribute('data-telefono-inicial') || '').trim()
          : '';
      }
      var phone = (telefono || '').trim();
      patientPhone.textContent = phone || (pacienteIdInput && pacienteIdInput.value ? 'Sin teléfono registrado' : 'Selecciona un paciente');
      if (confirmationPhone) {
        confirmationPhone.textContent = phone || 'sin teléfono registrado';
      }
      notifyChoice.disabled = phone === '';
      if (phone === '') {
        notifyChoice.checked = false;
      }
    }

    function refreshCenters(resetSelection) {
      if (!medicoIdInput || !centroSelect) return;

      var currentValue = resetSelection ? '' : centroSelect.value;
      centroSelect.disabled = true;
      if (!medicoIdInput.value) {
        centroSelect.innerHTML = '<option value="">-- Selecciona un centro --</option>';
        return;
      }

      fetch('index.php?page=CitasController&action=availableCenters&medico_id=' + encodeURIComponent(medicoIdInput.value))
        .then(function (response) { return response.json(); })
        .then(function (data) {
          centroSelect.innerHTML = '<option value="">-- Selecciona un centro --</option>';
          data.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            option.selected = String(item.value) === String(currentValue);
            centroSelect.appendChild(option);
          });
          centroSelect.disabled = data.length === 0;
        })
        .catch(function () {
          centroSelect.innerHTML = '<option value="">No se pudieron cargar los centros</option>';
        });
    }

    function refreshTimes() {
      if (!medicoIdInput || !fechaInput || !horaSelect) return;
      if (!medicoIdInput.value || !fechaInput.value) return;

      fetch('index.php?page=CitasController&action=availableTimes&medico_id=' + encodeURIComponent(medicoIdInput.value) + '&paciente_id=' + encodeURIComponent(pacienteIdInput ? pacienteIdInput.value : '') + '&fecha=' + encodeURIComponent(fechaInput.value))
        .then(function (response) { return response.json(); })
        .then(function (data) {
          var currentValue = horaSelect.value;
          horaSelect.innerHTML = '<option value="">-- Selecciona una hora --</option>';
          data.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            if (item.value === currentValue) {
              option.selected = true;
            }
            horaSelect.appendChild(option);
          });
        });
    }

    function actualizarValidezCombo(searchInput, hiddenInput, mensaje) {
      if (!searchInput || !hiddenInput) return;
      if (!hiddenInput.value || parseInt(hiddenInput.value, 10) <= 0) {
        searchInput.setCustomValidity(mensaje);
      } else {
        searchInput.setCustomValidity('');
      }
    }

    medicoCombo && medicoCombo.addEventListener('sc-combo:select', function () {
      actualizarValidezCombo(medicoSearch, medicoIdInput, 'Selecciona un médico de la lista de resultados.');
      refreshCenters(true);
      refreshTimes();
    });
    medicoCombo && medicoCombo.addEventListener('sc-combo:clear', function () {
      actualizarValidezCombo(medicoSearch, medicoIdInput, 'Selecciona un médico de la lista de resultados.');
      refreshCenters(true);
      refreshTimes();
    });
    pacienteCombo && pacienteCombo.addEventListener('sc-combo:select', function (event) {
      actualizarValidezCombo(pacienteSearch, pacienteIdInput, 'Selecciona un paciente de la lista de resultados.');
      refreshPatientPhone(event.detail && event.detail.telefono);
      refreshTimes();
    });
    pacienteCombo && pacienteCombo.addEventListener('sc-combo:clear', function () {
      actualizarValidezCombo(pacienteSearch, pacienteIdInput, 'Selecciona un paciente de la lista de resultados.');
      refreshPatientPhone('');
      refreshTimes();
    });
    fechaInput && fechaInput.addEventListener('change', refreshTimes);

    appointmentForm && appointmentForm.addEventListener('submit', function (event) {
      event.preventDefault();

      actualizarValidezCombo(pacienteSearch, pacienteIdInput, 'Selecciona un paciente de la lista de resultados.');
      actualizarValidezCombo(medicoSearch, medicoIdInput, 'Selecciona un médico de la lista de resultados.');

      if (!appointmentForm.checkValidity()) {
        appointmentForm.reportValidity();
        return;
      }

      refreshPatientPhone();
      confirmationDialog.showModal();
    });

    cancelConfirmation && cancelConfirmation.addEventListener('click', function () {
      confirmationDialog.close();
    });

    confirmCreation && confirmCreation.addEventListener('click', function () {
      notifyInput.value = notifyChoice.checked && !notifyChoice.disabled ? '1' : '0';
      confirmationDialog.close();
      appointmentForm.submit();
    });

    actualizarValidezCombo(pacienteSearch, pacienteIdInput, 'Selecciona un paciente de la lista de resultados.');
    actualizarValidezCombo(medicoSearch, medicoIdInput, 'Selecciona un médico de la lista de resultados.');
    refreshPatientPhone();
    refreshCenters(false);
    refreshTimes();
  });
</script>
