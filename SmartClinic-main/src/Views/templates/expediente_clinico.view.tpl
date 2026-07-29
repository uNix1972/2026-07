<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div class="clinical-hero__content">
      <span class="clinical-hero__eyebrow">SmartClinic · Información protegida</span>
      <h1>Expediente clínico</h1>
      <p>Consultas atendidas por el médico, organizadas por cita y listas para descargar.</p>
    </div>
    <div class="clinical-hero__actions">
      <a class="btn btn--outline" href="{{volver}}">Volver</a>
    </div>
  </header>

  <form method="GET" action="index.php" class="clinical-filter">
    <input type="hidden" name="page" value="DoctoresController">
    <input type="hidden" name="action" value="expediente">
    <input type="hidden" name="paciente_id" value="{{paciente_id}}">
    <div><label for="fecha_desde">Desde</label><input id="fecha_desde" type="date" name="fecha_desde" value="{{fecha_desde}}"></div>
    <div><label for="fecha_hasta">Hasta</label><input id="fecha_hasta" type="date" name="fecha_hasta" value="{{fecha_hasta}}"></div>
    <button class="btn btn--primary" type="submit">Filtrar expedientes</button>
    <a class="btn btn--outline" href="index.php?page=DoctoresController&action=expediente&paciente_id={{paciente_id}}">Limpiar</a>
  </form>

  {{if citas}}
  {{foreach citas}}
  <article class="sc-panel-card clinical-record-card" style="margin-bottom:18px;">
    <div class="clinical-record-card__header">
      <div>
        <h3>Cita #{{id}} · {{fecha_hora}}</h3>
        <div class="clinical-record-card__meta">
          <span class="clinical-chip">{{nombre_estado}}</span>
          <span class="clinical-chip">{{centro_nombre}}</span>
          <span class="clinical-chip">{{nombre_especialidad}}</span>
        </div>
      </div>
      <a class="btn btn--primary" href="index.php?page=DoctoresController&action=pdf&cita_id={{id}}">Descargar PDF</a>
    </div>

    <div class="clinical-vitals-summary" style="border-radius:14px; padding:16px; margin:16px 0;">
      <strong>Signos vitales tomados</strong>
      <div class="clinical-vitals-grid">
        <div class="clinical-vital"><span class="clinical-vital__label">Temperatura</span><span class="clinical-vital__value">{{temperatura}} °C</span></div>
        <div class="clinical-vital"><span class="clinical-vital__label">Presión arterial</span><span class="clinical-vital__value">{{presion_sistolica}}/{{presion_diastolica}}</span></div>
        <div class="clinical-vital"><span class="clinical-vital__label">Frecuencia cardiaca</span><span class="clinical-vital__value">{{frecuencia_cardiaca}} lpm</span></div>
        <div class="clinical-vital"><span class="clinical-vital__label">Frecuencia respiratoria</span><span class="clinical-vital__value">{{frecuencia_respiratoria}} rpm</span></div>
        <div class="clinical-vital"><span class="clinical-vital__label">Saturación</span><span class="clinical-vital__value">{{saturacion_oxigeno}}%</span></div>
        <div class="clinical-vital"><span class="clinical-vital__label">Peso / talla</span><span class="clinical-vital__value">{{peso}} kg · {{talla}} cm</span></div>
      </div>
      <p class="clinical-section-intro" style="margin:12px 0 0;">{{signos_notas}}</p>
    </div>

    {{if historial_id}}
    <div class="clinical-note-grid">
      <div class="clinical-note"><strong>Motivo de consulta</strong>{{motivo_consulta}}</div>
      <div class="clinical-note"><strong>Diagnóstico</strong>{{diagnostico}}</div>
      <div class="clinical-note"><strong>Tratamiento</strong>{{tratamiento}}</div>
      <div class="clinical-note"><strong>Observaciones</strong>{{observaciones}}</div>
    </div>
    {{endif historial_id}}
    {{ifnot historial_id}}
    <div class="clinical-empty">Esta cita aún no tiene nota clínica.</div>
    {{endifnot historial_id}}
  </article>
  {{endfor citas}}
  {{endif citas}}

  {{ifnot citas}}
  <div class="sc-panel-card"><div class="clinical-empty">No hay citas atendidas para este paciente.</div></div>
  {{endifnot citas}}
</div>
