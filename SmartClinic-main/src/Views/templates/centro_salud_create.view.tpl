<div class="container" style="max-width:900px;margin:120px auto 80px;">
    <div style="background:#fff;padding:32px;border:1px solid #E5E7EB;border-radius:8px;">
        <h2 style="color:#033B9F;margin:0 0 24px;font-size:2rem;">Nuevo Centro de Salud</h2>

        {{if error}}
        <div class="form-alert error" style="display:block;margin-bottom:16px;">{{error}}</div>
        {{endif error}}

        <form method="POST" action="index.php?page=CentrosSaludController&action=create">
            <input type="hidden" name="csrf_token" value="{{csrf_token}}">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">
                <div class="form-group">
                    <label for="codigo">Código</label>
                    <input id="codigo" type="text" name="codigo" maxlength="30" value="{{codigo}}" required>
                </div>

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" type="text" name="nombre" maxlength="150" value="{{nombre}}" required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" required>
                        {{foreach tipos}}
                        <option value="{{valor}}" {{if selected}}selected{{endif selected}}>{{valor}}</option>
                        {{endfor tipos}}
                    </select>
                </div>

                <div class="form-group">
                    <label for="ciudad">Ciudad</label>
                    <input id="ciudad" type="text" name="ciudad" maxlength="100" value="{{ciudad}}" required>
                </div>

                <div class="form-group" style="grid-column:1/-1;">
                    <label for="direccion">Dirección</label>
                    <input id="direccion" type="text" name="direccion" maxlength="255" value="{{direccion}}" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input id="telefono" type="tel" name="telefono" maxlength="20" value="{{telefono}}">
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input id="email" type="email" name="email" maxlength="150" value="{{email}}">
                </div>
            </div>

            <div class="form-actions" style="margin-top:26px;">
                <a class="btn btn--outline" href="index.php?page=CentrosSaludController&action=index">Cancelar</a>
                <button type="submit" class="btn btn--primary">Guardar centro</button>
            </div>
        </form>
    </div>
</div>

