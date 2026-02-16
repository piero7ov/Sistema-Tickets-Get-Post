      <h1>Ver ticket</h1>
      <p class="muted">
        <a class="btnlink" href="<?= h($returnFromView) ?>">← Volver</a>
        <?php if ($ticket): ?>
          <a class="btnlink" href="?vista=edit&id=<?= (int)$ticket["id"] ?>&return=<?= rawurlencode($returnFromView) ?>">Editar</a>
        <?php endif; ?>
      </p>

      <?php if ($verError !== ""): ?>
        <div class="error"><?= h($verError) ?></div>
      <?php else: ?>

        <div class="box">
          <div class="muted">Acciones (POST)</div>
          <div style="margin-top:10px;">
            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="abierto">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">Abrir</button>
            </form>

            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="en_proceso">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">En proceso</button>
            </form>

            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="cerrado">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">Cerrar</button>
            </form>

            <form class="inline" method="post" action=""
                  onsubmit="return confirm('¿Borrar ticket #<?= (int)$ticket['id'] ?>?');">
              <input type="hidden" name="accion" value="borrar">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="return" value="<?= h($returnFromView) ?>">
              <button type="submit" style="background:var(--danger);">Borrar</button>
            </form>
          </div>
        </div>

        <table>
          <tbody>
            <tr><th>ID</th><td><?= (int)$ticket["id"] ?></td></tr>
            <tr><th>Usuario</th><td><?= h($ticket["minecraft_user"]) ?></td></tr>
            <tr><th>Tipo</th><td><?= h($ticket["tipo"]) ?></td></tr>
            <tr>
              <th>Prioridad</th>
              <td><span class="badge prio-<?= prioClass($ticket["prioridad"]) ?>"><?= h($ticket["prioridad"]) ?></span></td>
            </tr>
            <tr>
              <th>Estado</th>
              <td><span class="badge estado-<?= estadoClass($ticket["estado"]) ?>"><?= h($ticket["estado"]) ?></span></td>
            </tr>
            <tr><th>Creado</th><td><?= h($ticket["creado_en"]) ?></td></tr>
            <tr><th>Actualizado</th><td><?= h($ticket["actualizado_en"]) ?></td></tr>
            <tr><th>Título</th><td><?= h($ticket["titulo"]) ?></td></tr>
            <tr><th>Descripción</th><td><?= nl2br(h($ticket["descripcion"])) ?></td></tr>
          </tbody>
        </table>

      <?php endif; ?>
