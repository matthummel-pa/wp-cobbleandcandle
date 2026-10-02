{{-- Gallery Grid (HANDOFF §3 Gallery): category chips, a dense grid (wide/tall tiles by photo shape),
     and a <dialog> lightbox with arrows, Esc and focus return. Placeholder art until photos are chosen. --}}
@php
  $ids = array_values(array_filter(array_map('intval', (array) $attributes['imageIds'])));
  $items = [];
  $cats = [];
  foreach ($ids as $id) {
      $meta = wp_get_attachment_metadata($id) ?: [];
      $w = (int) ($meta['width'] ?? 0);
      $h = (int) ($meta['height'] ?? 0);
      $terms = get_the_terms($id, 'cc_gallery');
      $term = $terms && ! is_wp_error($terms) ? $terms[0] : null;
      if ($term) {
          $cats[$term->slug] = $term->name;
      }
      $items[] = [
          'id' => $id,
          'art' => '',
          'cat' => $term ? $term->slug : '',
          'caption' => wp_get_attachment_caption($id) ?: (string) get_post_meta($id, '_wp_attachment_image_alt', true),
          'shape' => $w && $h ? ($w > $h * 1.3 ? 'g-w' : ($h > $w * 1.2 ? 'g-t' : '')) : '',
          'full' => (string) wp_get_attachment_image_url($id, 'full'),
      ];
  }
  if (! $items) {
      // Demo tiles from the mockups until photos are chosen.
      $cats = ['rooms' => __('Rooms', 'cobbleandcandle'), 'plates' => __('Plates', 'cobbleandcandle'), 'cellar' => __('Cellar & bar', 'cobbleandcandle'), 'outside' => __('Outside', 'cobbleandcandle')];
      foreach ([
          ['room', 'rooms', __('The front room at dusk', 'cobbleandcandle'), 'g-w'], ['dish-plate', 'plates', __('Signature plate', 'cobbleandcandle'), ''],
          ['cellar', 'cellar', __('The vaulted cellar', 'cobbleandcandle'), 'g-t'], ['dish-dessert', 'plates', __('Pudding course', 'cobbleandcandle'), ''],
          ['facade', 'outside', __('Our front door', 'cobbleandcandle'), 'g-t'], ['bar', 'cellar', __('The back bar', 'cobbleandcandle'), 'g-w'],
          ['dish-pie', 'plates', __('From the oven', 'cobbleandcandle'), ''], ['hearth', 'rooms', __('The hearth, lit nightly', 'cobbleandcandle'), ''],
          ['table', 'rooms', __('Long table for a private supper', 'cobbleandcandle'), 'g-w'], ['dish-board', 'plates', __('Cheese from the cave', 'cobbleandcandle'), 'g-t'],
          ['chef', 'rooms', __('The pass', 'cobbleandcandle'), 'g-t'], ['dish-duck', 'plates', __('Dry-aged duck', 'cobbleandcandle'), 'g-w'],
      ] as [$art, $cat, $caption, $shape]) {
          $items[] = ['id' => 0, 'art' => $art, 'cat' => $cat, 'caption' => $caption, 'shape' => $shape, 'full' => ''];
      }
  }
@endphp
<div {!! $wrapper !!}>
  <section class="section" x-data="gallery">
    <div class="container">
      @if (count($cats) > 1)
        <div class="fchips gal-chips" role="group" aria-label="{{ __('Filter gallery', 'cobbleandcandle') }}">
          <button type="button" class="fchip-b" aria-pressed="true" :aria-pressed="(filter === 'all').toString()" @click="filter = 'all'">{{ __('All', 'cobbleandcandle') }}</button>
          @foreach ($cats as $slug => $name)
            <button type="button" class="fchip-b" aria-pressed="false" :aria-pressed="(filter === '{{ $slug }}').toString()" @click="filter = '{{ $slug }}'">{{ $name }}</button>
          @endforeach
        </div>
      @endif
      <ul class="ggrid">
        @foreach ($items as $item)
          <li class="gitem {{ $item['shape'] }}" :hidden="filter !== 'all' && filter !== '{{ $item['cat'] }}'">
            <button type="button" class="gbtn" data-full="{{ $item['full'] }}" @click="open($el)"
                    aria-label="{{ $item['caption'] !== '' ? sprintf(__('Open image: %s', 'cobbleandcandle'), $item['caption']) : __('Open image', 'cobbleandcandle') }}">
              <x-media :image-id="$item['id']" :kind="$item['art'] ?: 'room'" ratio="r-fill" size="medium_large" />
              @if ($item['caption'] !== '')
                <span class="gcap">{{ $item['caption'] }}</span>
              @endif
              <span class="gzoom"><x-icon name="expand" /></span>
            </button>
          </li>
        @endforeach
      </ul>
    </div>
    <dialog class="lb" x-ref="dialog" aria-label="{{ __('Image viewer', 'cobbleandcandle') }}" @close="closed()" @keydown.arrow-left="show(index - 1)" @keydown.arrow-right="show(index + 1)">
      <div class="lb-in">
        <figure class="lb-fig">
          <div class="lb-media" x-ref="media"></div>
          <figcaption><span class="lb-cap" x-text="caption"></span><span class="lb-count" x-text="count"></span></figcaption>
        </figure>
        <button type="button" class="icon-btn lb-close" @click="$refs.dialog.close()"><x-icon name="close" /><span class="sr">{{ __('Close', 'cobbleandcandle') }}</span></button>
        <button type="button" class="icon-btn lb-prev" @click="show(index - 1)"><x-icon name="chev-left" /><span class="sr">{{ __('Previous image', 'cobbleandcandle') }}</span></button>
        <button type="button" class="icon-btn lb-next" @click="show(index + 1)"><x-icon name="chev-right" /><span class="sr">{{ __('Next image', 'cobbleandcandle') }}</span></button>
      </div>
    </dialog>
  </section>
</div>
