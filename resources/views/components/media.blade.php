{{-- Photo slot: the chosen image, or a themeable placeholder illustration (resources/views/art/*). --}}
@props(['imageId' => 0, 'kind' => 'room', 'ratio' => 'r-4x5', 'size' => 'large', 'eager' => false])
@php($uid = wp_unique_id('art'))
{{-- Content photos without alt text fall back to their Media Library caption (never the file name); decorative images such as the logo don't use this component. --}}
@php($altFallback = (int) $imageId > 0 && trim((string) get_post_meta((int) $imageId, '_wp_attachment_image_alt', true)) === '' ? trim(wp_strip_all_tags((string) wp_get_attachment_caption((int) $imageId))) : '')
<div {{ $attributes->merge(['class' => 'media '.$ratio]) }} @if ((int) $imageId <= 0) aria-hidden="true" @endif>
  @if ((int) $imageId > 0)
    {!! wp_get_attachment_image((int) $imageId, $size, false, array_filter(['loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : null, 'decoding' => 'async', 'alt' => $altFallback !== '' ? $altFallback : null])) !!}
  @else
    @includeIf('art.'.$kind, ['uid' => $uid])
  @endif
</div>
