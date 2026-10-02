{{-- Gallery Mosaic: five tiles (photos, or placeholder art until images are chosen). --}}
@php
  $hid = wp_unique_id('gal-');
  $ids = array_values(array_filter(array_map('intval', (array) $attributes['imageIds'])));
  $art = ['room', 'dish-plate', 'cellar', 'hearth', 'facade'];
  $tiles = ['g-a', 'g-b', 'g-c', 'g-d', 'g-e'];
@endphp
<section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
  <div class="section section--tight">
    <div class="container">
      <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" :id="$hid" :link="$attributes['linkUrl'] ?: null" :link-label="$attributes['linkLabel']" />
      <div class="gmosaic">
        @foreach ($tiles as $i => $tile)
          @php($label = isset($ids[$i]) ? (get_post_meta($ids[$i], '_wp_attachment_image_alt', true) ?: __('Open the gallery', 'cobbleandcandle')) : __('Open the gallery', 'cobbleandcandle'))
          <a class="gtile {{ $tile }}" href="{{ $attributes['linkUrl'] ?: '#' }}">
            <x-media :image-id="$ids[$i] ?? 0" :kind="$art[$i]" ratio="r-fill" size="medium_large" />
            <span class="sr">{{ $label }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </div>
</section>
