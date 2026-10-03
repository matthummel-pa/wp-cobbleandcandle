{{-- Demo bar: a slim strip above the header with the business-type and style switchers. Only when the Site Header block enables the demo switchers; never on a buyer's site. Hidden for the session with the close button. --}}
<div class="demobar" x-data="demoBar" :hidden="hidden" data-demobar>
  <div class="container demobar-in">
    <span class="demobar-brand"><x-icon name="palette" /><strong>{{ __('Cobble & Candle', 'cobbleandcandle') }}</strong> <span class="demobar-tag">{{ __('Theme demo', 'cobbleandcandle') }}</span></span>
    <div class="demobar-sw">
      @include('partials.kind-switcher')
      @include('partials.theme-switcher')
    </div>
    <div class="demobar-r">
      <a class="demobar-cta" href="{!! esc_url(apply_filters('cobbleandcandle/demo_link', 'https://matthummel.com/projects/cobbleandcandle/')) !!}">{{ __('Get this theme', 'cobbleandcandle') }}<x-icon name="arrow" /></a>
      <button type="button" class="demobar-x" @click="hide()"><x-icon name="close" /><span class="sr">{{ __('Hide the demo bar', 'cobbleandcandle') }}</span></button>
    </div>
  </div>
</div>
