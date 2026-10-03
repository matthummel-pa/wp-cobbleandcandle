{{-- Location switcher: button + listbox (HANDOFF §3, §8). Arrows / Home / End / Enter / Esc. --}}
@php($uid = wp_unique_id('locsw-'))
<div class="locsw" x-data="locationSwitcher" @cobble-open-locations.window="openFromPage()" @click.outside="close()" @keydown.escape.stop="close(true)">
  <button type="button" class="locsw-btn" aria-haspopup="listbox" aria-controls="{{ $uid }}" aria-expanded="false" :aria-expanded="open.toString()" x-ref="button" @click="toggle()" @keydown.arrow-down.prevent="show()">
    <x-icon name="pin" />
    <span class="locsw-l">{{ __('Location', 'cobbleandcandle') }}</span>
    <b class="locsw-n" x-text="$store.site.loc.name">{{ $current['name'] }}</b>
    <x-icon name="chev-down" class="i locsw-c" />
  </button>
  <ul class="locsw-list" id="{{ $uid }}" role="listbox" aria-label="{{ __('Choose a location', 'cobbleandcandle') }}" hidden :hidden="!open" x-ref="list" @keydown="keys($event)">
    @foreach ($locations as $i => $l)
      <li role="option" id="{{ $uid }}-{{ $i }}" tabindex="-1" aria-selected="{{ $l['slug'] === $current['slug'] ? 'true' : 'false' }}"
          :aria-selected="($store.site.current === {{ $i }}).toString()" @click="choose({{ $i }})">
        <span class="lo-n">{{ $l['name'] }}</span>
        <x-status :status="$l['status']" :of="$l['slug']" size="sm" />
        <x-icon name="check" class="i lo-check" />
      </li>
    @endforeach
  </ul>
</div>
