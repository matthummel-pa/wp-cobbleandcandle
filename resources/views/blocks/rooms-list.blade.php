{{-- Rooms: room cards with an optional section head (home "Stay with us") and house notes (rooms page). --}}
@php
  $rooms = \App\rooms((int) $attributes['count'] ?: 50);
  $notes = array_filter(array_map('trim', explode("\n", (string) $attributes['notes'])));
  $hid = wp_unique_id('rooms-');
@endphp
@if ($rooms || \App\is_editor_preview() || is_post_type_archive('cobble_room'))
  <section {!! $wrapper !!} @if ($attributes['title'] !== '') aria-labelledby="{{ $hid }}" @endif>
    <div class="section">
      <div class="container">
        @if ($attributes['title'] !== '')
          <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" :intro="$attributes['intro']" :id="$hid" :link="$attributes['linkUrl'] && ! is_post_type_archive('cobble_room') ? $attributes['linkUrl'] : null" :link-label="$attributes['linkLabel']" />
        @endif
        @if ($rooms)
          <div class="grid-3 room-grid">
            @foreach ($rooms as $room)
              <x-room-card :room="$room" />
            @endforeach
          </div>
        @else
          <p class="empty"><x-icon name="bed" /> {{ $attributes['emptyText'] }}</p>
        @endif
        @if ($notes)
          <ul class="ticks rooms-notes">@foreach ($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
        @endif
      </div>
    </div>
  </section>
@endif
