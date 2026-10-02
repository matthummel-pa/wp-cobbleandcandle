{{-- Signature Dishes: menu items flagged as Chef's picks. --}}
@php($items = \App\chef_picks((int) $attributes['count']))
@if ($items)
  @php($hid = wp_unique_id('sig-'))
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section">
      <div class="container">
        <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" :intro="$attributes['intro']" center :id="$hid" />
        <div class="grid-3 dish-grid">
          @foreach ($items as $i => $item)
            <x-dish-card :item="$item" :art="\App\dish_art($i)" />
          @endforeach
        </div>
      </div>
    </div>
  </section>
@endif
