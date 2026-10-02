{{-- Site Footer block: brand, about line, social links, footer + legal menus (location columns arrive with the Locations CPT). --}}
@php
  $about = $attributes['about'] ?: get_bloginfo('description');
  $social = array_filter([
    'insta' => ['url' => $attributes['instagram'], 'label' => __('Instagram', 'cobbleandcandle')],
    'fb' => ['url' => $attributes['facebook'], 'label' => __('Facebook', 'cobbleandcandle')],
    'mail' => ['url' => $attributes['email'] ? 'mailto:'.antispambot(sanitize_email($attributes['email'])) : '', 'label' => __('Email', 'cobbleandcandle')],
  ], fn ($s) => $s['url'] !== '');
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
          @if ($social)
            <div class="social">
              @foreach ($social as $icon => $s)
                <a class="icon-btn" href="{!! esc_url($s['url']) !!}"><x-icon :name="$icon" /><span class="sr">{{ $s['label'] }}</span></a>
              @endforeach
            </div>
          @endif
        </div>
        @if (\App\locations())
          <h2 class="sr">{{ __('Our locations', 'cobbleandcandle') }}</h2>
        @endif
        @foreach (\App\locations() as $l)
          <div class="f-loc">
            <h3 class="h4"><a href="{!! esc_url($l['url']) !!}">{{ $l['name'] }}</a></h3>
            <x-status :status="$l['status']" size="sm" />
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
