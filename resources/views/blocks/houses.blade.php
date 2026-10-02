{{-- Houses (HANDOFF §3 About): one card per location with status and a link to its page. --}}
@php
  $locations = \App\locations();
  $hid = wp_unique_id('houses-');
@endphp
@if ($locations)
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section">
      <div class="container">
        <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" :id="$hid" />
        <div class="grid-3">
          @foreach ($locations as $l)
            <article class="house">
              <x-media :image-id="(int) get_post_thumbnail_id($l['id'])" kind="facade" ratio="r-4x3" size="medium_large" />
              <div class="house-b">
                <h3 class="h4">{{ $l['name'] }}</h3>
                @if ($l['address'] !== '')
                  <p class="muted">{{ $l['address'] }}</p>
                @endif
                <x-status :status="$l['status']" :of="$l['slug']" size="sm" />
                <a class="link-arrow" href="{!! esc_url($l['url']) !!}">{{ __('Visit', 'cobbleandcandle') }}<x-icon name="arrow" /><span class="sr"> {{ $l['name'] }}</span></a>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </div>
  </section>
@endif
