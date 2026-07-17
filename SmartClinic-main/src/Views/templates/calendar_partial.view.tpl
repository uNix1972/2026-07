<div class="calendar-header" id="calendar-header-partial">
  <h2 class="calendar-title" id="calendar-title">{{cal_nombre_mes}} {{cal_anio}}</h2>
  <div class="calendar-nav">
    <button type="button" class="calendar-nav-btn" data-action="prev" data-mes="{{prev_mes}}" data-anio="{{prev_anio}}" title="Mes anterior">‹</button>
    <button type="button" class="calendar-nav-btn" data-action="next" data-mes="{{next_mes}}" data-anio="{{next_anio}}" title="Mes siguiente">›</button>
  </div>
</div>
<div class="calendar-grid" id="calendar-grid">
  <div class="calendar-day-name">Dom</div>
  <div class="calendar-day-name">Lun</div>
  <div class="calendar-day-name">Mar</div>
  <div class="calendar-day-name">Mié</div>
  <div class="calendar-day-name">Jue</div>
  <div class="calendar-day-name">Vie</div>
  <div class="calendar-day-name">Sáb</div>
  {{foreach cal_semanas}}
  {{foreach dias}}
  <a href="index.php?page=HomeController&action=dayView&fecha={{fecha}}" class="calendar-day {{if other_month}}other-month{{endif other_month}} {{if today}}today{{endif today}} {{if has_events}}has-events{{endif has_events}}">
    <span class="calendar-day-number">{{dia}}</span>
    <div class="calendar-events">
      {{foreach eventos}}
      <span class="calendar-event estado-{{estado_id}}">{{hora}} {{paciente}}</span>
      {{endfor eventos}}
      {{if more_events}}
      <span class="calendar-more">+{{more_count}} más</span>
      {{endif more_events}}
    </div>
  </a>
  {{endfor dias}}
  {{endfor cal_semanas}}
</div>
<div class="calendar-legend">
  <span class="legend-item"><span class="legend-dot pending"></span>Pendiente</span>
  <span class="legend-item"><span class="legend-dot completed"></span>Completada</span>
  <span class="legend-item"><span class="legend-dot cancelled"></span>Cancelada</span>
</div>