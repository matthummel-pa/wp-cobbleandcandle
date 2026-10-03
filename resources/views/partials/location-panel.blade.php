{{-- One location's details (.lpanel): map or photo with directions, address, contact, weekly and
     holiday hours, getting there, and actions. $l is a cobble_location() array; $pin its map index. --}}
@php
  $notes = array_filter([
      ['car', __('Parking', 'cobbleandcandle'), (string) get_post_meta($l['id'], 'cobble_parking', true)],
      ['train', __('Transit', 'cobbleandcandle'), (string) get_post_meta($l['id'], 'cobble_transit', true)],
      ['access', __('Access', 'cobbleandcandle'), (string) get_post_meta($l['id'], 'cobble_accessibility', true)],
  ], fn ($n) => $n[2] !== '');
  $holidays = \App\holiday_rows($l['id']);
  $photo = (int) get_post_thumbnail_id($l['id']);
  $uid = wp_unique_id('map');
@endphp
<div class="lmap card">
  @if ($photo)
    <x-media :image-id="$photo" ratio="r-4x3" />
  @else
    @include('art.map', ['uid' => $uid, 'pin' => $pin ?? -1, 'names' => array_column(\App\locations(), 'name')])
  @endif
  @if ($l['map_url'] !== '')
    <a class="btn btn--primary lmap-btn" href="{!! esc_url($l['map_url']) !!}"><x-icon name="nav" /><span>{{ __('Get directions', 'cobbleandcandle') }}</span></a>
  @endif
</div>
<div class="ldetail">
  <{{ $heading ?? 'h2' }} class="h2">{{ $l['name'] }}</{{ $heading ?? 'h2' }}>
  <x-status :status="$l['status']" :of="$l['slug']" />
  @if ($l['address'] !== '')
    <address class="laddr"><x-icon name="pin" /><span>{{ $l['address'] }}</span></address>
  @endif
  <p class="lcontact">
    @if ($l['phone'] !== '')
      <a href="{!! esc_url($l['tel']) !!}"><x-icon name="phone" />{{ $l['phone'] }}</a>
    @endif
    @if ($l['email'] !== '')
      <a href="mailto:{{ $l['email'] }}"><x-icon name="mail" />{{ $l['email'] }}</a>
    @endif
  </p>
  <div class="lcols">
    <div>
      <h3 class="h4">{{ __('Opening hours', 'cobbleandcandle') }}</h3>
      <table class="week">
        <caption class="sr">{{ sprintf(__('Weekly hours at %s', 'cobbleandcandle'), $l['name']) }}</caption>
        <tbody>
          @foreach (\App\week_rows($l['id']) as [$day, $hours, $today])
            <tr @class(['is-today' => $today])><th scope="row">{{ $day }}@if ($today)<span class="today-tag">{{ __('Today', 'cobbleandcandle') }}</span>@endif</th><td>{{ $hours }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($holidays)
      <div>
        <h3 class="h4">{{ __('Holiday hours', 'cobbleandcandle') }}</h3>
        <ul class="holiday">
          @foreach ($holidays as [$label, $date, $hours])
            <li><span class="hol-d">{{ $date }}</span><span class="hol-n">{{ $label }}</span><span class="hol-h">{{ $hours }}</span></li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>
  @if ($notes)
    <ul class="notes">
      @foreach ($notes as [$icon, $label, $text])
        <li><x-icon :name="$icon" /><div><b>{{ $label }}</b><p>{{ $text }}</p></div></li>
      @endforeach
    </ul>
  @endif
  <div class="cta-row">
    @if (($reserveUrl ?? '') !== '')
      <x-button :href="add_query_arg('loc', $l['slug'], $reserveUrl)" icon="calendar">{{ $reserveLabel ?? __('Reserve a table', 'cobbleandcandle') }}</x-button>
    @endif
    @if (($detailsUrl ?? '') !== '')
      <x-button :href="$detailsUrl" variant="secondary">{{ __('About this house', 'cobbleandcandle') }}</x-button>
    @endif
    @if ($l['tel'] !== '')
      <x-button :href="$l['tel']" variant="text" icon="phone">{{ __('Call', 'cobbleandcandle') }}</x-button>
    @endif
  </div>
</div>
