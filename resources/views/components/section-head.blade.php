{{-- Section header (.sh): eyebrow, title, optional intro and link. --}}
@props(['eyebrow' => '', 'title' => '', 'intro' => '', 'center' => false, 'id' => null, 'link' => null, 'linkLabel' => ''])
<header class="sh{{ $center ? ' sh--center' : '' }}">
  @if ($center)
    <x-ornament class="orn sh-orn" :width="160" />
  @endif
  <div class="sh-text">
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2 class="h2" @if ($id) id="{{ $id }}" @endif>{{ $title }}</h2>
    @if ($intro !== '')
      <p class="sh-intro">{{ $intro }}</p>
    @endif
  </div>
  @if ($link && $linkLabel !== '')
    <div class="sh-link"><a class="link-arrow" href="{{ $link }}">{{ $linkLabel }}<x-icon name="arrow" /></a></div>
  @endif
</header>
