{{-- Plain 404 message while Cobble & Candle Core is not active. --}}
<div class="container" style="padding-block:64px">
  <h1 class="h1">{{ __('Page not found', 'cobbleandcandle') }}</h1>
  <p><a href="{!! esc_url(home_url('/')) !!}">{{ __('Back to the home page', 'cobbleandcandle') }}</a></p>
</div>
