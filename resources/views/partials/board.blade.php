{{-- Specials board (.board): a chalkboard of name……price lines from a menu. --}}
@php($bid = wp_unique_id('board-'))
<aside class="board" aria-labelledby="{{ $bid }}">
  <div class="board-in">
    <x-ornament :width="110" />
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h3 class="board-h" id="{{ $bid }}">{{ $title }}</h3>
    <ul class="board-l">
      @foreach ($items as $item)
        @php($price = $item['price'] !== '' ? $item['price'] : ($item['variants'][0]['price'] ?? ''))
        <li><span>{{ $item['name'] }}</span><span class="leader" aria-hidden="true"></span><span class="price">{{ $price }}</span></li>
      @endforeach
    </ul>
    @if ($foot !== '')
      <p class="board-f">{{ $foot }}</p>
    @endif
  </div>
</aside>
