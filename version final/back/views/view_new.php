      <h1>Nuevo ticket</h1>
      <div class="muted">Formulario con POST (INSERT).</div>

      <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
      <?php endif; ?>

      <div class="box">
        <form method="post" action="?vista=new">
          <input type="hidden" name="accion" value="crear">

          <div class="row">
            <div>
              <div class="muted">Minecraft user</div>
              <input name="minecraft_user" maxlength="32"
                     value="<?= h($old["minecraft_user"]) ?>"
                     placeholder="Ej: PieroDev">
            </div>

            <div>
              <div class="muted">Tipo</div>
              <select name="tipo">
                <option value="ayuda" <?= $old["tipo"]==="ayuda" ? "selected" : "" ?>>ayuda</option>
                <option value="bug" <?= $old["tipo"]==="bug" ? "selected" : "" ?>>bug</option>
                <option value="reporte" <?= $old["tipo"]==="reporte" ? "selected" : "" ?>>reporte</option>
                <option value="sugerencia" <?= $old["tipo"]==="sugerencia" ? "selected" : "" ?>>sugerencia</option>
              </select>
            </div>

            <div>
              <div class="muted">Prioridad</div>
              <select name="prioridad">
                <option value="baja" <?= $old["prioridad"]==="baja" ? "selected" : "" ?>>baja</option>
                <option value="media" <?= $old["prioridad"]==="media" ? "selected" : "" ?>>media</option>
                <option value="alta" <?= $old["prioridad"]==="alta" ? "selected" : "" ?>>alta</option>
              </select>
            </div>
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Título</div>
            <input name="titulo" maxlength="120" style="width:100%;"
                   value="<?= h($old["titulo"]) ?>"
                   placeholder="Ej: Se bugueó el spawn">
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Descripción</div>
            <textarea name="descripcion" placeholder="Describe el problema..."><?= h($old["descripcion"]) ?></textarea>
          </div>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit">Guardar</button>
            <a class="btnlink" href="?vista=list">Volver</a>
          </div>
        </form>
      </div>
