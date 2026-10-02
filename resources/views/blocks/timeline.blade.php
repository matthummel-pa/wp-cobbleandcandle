{{-- Timeline (HANDOFF §3 About): Year | Title | Text lines as an ordered list. --}}
@php
  $items = \App\pipe_lines($attributes['items'], 3);
  $hid = wp_unique_id('tl-');
@endphp
@if ($items)
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section section--alt texture">
      <div class="container">
        <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" center :id="$hid" />
        <ol class="timeline">
          @foreach ($items as [$year, $title, $text])
            <li class="tl-i"><span class="tl-y">{{ $year }}</span><h3 class="h4">{{ $title }}</h3>@if ($text !== '')<p>{{ $text }}</p>@endif</li>
          @endforeach
        </ol>
      </div>
    </div>
  </section>
@endif
