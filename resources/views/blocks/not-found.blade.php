{{-- Page Not Found (HANDOFF §3 404): message, ways back, search, and every house with its status. --}}
@php($locations = \App\locations())
<div {!! $wrapper !!}>
  <section class="section nf">
    <div class="container nf-in">
      <x-media :image-id="$attributes['imageId']" kind="facade" ratio="r-4x5" class="nf-m" eager />
      <div class="nf-c">
        <p class="eyebrow">{{ $attributes['eyebrow'] }}</p>
        <h1 class="h1 h1--page">{{ $attributes['title'] }}</h1>
        @if ($attributes['text'] !== '')
          <p class="lede">{{ $attributes['text'] }}</p>
        @endif
        <div class="cta-row">
          <x-button :href="home_url('/')">{{ __('Back to home', 'cobbleandcandle') }}</x-button>
          @if ($attributes['menuUrl'] !== '')
            <x-button :href="$attributes['menuUrl']" variant="secondary">{{ __('See the menu', 'cobbleandcandle') }}</x-button>
          @endif
          @if ($attributes['reserveUrl'] !== '')
            <x-button :href="$attributes['reserveUrl']" variant="text" icon="calendar">{{ __('Reserve a table', 'cobbleandcandle') }}</x-button>
          @endif
        </div>
        {!! get_search_form(['echo' => false, 'aria_label' => __('Search the site', 'cobbleandcandle')]) !!}
        @if ($locations)
          <h2 class="h4">{{ __('Or visit one of our houses', 'cobbleandcandle') }}</h2>
          <ul class="nf-locs">
            @foreach ($locations as $l)
              <li><a href="{!! esc_url($l['url']) !!}">{{ $l['name'] }}</a><x-status :status="$l['status']" :of="$l['slug']" size="sm" /></li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </section>
</div>
