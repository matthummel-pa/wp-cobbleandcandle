{{-- Price, or labelled size variants (Glass / Bottle, Starter / Main). --}}
@props(['item' => []])
@php($variants = array_filter((array) ($item['variants'] ?? []), fn ($v) => ($v['price'] ?? '') !== ''))
@if ($variants)
  <span class="price"><span class="vars">@foreach ($variants as $v)<span class="var"><small>{{ $v['label'] ?? '' }}</small>{{ $v['price'] }}</span>@endforeach</span></span>
@elseif (($item['price'] ?? '') !== '')
  <span class="price">{{ $item['price'] }}</span>
@endif
