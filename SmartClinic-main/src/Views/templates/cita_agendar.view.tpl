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
        <label for="paciente_id">Paciente</label>
        <select id="paciente_id" name="paciente_id" required>
          <option value="">-- Selecciona un paciente --</option>
          {{foreach pacientes}}
          <option value="{{id}}" data-telefono="{{telefono}}" {{if selected}}selected{{endif selected}}>{{nombres}} {{apellidos}} ({{identidad}})</option>
          {{endfor pacientes}}
        </select>
        <div style="margin-top:8px;padding:10px 12px;background:#F8FAFC;border-left:3px solid #0260CB;color:#334155;">
          Teléfono para notificación:
          <strong id="patient_notification_phone">Selecciona un paciente</strong>
        </div>
      </div>

      <div class="form-group">
        <label for="medico_id">Médico</label>
        <select id="medico_id" name="medico_id" required>
          <option value="">-- Selecciona un médico --</option>
          {{foreach medicos}}
          <option value="{{id}}" {{if selected}}selected{{endif selected}}>Dr/a {{nombres}} {{apellidos}} - {{nombre_especialidad}}</option>
          {{endfor medicos}}
        </select>
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
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var appointmentForm = document.getElementById('appointment_form');
    var pacienteSelect = document.querySelector('select[name="paciente_id"]');
    var patientPhone = document.getElementById('patient_notification_phone');
    var notifyInput = document.getElementById('notify_patient');
    var confirmationDialog = document.getElementById('appointment_confirmation');
    var confirmationPhone = document.getElementById('confirmation_patient_phone');
    var notifyChoice = document.getElementById('notify_patient_choice');
    var cancelConfirmation = document.getElementById('cancel_appointment_confirmation');
    var confirmCreation = document.getElementById('confirm_appointment_creation');
    var medicoSelect = document.querySelector('select[name="medico_id"]');
    var centroSelect = document.querySelector('select[name="centro_salud_id"]');
    var fechaInput = document.querySelector('input[name="fecha"]');
    var horaSelect = document.querySelector('select[name="hora"]');

    function refreshPatientPhone() {
      if (!pacienteSelect || !patientPhone || !notifyChoice) return;

      var selectedOption = pacienteSelect.options[pacienteSelect.selectedIndex];
      var phone = selectedOption ? (selectedOption.getAttribute('data-telefono') || '').trim() : '';
      patientPhone.textContent = phone || (pacienteSelect.value ? 'Sin teléfono registrado' : 'Selecciona un paciente');
      if (confirmationPhone) {
        confirmationPhone.textContent = phone || 'sin teléfono registrado';
      }
      notifyChoice.disabled = phone === '';
      if (phone === '') {
        notifyChoice.checked = false;
      }
    }

    function refreshCenters(resetSelection) {
      if (!medicoSelect || !centroSelect) return;

      var currentValue = resetSelection ? '' : centroSelect.value;
      centroSelect.disabled = true;
      if (!medicoSelect.value) {
        centroSelect.innerHTML = '<option value="">-- Selecciona un centro --</option>';
        return;
      }

      fetch('index.php?page=CitasController&action=availableCenters&medico_id=' + encodeURIComponent(medicoSelect.value))
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
      if (!medicoSelect || !fechaInput || !horaSelect) return;
      if (!medicoSelect.value || !fechaInput.value) return;

      fetch('index.php?page=CitasController&action=availableTimes&medico_id=' + encodeURIComponent(medicoSelect.value) + '&paciente_id=' + encodeURIComponent(pacienteSelect ? pacienteSelect.value : '') + '&fecha=' + encodeURIComponent(fechaInput.value))
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

    medicoSelect && medicoSelect.addEventListener('change', function () {
      refreshCenters(true);
      refreshTimes();
    });
    pacienteSelect && pacienteSelect.addEventListener('change', function () {
      refreshPatientPhone();
      refreshTimes();
    });
    fechaInput && fechaInput.addEventListener('change', refreshTimes);

    appointmentForm && appointmentForm.addEventListener('submit', function (event) {
      event.preventDefault();

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

    refreshPatientPhone();
    refreshCenters(false);
    refreshTimes();
  });
</script>
