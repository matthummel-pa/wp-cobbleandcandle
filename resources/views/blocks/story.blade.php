{{-- Story: portrait + caption beside the story copy typed on the canvas, then live stats. --}}
@php($houses = count(\App\locations()))
<div {!! $wrapper !!}>
  <section class="section">
    <div class="container story">
      <figure class="story-m">
        <x-media :image-id="$attributes['imageId']" kind="chef" ratio="r-4x5" />
        @if ($attributes['caption'] !== '')
          <figcaption>{{ $attributes['caption'] }}</figcaption>
        @endif
      </figure>
      <div class="story-c">
        {!! $content !!}
        <dl class="stats">
          @if (\App\brand('est') !== '')
            <div><dt>{{ __('Established', 'cobbleandcandle') }}</dt><dd>{{ \App\brand('est') }}</dd></div>
          @endif
          @if ($houses > 0)
            <div><dt>{{ _n('House', 'Houses', $houses, 'cobbleandcandle') }}</dt><dd>{{ number_format_i18n($houses) }}</dd></div>
          @endif
          @if ($attributes['seatings'] !== '')
            <div><dt>{{ __('Seatings nightly', 'cobbleandcandle') }}</dt><dd>{{ $attributes['seatings'] }}</dd></div>
          @endif
        </dl>
      </div>
    </div>
  </section>
</div>
