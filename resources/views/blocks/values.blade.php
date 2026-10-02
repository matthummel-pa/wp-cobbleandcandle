{{-- Values (HANDOFF §3 About): icon | Title | Text lines as cards. --}}
@php
  $items = \App\pipe_lines($attributes['items'], 3);
  $icons = array_keys(\App\icon_symbols());
  $hid = wp_unique_id('val-');
@endphp
@if ($items)
  <section {!! $wrapper !!} aria-labelledby="{{ $hid }}">
    <div class="section section--alt">
      <div class="container">
        <x-section-head :eyebrow="$attributes['eyebrow']" :title="$attributes['title']" center :id="$hid" />
        <div class="grid-3 vals">
          @foreach ($items as [$icon, $title, $text])
            <div class="val">
              <x-icon :name="in_array($icon, $icons, true) ? $icon : 'star'" class="i i--lg i--accent" />
              <h3 class="h4">{{ $title }}</h3>
              @if ($text !== '')
                <p>{{ $text }}</p>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>
@endif
