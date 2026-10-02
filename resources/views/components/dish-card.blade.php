{{-- Dish card (.dish): 4:5 photo, flag, name, description, badges, price. --}}
@props(['item' => [], 'art' => 'dish-plate', 'heading' => 'h3'])
<article class="dish" data-diet="{{ implode(' ', $item['diet'] ?? []) }}">
  <x-media :image-id="$item['image_id'] ?? 0" :kind="$art" ratio="r-4x5" class="dish-m" size="medium_large" />
  @if (($item['flag'] ?? '') !== '')
    <span class="flag flag--over">{{ $item['flag'] }}</span>
  @endif
  <div class="dish-b">
    <{{ $heading }} class="dish-n">{{ $item['name'] }}</{{ $heading }}>
    @if (($item['desc'] ?? '') !== '')
      <p class="dish-d">{{ $item['desc'] }}</p>
    @endif
    <div class="dish-f"><x-diet-badges :diet="$item['diet'] ?? []" /><x-price :item="$item" /></div>
  </div>
</article>
