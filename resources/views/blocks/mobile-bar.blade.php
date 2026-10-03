{{-- Mobile Action Bar block: fixed bottom bar below 768px (HANDOFF §3, §8). The primary action comes first. --}}
@php
  $current = \App\current_location();
  $phone = $attributes['phone'] ?: ($current['phone'] ?? \App\brand('phone'));
  $directions = $attributes['directionsUrl'] ?: ($current['map_url'] ?? '');
  $order = $attributes['orderUrl'] ?: ($current['order_url'] ?? '');
  // Stay: only when the site has rooms. On a room page it leads, straight to that room's calendar.
  $onRoom = is_singular('cobble_room') && ! post_password_required(get_queried_object()); // Locked rooms have no booking card.
  $stay = $attributes['showStay'] && \App\rooms(1) ? ($onRoom ? '#book' : $attributes['stayUrl']) : '';
  $onRoom = $onRoom || (\App\page_kind() === 'bnb' && $stay !== ''); // The B&B demo page leads with Stay too.
  $bind = $current ? ['tel' => '$store.site.loc.tel', 'nav' => '$store.site.loc.map_url'] : [];
  $items = array_filter([
    ['url' => $attributes['reserveUrl'], 'icon' => 'calendar', 'label' => __('Reserve', 'cobbleandcandle'), 'primary' => ! $onRoom || $stay === ''],
    ['url' => $stay, 'icon' => 'bed', 'label' => $onRoom ? __('Book stay', 'cobbleandcandle') : __('Stay', 'cobbleandcandle'), 'primary' => $onRoom],
    ['url' => $order, 'icon' => 'bag', 'label' => __('Order', 'cobbleandcandle'), 'primary' => false],
    ['url' => $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : '', 'icon' => 'phone', 'label' => __('Call', 'cobbleandcandle'), 'primary' => false],
    ['url' => $directions, 'icon' => 'nav', 'label' => __('Directions', 'cobbleandcandle'), 'primary' => false],
  ], fn ($i) => $i['url'] !== '');
  usort($items, fn ($a, $b) => $b['primary'] <=> $a['primary']);
@endphp
@if ($items)
  <nav {!! $wrapper !!} aria-label="{{ __('Quick actions', 'cobbleandcandle') }}">
    <div class="mbar">
      @foreach ($items as $i)
        <a class="mbar-a{{ $i['primary'] ? ' mbar-a--primary' : '' }}" href="{!! esc_url($i['url']) !!}" @isset($bind[$i['icon']]) :href="{!! esc_url($bind[$i['icon']]) !!}" @endisset><x-icon :name="$i['icon']" /><span>{{ $i['label'] }}</span></a>
      @endforeach
    </div>
  </nav>
@endif
