// abrir/fechar modais <dialog>
(function () {
  const openers = document.querySelectorAll("[data-open]");
  openers.forEach((btn) =>
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-open");
      const dlg = document.getElementById(id);
      if (dlg) dlg.showModal();
    })
  );
  document.querySelectorAll("[data-close]").forEach((btn) =>
    btn.addEventListener("click", () => {
      const dlg = btn.closest("dialog");
      if (dlg) dlg.close();
    })
  );

  // simulador de ordenado
  function fmt(n) {
    return n.toLocaleString("pt-PT", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
  function calc() {
    const rate = parseFloat(document.getElementById("sim-rate")?.value || "0");
    const h = parseFloat(document.getElementById("sim-hours")?.value || "0");
    const ot = parseFloat(document.getElementById("sim-ot")?.value || "0");
    const mult = parseFloat(
      document.getElementById("sim-mult")?.value || "1.25"
    );
    const meal = parseFloat(document.getElementById("sim-meal")?.value || "0");
    const days = parseInt(document.getElementById("sim-days")?.value || "0");
    const gross = h * rate + ot * rate * mult + meal * days;
    const el = document.getElementById("sim-total");
    if (el) el.textContent = fmt(gross);
  }
  [
    "sim-rate",
    "sim-hours",
    "sim-ot",
    "sim-mult",
    "sim-meal",
    "sim-days",
  ].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("input", calc);
  });
  calc();
})();
