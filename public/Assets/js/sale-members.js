// Aba Dependentes do checkout: lista compacta + formulário em offcanvas.
(function () {

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var root = document.getElementById('members-root');
        if (!root) {
            return;
        }

        var business = root.dataset.business === '1';
        var kinships = JSON.parse(root.dataset.kinships || '{}');
        var employeeKeys = JSON.parse(root.dataset.employeeKeys || '[]');
        var oldMembers = JSON.parse(root.dataset.oldMembers || '[]');

        var listEl = document.getElementById('members-list');
        var emptyEl = document.getElementById('members-empty');
        var countEl = document.getElementById('members-count');
        var slotsEl = document.getElementById('members-slots');
        var addBtn = document.getElementById('add-member');
        var saveBtn = document.getElementById('member-save');
        var formName = document.getElementById('member-name');
        var formDoc = document.getElementById('member-document');
        var formKinship = document.getElementById('member-kinship');
        var form = document.getElementById('member-form');
        var offcanvasEl = document.getElementById('memberOffcanvas');
        var offcanvas = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;

        var members = [];
        var editIndex = null;

        function planApi() {
            return window.MaisMedPlanCard ? window.MaisMedPlanCard.first() : null;
        }

        function planState() {
            var api = planApi();
            return api ? api.getState() : { qty: 1, dep: 0, business: business, max: 99 };
        }

        function isEmployee(kinship) {
            return employeeKeys.indexOf(kinship) !== -1;
        }

        function slots() {
            var state = planState();
            if (business) {
                return {
                    employees: state.qty,
                    dependents: state.dep,
                    total: state.qty + state.dep,
                };
            }
            // Familiar: o titular ocupa 1 vaga; as demais são para dependentes.
            return {
                employees: 0,
                dependents: Math.max(0, state.qty - 1),
                total: Math.max(0, state.qty - 1),
            };
        }

        function counts() {
            var employees = 0;
            var dependents = 0;
            members.forEach(function (member) {
                if (business && isEmployee(member.kinship)) {
                    employees++;
                } else {
                    dependents++;
                }
            });
            return { employees: employees, dependents: dependents, total: members.length };
        }

        function kinshipLabel(key) {
            return kinships[key] || key || '—';
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function render() {
            var used = counts();
            var available = slots();

            countEl.textContent = String(used.total);
            if (business) {
                slotsEl.textContent = used.employees + '/' + available.employees + ' vínculos de empresa · '
                    + used.dependents + '/' + available.dependents + ' dependentes';
            } else {
                slotsEl.textContent = used.total + '/' + available.total + ' vagas do plano (além do titular)';
            }

            listEl.innerHTML = '';
            emptyEl.classList.toggle('d-none', members.length > 0);

            members.forEach(function (member, index) {
                var item = document.createElement('div');
                item.className = 'member-item';
                item.innerHTML =
                    '<div class="member-item-main">' +
                        '<strong>' + escapeHtml(member.name) + '</strong>' +
                        '<small>' + escapeHtml(member.document) + ' · ' + escapeHtml(kinshipLabel(member.kinship)) + '</small>' +
                        '<input type="hidden" name="members[' + index + '][name]" value="' + escapeHtml(member.name) + '">' +
                        '<input type="hidden" name="members[' + index + '][document]" value="' + escapeHtml(member.document) + '">' +
                        '<input type="hidden" name="members[' + index + '][kinship]" value="' + escapeHtml(member.kinship) + '">' +
                    '</div>' +
                    '<div class="member-item-actions">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary" data-edit="' + index + '">Editar</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + index + '">Remover</button>' +
                    '</div>';
                listEl.appendChild(item);
            });
        }

        function resetForm() {
            form.reset();
            formName.value = '';
            formDoc.value = '';
            formKinship.value = '';
            editIndex = null;
            document.getElementById('member-offcanvas-title').textContent = 'Adicionar pessoa';
        }

        function openForm(member, index) {
            resetForm();
            if (member) {
                editIndex = index;
                formName.value = member.name;
                formDoc.value = member.document;
                formKinship.value = member.kinship;
                document.getElementById('member-offcanvas-title').textContent = 'Editar pessoa';
                if (typeof maskCpf === 'function') {
                    maskCpf(formDoc);
                }
            }
            if (offcanvas) {
                offcanvas.show();
            }
        }

        function needsExtraSlot(kinship) {
            if (editIndex !== null) {
                return false;
            }
            var used = counts();
            var available = slots();
            if (business) {
                if (isEmployee(kinship)) {
                    return used.employees >= available.employees;
                }
                return used.dependents >= available.dependents;
            }
            return used.total >= available.total;
        }

        function bumpFor(kinship) {
            var api = planApi();
            if (!api) {
                return false;
            }
            var before = planState();
            if (business && isEmployee(kinship)) {
                if (before.qty >= before.max) {
                    return false;
                }
                api.bumpQty();
                return true;
            }
            if (business) {
                if (before.dep >= before.max) {
                    return false;
                }
                api.bumpDep();
                return true;
            }
            if (before.qty >= before.max) {
                return false;
            }
            api.bumpQty();
            return true;
        }

        function askToExpand(kinship, onYes) {
            var state = planState();
            var message = business
                ? (isEmployee(kinship)
                    ? 'As vagas de funcionários do plano já estão preenchidas. Deseja incluir mais um e recalcular o valor?'
                    : 'As vagas de dependentes do plano já estão preenchidas. Deseja incluir mais um e recalcular o valor?')
                : 'Você já preencheu as ' + Math.max(0, state.qty - 1) + ' vagas de dependentes deste plano. Deseja incluir mais uma pessoa? O valor será recalculado.';

            Swal.fire({
                title: 'Incluir mais pessoas?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, incluir',
                cancelButtonText: 'Não',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                if (!bumpFor(kinship)) {
                    Swal.fire({
                        title: 'Limite do plano',
                        text: 'Não é possível incluir mais pessoas neste plano.',
                        icon: 'warning',
                    });
                    return;
                }
                onYes();
            });
        }

        function tryOpenAdd() {
            var used = counts();
            var available = slots();
            var full = business
                ? (used.employees >= available.employees && used.dependents >= available.dependents)
                : used.total >= available.total;

            if (!full) {
                openForm();
                return;
            }

            askToExpand(business ? 'empregado' : 'filho', function () {
                openForm();
            });
        }

        function saveMember() {
            var name = (formName.value || '').trim();
            var documentValue = (formDoc.value || '').trim();
            var kinship = formKinship.value || '';

            if (!name || !documentValue || !kinship) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Preencha nome, CPF e vínculo.',
                    icon: 'warning',
                });
                return;
            }

            var digits = documentValue.replace(/\D/g, '');
            if (digits.length !== 11 || (typeof isValidCpf === 'function' && !isValidCpf(digits))) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Informe um CPF válido.',
                    icon: 'warning',
                });
                formDoc.focus();
                return;
            }

            var duplicate = members.some(function (member, index) {
                return index !== editIndex && member.document.replace(/\D/g, '') === digits;
            });
            if (duplicate) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Este CPF já foi adicionado.',
                    icon: 'warning',
                });
                return;
            }

            var payload = { name: name, document: documentValue, kinship: kinship };

            function commit() {
                if (editIndex !== null) {
                    members[editIndex] = payload;
                } else {
                    members.push(payload);
                }
                render();
                if (offcanvas) {
                    offcanvas.hide();
                }
                resetForm();
            }

            if (needsExtraSlot(kinship)) {
                askToExpand(kinship, commit);
                return;
            }

            commit();
        }

        addBtn.addEventListener('click', tryOpenAdd);
        saveBtn.addEventListener('click', saveMember);

        listEl.addEventListener('click', function (event) {
            var edit = event.target.getAttribute('data-edit');
            var remove = event.target.getAttribute('data-remove');
            if (edit !== null && edit !== undefined && edit !== '') {
                openForm(members[parseInt(edit, 10)], parseInt(edit, 10));
            }
            if (remove !== null && remove !== undefined && remove !== '') {
                members.splice(parseInt(remove, 10), 1);
                render();
            }
        });

        document.querySelectorAll('.pc-card').forEach(function (card) {
            card.addEventListener('plan-card:change', render);
        });

        oldMembers.forEach(function (member) {
            if (member && (member.name || member.document)) {
                members.push({
                    name: member.name || '',
                    document: member.document || '',
                    kinship: member.kinship || '',
                });
            }
        });

        render();
    });
})();
