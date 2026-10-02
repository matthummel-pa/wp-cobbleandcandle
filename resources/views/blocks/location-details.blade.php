{{-- Location Details: the location being viewed (single location template), else the current one. --}}
@php
  $post = \App\context_post();
  $l = $post && $post->post_type === 'cc_location' && function_exists('cc_location') ? cc_location($post) : \App\current_location();
  $pin = array_search($l['id'] ?? 0, array_column(\App\locations(), 'id'), true);
@endphp
@if ($l)
  <div {!! $wrapper !!}>
    <section class="section">
      <div class="container lpanel">
        @include('partials.location-panel', ['l' => $l, 'pin' => $pin === false ? -1 : $pin, 'heading' => 'h2', 'reserveUrl' => $attributes['reserveUrl'], 'reserveLabel' => $attributes['reserveLabel']])
      </div>
    </section>
  </div>
@endif
