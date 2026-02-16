      <h1>Editar ticket</h1>
      <p class="muted">
        <?php if ($editTicket): ?>
          <a class="btnlink" href="?vista=ver&id=<?= (int)$editTicket["id"] ?>&return=<?= rawurlencode($returnFromEdit) ?>">← Volver</a>
        <?php else: ?>
          <a class="btnlink" href="<?= h($returnFromEdit) ?>">← Volver</a>
        <?php endif; ?>
      </p>

      <?php if ($editError !== ""): ?>
        <div class="error"><?= h($editError) ?></div>
      <?php endif; ?>

      <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
      <?php endif; ?>

      <?php if ($editTicket): ?>
        <div class="box">
          <form method="post" action="<?= h($selfEdit) ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= (int)$editTicket["id"] ?>">
            <input type="hidden" name="return" value="<?= h($returnFromEdit) ?>">

            <div class="row">
              <div>
                <div class="muted">Minecraft user</div>
                <input name="minecraft_user" maxlength="32" value="<?= h($old["minecraft_user"]) ?>">
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
              <input name="titulo" maxlength="120" style="width:100%;" value="<?= h($old["titulo"]) ?>">
            </div>

            <div style="margin-top:10px;">
              <div class="muted">Descripción</div>
              <textarea name="descripcion"><?= h($old["descripcion"]) ?></textarea>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
              <button type="submit">Guardar cambios</button>
              <a class="btnlink" href="?vista=ver&id=<?= (int)$editTicket["id"] ?>&return=<?= rawurlencode($returnFromEdit) ?>">Cancelar</a>
            </div>
          </form>
        </div>
      <?php endif; ?>
