<div class="container" style="max-width:900px; margin:140px auto 100px auto;">

{{with medico}}

<div style="background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(3,59,159,.12);border:1px solid #EAF5FD;">

    <div style="margin-bottom:30px;">
        <h2 style="color:#033B9F;margin-bottom:10px;font-size:2.2rem;">Editar Médico</h2>

        <p style="color:#636366;">Actualiza los datos del médico y su especialidad.</p>
    </div>

    {{if &error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{&error}}</div>
    {{endif &error}}

    <form method="POST" action="index.php?page=MedicosController&action=edit&id={{id}}">

        <input type="hidden" name="csrf_token" value="{{&csrf_token}}">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Especialidad</label>
                <select name="especialidad_id" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
                    {{foreach &especialidades}}
                        <option value="{{id}}" {{if selected}}selected{{endif}}>{{nombre_especialidad}}</option>
                    {{endfor &especialidades}}
                </select>
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">N° Colegiatura</label>
                <input type="text" name="num_colegiatura" value="{{num_colegiatura}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Nombres</label>
                <input type="text" name="nombres" value="{{nombres}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Apellidos</label>
                <input type="text" name="apellidos" value="{{apellidos}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Teléfono</label>
                <input type="text" name="telefono" value="{{telefono}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

        </div>

        <div style="margin-top:30px;display:flex;justify-content:flex-end;gap:15px;">

            <a href="index.php?page=MedicosController&action=index" style="padding:12px 25px;border:1px solid #C7C7CC;border-radius:10px;text-decoration:none;color:#636366;">Cancelar</a>

            <button type="submit" style="background:#033B9F;color:#fff;border:none;padding:12px 25px;border-radius:10px;font-weight:600;cursor:pointer;">Actualizar Médico</button>

        </div>

    </form>

</div>

{{endwith medico}}

</div>
