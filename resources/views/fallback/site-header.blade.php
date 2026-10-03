{{-- Plain header while Cobble & Candle Core is not active (the full header is a plugin block). --}}
<header class="hdr hdr--fallback">
  <div class="container" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;padding-block:20px">
    <a class="brand-n" href="{{ home_url('/') }}" rel="home">{{ get_bloginfo('name') }}</a>
    @if (has_nav_menu('primary_navigation'))
      <nav aria-label="{{ __('Main', 'cobbleandcandle') }}">{!! \App\menu('primary_navigation', 'nav-fallback') !!}</nav>
    @endif
  </div>
</header>
