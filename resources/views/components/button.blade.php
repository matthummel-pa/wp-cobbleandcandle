{{-- .btn from the mockups. variant: primary | secondary | link | text; size: sm | block | null. --}}
@props(['href' => null, 'variant' => 'primary', 'size' => null, 'icon' => null, 'type' => 'button'])
@php($classes = trim('btn btn--'.$variant.($size ? ' btn--'.$size : '')))
@if ($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icon :name="$icon" />@endif<span>{{ $slot }}</span></a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icon :name="$icon" />@endif<span>{{ $slot }}</span></button>
@endif
