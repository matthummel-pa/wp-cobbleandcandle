{{-- Event Details (HANDOFF §3 single event): breadcrumbs, head, photo, story + courses + notes, ticket card,
     more events. The Core plugin prints the Event JSON-LD and serves the .ics download. --}}
@php
  $post = \App\context_post();
  $event = $post && $post->post_type === 'cc_event' && function_exists('cc_event') ? cc_event($post) : [];
  $place = ($event['location_id'] ?? 0) && function_exists('cc_location') ? cc_location($event['location_id']) : [];
  $notes = array_filter(array_map('trim', explode("\n", $attributes['notes'])));
  $related = $event ? array_slice(array_values(array_filter(\App\upcoming_events((int) $attributes['related'] + 1), fn ($e) => $e['id'] !== $event['id'])), 0, (int) $attributes['related']) : [];
  $reserve = $place ? add_query_arg('loc', $place['slug'], home_url('/reservations/')) : '';
  $crumbs = \App\crumbs();
  $locked = $event && post_password_required($post);
@endphp
@if ($event)
  <div {!! $wrapper !!}>
    <section class="section section--first">
      <div class="container">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb', 'cobbleandcandle') }}">
          <ol>
            @foreach ($crumbs as [$label, $url])
              @if ($url !== '')
                <li><a href="{{ $url }}">{{ $label }}</a></li>
              @else
                <li aria-current="page">{{ $label }}</li>
              @endif
            @endforeach
          </ol>
        </nav>
        <header class="ev-head">
          <p class="eyebrow">{{ implode(' · ', array_filter([$event['type'], $event['location']])) }}</p>
          <h1 class="h1 h1--page">{{ $event['title'] }}</h1>
          @if ($event['excerpt'] !== '')
            <p class="lede">{{ $event['excerpt'] }}</p>
          @endif
        </header>
        <x-media :image-id="$event['image_id']" kind="table" ratio="r-16x9" class="ev-hero" size="full" eager />
        <div class="ev-layout">
          <div class="prose">
            @if ($locked)
              {!! get_the_password_form($post) !!}
            @elseif (trim($post->post_content) !== '' && ! has_block('cobbleandcandle/event-details', $post))
              {{-- has_block(): this block inside its own event's content would render itself forever. --}}
              <h2 class="h3">{{ __('About the evening', 'cobbleandcandle') }}</h2>
              {!! apply_filters('the_content', $post->post_content) !!}
            @endif
            @if ($event['courses'] && ! $locked)
              <h2 class="h3">{{ __('The menu', 'cobbleandcandle') }}</h2>
              <ol class="courses">
                @foreach ($event['courses'] as $course)
                  <li>{{ $course['name'] ?? '' }}@if (($course['note'] ?? '') !== '') <small class="muted">{{ $course['note'] }}</small>@endif</li>
                @endforeach
              </ol>
            @endif
            @if ($notes)
              <h2 class="h3">{{ __('Good to know', 'cobbleandcandle') }}</h2>
              <ul class="ticks">@foreach ($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
            @endif
          </div>
          <aside class="card ticket" aria-labelledby="{{ $event['id'] }}-tk">
            <h2 class="h4" id="{{ $event['id'] }}-tk">{{ __('Book this event', 'cobbleandcandle') }}</h2>
            <ul class="meta-list">
              @if ($event['iso'] !== '')
                <li><x-icon name="calendar" /><time datetime="{{ $event['iso'] }}">{{ $event['when'] }}</time></li>
              @endif
              @if ($place)
                <li><x-icon name="pin" /><span>{{ $place['name'] }}@if ($place['address'] !== '')<br><small class="muted">{{ $place['address'] }}</small>@endif</span></li>
              @endif
              @if ($event['availability'] !== '')
                <li><x-icon name="users" />{{ $event['availability'] }}</li>
              @endif
            </ul>
            @if ($event['price'] !== '')
              <p class="ticket-price">{{ $event['price'] }}</p>
            @endif
            @if ($event['booking_url'] !== '')
              <x-button :href="$event['booking_url']" icon="calendar" size="block">{{ __('Book tickets', 'cobbleandcandle') }}</x-button>
            @elseif ($place && $place['tel'] !== '')
              <x-button :href="$place['tel']" icon="phone" size="block">{{ sprintf(__('Call %s to book', 'cobbleandcandle'), $place['name']) }}</x-button>
            @elseif ($reserve !== '')
              <x-button :href="$reserve" icon="calendar" size="block">{{ __('Reserve a table', 'cobbleandcandle') }}</x-button>
            @endif
            @if (function_exists('cc_event_ics_url') && $event['iso'] !== '')
              <x-button :href="cc_event_ics_url($event['id'])" variant="text" icon="calendar" download>{{ __('Add to calendar (.ics)', 'cobbleandcandle') }}</x-button>
            @endif
          </aside>
        </div>
      </div>
    </section>
    @if ($related)
      <section class="section section--alt">
        <div class="container">
          <x-section-head :eyebrow="__('More events', 'cobbleandcandle')" :title="__('You might also like', 'cobbleandcandle')" :link="get_post_type_archive_link('cc_event')" :link-label="__('All events', 'cobbleandcandle')" />
          <div class="grid-3">
            @foreach ($related as $item)
              <x-event-card :event="$item" />
            @endforeach
          </div>
        </div>
      </section>
    @endif
  </div>
@endif
@if (! $event && \App\is_editor_preview())
  <div {!! $wrapper !!}><p class="empty"><x-icon name="calendar" /> {{ __('Event Details shows the event being viewed: its photo, story, courses and ticket card.', 'cobbleandcandle') }}</p></div>
@endif
