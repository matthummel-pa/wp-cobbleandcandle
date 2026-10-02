{{-- Menu Teaser: ARIA tabs with the first dishes of each menu + a specials board (HANDOFF §3, §8). --}}
@php
  $menus = \App\menus();
  $hid = wp_unique_id('menu-');
  $board = [];
  foreach ($menus as $m) {
      if ($m['term']->slug === $attributes['boardMenu']) {
          $board = \App\menu_preview($m, 3);
      }
  }
@endphp
@if ($menus)
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section section--alt texture">
      <div class="container menu-teaser">
        <div class="mt-main" x-data="tabs">
          <header class="sh"><div class="sh-text">
            <p class="eyebrow">{{ $attributes['eyebrow'] }}</p>
            <h2 class="h2" id="{{ $hid }}">{{ $attributes['title'] }}</h2>
            @if ($attributes['intro'] !== '')
              <p class="sh-intro">{{ $attributes['intro'] }}</p>
            @endif
          </div></header>
          <div class="tabs" role="tablist" aria-label="{{ __('Menus', 'cobbleandcandle') }}" @keydown="keys($event)">
            @foreach ($menus as $i => $m)
              <button type="button" role="tab" class="tab" id="{{ $hid }}-t{{ $i }}" aria-controls="{{ $hid }}-p{{ $i }}"
                      aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? 0 : -1 }}"
                      :aria-selected="(active === {{ $i }}).toString()" :tabindex="active === {{ $i }} ? 0 : -1" @click="select({{ $i }})">{{ $m['term']->name }}</button>
            @endforeach
          </div>
          @foreach ($menus as $i => $m)
            <div role="tabpanel" class="tabpanel" id="{{ $hid }}-p{{ $i }}" aria-labelledby="{{ $hid }}-t{{ $i }}" tabindex="0" @if ($i > 0) hidden @endif :hidden="active !== {{ $i }}">
              @if ($m['intro'] !== '')
                <p class="tab-intro">{{ $m['intro'] }}</p>
              @endif
              <ul class="mlist">
                @foreach (\App\menu_preview($m, (int) $attributes['rows']) as $item)
                  <x-menu-row :item="$item" />
                @endforeach
              </ul>
            </div>
          @endforeach
          <noscript><style>.tabpanel[hidden]{display:block!important}</style></noscript>
          <div class="cta-row">
            @if ($attributes['menuUrl'] !== '')
              <x-button :href="$attributes['menuUrl']">{{ __('See the full menu', 'cobbleandcandle') }}</x-button>
            @endif
            @if ($attributes['pdfUrl'] !== '')
              <x-button :href="$attributes['pdfUrl']" variant="text" icon="download">{{ __('Printable PDF', 'cobbleandcandle') }}</x-button>
            @endif
          </div>
        </div>
        @includeWhen($board, 'partials.board', ['items' => $board, 'eyebrow' => $attributes['boardEyebrow'], 'title' => $attributes['boardTitle'], 'foot' => $attributes['boardFoot']])
      </div>
    </div>
  </section>
@endif
