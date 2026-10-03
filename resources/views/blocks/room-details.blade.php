{{-- Room Details: breadcrumbs, head, photo, story + amenities + house notes, and a booking card with a
     live availability calendar (free nights only, live total) that sends a booking request. --}}
@php
  $post = \App\context_post();
  $room = $post && $post->post_type === 'cobble_room' && function_exists('cobble_room') ? cobble_room($post) : [];
  $place = ($room['location_id'] ?? 0) && function_exists('cobble_location') ? cobble_location($room['location_id']) : [];
  $notes = array_filter(array_map('trim', explode("\n", $attributes['notes'])));
  $related = $room ? array_slice(array_values(array_filter(\App\rooms(), fn ($r) => $r['id'] !== $room['id'])), 0, (int) $attributes['related']) : [];
  $crumbs = \App\crumbs();
  $locked = $room && post_password_required($post);
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag after redirect.
  $status = isset($_GET['booking']) ? sanitize_key(wp_unslash($_GET['booking'])) : '';
  $messages = [
      'sent' => __('Request sent. We’ll confirm your stay by email shortly. Nothing is charged until we confirm.', 'cobbleandcandle'),
      'invalid' => __('Those dates or details don’t work for this room. Please check and try again.', 'cobbleandcandle'),
      'dinner' => __('Please choose a table time for dinner, or untick dinner.', 'cobbleandcandle'),
      'unavailable' => __('Sorry, someone just booked one of those nights. Please choose other dates.', 'cobbleandcandle'),
      'expired' => __('This form had expired. Please send it again.', 'cobbleandcandle'),
      'busy' => __('Too many requests from this connection. Please wait a few minutes or call us.', 'cobbleandcandle'),
      'error' => __('We couldn’t send your request. Please call us instead.', 'cobbleandcandle'),
  ];
  $uid = wp_unique_id('stay-');
  $picker = $room ? [
      'rest' => rest_url('cobbleandcandle/v1/rooms/'.$room['id'].'/availability'),
      'currency' => (string) apply_filters('cobble_currency', 'USD'),
      'minNights' => $room['min_nights'],
      'priceNight' => $room['price_night'],
      'priceWeekend' => $room['price_weekend'],
      'windows' => $place && function_exists('cobble_booking_windows') ? cobble_booking_windows($place['id']) : null,
  ] : [];
@endphp
@if ($room)
  <div {!! $wrapper !!}>
    <section class="section section--first">
      <div class="container">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb', 'cobbleandcandle') }}">
          <ol>
            @foreach ($crumbs as [$label, $url])
              @if ($url !== '')
                <li><a href="{!! esc_url($url) !!}">{{ $label }}</a></li>
              @else
                <li aria-current="page">{{ $label }}</li>
              @endif
            @endforeach
          </ol>
        </nav>
        <header class="ev-head">
          {{-- translators: %s: house (location) name --}}
          <p class="eyebrow">{{ $place ? sprintf(__('Rooms at %s', 'cobbleandcandle'), $place['name']) : __('Rooms & stays', 'cobbleandcandle') }}</p>
          <h1 class="h1 h1--page">{{ $room['name'] }}</h1>
          @if ($room['excerpt'] !== '' && ! $locked)
            <p class="lede">{{ $room['excerpt'] }}</p>
          @endif
          <ul class="room-facts room-facts--lg">
            @if ($room['beds'] !== '')
              <li><x-icon name="bed" />{{ $room['beds'] }}</li>
            @endif
            <li><x-icon name="users" />{{ sprintf(_n('Up to %d guest', 'Up to %d guests', $room['max_guests'], 'cobbleandcandle'), $room['max_guests']) }}</li>
            @if ($room['size'] !== '')
              <li><x-icon name="size" />{{ $room['size'] }}</li>
            @endif
          </ul>
        </header>
        <x-media :image-id="$room['image_id']" kind="room" ratio="r-16x9" class="ev-hero" size="full" eager />
        <div class="ev-layout">
          <div class="prose">
            @if ($locked)
              {!! get_the_password_form($post) !!}
            @elseif (trim($post->post_content) !== '' && ! has_block('cobbleandcandle/room-details', $post))
              {{-- has_block(): this block inside its own room's content would render itself forever. --}}
              <h2 class="h3">{{ __('About the room', 'cobbleandcandle') }}</h2>
              {!! apply_filters('the_content', $post->post_content) !!}
            @endif
            @if ($room['amenities'])
              <h2 class="h3">{{ __('In the room', 'cobbleandcandle') }}</h2>
              <ul class="ticks amenities">@foreach ($room['amenities'] as $amenity)<li>{{ $amenity }}</li>@endforeach</ul>
            @endif
            @if ($notes)
              <h2 class="h3">{{ __('Good to know', 'cobbleandcandle') }}</h2>
              <ul class="ticks">@foreach ($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
            @endif
          </div>

          @unless ($locked)
          <aside class="card ticket stay" id="book" aria-labelledby="{{ $uid }}-h">
            <h2 class="h4" id="{{ $uid }}-h">{{ __('Check dates & book', 'cobbleandcandle') }}</h2>
            @if ($room['price_night'] > 0)
              <p class="ticket-price">{{ cobble_money($room['price_night']) }} <small class="muted">{{ __('a night', 'cobbleandcandle') }}</small></p>
              @if ($room['price_weekend'] > 0 && $room['price_weekend'] !== $room['price_night'])
                {{-- translators: %s: weekend nightly price --}}
                <p class="muted stay-weekend">{{ sprintf(__('Friday & Saturday nights %s', 'cobbleandcandle'), cobble_money($room['price_weekend'])) }}</p>
              @endif
            @endif
            @if (isset($messages[$status]))
              <p class="{{ $status === 'sent' ? 'form-ok' : 'form-error' }}" role="status"><x-icon :name="$status === 'sent' ? 'check' : 'info'" /> {{ $messages[$status] }}</p>
            @endif

            <form class="stay-form" method="post" action="{!! esc_url(admin_url('admin-post.php')) !!}" x-data="stayPicker({{ wp_json_encode($picker) }})"
                  {{-- translators: %d: minimum number of nights --}}
                  data-min="{{ sprintf(_n('Minimum stay %d night.', 'Minimum stay %d nights.', $room['min_nights'], 'cobbleandcandle'), $room['min_nights']) }}"
                  data-taken="{{ __('Some of those nights are taken. Please choose other dates.', 'cobbleandcandle') }}"
                  {{-- translators: %d: number of nights --}}
                  data-nights="{{ __('%d night', 'cobbleandcandle') }}" data-nights-plural="{{ __('%d nights', 'cobbleandcandle') }}"
                  data-free="{{ __('available', 'cobbleandcandle') }}" data-full="{{ __('booked', 'cobbleandcandle') }}">
              <input type="hidden" name="action" value="cobble_room_booking">
              <input type="hidden" name="cobble_room" value="{{ $room['id'] }}">
              {!! wp_nonce_field('cobble_room_booking', 'cobble_room_booking_nonce', true, false) !!}
              <div class="sr" aria-hidden="true"><label for="{{ $uid }}-web">{{ __('Leave this empty', 'cobbleandcandle') }}</label><input id="{{ $uid }}-web" type="text" name="cobble_website" tabindex="-1" autocomplete="off"></div>

              <div class="cal" x-cloak x-show="ready" role="group" aria-label="{{ __('Availability calendar', 'cobbleandcandle') }}">
                <div class="cal-nav">
                  <button type="button" class="cal-btn" @click="shift(-1)" :disabled="offset === 0" aria-label="{{ __('Previous month', 'cobbleandcandle') }}"><x-icon name="chev-left" /></button>
                  <button type="button" class="cal-btn" @click="shift(1)" :disabled="atEnd" aria-label="{{ __('Next month', 'cobbleandcandle') }}"><x-icon name="chev-right" /></button>
                </div>
                <template x-for="month in months" :key="month.key">
                  <div class="cal-m">
                    <p class="cal-t" x-text="month.label"></p>
                    <div class="cal-g">
                      <template x-for="(w, i) in weekdays" :key="i"><span class="cal-w" aria-hidden="true" x-text="w"></span></template>
                      <template x-for="n in month.blank" :key="'b' + n"><span></span></template>
                      <template x-for="day in month.days" :key="day.iso">
                        <button type="button" class="cal-d" :class="day.cls" :disabled="day.disabled" :aria-pressed="day.selected.toString()" :aria-label="day.label" @click="pick(day.iso)" x-text="day.n"></button>
                      </template>
                    </div>
                  </div>
                </template>
                <p class="cal-key"><span class="cal-k cal-k--free"></span>{{ __('Free', 'cobbleandcandle') }} <span class="cal-k cal-k--full"></span>{{ __('Booked', 'cobbleandcandle') }} <span class="cal-k cal-k--sel"></span>{{ __('Your stay', 'cobbleandcandle') }}</p>
              </div>

              <div class="form-grid">
                <div class="field"><label for="{{ $uid }}-in">{{ __('Check-in', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $uid }}-in" name="cobble_check_in" type="date" min="{{ wp_date('Y-m-d') }}" required x-model="checkIn" @change="clearOut()"></div>
                <div class="field"><label for="{{ $uid }}-out">{{ __('Check-out', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $uid }}-out" name="cobble_check_out" type="date" min="{{ wp_date('Y-m-d', strtotime('+1 day')) }}" required x-model="checkOut" :min="minOut"></div>
              </div>
              <p class="hint" aria-live="polite" x-text="problem"></p>
              <div class="stay-total" x-show="total" x-cloak aria-live="polite"><span x-text="nightsLabel"></span><b x-text="total"></b></div>

              <div class="field"><label for="{{ $uid }}-guests">{{ __('Guests', 'cobbleandcandle') }}</label><div class="select"><select id="{{ $uid }}-guests" name="cobble_guests">@for ($n = 1; $n <= $room['max_guests']; $n++)<option value="{{ $n }}" @selected($n === min(2, $room['max_guests']))>{{ sprintf(_n('%d guest', '%d guests', $n, 'cobbleandcandle'), $n) }}</option>@endfor</select><x-icon name="chev-down" /></div></div>
              <div class="field"><label for="{{ $uid }}-name">{{ __('Full name', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $uid }}-name" name="cobble_name" type="text" autocomplete="name" required></div>
              <div class="field"><label for="{{ $uid }}-email">{{ __('Email', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $uid }}-email" name="cobble_email" type="email" autocomplete="email" required></div>
              <div class="field"><label for="{{ $uid }}-tel">{{ __('Phone', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $uid }}-tel" name="cobble_phone" type="tel" autocomplete="tel" required></div>
              @if ($place && function_exists('cobble_booking_windows'))
                <fieldset class="dine">
                  <legend class="sr">{{ __('Dinner on arrival', 'cobbleandcandle') }}</legend>
                  <label class="check"><input type="checkbox" name="cobble_dinner" value="1" x-model="dinner"><span>{{ sprintf(__('Add dinner at %s on your first night', 'cobbleandcandle'), $place['name']) }}</span></label>
                  <div class="field" x-show="dinner" x-cloak>
                    <label for="{{ $uid }}-dine">{{ __('Table time', 'cobbleandcandle') }}</label>
                    <div class="select"><select id="{{ $uid }}-dine" name="cobble_dinner_time" x-model="dinnerTime" :disabled="!dinner" :required="dinner">
                      <option value="">{{ __('Choose a time', 'cobbleandcandle') }}</option>
                      <template x-for="slot in dinnerSlots" :key="slot.value"><option :value="slot.value" x-text="slot.label"></option></template>
                    </select><x-icon name="chev-down" /></div>
                    <p class="hint" x-show="checkIn && !dinnerSlots.length">{{ __('The kitchen is closed that night. Ask us about a late supper tray.', 'cobbleandcandle') }}</p>
                    <p class="hint" x-show="!checkIn">{{ __('Pick your dates first.', 'cobbleandcandle') }}</p>
                  </div>
                  <noscript><div class="field"><label for="{{ $uid }}-dine-ns">{{ __('Table time (HH:MM)', 'cobbleandcandle') }}</label><input id="{{ $uid }}-dine-ns" name="cobble_dinner_time" type="time" step="1800"></div></noscript>
                </fieldset>
              @endif
              <div class="field"><label for="{{ $uid }}-msg">{{ __('Arrival time or requests', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><textarea id="{{ $uid }}-msg" name="cobble_message" rows="3"></textarea></div>
              <button class="btn btn--primary btn--block" type="submit" :disabled="!valid"><x-icon name="calendar" /><span>{{ __('Request this stay', 'cobbleandcandle') }}</span></button>
              <p class="hint">{{ __('This is a request: we confirm every stay personally. No card needed.', 'cobbleandcandle') }}</p>
            </form>
          </aside>
          @endunless
        </div>
      </div>
    </section>
    @if ($related)
      <section class="section section--alt">
        <div class="container">
          <x-section-head :eyebrow="__('More rooms', 'cobbleandcandle')" :title="__('Other places to rest', 'cobbleandcandle')" :link="get_post_type_archive_link('cobble_room')" :link-label="__('All rooms', 'cobbleandcandle')" />
          <div class="grid-3 room-grid">
            @foreach ($related as $item)
              <x-room-card :room="$item" />
            @endforeach
          </div>
        </div>
      </section>
    @endif
  </div>
@endif
@if (! $room && \App\is_editor_preview())
  <div {!! $wrapper !!}><p class="empty"><x-icon name="bed" /> {{ __('Room Details shows the room being viewed: its photo, amenities and the booking calendar.', 'cobbleandcandle') }}</p></div>
@endif
