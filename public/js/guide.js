// Ratio calculator on a guide page: scales water and pours to a new coffee dose.
document.addEventListener('DOMContentLoaded', function () {
    var card = document.querySelector('[data-base-coffee]');
    var form = document.querySelector('.scaler');
    if (!card || !form) return;

    var base = parseFloat(card.dataset.baseCoffee);
    var input = document.getElementById('scale-coffee');
    var note = form.querySelector('.scaler-note');
    if (!(base > 0)) return;

    form.hidden = false; // only shown when JavaScript is available

    function format(value) {
        var rounded = Math.round(value * 10) / 10;
        return rounded % 1 === 0 ? rounded.toFixed(0) : rounded.toFixed(1);
    }

    function apply() {
        var dose = parseFloat(input.value);
        if (!(dose > 0)) return;
        var factor = dose / base;
        document.querySelectorAll('[data-scale]').forEach(function (el) {
            el.textContent = format(parseFloat(el.dataset.base) * factor);
        });
        note.textContent = factor === 1
            ? 'Showing the original recipe.'
            : 'Scaled for ' + format(dose) + ' g of coffee. The ratio stays the same.';
    }

    form.addEventListener('submit', function (event) { event.preventDefault(); });
    input.addEventListener('input', apply);
    form.querySelector('[data-scale-reset]').addEventListener('click', function () {
        input.value = format(base);
        apply();
    });
});
