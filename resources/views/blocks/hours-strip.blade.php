{{-- Hours Strip: tonight / find us / call to book / all houses, for the current location. --}}
@php
  $current = \App\current_location();
  $locations = \App\locations();
@endphp
@if ($current)
  <section {!! $wrapper !!} aria-label="{{ __('Hours and location', 'cobbleandcandle') }}">
    <div class="strip">
      <div class="container strip-in">
        <div class="strip-c">
          <p class="eyebrow">{{ __('Tonight', 'cobbleandcandle') }}</p>
          <x-status :status="$current['status']" bind="$store.site.loc.status" />
          @if ($attributes['tonightNote'] !== '')
            <p class="muted">{{ $attributes['tonightNote'] }}</p>
          @endif
        </div>
        <div class="strip-c">
          <p class="eyebrow">{{ __('Find us', 'cobbleandcandle') }}</p>
          <p>{{ $current['address'] }}</p>
          @if ($current['map_url'] !== '')
            <a class="link-arrow" href="{{ $current['map_url'] }}" :href="$store.site.loc.map_url">{{ __('Directions', 'cobbleandcandle') }}<x-icon name="arrow" /></a>
          @endif
        </div>
        <div class="strip-c">
          <p class="eyebrow">{{ __('Call to book', 'cobbleandcandle') }}</p>
          @if ($current['phone'] !== '')
            <p><a class="strip-phone" href="{{ $current['tel'] }}" :href="$store.site.loc.tel" x-text="$store.site.loc.phone">{{ $current['phone'] }}</a></p>
          @endif
          <p class="muted">{{ sprintf(__('Today %s', 'cobbleandcandle'), $current['today']) }}</p>
        </div>
        @if (count($locations) > 1)
          <div class="strip-c">
            <p class="eyebrow">{{ sprintf(_n('%s house', '%s houses', count($locations), 'cobbleandcandle'), number_format_i18n(count($locations))) }}</p>
            <p>{{ wp_sprintf('%l', array_column($locations, 'name')) }}</p>
            @if ($attributes['locationsUrl'] !== '')
              <a class="link-arrow" href="{{ $attributes['locationsUrl'] }}">{{ __('All locations', 'cobbleandcandle') }}<x-icon name="arrow" /></a>
            @endif
          </div>
        @endif
      </div>
    </div>
  </section>
@endif
