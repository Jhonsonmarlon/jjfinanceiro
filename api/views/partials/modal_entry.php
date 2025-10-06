<dialog id="modal-entry" class="modal">
    <form method="post" action="?r=entries/save" class="card">
        <h3>Novo lançamento</h3>
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="grid cols-3">
            <label>Tipo<br>
                <select name="kind" class="input">
                    <option value="expense">Despesa</option>
                    <option value="income">Receita</option>
                    <option value="transfer">Transferência</option>
                </select>
            </label>
            <label>Categoria<br><input class="input" name="category" placeholder="Mercado, Renda..."></label>
            <label>Conta<br><input class="input" name="account" placeholder="PT-IBAN, Nubank..."></label>
        </div><br>
        <div class="grid cols-3">
            <label>Valor<br><input class="input" type="number" step="0.01" name="amount" required></label>
            <label>Moeda<br><input class="input" name="currency" value="EUR" maxlength="3"></label>
            <label>Data<br><input class="input" type="date" name="entry_date" value="<?= date('Y-m-d') ?>"></label>
        </div><br>
        <label>Descrição<br><input class="input" name="description" placeholder="Detalhes"></label><br>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="button" data-close>Cancelar</button>
            <button class="button success">Adicionar</button>
        </div>
    </form>
</dialog>