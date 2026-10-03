{{-- Plain footer while Cobble & Candle Core is not active. --}}
<footer class="ftr ftr--fallback">
  <div class="container" style="padding-block:32px">
    @if (has_nav_menu('footer_navigation'))
      <nav aria-label="{{ __('Footer', 'cobbleandcandle') }}">{!! \App\menu('footer_navigation', 'nav-fallback') !!}</nav>
    @endif
    <p class="muted">&copy; {{ wp_date('Y') }} {{ \App\site_name() }}</p>
  </div>
</footer>
