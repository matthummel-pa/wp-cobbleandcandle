{{-- Events Archive (HANDOFF §3 Events): featured next event, type filter chips, event rows, regulars + board. --}}
@php
  $events = \App\upcoming_events(50);
  $featured = $attributes['showFeatured'] ? ($events[0] ?? null) : null;
  $types = array_values(array_unique(array_filter(array_column($events, 'type'))));
  $regulars = array_filter(array_map('trim', explode("\n", $attributes['regulars'])));
  $board = [];
  foreach (\App\menus() as $m) {
      if ($m['term']->slug === $attributes['boardMenu']) {
          $board = \App\menu_preview($m, 3);
      }
  }
  $hid = wp_unique_id('ev-');
@endphp
<div {!! $wrapper !!}>
  <section class="section">
    <div class="container">
      @if ($featured)
        <article class="feature card">
          <x-media :image-id="$featured['image_id']" kind="table" ratio="r-16x9" class="feature-m" size="large" />
          <div class="feature-b">
            <p class="eyebrow">{{ $featured['type'] !== '' ? sprintf(__('Next up · %s', 'cobbleandcandle'), $featured['type']) : __('Next up', 'cobbleandcandle') }}</p>
            <h2 class="h2"><a href="{{ $featured['url'] }}">{{ $featured['title'] }}</a></h2>
            @if ($featured['excerpt'] !== '')
              <p>{{ $featured['excerpt'] }}</p>
            @endif
            <ul class="meta-list">
              @if ($featured['iso'] !== '')
                <li><x-icon name="calendar" /><time datetime="{{ $featured['iso'] }}">{{ $featured['when'] }}</time></li>
              @endif
              @if ($featured['location'] !== '')
                <li><x-icon name="pin" />{{ $featured['location'] }}</li>
              @endif
              @if ($featured['availability'] !== '')
                <li><x-icon name="users" />{{ $featured['availability'] }}</li>
              @endif
            </ul>
            <div class="cta-row">
              @if ($featured['booking_url'] !== '')
                <x-button :href="$featured['booking_url']" icon="calendar">{{ $featured['price'] !== '' ? sprintf(__('Book tickets · %s', 'cobbleandcandle'), $featured['price']) : __('Book tickets', 'cobbleandcandle') }}</x-button>
              @endif
              <x-button :href="$featured['url']" :variant="$featured['booking_url'] !== '' ? 'secondary' : 'primary'">{{ __('Details', 'cobbleandcandle') }}</x-button>
            </div>
          </div>
        </article>
      @endif

      <div x-data="{ type: 'all' }">
        <div class="ev-tools">
          <h2 class="h3" id="{{ $hid }}">{{ $attributes['listTitle'] }}</h2>
          @if (count($types) > 1)
            <div class="fchips" role="group" aria-labelledby="{{ $hid }}">
              <button type="button" class="fchip-b" aria-pressed="true" :aria-pressed="(type === 'all').toString()" @click="type = 'all'">{{ __('All', 'cobbleandcandle') }}</button>
              @foreach ($types as $type)
                <button type="button" class="fchip-b" aria-pressed="false" :aria-pressed="(type === @js($type)).toString()" @click="type = @js($type)">{{ $type }}</button>
              @endforeach
            </div>
          @endif
        </div>
        @if ($events)
          <ol class="evlist">
            @foreach ($events as $event)
              <li class="evrow" :hidden="type !== 'all' && type !== @js($event['type'])">
                @if ($event['day'] !== '')
                  <div class="datebox" aria-hidden="true"><span class="db-w">{{ $event['weekday'] }}</span><span class="db-d">{{ $event['day'] }}</span><span class="db-m">{{ $event['month'] }}</span></div>
                @endif
                <div class="evrow-b">
                  <p class="ev-type">{{ implode(' · ', array_filter([$event['type'], $event['location']])) }}</p>
                  <h3 class="h4"><a href="{{ $event['url'] }}">{{ $event['title'] }}</a></h3>
                  @if ($event['excerpt'] !== '')
                    <p class="muted">{{ $event['excerpt'] }}</p>
                  @endif
                </div>
                <div class="evrow-m">
                  @if ($event['iso'] !== '')
                    <span><x-icon name="clock" /><time datetime="{{ $event['iso'] }}">{{ $event['when'] }}</time></span>
                  @endif
                  @if ($event['price'] !== '')
                    <span><b>{{ $event['price'] }}</b></span>
                  @endif
                  @if ($event['availability'] !== '')
                    <span class="chip chip--quiet">{{ $event['availability'] }}</span>
                  @endif
                </div>
                <div class="evrow-a"><a class="btn btn--secondary btn--sm" href="{{ $event['url'] }}"><span>{{ __('Details', 'cobbleandcandle') }}</span><span class="sr"> {{ sprintf(__('about %s', 'cobbleandcandle'), $event['title']) }}</span></a></div>
              </li>
            @endforeach
          </ol>
        @else
          <p class="empty"><x-icon name="calendar" /> {{ $attributes['emptyText'] }}</p>
        @endif
      </div>
    </div>
  </section>

  @if ($regulars || $board)
    <section class="section section--alt texture">
      <div class="container split split--board">
        <div>
          <x-section-head :eyebrow="$attributes['regularsEyebrow']" :title="$attributes['regularsTitle']" :intro="$attributes['regularsIntro']" />
          @if ($regulars)
            <ul class="ticks">@foreach ($regulars as $line)<li>{{ $line }}</li>@endforeach</ul>
          @endif
        </div>
        @includeWhen($board, 'partials.board', ['items' => $board, 'eyebrow' => $attributes['boardEyebrow'], 'title' => $attributes['boardTitle'], 'foot' => $attributes['boardFoot']])
      </div>
    </section>
  @endif
</div>
