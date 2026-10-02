{{-- Page Hero (.phero): breadcrumbs, eyebrow, H1 and lede over a photo or placeholder art (HANDOFF §4). --}}
@php
  $hero = \App\page_hero($attributes);
  $crumbs = $attributes['showCrumbs'] ? \App\crumbs() : [];
@endphp
<div {!! $wrapper !!}>
  <section class="phero">
    <x-media :image-id="$hero['image_id']" :kind="$attributes['art']" ratio="r-hero-s" class="phero-m" size="full" eager />
    <div class="container phero-c">
      @if (count($crumbs) > 1)
        <nav class="crumbs" aria-label="{{ __('Breadcrumb', 'cobbleandcandle') }}">
          <ol>
            @foreach ($crumbs as [$label, $url])
              @if ($url !== '')
                <li><a href="{!! esc_url($url) !!}">{{ $label }}</a></li>
              @else
                <li aria-current="page">{{ $label }}</li>
              @endif
            @endforeach
          </ol>
        </nav>
      @endif
      @if ($hero['eyebrow'] !== '')
        <p class="eyebrow eyebrow--hero">{{ $hero['eyebrow'] }}</p>
      @endif
      <h1 class="h1">{{ $hero['title'] }}</h1>
      @if ($hero['lede'] !== '')
        <p class="lede">{{ $hero['lede'] }}</p>
      @endif
    </div>
  </section>
</div>
