{{-- Menu row (.mrow): name……price with dotted leader, description, badges. --}}
@props(['item' => [], 'heading' => null])
@php($tag = $heading ?: 'span')
<li class="mrow" data-diet="{{ implode(' ', $item['diet'] ?? []) }}">
  <div class="mrow-top">
    <{{ $tag }} class="mrow-name">{{ $item['name'] }}</{{ $tag }}>
    @if (($item['flag'] ?? '') !== '')
      <span class="flag">{{ $item['flag'] }}</span>
    @endif
    <span class="leader" aria-hidden="true"></span>
    <x-price :item="$item" />
  </div>
  <div class="mrow-bot">
    @if (($item['desc'] ?? '') !== '')
      <p class="mrow-desc">{{ $item['desc'] }}</p>
    @endif
    <x-diet-badges :diet="$item['diet'] ?? []" />
  </div>
</li>
