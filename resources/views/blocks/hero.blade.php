{{-- Hero: photo (or the street illustration), the headline/intro/buttons typed on the canvas, caption. --}}
<div {!! $wrapper !!}>
  <section class="hero">
    <x-media :image-id="$attributes['imageId']" kind="street" ratio="r-hero" class="hero-m" size="full" eager />
    <div class="container hero-c">{!! $content !!}</div>
    @if ($attributes['caption'] !== '')
      <p class="hero-cap">{{ sprintf(__('Pictured: %s', 'cobbleandcandle'), $attributes['caption']) }}</p>
    @endif
  </section>
</div>
