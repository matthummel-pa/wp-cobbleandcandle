{{-- Demo business-type switcher: links the Restaurant, Tavern and B&B demo home pages. Only shown when the Site Header block enables the demo switchers. --}}
@php
  $currentKind = \App\page_kind() ?: (is_front_page() ? 'restaurant' : '');
  $links = [];
  foreach (\App\kinds() as $kind => $def) {
    $url = \App\kind_home_url($kind);
    if ($url !== '') {
      $links[$kind] = ['url' => $url, 'label' => $def['label']];
    }
  }
@endphp
@if (count($links) > 1)
  <nav class="kindsw" aria-label="{{ __('Demo: business type', 'cobbleandcandle') }}">
    <span class="kindsw-label"><x-icon name="pin" /><span>{{ __('Demo', 'cobbleandcandle') }}</span></span>
    @foreach ($links as $kind => $link)
      <a class="ksw" href="{!! esc_url($link['url']) !!}" @if ($kind === $currentKind) aria-current="page" @endif>{{ $link['label'] }}</a>
    @endforeach
  </nav>
@endif
