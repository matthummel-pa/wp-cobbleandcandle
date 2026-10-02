{{-- Crest + site name + "tagline · est. year", linking home. --}}
@php($sub = implode(' · ', array_filter([\App\brand('tagline'), \App\brand('est') ? sprintf(__('Est. %s', 'cobbleandcandle'), \App\brand('est')) : ''])))
<a class="brand" href="{!! esc_url(home_url('/')) !!}" rel="home">
  @if (\App\logo_id())
    {!! wp_get_attachment_image(\App\logo_id(), 'medium', false, ['class' => 'brand-logo', 'alt' => '', 'loading' => 'eager']) !!}
  @else
    <x-crest :size="52" />
  @endif
  <span class="brand-t">
    <span class="brand-n">{{ \App\site_name() }}</span>
    @if ($sub !== '')
      <small class="brand-s">{{ $sub }}</small>
    @endif
  </span>
</a>
