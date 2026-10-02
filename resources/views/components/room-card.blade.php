{{-- Room card (.room): photo, name, beds · guests · size, price from, amenities, link. --}}
@props(['room' => [], 'heading' => 'h3'])
@php($heading = in_array($heading, ['h2', 'h3', 'h4'], true) ? $heading : 'h3')
<article class="room card">
  <x-media :image-id="$room['image_id']" kind="room" ratio="r-4x3" class="room-m" size="large" />
  <div class="room-b">
    <{{ $heading }} class="ev-t"><a class="room-link" href="{!! esc_url($room['url']) !!}">{{ $room['name'] }}</a></{{ $heading }}>
    <ul class="room-facts">
      @if ($room['beds'] !== '')
        <li><x-icon name="bed" />{{ $room['beds'] }}</li>
      @endif
      <li><x-icon name="users" />{{ sprintf(_n('Up to %d guest', 'Up to %d guests', $room['max_guests'], 'cobbleandcandle'), $room['max_guests']) }}</li>
      @if ($room['size'] !== '')
        <li><x-icon name="size" />{{ $room['size'] }}</li>
      @endif
    </ul>
    @if ($room['excerpt'] !== '')
      <p class="ev-d">{{ $room['excerpt'] }}</p>
    @endif
    <div class="ev-f">
      @if ($room['from'] !== '')
        {{-- translators: %s: lowest nightly price --}}
        <span class="room-price">{!! wp_kses(sprintf(__('From <b>%s</b> a night', 'cobbleandcandle'), esc_html($room['from'])), ['b' => []]) !!}</span>
      @endif
      <span class="link-arrow" aria-hidden="true">{{ __('Dates & booking', 'cobbleandcandle') }}<x-icon name="arrow" /></span>
    </div>
  </div>
</article>
