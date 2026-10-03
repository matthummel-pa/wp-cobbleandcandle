{{-- Full Menu (HANDOFF §3 Menu page): menu tabs, sticky section links with scrollspy, dietary filters
     (AND logic, live count), per-location availability, chef's picks, allergen key, order card. --}}
@php
  $menus = \App\menus();
  $current = \App\current_location();
  $slugs = [];
  foreach (\App\locations() as $l) {
      $slugs[$l['id']] = $l['slug'];
  }
  $locs = fn (array $item): string => implode(' ', array_filter(array_map(fn ($id) => $slugs[(int) $id] ?? '', $item['locations'] ?? [])));
  $picks = (int) $attributes['picks'] > 0 ? \App\chef_picks((int) $attributes['picks']) : [];
  $pdf = $attributes['pdfUrl'] !== '' ? $attributes['pdfUrl'] : (isset($current['id']) ? (string) get_post_meta($current['id'], 'cobble_menu_pdf', true) : '');
  $diets = [
      'v' => [__('Vegetarian', 'cobbleandcandle'), 'leaf'],
      'vg' => [__('Vegan', 'cobbleandcandle'), 'leaf'],
      'gf' => [__('Gluten-free', 'cobbleandcandle'), 'wheat'],
      'spicy' => [__('Spicy', 'cobbleandcandle'), 'chili'],
  ];
  $hid = wp_unique_id('fm-');
@endphp
@if ($menus)
  <div {!! $wrapper !!}>
    <section class="section section--menu" x-data="menuFilter" aria-label="{{ __('Menus', 'cobbleandcandle') }}"
             data-hidden-one="{{ __('1 dish hidden across the menus', 'cobbleandcandle') }}"
             {{-- translators: %d: number of dishes --}}
             data-hidden-many="{{ __('%d dishes hidden across the menus', 'cobbleandcandle') }}">
      <div class="container">
        <div class="menu-tools">
          @if ($current)
            <div class="menu-where">
              <p class="eyebrow">{{ __('Showing the menu at', 'cobbleandcandle') }}</p>
              <p class="h4"><span x-text="$store.site.loc.name">{{ $current['name'] }}</span> <x-status :status="$current['status']" size="sm" bind="$store.site.loc.status" /></p>
              <button type="button" class="btn--link" @click="$dispatch('cobble-open-locations')">{{ __('Change location', 'cobbleandcandle') }}</button>
            </div>
          @endif
          <fieldset class="filters">
            <legend><x-icon name="filter" /> {{ __('Dietary filter', 'cobbleandcandle') }}</legend>
            <div class="fchips">
              @foreach ($diets as $value => [$label, $icon])
                <label class="fchip"><input type="checkbox" value="{{ $value }}" x-model="diets"><span><x-icon :name="$icon" />{{ $label }}</span></label>
              @endforeach
            </div>
            <p class="filter-count" role="status" aria-live="polite" x-text="summary"></p>
          </fieldset>
          @if ($pdf !== '')
            <x-button :href="$pdf" variant="text" icon="download" class="menu-pdf">{{ __('Printable PDF', 'cobbleandcandle') }}</x-button>
          @endif
        </div>

        <div x-data="tabs">
          <div class="tabs tabs--lg" role="tablist" aria-label="{{ __('Menus', 'cobbleandcandle') }}" @keydown="keys($event)">
            @foreach ($menus as $i => $m)
              <button type="button" role="tab" class="tab" id="{{ $hid }}-t{{ $i }}" aria-controls="{{ $hid }}-p{{ $i }}"
                      aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? 0 : -1 }}"
                      :aria-selected="(active === {{ $i }}).toString()" :tabindex="active === {{ $i }} ? 0 : -1" @click="select({{ $i }})">{{ $m['term']->name }}</button>
            @endforeach
          </div>

          @foreach ($menus as $i => $m)
            <div role="tabpanel" class="tabpanel menu-panel" id="{{ $hid }}-p{{ $i }}" aria-labelledby="{{ $hid }}-t{{ $i }}" @if ($i > 0) hidden @endif :hidden="active !== {{ $i }}">
              <nav class="catbar" aria-label="{{ sprintf(__('Sections in %s', 'cobbleandcandle'), $m['term']->name) }}" x-data="catbar">
                <ul>
                  @foreach ($m['sections'] as $c => $section)
                    <li><a href="#{{ $hid }}-{{ $i }}-{{ $c }}" @if ($c === 0) aria-current="true" @endif :aria-current="current === '{{ $hid }}-{{ $i }}-{{ $c }}' ? 'true' : null" @click="current = '{{ $hid }}-{{ $i }}-{{ $c }}'">{{ $section['term']->name }}</a></li>
                  @endforeach
                </ul>
              </nav>
              <div class="menu-body">
                @if ($m['intro'] !== '')
                  <p class="tab-intro tab-intro--lg">{{ $m['intro'] }}</p>
                @endif
                @if ($i === 0 && $picks)
                  <section class="mcat" aria-labelledby="{{ $hid }}-picks">
                    <h2 class="h3 mcat-h" id="{{ $hid }}-picks"><x-icon name="star" class="i i--fill i--accent" /> {{ __('Chef’s picks', 'cobbleandcandle') }}</h2>
                    <div class="grid-3 dish-grid dish-grid--menu">
                      @foreach ($picks as $k => $item)
                        <x-dish-card :item="$item" :art="\App\dish_art($k)" data-locs="{{ $locs($item) }}" />
                      @endforeach
                    </div>
                  </section>
                @endif
                @foreach ($m['sections'] as $c => $section)
                  <section class="mcat" id="{{ $hid }}-{{ $i }}-{{ $c }}" aria-labelledby="{{ $hid }}-{{ $i }}-{{ $c }}-h">
                    <h2 class="h3 mcat-h" id="{{ $hid }}-{{ $i }}-{{ $c }}-h">{{ $section['term']->name }}</h2>
                    <ul class="mlist mlist--2">
                      @foreach ($section['items'] as $item)
                        <x-menu-row :item="$item" heading="h3" data-locs="{{ $locs($item) }}" />
                      @endforeach
                    </ul>
                    <p class="mcat-empty" hidden>{{ __('No dishes in this section match your filters.', 'cobbleandcandle') }} <button type="button" class="btn--link" @click="clear()">{{ __('Clear filters', 'cobbleandcandle') }}</button></p>
                  </section>
                @endforeach
              </div>
            </div>
          @endforeach
          <noscript><style>.menu-panel[hidden]{display:block!important}</style></noscript>
        </div>

        <div class="menu-foot" id="allergens">
          <div class="legend">
            <h2 class="h4">{{ __('Key', 'cobbleandcandle') }}</h2>
            <ul>
              @foreach ($diets as $value => [$label])
                <li><x-diet-badges :diet="[$value]" /> {{ $label }}</li>
              @endforeach
            </ul>
          </div>
          @if ($attributes['allergenNote'] !== '')
            <p class="muted">{{ $attributes['allergenNote'] }}</p>
          @endif
        </div>

        @if ($attributes['orderTitle'] !== '')
          @php($order = $attributes['orderUrl'] !== '' ? $attributes['orderUrl'] : ($current['order_url'] ?? ''))
          <div class="order-card card" id="order" @if ($attributes['orderUrl'] === '') x-show="$store.site.loc.order_url" @endif @if ($order === '') hidden @endif>
            <div><x-icon name="bag" class="i i--lg i--accent" /></div>
            <div><h2 class="h3">{{ $attributes['orderTitle'] }}</h2><p>{{ $attributes['orderText'] }}</p></div>
            <a class="btn btn--primary" href="{!! esc_url($order) !!}" @if ($attributes['orderUrl'] === '') :href="$store.site.loc.order_url" @endif><span>{{ __('Start an order', 'cobbleandcandle') }}</span><x-icon name="arrow" /></a>
          </div>
        @endif
      </div>
    </section>
  </div>
@endif
