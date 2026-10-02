{{-- Open-now status (HANDOFF §9): server-rendered text; Alpine updates it when the location changes. --}}
@props(['status' => [], 'size' => null, 'bind' => null])
<span {{ $attributes->merge(['class' => 'status'.($size ? ' status--'.$size : '')]) }} data-state="{{ $status['state'] ?? 'off' }}" @if ($bind) :data-state="{{ $bind }}.state" @endif>
  <span class="dot" aria-hidden="true"></span>
  <span class="status-t" @if ($bind) x-text="{{ $bind }}.text" @endif>{{ $status['text'] ?? '' }}</span>
</span>
