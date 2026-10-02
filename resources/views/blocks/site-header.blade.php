{{-- Site Header block: utility bar, split nav with centred crest, Order/Reserve, mobile drawer (HANDOFF §3). --}}
@php
  $reserveUrl = $attributes['reserveUrl'] ?: '';
  $orderUrl = $attributes['orderUrl'] ?: '';
  $locations = \App\locations();
  $current = \App\current_location();
  $phone = $current['phone'] ?? \App\brand('phone');
  $orderUrl = $orderUrl ?: ($current['order_url'] ?? '');
  $showUtility = $attributes['showUtilityBar'] && ($locations || $phone !== '' || $attributes['showStyleSwitcher']);
@endphp
<div {!! $wrapper !!} x-data="siteHeader">
  @if ($locations)
    <script type="application/json" id="cc-locations">{!! \App\locations_json() !!}</script>
    <script type="application/json" id="cc-status">{!! \App\status_json() !!}</script>
  @endif
  @if ($attributes['showStyleSwitcher'])
    {{-- Demo only: re-apply a visitor's chosen direction (or ?theme=) before the page paints. --}}
    {!! wp_get_inline_script_tag("(function(d){try{var t=new URLSearchParams(location.search).get('theme')||localStorage.getItem('rm-theme');if(/^(lampwright|ember-arch|ashlar-iron)$/.test(t)){d.documentElement.dataset.theme=t;localStorage.setItem('rm-theme',t)}}catch(e){}})(document);") !!}
  @endif
  @if ($showUtility)
    <div class="util" role="region" aria-label="{{ __('Contact and style', 'cobbleandcandle') }}">
      <div class="container util-in">
        <div class="util-l">
          @if (count($locations) > 1)
            @include('partials.location-switcher', ['locations' => $locations, 'current' => $current])
          @endif
          @if ($current)
            <span class="util-status"><x-status :status="$current['status']" bind="$store.site.loc.status" /></span>
          @endif
          @if ($phone !== '')
            <a class="util-phone" href="{!! esc_url('tel:'.preg_replace('/[^0-9+]/', '', $phone)) !!}" @if ($current) :href="$store.site.loc.tel" @endif><x-icon name="phone" /><span @if ($current) x-text="$store.site.loc.phone" @endif>{{ $phone }}</span></a>
          @endif
        </div>
        <div class="util-r">
          @if ($attributes['showStyleSwitcher'])
            @include('partials.theme-switcher')
          @endif
        </div>
      </div>
    </div>
  @endif

  <header class="hdr" data-hdr>
    <div class="container hdr-in">
      <nav class="nav nav--l" aria-label="{{ __('Primary', 'cobbleandcandle') }}">{!! \App\menu('primary_navigation') !!}</nav>
      @include('partials.brand')
      <div class="hdr-r">
        <nav class="nav nav--r" aria-label="{{ __('Secondary', 'cobbleandcandle') }}">{!! \App\menu('secondary_navigation') !!}</nav>
        @if ($orderUrl !== '')
          <x-button :href="$orderUrl" variant="secondary" size="sm" class="hdr-order">{{ $attributes['orderLabel'] }}</x-button>
        @endif
        @if ($reserveUrl !== '')
          <x-button :href="$reserveUrl" size="sm" class="hdr-reserve">{{ $attributes['reserveLabel'] }}</x-button>
        @endif
        <button type="button" class="hdr-burger" aria-controls="drawer" aria-expanded="false" :aria-expanded="open.toString()" x-ref="burger" @click="openDrawer()">
          <x-icon name="menu" /><span class="sr">{{ __('Open menu', 'cobbleandcandle') }}</span>
        </button>
      </div>
    </div>
  </header>

  <div class="drawer" id="drawer" hidden :hidden="!open" x-ref="drawer" @keydown.escape.window="closeDrawer()" @keydown="trap($event)">
    <div class="drawer-in" role="dialog" aria-modal="true" aria-label="{{ __('Site menu', 'cobbleandcandle') }}">
      <div class="drawer-top">
        @include('partials.brand')
        <button type="button" class="icon-btn" @click="closeDrawer()"><x-icon name="close" /><span class="sr">{{ __('Close menu', 'cobbleandcandle') }}</span></button>
      </div>
      <nav aria-label="{{ __('Mobile', 'cobbleandcandle') }}">{!! \App\menu('primary_navigation', 'drawer-nav') !!}{!! \App\menu('secondary_navigation', 'drawer-nav') !!}</nav>
      <div class="drawer-cta">
        @if ($reserveUrl !== '')
          <x-button :href="$reserveUrl" icon="calendar">{{ $attributes['reserveLabel'] }}</x-button>
        @endif
        @if ($orderUrl !== '')
          <x-button :href="$orderUrl" variant="secondary" icon="bag">{{ $attributes['orderLabel'] }}</x-button>
        @endif
      </div>
      @if ($attributes['showStyleSwitcher'])
        <div class="drawer-theme"><p class="eyebrow">{{ __('Demo style', 'cobbleandcandle') }}</p>@include('partials.theme-switcher')</div>
      @endif
    </div>
  </div>
</div>
