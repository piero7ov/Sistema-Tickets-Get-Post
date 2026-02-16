      <h1>Listado de tickets</h1>
      <p class="muted">Listado real desde BD. Búsqueda: <?= $useFullText ? "FULLTEXT" : "LIKE" ?>.</p>

      <div class="box">
        <form method="get" class="row">
          <input type="hidden" name="vista" value="list">

          <div>
            <div class="muted">Buscar</div>
            <input name="q" value="<?= h($q) ?>" placeholder="spawn, robo, plugin...">
          </div>

          <div>
            <div class="muted">Estado</div>
            <select name="estado">
              <option value="" <?= $estado==="" ? "selected" : "" ?>>todos</option>
              <option value="abierto" <?= $estado==="abierto" ? "selected" : "" ?>>abierto</option>
              <option value="en_proceso" <?= $estado==="en_proceso" ? "selected" : "" ?>>en_proceso</option>
              <option value="cerrado" <?= $estado==="cerrado" ? "selected" : "" ?>>cerrado</option>
            </select>
          </div>

          <div>
            <div class="muted">Tipo</div>
            <select name="tipo">
              <option value="" <?= $tipo==="" ? "selected" : "" ?>>todos</option>
              <option value="bug" <?= $tipo==="bug" ? "selected" : "" ?>>bug</option>
              <option value="reporte" <?= $tipo==="reporte" ? "selected" : "" ?>>reporte</option>
              <option value="ayuda" <?= $tipo==="ayuda" ? "selected" : "" ?>>ayuda</option>
              <option value="sugerencia" <?= $tipo==="sugerencia" ? "selected" : "" ?>>sugerencia</option>
            </select>
          </div>

          <div>
            <div class="muted">Orden</div>
            <select name="order">
              <option value="new" <?= $order==="new" ? "selected" : "" ?>>más nuevos</option>
              <option value="old" <?= $order==="old" ? "selected" : "" ?>>más antiguos</option>
              <option value="prio" <?= $order==="prio" ? "selected" : "" ?>>prioridad</option>
            </select>
          </div>

          <div style="align-self:end;">
            <button type="submit">Aplicar</button>
          </div>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Minecraft user</th>
            <th>Tipo</th>
            <th>Título (ver)</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Creado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$tickets): ?>
            <tr><td colspan="8">No hay tickets con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <tr>
                <td><?= (int)$t["id"] ?></td>
                <td><?= h($t["minecraft_user"]) ?></td>
                <td><?= h($t["tipo"]) ?></td>
                <td>
                  <a class="btnlink" href="?vista=ver&id=<?= (int)$t["id"] ?>&return=<?= rawurlencode($returnHere) ?>">
                    <?= h($t["titulo"]) ?>
                  </a>
                </td>
                <td>
                  <span class="badge prio-<?= prioClass($t["prioridad"]) ?>"><?= h($t["prioridad"]) ?></span>
                </td>
                <td>
                  <span class="badge estado-<?= estadoClass($t["estado"]) ?>"><?= h($t["estado"]) ?></span>
                </td>
                <td class="muted"><?= h($t["creado_en"]) ?></td>

                <td>
                  <a class="btnlink" href="?vista=edit&id=<?= (int)$t["id"] ?>&return=<?= rawurlencode($returnHere) ?>">Editar</a>

                  <form class="inline" method="post" action="<?= h($returnHere) ?>">
                    <input type="hidden" name="accion" value="estado">
                    <input type="hidden" name="id" value="<?= (int)$t["id"] ?>">
                    <input type="hidden" name="estado" value="cerrado">
                    <input type="hidden" name="return" value="<?= h($returnHere) ?>">
                    <button type="submit">Cerrar</button>
                  </form>

                  <form class="inline" method="post" action="<?= h($returnHere) ?>"
                        onsubmit="return confirm('¿Borrar ticket #<?= (int)$t['id'] ?>?');">
                    <input type="hidden" name="accion" value="borrar">
                    <input type="hidden" name="id" value="<?= (int)$t["id"] ?>">
                    <input type="hidden" name="return" value="<?= h($returnHere) ?>">
                    <button type="submit" style="background:var(--danger);">Borrar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
