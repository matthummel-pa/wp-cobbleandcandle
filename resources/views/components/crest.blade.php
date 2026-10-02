{{-- Monogram in a rope ring. Reads only CSS vars, so it re-skins per style direction. --}}
@props(['size' => 56, 'class' => 'crest', 'mono' => null, 'est' => null])
@php
  $uid = wp_unique_id('crest-');
  $mono = $mono ?? mb_strtoupper(mb_substr(\App\site_name(), 0, 1));
  $est = $est ?? \App\brand('est');
@endphp
<svg class="{{ $class }}" viewBox="0 0 80 80" width="{{ (int) $size }}" height="{{ (int) $size }}" aria-hidden="true">
  <defs><path id="{{ $uid }}" d="M13 45 A27 27 0 0 0 67 45"/></defs>
  <circle cx="40" cy="40" r="38" fill="none" style="stroke:currentColor" stroke-width="1.5"/>
  <circle cx="40" cy="40" r="33.5" fill="none" style="stroke:currentColor" stroke-width=".7" stroke-dasharray="1 2.2"/>
  <circle cx="40" cy="40" r="21" style="fill:currentColor" opacity=".12"/>
  <text class="crest-mono" x="40" y="45" text-anchor="middle" style="fill:currentColor;font-family:var(--font-display);font-style:var(--em-style)">{{ $mono }}</text>
  @if ($est)
    <text font-size="6.4" letter-spacing="2.2" style="fill:currentColor;font-family:var(--font-sans);font-weight:700"><textPath href="#{{ $uid }}" startOffset="50%" text-anchor="middle">{{ sprintf(__('EST · %s', 'cobbleandcandle'), $est) }}</textPath></text>
  @endif
  <path d="M21 30l3 2-3 2-3-2zM59 30l3 2-3 2-3-2z" style="fill:currentColor"/>
</svg>
