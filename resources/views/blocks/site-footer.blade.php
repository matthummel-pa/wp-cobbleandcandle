{{-- Site Footer block: newsletter band, brand, about line, social links, footer + legal menus. --}}
@php
  $about = $attributes['about'] ?: get_bloginfo('description');
  $social = array_filter([
    'insta' => ['url' => $attributes['instagram'], 'label' => __('Instagram', 'cobbleandcandle')],
    'fb' => ['url' => $attributes['facebook'], 'label' => __('Facebook', 'cobbleandcandle')],
    'mail' => ['url' => $attributes['email'] ? 'mailto:'.antispambot(sanitize_email($attributes['email'])) : '', 'label' => __('Email', 'cobbleandcandle')],
  ], fn ($s) => $s['url'] !== '');
  // The band is only useful with the Core plugin: without it nothing would receive the signup.
  $newsletter = function_exists('cc_newsletter_form_url') && $attributes['newsTitle'] !== '';
  $nid = wp_unique_id('news-');
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag after redirect.
  $status = isset($_GET['newsletter']) ? sanitize_key(wp_unslash($_GET['newsletter'])) : '';
  $messages = [
      'sent' => __('Thank you, you’re on the list.', 'cobbleandcandle'),
      'invalid' => __('Please check that email address and try again.', 'cobbleandcandle'),
      'expired' => __('This form had expired. Please send it again.', 'cobbleandcandle'),
      'busy' => __('Too many signups from this connection. Please wait a few minutes.', 'cobbleandcandle'),
      'error' => __('We couldn’t add you just now. Please try again later.', 'cobbleandcandle'),
  ];
@endphp
<footer {!! $wrapper !!}>
  <div class="ftr">
    <div class="container">
      @if ($newsletter)
        <section class="news" id="newsletter" aria-labelledby="{{ $nid }}-h">
          <div>
            <x-ornament :width="120" />
            <h2 class="h3" id="{{ $nid }}-h">{{ $attributes['newsTitle'] }}</h2>
            @if ($attributes['newsText'] !== '')
              <p>{{ $attributes['newsText'] }}</p>
            @endif
          </div>
          <form class="news-f" method="post" action="{{ cc_newsletter_form_url() }}">
            @if (isset($messages[$status]))
              <p class="{{ $status === 'sent' ? 'form-ok' : 'form-error' }}" role="status"><x-icon :name="$status === 'sent' ? 'check' : 'info'" /> {{ $messages[$status] }}</p>
            @endif
            <input type="hidden" name="action" value="cc_newsletter">
            {!! wp_nonce_field('cc_newsletter', 'cc_newsletter_nonce', true, false) !!}
            <div class="sr" aria-hidden="true"><label for="{{ $nid }}-web">{{ __('Leave this empty', 'cobbleandcandle') }}</label><input id="{{ $nid }}-web" type="text" name="cc_website" tabindex="-1" autocomplete="off"></div>
            <label for="{{ $nid }}-email">{{ __('Email address', 'cobbleandcandle') }}</label>
            <div class="news-row">
              <input id="{{ $nid }}-email" name="cc_email" type="email" autocomplete="email" placeholder="{{ __('you@example.com', 'cobbleandcandle') }}" required>
              <button class="btn btn--primary" type="submit">{{ $attributes['newsButton'] !== '' ? $attributes['newsButton'] : __('Subscribe', 'cobbleandcandle') }}</button>
            </div>
            <p class="hint">{{ __('Monthly. No spam. Unsubscribe any time.', 'cobbleandcandle') }}</p>
          </form>
        </section>
      @endif
      <div class="f-grid">
        <div class="f-brand">
          @include('partials.brand')
          @if ($about)
            <p class="muted">{{ $about }}</p>
          @endif
          @if ($social)
            <div class="social">
              @foreach ($social as $icon => $s)
                <a class="icon-btn" href="{{ $s['url'] }}"><x-icon :name="$icon" /><span class="sr">{{ $s['label'] }}</span></a>
              @endforeach
            </div>
          @endif
        </div>
        @foreach (\App\locations() as $l)
          <div class="f-loc">
            <h3 class="h4"><a href="{{ $l['url'] }}">{{ $l['name'] }}</a></h3>
            <x-status :status="$l['status']" size="sm" />
            @if ($l['street'] !== '')
              <p><a href="{{ $l['map_url'] }}">{{ $l['street'] }}@if ($l['locality'] !== '')<br>{{ $l['locality'] }}@endif</a></p>
            @endif
            @if ($l['phone'] !== '')
              <p><a href="{{ $l['tel'] }}">{{ $l['phone'] }}</a></p>
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
