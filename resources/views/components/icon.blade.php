{{-- Front end: <use> the inline sprite. Editor previews (REST): inline the paths, since the canvas has no sprite. --}}
@props(['name', 'class' => 'i'])
@if (\App\is_editor_preview())
  <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" aria-hidden="true" focusable="false">{!! \App\icon_symbols()[$name] ?? '' !!}</svg>
@else
  <svg {{ $attributes->merge(['class' => $class]) }} aria-hidden="true" focusable="false"><use href="#i-{{ $name }}"/></svg>
@endif
