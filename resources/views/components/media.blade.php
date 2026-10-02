{{-- Photo slot: the chosen image, or a themeable placeholder illustration (resources/views/art/*). --}}
@props(['imageId' => 0, 'kind' => 'room', 'ratio' => 'r-4x5', 'size' => 'large', 'eager' => false])
@php($uid = wp_unique_id('art'))
<div {{ $attributes->merge(['class' => 'media '.$ratio]) }} @if ((int) $imageId <= 0) aria-hidden="true" @endif>
  @if ((int) $imageId > 0)
    {!! wp_get_attachment_image((int) $imageId, $size, false, array_filter(['loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : null, 'decoding' => 'async'])) !!}
  @else
    @includeIf('art.'.$kind, ['uid' => $uid])
  @endif
</div>
