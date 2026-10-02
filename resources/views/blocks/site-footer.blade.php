{{-- Site Footer block: brand, about line, social links, footer + legal menus (location columns arrive with the Locations CPT). --}}
@php
  $about = $attributes['about'] ?: get_bloginfo('description');
  // Profiles from Settings → Restaurant, with this block's own fields as a fallback.
  $profiles = function_exists('cc_social_profiles') ? cc_social_profiles() : [];
  $profiles += array_filter(['instagram' => $attributes['instagram'], 'facebook' => $attributes['facebook']]);
  if ($attributes['email'] !== '' && is_email($attributes['email'])) {
      $profiles['mail'] = 'mailto:'.sanitize_email($attributes['email']);
  }
  // Core Social Links block: an icon for every network (Tripadvisor has none, so it uses the link icon).
  $links = '';
  foreach ($profiles as $service => $url) {
      $links .= sprintf('<!-- wp:social-link %s /-->', wp_json_encode(array_filter([
          'url' => $url,
          'service' => $service === 'tripadvisor' ? 'chain' : $service,
          'label' => $service === 'tripadvisor' ? 'Tripadvisor' : null,
      ])));
  }
  $social = $links !== '' ? do_blocks('<!-- wp:social-links {"className":"is-style-logos-only"} --><ul class="wp-block-social-links is-style-logos-only">'.$links.'</ul><!-- /wp:social-links -->') : '';
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
          @if ($social !== '')
            <div class="social">{!! $social !!}</div>
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
