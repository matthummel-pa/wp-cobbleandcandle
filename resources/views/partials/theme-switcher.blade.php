{{-- Demo style switcher (HANDOFF §2.3). Only shown when the Site Header block enables it. --}}
<div class="themesw" role="group" aria-label="{{ __('Demo style direction', 'cobbleandcandle') }}">
  <span class="themesw-label"><x-icon name="palette" /><span>{{ __('Style', 'cobbleandcandle') }}</span></span>
  @foreach (\App\directions() as $key => $d)
    <button type="button" class="tsw" data-set-theme="{{ $key }}" title="{{ $d['title'] }}"
            :aria-pressed="($store.site.theme === '{{ $key }}').toString()"
            @click="$store.site.setTheme('{{ $key }}')"
            aria-pressed="{{ $key === \App\direction() ? 'true' : 'false' }}">
      <span class="tsw-sw" style="--sw-a:{{ $d['sw'][0] }};--sw-b:{{ $d['sw'][1] }}" aria-hidden="true"></span>
      <span class="tsw-l">{{ $d['label'] }}</span>
    </button>
  @endforeach
</div>
