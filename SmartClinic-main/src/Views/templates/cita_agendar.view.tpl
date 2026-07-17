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

    <form method="POST" action="index.php?page=CitasController&action=agendar" novalidate>
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-group">
        <label for="paciente_id">Paciente</label>
        <select id="paciente_id" name="paciente_id" required>
          <option value="">-- Selecciona un paciente --</option>
          {{foreach pacientes}}
          <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombres}} {{apellidos}} ({{identidad}})</option>
          {{endfor pacientes}}
        </select>
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var medicoSelect = document.querySelector('select[name="medico_id"]');
    var fechaInput = document.querySelector('input[name="fecha"]');
    var horaSelect = document.querySelector('select[name="hora"]');

    function refreshTimes() {
      if (!medicoSelect || !fechaInput || !horaSelect) return;
      if (!medicoSelect.value || !fechaInput.value) return;

      fetch('index.php?page=CitasController&action=availableTimes&medico_id=' + encodeURIComponent(medicoSelect.value) + '&fecha=' + encodeURIComponent(fechaInput.value))
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

    medicoSelect && medicoSelect.addEventListener('change', refreshTimes);
    fechaInput && fechaInput.addEventListener('change', refreshTimes);
    refreshTimes();
  });
</script>
