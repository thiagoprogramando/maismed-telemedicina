{{-- Card de plano com simulador. Parâmetros: $plan, $parent (opcional), $cta (bool), $qty e $dep (opcionais), $form (id do formulário que recebe a quantidade escolhida, opcional) --}}
@php
    $isBusiness = $plan->isBusiness();
    $priceCents = (int) round($plan->price * 100);
    $extraCents = (int) round($plan->extra_price * 100);
    $included   = $plan->includedUsers();
    $max        = $plan->simulatorMax();
    $hasStepper = $plan->hasSimulator();
    $quote      = $plan->quote($qty ?? null, $dep ?? null);
    $qty        = $quote['quantity'] ?? 1;
    $dep        = $quote['dependents'];
    $cta        = $cta ?? true;
    $form       = $form ?? null;
    $parent     = $parent ?? null;
    $money      = fn ($value) => 'R$ ' . number_format($value, 2, ',', '.');
    $features   = $plan->featuresList();
    $phone      = $parent ? preg_replace('/\D/', '', (string) $parent->phone) : '';
@endphp

<div class="pc-card {{ $isBusiness ? 'pc-business' : 'pc-family' }}"
    data-name="{{ $plan->name }}"
    data-type="{{ $isBusiness ? 'business' : 'family' }}"
    data-price="{{ $priceCents }}"
    data-extra="{{ $extraCents }}"
    data-included="{{ $included }}"
    data-max="{{ $max }}"
    data-stepper="{{ $hasStepper ? 1 : 0 }}"
    data-qty="{{ $qty }}"
    data-dep="{{ $dep }}"
    data-suffix="{{ $plan->periodSuffix() }}">

    @if ($form && $hasStepper)
        <input type="hidden" name="quantity" form="{{ $form }}" data-in="qty" value="{{ $qty }}">
        <input type="hidden" name="dependents" form="{{ $form }}" data-in="dep" value="{{ $dep }}">
    @endif

    <div class="pc-head">
        <span class="pc-badge">{{ $plan->badge ?: ($isBusiness ? 'Para empresas · CNPJ' : 'Para famílias') }}</span>
        <span class="pc-icon"><i data-lucide="{{ $isBusiness ? 'building-2' : 'users' }}"></i></span>
    </div>

    <h3 class="pc-name">{{ $plan->name }}</h3>

    @if ($isBusiness)
        <p class="pc-price"><strong>{{ $money($plan->price) }}</strong> por vida</p>
        @if ($extraCents > 0)
            <p class="pc-sub">Funcionários + familiares {{ $money($plan->extra_price) }}</p>
        @endif
    @elseif ($extraCents > 0)
        <p class="pc-price"><strong>{{ $money($plan->price) }}</strong> até {{ $included }} {{ $included > 1 ? 'pessoas' : 'pessoa' }}</p>
        <p class="pc-sub">+ {{ $money($plan->extra_price) }} por pessoa extra</p>
    @else
        <p class="pc-price"><strong>{{ $money($plan->price) }}</strong>{{ $plan->periodSuffix() }}</p>
        @if ($plan->max_users)
            <p class="pc-sub">Até {{ $plan->max_users }} {{ $plan->max_users > 1 ? 'pessoas' : 'pessoa' }}</p>
        @endif
    @endif

    @if ($plan->description)
        <p class="pc-description">{{ $plan->description }}</p>
    @endif

    @if (count($features))
        <ul class="pc-features">
            @foreach ($features as $feature)
                <li><i data-lucide="check"></i> <span>{{ $feature }}</span></li>
            @endforeach
        </ul>
    @endif

    @if ($hasStepper)
        <div class="pc-field">
            <div class="pc-field-label">
                <span>{{ $isBusiness ? 'Funcionários' : 'Quantidade de pessoas' }}</span>
                @if ($isBusiness) <small>{{ $money($plan->price) }} cada</small> @endif
            </div>
            <div class="pc-stepper">
                <button type="button" class="pc-step" data-target="qty" data-step="-1" aria-label="Diminuir"><i data-lucide="minus"></i></button>
                <div class="pc-count"><strong data-out="qty">{{ $qty }}</strong> <span data-out="qty-label">{{ $isBusiness ? 'vidas' : 'pessoas' }}</span></div>
                <button type="button" class="pc-step" data-target="qty" data-step="1" aria-label="Aumentar"><i data-lucide="plus"></i></button>
            </div>
            @unless ($isBusiness)
                <div class="pc-range"><span>1</span><span>{{ $max }}</span></div>
            @endunless
        </div>
    @endif

    @if ($isBusiness && $extraCents > 0)
        <div class="pc-field">
            <div class="pc-field-label">
                <span>Familiares dependentes</span>
                <small>{{ $money($plan->extra_price) }} cada</small>
            </div>
            <div class="pc-stepper">
                <button type="button" class="pc-step" data-target="dep" data-step="-1" aria-label="Diminuir"><i data-lucide="minus"></i></button>
                <div class="pc-count"><strong data-out="dep">{{ $dep }}</strong> <span data-out="dep-label">vidas</span></div>
                <button type="button" class="pc-step" data-target="dep" data-step="1" aria-label="Aumentar"><i data-lucide="plus"></i></button>
            </div>
        </div>
    @endif

    <div class="pc-total">
        <div class="pc-total-label">Total {{ mb_strtolower($plan->timeLabel()) }}</div>
        <div class="pc-total-value"><strong data-out="total">{{ $money($quote['price']) }}</strong> <span>{{ $plan->periodSuffix() }}</span></div>
        <div class="pc-rows" data-out="rows"></div>
    </div>

    @if ($cta)
        <div class="pc-actions">
            <a class="pc-cta" data-cta href="{{ route('create-sale', ['plan' => $plan->slug, 'parent' => $parent?->uuid]) }}">
                <i data-lucide="arrow-up-right"></i> Contratar agora
            </a>
            @if ($phone)
                <a class="pc-wa" data-wa="55{{ $phone }}" href="https://wa.me/55{{ $phone }}" target="_blank" rel="noopener">
                    <i data-lucide="message-circle"></i> Falar com o consultor no WhatsApp
                </a>
            @endif
        </div>
    @endif
</div>
