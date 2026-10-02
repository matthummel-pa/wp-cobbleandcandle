{{-- Mobile Action Bar block: fixed bottom bar below 768px (HANDOFF §3, §8). --}}
@php
  $current = \App\current_location();
  $phone = $attributes['phone'] ?: ($current['phone'] ?? \App\brand('phone'));
  $directions = $attributes['directionsUrl'] ?: ($current['map_url'] ?? '');
  $order = $attributes['orderUrl'] ?: ($current['order_url'] ?? '');
  $bind = $current ? ['tel' => '$store.site.loc.tel', 'nav' => '$store.site.loc.map_url'] : [];
  $items = array_filter([
    ['url' => $attributes['reserveUrl'], 'icon' => 'calendar', 'label' => __('Reserve', 'cobbleandcandle'), 'primary' => true],
    ['url' => $order, 'icon' => 'bag', 'label' => __('Order', 'cobbleandcandle'), 'primary' => false],
    ['url' => $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : '', 'icon' => 'phone', 'label' => __('Call', 'cobbleandcandle'), 'primary' => false],
    ['url' => $directions, 'icon' => 'nav', 'label' => __('Directions', 'cobbleandcandle'), 'primary' => false],
  ], fn ($i) => $i['url'] !== '');
@endphp
@if ($items)
  <nav {!! $wrapper !!} aria-label="{{ __('Quick actions', 'cobbleandcandle') }}">
    <div class="mbar">
      @foreach ($items as $i)
        <a class="mbar-a{{ $i['primary'] ? ' mbar-a--primary' : '' }}" href="{{ $i['url'] }}" @isset($bind[$i['icon']]) :href="{{ $bind[$i['icon']] }}" @endisset><x-icon :name="$i['icon']" /><span>{{ $i['label'] }}</span></a>
      @endforeach
    </div>
  </nav>
@endif
