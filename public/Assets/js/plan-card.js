// Simulador dos cards de plano (resources/views/sale/plan_card.blade.php).
// Valores trafegam em centavos para evitar erro de arredondamento.
(function () {

    function money(cents) {
        return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function plural(count, singular, pluralForm) {
        return count === 1 ? singular : pluralForm;
    }

    function initCard(card) {

        const data = card.dataset;
        const plan = {
            name:     data.name,
            business: data.type === 'business',
            price:    parseInt(data.price, 10) || 0,
            extra:    parseInt(data.extra, 10) || 0,
            included: parseInt(data.included, 10) || 1,
            max:      parseInt(data.max, 10) || 1,
            stepper:  data.stepper === '1',
        };
        const state = {
            qty: parseInt(data.qty, 10) || 1,
            dep: parseInt(data.dep, 10) || 0,
        };
        const limits = { qty: [1, plan.max], dep: [0, plan.max] };
        const out = name => card.querySelector('[data-out="' + name + '"]');

        function calculate() {

            const rows = [];
            let total, message;

            if (plan.business) {
                const employees  = state.qty * plan.price;
                const dependents = state.dep * plan.extra;
                total = employees + dependents;
                rows.push([state.qty + ' func. × ' + money(plan.price), money(employees)]);
                message = 'com ' + state.qty + ' ' + plural(state.qty, 'funcionário', 'funcionários');
                if (plan.extra > 0) {
                    rows.push([state.dep + ' dep. × ' + money(plan.extra), money(dependents)]);
                    message += ' + ' + state.dep + ' ' + plural(state.dep, 'dependente', 'dependentes');
                }
            } else if (plan.stepper) {
                const extras = Math.max(0, state.qty - plan.included);
                total = plan.price + extras * plan.extra;
                rows.push(['Base até ' + plan.included + ' ' + plural(plan.included, 'pessoa', 'pessoas'), money(plan.price)]);
                if (extras > 0) {
                    rows.push([extras + ' ' + plural(extras, 'pessoa extra', 'pessoas extras') + ' × ' + money(plan.extra), money(extras * plan.extra)]);
                }
                rows.push(['Valor por pessoa', money(Math.round(total / state.qty)) + '/pessoa']);
                message = 'com ' + state.qty + ' ' + plural(state.qty, 'pessoa', 'pessoas');
            } else {
                total = plan.price;
                message = '';
            }

            return { total: total, rows: rows, message: message };
        }

        function render() {

            const result = calculate();

            if (out('qty')) {
                out('qty').textContent = state.qty;
                out('qty-label').textContent = plan.business ? plural(state.qty, 'vida', 'vidas') : plural(state.qty, 'pessoa', 'pessoas');
            }
            if (out('dep')) {
                out('dep').textContent = state.dep;
                out('dep-label').textContent = plural(state.dep, 'vida', 'vidas');
            }

            out('total').textContent = money(result.total);

            // Na adesão, a quantidade escolhida segue junto com o formulário; o servidor recalcula o total.
            card.querySelectorAll('[data-in]').forEach(function (input) {
                input.value = state[input.dataset.in];
            });

            const rows = out('rows');
            rows.textContent = '';
            result.rows.forEach(function (row) {
                const line  = document.createElement('div');
                const label = document.createElement('span');
                const value = document.createElement('strong');
                label.textContent = row[0];
                value.textContent = row[1];
                line.className = 'pc-row';
                line.appendChild(label);
                line.appendChild(value);
                rows.appendChild(line);
            });

            card.querySelectorAll('.pc-step').forEach(function (button) {
                const next = state[button.dataset.target] + parseInt(button.dataset.step, 10);
                const range = limits[button.dataset.target];
                button.disabled = next < range[0] || next > range[1];
            });

            const cta = card.querySelector('[data-cta]');
            if (cta && plan.stepper) {
                const url = new URL(cta.href, window.location.href);
                url.searchParams.set('qty', state.qty);
                if (plan.business) {
                    url.searchParams.set('dep', state.dep);
                }
                cta.href = url.toString();
            }

            const whatsapp = card.querySelector('[data-wa]');
            if (whatsapp) {
                const text = 'Olá! Quero o plano ' + plan.name + (result.message ? ' ' + result.message : '') + ', total ' + money(result.total) + data.suffix;
                whatsapp.href = 'https://wa.me/' + whatsapp.dataset.wa + '?text=' + encodeURIComponent(text);
            }
        }

        card.querySelectorAll('.pc-step').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.target;
                const next   = state[target] + parseInt(button.dataset.step, 10);
                if (next >= limits[target][0] && next <= limits[target][1]) {
                    state[target] = next;
                    render();
                }
            });
        });

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pc-card').forEach(initCard);
    });
})();
