{{-- Crest + site name + "tagline · est. year", linking home. --}}
@php($sub = implode(' · ', array_filter([\App\brand('tagline'), \App\brand('est') ? sprintf(__('Est. %s', 'cobbleandcandle'), \App\brand('est')) : ''])))
<a class="brand" href="{{ home_url('/') }}" rel="home">
  <x-crest :size="52" />
  <span class="brand-t">
    <span class="brand-n">{{ \App\site_name() }}</span>
    @if ($sub !== '')
      <small class="brand-s">{{ $sub }}</small>
    @endif
  </span>
</a>
