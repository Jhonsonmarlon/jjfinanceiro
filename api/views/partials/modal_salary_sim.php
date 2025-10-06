<dialog id="modal-salary" class="modal">
    <div class="card">
        <h3>Simular ordenado (Portugal)</h3>
        <div class="grid cols-3">
            <label>€ por hora<br><input class="input" type="number" step="0.01" id="sim-rate" value="5.00"></label>
            <label>Horas normais<br><input class="input" type="number" step="0.01" id="sim-hours" value="160"></label>
            <label>Horas extra<br><input class="input" type="number" step="0.01" id="sim-ot" value="0"></label>
        </div><br>
        <div class="grid cols-3">
            <label>Multiplicador HE<br><input class="input" type="number" step="0.01" id="sim-mult"
                    value="1.25"></label>
            <label>Alimentação/dia (€)<br><input class="input" type="number" step="0.01" id="sim-meal"
                    value="6.00"></label>
            <label>Dias trabalhados<br><input class="input" type="number" id="sim-days" value="22"></label>
        </div><br>
        <div class="card" style="background:#0c162e">
            <div class="muted">Estimativa bruta</div>
            <div class="kpi" id="sim-total">0,00</div>
        </div>
        <br>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="button" data-close>Fechar</button>
        </div>
    </div>
</dialog>