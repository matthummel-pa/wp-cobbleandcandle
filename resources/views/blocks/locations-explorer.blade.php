{{-- Locations Explorer (HANDOFF §3 Locations): house buttons switch the panel and the site-wide location. --}}
@php
  $locations = \App\locations();
  $current = \App\current_location();
@endphp
@if ($locations)
  <div {!! $wrapper !!}>
    <section class="section" x-data>
      <div class="container">
        @if (count($locations) > 1)
          <div class="ltabs" role="group" aria-label="{{ __('Choose a location', 'cobbleandcandle') }}">
            @foreach ($locations as $i => $l)
              <button type="button" class="ltab" aria-pressed="{{ $l['id'] === $current['id'] ? 'true' : 'false' }}"
                      :aria-pressed="($store.site.current === {{ $i }}).toString()" @click="$store.site.setLocation({{ $i }})">
                <span class="ltab-n">{{ $i + 1 }}</span>
                <span><b>{{ $l['name'] }}</b><x-status :status="$l['status']" :of="$l['slug']" size="sm" /></span>
              </button>
            @endforeach
          </div>
        @endif
        @foreach ($locations as $i => $l)
          <div class="lpanel" @if ($l['id'] !== $current['id']) hidden @endif :hidden="$store.site.current !== {{ $i }}">
            @include('partials.location-panel', ['l' => $l, 'pin' => $i, 'reserveUrl' => $attributes['reserveUrl'], 'reserveLabel' => $attributes['reserveLabel'], 'detailsUrl' => $l['url']])
            
          </div>
        @endforeach
      </div>
    </section>
  </div>
@endif
