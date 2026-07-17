<div class="container section-pad">

    <div style="max-width:700px; margin:0 auto;">
        <h2 style="font-size:2.5rem; color:#111827; margin-bottom:1.5rem;">Editar cita</h2>

        {{if modo_lectura}}
        <div style="background:#FEF3C7; border:1px solid #FCD34D; border-radius:12px; padding:1rem; margin-bottom:1.5rem; color:#92400E;">
            <strong>⚠ Modo solo lectura:</strong> Esta cita es de una fecha pasada. Solo puede visualizar los datos, no editarlos.
        </div>
        {{endif modo_lectura}}

        {{if error}}
        <div style="background:#FEE2E2; border:1px solid #FCA5A5; border-radius:12px; padding:1rem; margin-bottom:1.5rem; color:#991B1B;">
            {{error}}
        </div>
        {{endif error}}

        <div style="background:#fff; border-radius:16px; padding:2rem; box-shadow:0 4px 20px rgba(0,0,0,.08);">
            <form method="POST" action="index.php?page=CitasController&action=edit&id={{cita_id}}" novalidate {{if modo_lectura}}readonly{{endif modo_lectura}}>
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Paciente</label>
                    <select name="paciente_id" required {{if modo_lectura}}disabled{{endif modo_lectura}} style="width:100%; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-size:1rem; {{if modo_lectura}}background:#f8fafc; color:#6b7280;{{endif modo_lectura}}">
                        {{foreach pacientes}}
                        <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombres}} {{apellidos}} ({{identidad}})</option>
                        {{endfor pacientes}}
                    </select>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Médico</label>
                    <select name="medico_id" required {{if modo_lectura}}disabled{{endif modo_lectura}} style="width:100%; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-size:1rem; {{if modo_lectura}}background:#f8fafc; color:#6b7280;{{endif modo_lectura}}">
                        {{foreach medicos}}
                        <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombres}} {{apellidos}} - {{nombre_especialidad}}</option>
                        {{endfor medicos}}
                    </select>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Fecha</label>
                    <input type="date" name="fecha" value="{{fecha}}" min="{{minDate}}" max="{{maxDate}}" required {{if modo_lectura}}disabled{{endif modo_lectura}} style="width:100%; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-size:1rem; {{if modo_lectura}}background:#f8fafc; color:#6b7280;{{endif modo_lectura}}">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Hora</label>
                    <select name="hora" required {{if modo_lectura}}disabled{{endif modo_lectura}} style="width:100%; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-size:1rem; {{if modo_lectura}}background:#f8fafc; color:#6b7280;{{endif modo_lectura}}">
                        <option value="">-- Selecciona una hora --</option>
                        {{foreach timeOptions}}
                        <option value="{{value}}" {{if selected}}selected{{endif selected}}>{{label}}</option>
                        {{endfor timeOptions}}
                    </select>
                    <small style="display:block; margin-top:0.5rem; color:#475569;">Solo horarios de 30 minutos: 00 o 30, sin almuerzo de 12:00 a 13:00.</small>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Estado</label>
                    <select name="estado_id" required {{if modo_lectura}}disabled{{endif modo_lectura}} style="width:100%; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-size:1rem; {{if modo_lectura}}background:#f8fafc; color:#6b7280;{{endif modo_lectura}}">
                        {{foreach estados}}
                        <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{label}}</option>
                        {{endfor estados}}
                    </select>
                </div>

                {{ifnot modo_lectura}}
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <button type="submit" style="flex:1; min-width:150px; background:#0b4bb8; color:#fff; padding:0.95rem; border:none; border-radius:12px; font-weight:700; cursor:pointer; font-size:1rem;">
                        Guardar cambios
                    </button>
                    <a href="index.php?page=CitasController&action=index" style="flex:1; min-width:150px; background:#f8fafc; color:#0f172a; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-weight:700; text-decoration:none; text-align:center; font-size:1rem;">
                        Cancelar
                    </a>
                </div>
                {{endifnot modo_lectura}}

                {{if modo_lectura}}
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <a href="index.php?page=CitasController&action=index" style="flex:1; min-width:150px; background:#f8fafc; color:#0f172a; padding:0.95rem; border:1px solid #e2e8f0; border-radius:12px; font-weight:700; text-decoration:none; text-align:center; font-size:1rem;">
                        Volver
                    </a>
                </div>
                {{endif modo_lectura}}

            </form>
        </div>
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
    var currentCitaId = new URLSearchParams(window.location.search).get('id') || '';

        fetch('index.php?page=CitasController&action=availableTimes&medico_id=' + encodeURIComponent(medicoSelect.value) + '&fecha=' + encodeURIComponent(fechaInput.value) + '&exclude_id=' + encodeURIComponent(currentCitaId))
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
