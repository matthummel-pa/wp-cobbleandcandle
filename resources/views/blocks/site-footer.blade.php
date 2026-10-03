{{-- Site Footer block: brand, about line, social links, footer + legal menus (location columns arrive with the Locations CPT). --}}
@php
  $about = $attributes['about'] ?: get_bloginfo('description');
  // Profiles from Settings → Restaurant, with this block's own fields (sanitized) as a fallback.
  $profiles = function_exists('cobble_social_profiles') ? cobble_social_profiles() : [];
  $profiles += array_filter(['instagram' => esc_url_raw($attributes['instagram']), 'facebook' => esc_url_raw($attributes['facebook'])]);
  if ($attributes['email'] !== '' && is_email($attributes['email'])) {
      $profiles['mail'] = 'mailto:'.sanitize_email($attributes['email']);
  }
  // Core Social Links block, built as block arrays (no markup parsing). Tripadvisor has no core icon.
  $links = [];
  foreach ($profiles as $service => $url) {
      $links[] = ['blockName' => 'core/social-link', 'attrs' => array_filter([
          'url' => $url,
          'service' => $service === 'tripadvisor' ? 'chain' : $service,
          'label' => $service === 'tripadvisor' ? 'Tripadvisor' : null,
      ]), 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []];
  }
  $footerSocialLinks = $links ? render_block([
      'blockName' => 'core/social-links',
      'attrs' => ['className' => 'is-style-logos-only'],
      'innerBlocks' => $links,
      'innerHTML' => '<ul class="wp-block-social-links is-style-logos-only"></ul>',
      'innerContent' => array_merge(['<ul class="wp-block-social-links is-style-logos-only">'], array_fill(0, count($links), null), ['</ul>']),
  ]) : '';
@endphp
<footer {!! $wrapper !!}>
  <div class="ftr">
    <div class="container">
      <div class="f-grid">
        <div class="f-brand">
          @include('partials.brand')
          @if ($about)
            <p class="muted">{{ $about }}</p>
          @endif
          @if ($footerSocialLinks !== '')
            <div class="social">{!! $footerSocialLinks !!}</div>
          @endif
        </div>
        @if (\App\locations())
          <h2 class="sr">{{ __('Our locations', 'cobbleandcandle') }}</h2>
        @endif
        @foreach (\App\locations() as $l)
          <div class="f-loc">
            <h3 class="h4"><a href="{!! esc_url($l['url']) !!}">{{ $l['name'] }}</a></h3>
            <x-status :status="$l['status']" :of="$l['slug']" size="sm" />
            @if ($l['street'] !== '')
              <p><a href="{!! esc_url($l['map_url']) !!}">{{ $l['street'] }}@if ($l['locality'] !== '')<br>{{ $l['locality'] }}@endif</a></p>
            @endif
            @if ($l['phone'] !== '')
              <p><a href="{!! esc_url($l['tel']) !!}">{{ $l['phone'] }}</a></p>
            @endif
            <x-hours :rows="$l['hours']" />
          </div>
        @endforeach
        @if (has_nav_menu('footer_navigation'))
          <nav class="f-nav" aria-label="{{ __('Footer', 'cobbleandcandle') }}">{!! \App\menu('footer_navigation') !!}</nav>
        @endif
      </div>
      <div class="f-bottom">
        <p>&copy; {{ wp_date('Y') }} {{ \App\site_name() }}</p>
        @if (has_nav_menu('legal_navigation'))
          <nav aria-label="{{ __('Legal', 'cobbleandcandle') }}">{!! \App\menu('legal_navigation') !!}</nav>
        @endif
      </div>
    </div>
  </div>
</footer>
