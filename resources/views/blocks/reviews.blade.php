{{-- Reviews & Press: press names, then the quotes typed on the canvas as cards (stars are decorative). --}}
@php
  $hid = wp_unique_id('rev-');
  $press = array_filter(array_map('trim', explode(',', (string) $attributes['press'])));
@endphp
<div {!! $wrapper !!}>
  <section class="section" aria-labelledby="{{ $hid }}">
    <div class="container">
      <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" center :id="$hid" />
      @if ($press)
        <ul class="press" aria-label="{{ __('As featured in', 'cobbleandcandle') }}">
          @foreach ($press as $i => $name)
            <li class="press-{{ $i }}">{{ $name }}</li>
          @endforeach
        </ul>
      @endif
      <div class="grid-3 reviews-grid">{!! $content !!}</div>
    </div>
  </section>
</div>
