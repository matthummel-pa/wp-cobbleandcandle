{{-- Open-now status (HANDOFF §9): server-rendered first paint; the browser recomputes it (Alpine store) so cached pages stay current. `of` = location slug. --}}
@props(['status' => [], 'size' => null, 'bind' => null, 'of' => null])
<span {{ $attributes->merge(['class' => 'status'.($size ? ' status--'.$size : '')]) }} data-state="{{ $status['state'] ?? 'off' }}" @if ($of) data-status-of="{{ $of }}" @endif @if ($bind) :data-state="{{ $bind }}.state" @endif>
  <span class="dot" aria-hidden="true"></span>
  <span class="status-t" @if ($bind) x-text="{{ $bind }}.text" @endif>{{ $status['text'] ?? '' }}</span>
</span>
