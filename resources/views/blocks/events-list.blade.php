{{-- Upcoming Events: next N events as cards. --}}
@php($events = \App\upcoming_events((int) $attributes['count']))
@if ($events)
  @php($hid = wp_unique_id('ev-'))
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section section--alt">
      <div class="container">
        <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" :intro="$attributes['intro']" :id="$hid" :link="$attributes['linkUrl'] ?: null" :link-label="$attributes['linkLabel']" />
        <div class="grid-3">
          @foreach ($events as $event)
            <x-event-card :event="$event" />
          @endforeach
        </div>
      </div>
    </div>
  </section>
@endif
