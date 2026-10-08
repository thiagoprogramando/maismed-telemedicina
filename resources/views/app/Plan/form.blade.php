{{-- Campos do cadastro de plano. Parâmetro: $plan (null no cadastro) --}}
@php
    $plan = $plan ?? null;
    $type = $plan->type ?? 'family';
@endphp

<div class="plan-form" data-type="{{ $type }}">

    <div class="plan-section">
        <div class="plan-section-title"><i data-lucide="tag"></i> Identificação</div>
        <div class="row">
            <div class="col-12 col-md-8 mb-2">
                <div class="form-floating">
                    <input type="text" class="form-control" name="name" id="plan_name" placeholder="Nome do plano" value="{{ $plan->name ?? '' }}" required>
                    <label for="plan_name">Nome do plano</label>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-2">
                <div class="form-floating">
                    <select class="form-select" name="status" id="plan_status" required>
                        <option value="active" @selected(($plan->status ?? 'active') == 'active')>Ativo</option>
                        <option value="inactive" @selected(($plan->status ?? '') == 'inactive')>Inativo</option>
                    </select>
                    <label for="plan_status">Status</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="plan-types">
                    <label class="plan-type">
                        <input type="radio" name="type" value="family" @checked($type == 'family')>
                        <span>
                            <i data-lucide="users"></i>
                            <strong>Familiar</strong>
                            <small>Preço base para um grupo, com valor por pessoa extra.</small>
                        </span>
                    </label>
                    <label class="plan-type">
                        <input type="radio" name="type" value="business" @checked($type == 'business')>
                        <span>
                            <i data-lucide="building-2"></i>
                            <strong>Empresarial</strong>
                            <small>Valor por funcionário, com valor por dependente.</small>
                        </span>
                    </label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-floating">
                    <input type="text" class="form-control" name="badge" id="plan_badge" placeholder="Selo de destaque" maxlength="60" value="{{ $plan->badge ?? '' }}">
                    <label for="plan_badge">Selo de destaque (opcional)</label>
                </div>
                <small class="text-muted">Texto curto exibido no topo do card, por exemplo "Mais escolhido".</small>
            </div>
        </div>
    </div>

    <div class="plan-section">
        <div class="plan-section-title"><i data-lucide="circle-dollar-sign"></i> Preço</div>
        <div class="row">
            <div class="col-12 col-md-6 mb-2">
                <div class="form-floating">
                    <input type="text" class="form-control money" name="price" id="plan_price" oninput="maskValue(this)" placeholder="Preço" value="{{ $plan ? number_format($plan->price, 2, ',', '.') : '' }}">
                    <label for="plan_price"><span class="only-family">Preço base (R$)</span><span class="only-business">Valor por funcionário (R$)</span></label>
                </div>
                <small class="text-muted only-family">Valor cobrado pelo grupo incluído.</small>
                <small class="text-muted only-business">Valor cobrado por cada funcionário.</small>
            </div>
            <div class="col-12 col-md-6 mb-2 only-family">
                <div class="form-floating">
                    <input type="number" class="form-control" name="included_users" id="plan_included_users" min="1" placeholder="Pessoas incluídas" value="{{ $plan->included_users ?? '' }}">
                    <label for="plan_included_users">Pessoas incluídas no preço base</label>
                </div>
                <small class="text-muted">Até quantas pessoas pagam só o preço base.</small>
            </div>
            <div class="col-12 col-md-6 mb-2">
                <div class="form-floating">
                    <input type="text" class="form-control money" name="extra_price" id="plan_extra_price" oninput="maskValue(this)" placeholder="Valor extra" value="{{ $plan ? number_format($plan->extra_price, 2, ',', '.') : '' }}">
                    <label for="plan_extra_price"><span class="only-family">Valor por pessoa extra (R$)</span><span class="only-business">Valor por dependente (R$)</span></label>
                </div>
                <small class="text-muted only-family">Somado para cada pessoa além das incluídas. Deixe 0,00 para preço fixo.</small>
                <small class="text-muted only-business">Somado para cada familiar dependente. Deixe 0,00 para não oferecer dependentes.</small>
            </div>
            <div class="col-12 col-md-6 mb-2">
                <div class="form-floating">
                    <input type="number" class="form-control" name="max_users" id="plan_max_users" min="1" placeholder="Limite de pessoas" value="{{ $plan->max_users ?? '' }}">
                    <label for="plan_max_users">Limite de pessoas</label>
                </div>
                <small class="text-muted">Máximo que o cliente pode escolher no simulador.</small>
            </div>
            <div class="col-12 col-md-6 mb-2">
                <div class="form-floating">
                    <select class="form-select" name="time" id="plan_time" required>
                        <option value="month" @selected(($plan->time ?? 'month') == 'month')>Mensal</option>
                        <option value="semi-annual" @selected(($plan->time ?? '') == 'semi-annual')>Semestral</option>
                        <option value="year" @selected(($plan->time ?? '') == 'year')>Anual</option>
                        <option value="lifetime" @selected(($plan->time ?? '') == 'lifetime')>Vitalício</option>
                    </select>
                    <label for="plan_time">Cobrança</label>
                </div>
            </div>
            <div class="col-12 col-md-6 mb-2">
                <div class="form-floating">
                    <input type="text" class="form-control money" name="commission" id="plan_commission" oninput="maskValue(this)" placeholder="Comissão" value="{{ $plan ? number_format($plan->commission, 2, ',', '.') : '' }}">
                    <label for="plan_commission">Comissão do consultor (R$)</label>
                </div>
                <small class="text-muted">Valor por pessoa contratada, creditado ao consultor a cada parcela paga.</small>
            </div>
        </div>
    </div>

    <div class="plan-section">
        <div class="plan-section-title"><i data-lucide="file-text"></i> Conteúdo</div>
        <div class="row">
            <div class="col-12 mb-2">
                <div class="form-floating">
                    <textarea class="form-control" name="description" id="plan_description" placeholder="Descrição" maxlength="255" style="height: 80px">{{ $plan->description ?? '' }}</textarea>
                    <label for="plan_description">Descrição curta</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-floating">
                    <textarea class="form-control" name="features" id="plan_features" placeholder="Benefícios" style="height: 110px">{{ $plan->features ?? '' }}</textarea>
                    <label for="plan_features">Benefícios (um por linha)</label>
                </div>
                <small class="text-muted">Cada linha vira um item da lista no card.</small>
            </div>
            <div class="col-12 mb-2">
                <small class="text-muted d-block mb-1">Termo & Contrato</small>
                <div class="full-editor" style="height: 140px;">{!! $plan->terms ?? '' !!}</div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .plan-section { margin-bottom: 18px; }
        .plan-section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #4e73df; }
        .plan-section-title svg { width: 16px; height: 16px; }
        .plan-types { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .plan-type { position: relative; margin: 0; cursor: pointer; }
        .plan-type input { position: absolute; opacity: 0; }
        .plan-type > span { display: block; height: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 10px; }
        .plan-type svg { display: block; width: 20px; height: 20px; margin-bottom: 4px; color: #858796; }
        .plan-type small { display: block; color: #858796; }
        .plan-type input:checked + span { border-color: #4e73df; background: #f1f4fd; box-shadow: 0 0 0 1px #4e73df; }
        .plan-type input:checked + span svg { color: #4e73df; }
        .plan-type input:focus-visible + span { outline: 2px solid #4e73df; outline-offset: 2px; }
        .plan-form[data-type="family"] .only-business,
        .plan-form[data-type="business"] .only-family { display: none; }
    </style>
    <script>
        document.addEventListener('change', function (event) {
            if (event.target.matches('.plan-form input[name="type"]')) {
                event.target.closest('.plan-form').dataset.type = event.target.value;
            }
        });
    </script>
@endonce
