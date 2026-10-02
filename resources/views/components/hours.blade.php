{{-- Grouped weekly hours: [["Wed – Sat", "5:30pm – 11pm"], …] --}}
@props(['rows' => [], 'size' => 'sm'])
@if ($rows)
  <dl {{ $attributes->merge(['class' => 'hours'.($size ? ' hours--'.$size : '')]) }}>
    @foreach ($rows as [$days, $time])
      <div class="hrow"><dt>{{ $days }}</dt><dd>{{ $time }}</dd></div>
    @endforeach
  </dl>
@endif
