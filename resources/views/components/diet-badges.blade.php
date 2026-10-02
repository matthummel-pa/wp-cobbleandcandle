{{-- Diet badges: a letter plus a visually hidden full name (HANDOFF §11). --}}
@props(['diet' => []])
@php($names = ['v' => __('Vegetarian', 'cobbleandcandle'), 'vg' => __('Vegan', 'cobbleandcandle'), 'gf' => __('Gluten-free', 'cobbleandcandle'), 'spicy' => __('Spicy', 'cobbleandcandle')])
@if ($diet)
  <span class="badges">
    @foreach ($diet as $d)
      @if ($d === 'spicy')
        <abbr class="badge badge--s" title="{{ $names['spicy'] }}"><x-icon name="chili" /><span class="sr">{{ $names['spicy'] }}</span></abbr>
      @elseif (isset($names[$d]))
        <abbr class="badge badge--{{ $d }}" title="{{ $names[$d] }}">{{ strtoupper($d) }}</abbr>
      @endif
    @endforeach
  </span>
@endif
