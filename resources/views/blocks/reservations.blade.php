{{-- Reservations (HANDOFF §3): location cards, then a booking panel per location's booking mode
     (native request form, OpenTable/Resy widget behind a facade, or call to book), plus a sidebar. --}}
@php
  $locations = \App\locations();
  $current = \App\current_location();
  $choices = function_exists('cc_reservation_choices') ? cc_reservation_choices() : null;
  $notes = array_filter(array_map('trim', explode("\n", $attributes['notes'])));
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag after redirect.
  $status = isset($_GET['reservation']) ? sanitize_key(wp_unslash($_GET['reservation'])) : '';
  $messages = [
      'sent' => __('Request sent. The house will confirm your table by email or phone.', 'cobbleandcandle'),
      'invalid' => __('That time isn’t available or a detail is missing. Please check and try again.', 'cobbleandcandle'),
      'expired' => __('This form had expired. Please send it again.', 'cobbleandcandle'),
      'busy' => __('Too many messages from this connection. Please wait a few minutes or call us.', 'cobbleandcandle'),
      'error' => __('We couldn’t send your request. Please call the house instead.', 'cobbleandcandle'),
  ];
  $uid = wp_unique_id('resv-');
@endphp
@if ($locations)
  <div {!! $wrapper !!}>
    <section class="section" id="book" x-data>
      <div class="container resv">
        <div class="resv-main">
          <fieldset class="lcards">
            <legend class="eyebrow">{{ __('1 · Choose a house', 'cobbleandcandle') }}</legend>
            <div class="lcards-g">
              @foreach ($locations as $i => $l)
                <button type="button" class="lcard" aria-pressed="{{ $l['id'] === $current['id'] ? 'true' : 'false' }}"
                        :aria-pressed="($store.site.current === {{ $i }}).toString()" @click="$store.site.setLocation({{ $i }})">
                  <span class="lcard-n">{{ $l['name'] }}</span>
                  <x-status :status="$l['status']" size="sm" />
                  <span class="lcard-b">{{ ['native' => __('Book online', 'cobbleandcandle'), 'opentable' => __('Book via OpenTable', 'cobbleandcandle'), 'resy' => __('Book via Resy', 'cobbleandcandle'), 'call' => __('Book by phone', 'cobbleandcandle')][$l['booking_mode'] ?: 'native'] ?? '' }}</span>
                  <x-icon name="check" class="i lcard-check" />
                </button>
              @endforeach
            </div>
          </fieldset>

          <div class="card resv-card">
            <p class="eyebrow">{{ __('2 · Find a table', 'cobbleandcandle') }}</p>
            @if (isset($messages[$status]))
              <p class="{{ $status === 'sent' ? 'form-ok' : 'form-error' }}" role="status"><x-icon :name="$status === 'sent' ? 'check' : 'info'" /> {{ $messages[$status] }}</p>
            @endif
            @foreach ($locations as $i => $l)
              @php
                $mode = $l['booking_mode'] ?: 'native';
                if ($mode === 'native' && ! $choices) { $mode = 'call'; }
                if (in_array($mode, ['opentable', 'resy'], true) && $l['booking_url'] === '') { $mode = 'call'; }
                $fid = $uid.'-'.$i;
              @endphp
              <div class="resv-panel" @if ($l['id'] !== $current['id']) hidden @endif :hidden="$store.site.current !== {{ $i }}">
                <h2 class="h3 resv-h"><x-icon name="calendar" /> {{ sprintf(__('Book at %s', 'cobbleandcandle'), $l['name']) }}</h2>

                @if ($mode === 'native')
                  <form class="resv-form" method="post" action="{{ admin_url('admin-post.php') }}" x-data="bookingForm({{ wp_json_encode(cc_booking_windows($l['id'])) }})"
                        data-full="{{ __('No tables left online for this day. Try another date or call us.', 'cobbleandcandle') }}"
                        {{-- translators: 1: party size, 2: time --}}
                        data-submit="{{ __('Request a table for %1$s · %2$s', 'cobbleandcandle') }}" data-submit-empty="{{ __('Choose a time', 'cobbleandcandle') }}">
                    <input type="hidden" name="action" value="cc_reservation">
                    <input type="hidden" name="cc_location" value="{{ $l['id'] }}">
                    {!! wp_nonce_field('cc_reservation', 'cc_reservation_nonce', true, false) !!}
                    <div class="sr" aria-hidden="true"><label for="{{ $fid }}-web">{{ __('Leave this empty', 'cobbleandcandle') }}</label><input id="{{ $fid }}-web" type="text" name="cc_website" tabindex="-1" autocomplete="off"></div>
                    <div class="form-grid form-grid--3">
                      <div class="field"><label for="{{ $fid }}-date">{{ __('Date', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-date" name="cc_date" type="date" min="{{ wp_date('Y-m-d') }}" required x-model="date"></div>
                      <div class="field"><label for="{{ $fid }}-party">{{ __('Party size', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><div class="select"><select id="{{ $fid }}-party" name="cc_party" required x-model="party">@foreach ($choices['party'] as $n)<option value="{{ $n }}" @selected($n === '2')>{{ sprintf(_n('%s guest', '%s guests', (int) $n, 'cobbleandcandle'), $n) }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
                      <div class="field"><label for="{{ $fid }}-seat">{{ __('Seating', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><div class="select"><select id="{{ $fid }}-seat" name="cc_seating">@foreach ($choices['seating'] as $s)<option>{{ $s }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
                    </div>
                    <fieldset class="slots">
                      <legend>{{ __('Times', 'cobbleandcandle') }} <span x-text="dayLabel"></span></legend>
                      <div class="slot-grid">
                        <template x-for="slot in slots" :key="slot.value">
                          <label class="slot"><input type="radio" name="cc_time" required :value="slot.value" x-model="time"><span x-text="slot.label"></span></label>
                        </template>
                      </div>
                      <p class="hint" x-show="!slots.length" x-text="$root.dataset.full"></p>
                      <noscript><div class="field"><label for="{{ $fid }}-time">{{ __('Time (HH:MM)', 'cobbleandcandle') }}</label><input id="{{ $fid }}-time" name="cc_time" type="time" step="1800"></div></noscript>
                    </fieldset>
                    <div class="form-grid">
                      <div class="field"><label for="{{ $fid }}-name">{{ __('Full name', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-name" name="cc_name" type="text" autocomplete="name" required></div>
                      <div class="field"><label for="{{ $fid }}-tel">{{ __('Phone', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-tel" name="cc_phone" type="tel" autocomplete="tel" required aria-describedby="{{ $fid }}-tel-h"><p class="hint" id="{{ $fid }}-tel-h">{{ __('Only used if we need to reach you about this booking.', 'cobbleandcandle') }}</p></div>
                      <div class="field"><label for="{{ $fid }}-email">{{ __('Email', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-email" name="cc_email" type="email" autocomplete="email" required></div>
                      <div class="field"><label for="{{ $fid }}-occ">{{ __('Occasion', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><div class="select"><select id="{{ $fid }}-occ" name="cc_occasion">@foreach ($choices['occasions'] as $o)<option>{{ $o }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
                      <div class="field field--full"><label for="{{ $fid }}-req">{{ __('Requests or allergies', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><textarea id="{{ $fid }}-req" name="cc_requests" rows="3"></textarea></div>
                    </div>
                    <label class="check"><input type="checkbox" name="cc_newsletter" value="1"><span>{{ __('Send me the monthly newsletter', 'cobbleandcandle') }}</span></label>
                    <button class="btn btn--primary btn--block" type="submit" :disabled="!time"><x-icon name="calendar" /><span x-text="submitLabel">{{ __('Request a table', 'cobbleandcandle') }}</span></button>
                    <p class="hint">{{ __('This is a request: the house confirms every table personally.', 'cobbleandcandle') }}</p>
                  </form>

                @elseif ($mode === 'call')
                  <div class="callbook">
                    <x-icon name="phone" class="i i--xl i--accent" />
                    <h3 class="h3">{{ __('Bookings by phone', 'cobbleandcandle') }}</h3>
                    <p>{{ sprintf(__('%s takes every booking by phone so we can seat you well.', 'cobbleandcandle'), $l['name']) }}</p>
                    @if ($l['phone'] !== '')
                      <a class="callbook-n" href="{{ esc_url($l['tel']) }}">{{ $l['phone'] }}</a>
                    @endif
                    @if ($attributes['callHours'] !== '')
                      <p class="muted">{{ $attributes['callHours'] }}</p>
                    @endif
                    <div class="cta-row cta-row--c">
                      @if ($l['tel'] !== '')
                        <x-button :href="$l['tel']" icon="phone">{{ __('Call now', 'cobbleandcandle') }}</x-button>
                      @endif
                      @if ($l['email'] !== '')
                        <x-button :href="'mailto:'.$l['email']" variant="secondary" icon="mail">{{ __('Email us', 'cobbleandcandle') }}</x-button>
                      @endif
                    </div>
                  </div>

                @else
                  @php($provider = $mode === 'resy' ? 'Resy' : 'OpenTable')
                  {{-- Facade: the provider's widget only loads when asked, keeping the page fast and private. --}}
                  <div class="embed" x-data="{ loaded: false }" :aria-busy="loaded.toString()">
                    <div class="embed-top"><span class="embed-logo">{{ $provider }}</span><span class="chip chip--quiet" x-show="!loaded">{{ __('Live availability', 'cobbleandcandle') }}</span></div>
                    <template x-if="loaded">
                      <iframe class="embed-frame" src="{{ esc_url($l['booking_url']) }}" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" title="{{ sprintf(__('%1$s booking for %2$s', 'cobbleandcandle'), $provider, $l['name']) }}" loading="lazy" style="width:100%;min-height:560px;border:0"></iframe>
                    </template>
                    <div x-show="!loaded">
                      <div class="sk sk--w60"></div><div class="sk-row"><div class="sk"></div><div class="sk"></div><div class="sk"></div></div>
                      <x-button icon="calendar" class="btn--block" x-on:click="loaded = true">{{ __('Check availability', 'cobbleandcandle') }}</x-button>
                    </div>
                    <p class="embed-note"><x-icon name="info" /> {!! wp_kses(sprintf(__('Booking loads from %1$s. Trouble? <a href="%2$s">Book on %1$s</a> or call %3$s.', 'cobbleandcandle'), esc_html($provider), esc_url($l['booking_url']), esc_html($l['phone'])), ['a' => ['href' => []]]) !!}</p>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </div>

        <aside class="resv-side">
          @foreach ($locations as $i => $l)
            <div class="resv-side-in" @if ($l['id'] !== $current['id']) hidden @endif :hidden="$store.site.current !== {{ $i }}">
              <div class="card">
                <h2 class="h4"><x-icon name="clock" /> {{ sprintf(__('Hours at %s', 'cobbleandcandle'), $l['name']) }}</h2>
                <x-status :status="$l['status']" />
                <x-hours :rows="$l['hours']" />
              </div>
              @if ($notes)
                <div class="card">
                  <h2 class="h4"><x-icon name="info" /> {{ __('Good to know', 'cobbleandcandle') }}</h2>
                  <ul class="ticks">@foreach ($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
                </div>
              @endif
              <div class="card">
                <h2 class="h4"><x-icon name="pin" /> {{ __('Getting there', 'cobbleandcandle') }}</h2>
                <p>{{ $l['address'] }}</p>
                @php($parking = (string) get_post_meta($l['id'], 'cc_parking', true))
                @if ($parking !== '')
                  <p class="muted">{{ $parking }}</p>
                @endif
                @if ($l['map_url'] !== '')
                  <a class="link-arrow" href="{{ esc_url($l['map_url']) }}">{{ __('Directions', 'cobbleandcandle') }}<x-icon name="arrow" /></a>
                @endif
              </div>
            </div>
          @endforeach
        </aside>
      </div>
    </section>
  </div>
@endif
