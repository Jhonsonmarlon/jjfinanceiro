<dialog id="modal-rate" class="modal">
    <form method="post" action="?r=rates/save" class="card">
        <h3>Atualizar taxa de câmbio</h3>
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="grid cols-3">
            <label>Moeda<br><input class="input" name="currency_code" maxlength="3" required></label>
            <label>Taxa → EUR<br><input class="input" type="number" step="0.00000001" name="rate_eur" required></label>
            <label>Data<br><input class="input" type="date" name="rate_date" value="<?= date('Y-m-d') ?>"></label>
        </div><br>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="button" data-close>Cancelar</button>
            <button class="button primary">Guardar/Atualizar</button>
        </div>
    </form>
</dialog>