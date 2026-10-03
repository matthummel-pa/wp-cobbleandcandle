{{-- Contact Form (HANDOFF §3 Locations): intro + direct contacts, and a form posting to the Core plugin. --}}
@php
  $topics = function_exists('cobble_contact_topics') ? cobble_contact_topics() : [];
  $locations = \App\locations();
  $current = \App\current_location();
  $contacts = [];
  foreach (array_filter(array_map('trim', explode("\n", $attributes['contacts']))) as $line) {
      [$label, $value] = array_pad(array_map('trim', explode(':', $line, 2)), 2, '');
      $contacts[] = [$label, $value];
  }
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag after redirect.
  $status = isset($_GET['contact']) ? sanitize_key(wp_unslash($_GET['contact'])) : '';
  $messages = [
      'sent' => __('Thanks, your message is on its way.', 'cobbleandcandle'),
      'invalid' => __('Please fill in the required fields and try again.', 'cobbleandcandle'),
      'expired' => __('This form had expired. Please send it again.', 'cobbleandcandle'),
      'busy' => __('Too many messages from this connection. Please wait a few minutes or call us.', 'cobbleandcandle'),
      'error' => __('We couldn’t send your message. Please call us instead.', 'cobbleandcandle'),
  ];
  $privacy = get_privacy_policy_url();
  $fid = wp_unique_id('cf-');
@endphp
@if ($topics)
  <div {!! $wrapper !!}>
    <section class="section section--alt" id="contact" aria-labelledby="{{ $fid }}-title">
      <div class="container contact">
        <div>
          <p class="eyebrow">{{ $attributes['eyebrow'] }}</p>
          <h2 class="h2" id="{{ $fid }}-title">{{ $attributes['title'] }}</h2>
          @if ($attributes['intro'] !== '')
            <p>{{ $attributes['intro'] }}</p>
          @endif
          @if ($contacts)
            <ul class="notes">
              @foreach ($contacts as [$label, $value])
                <li><x-icon :name="is_email($value) ? 'mail' : 'info'" /><div><b>{{ $label }}</b><p>@if (is_email($value))<a href="mailto:{{ $value }}">{{ $value }}</a>@else{{ $value }}@endif</p></div></li>
              @endforeach
            </ul>
          @endif
        </div>
        <form class="card form" method="post" action="{{ admin_url('admin-post.php') }}" aria-labelledby="{{ $fid }}-h">
          <h3 class="h3" id="{{ $fid }}-h">{{ __('Send a message', 'cobbleandcandle') }}</h3>
          @if (isset($messages[$status]))
            <p class="{{ $status === 'sent' ? 'form-ok' : 'form-error' }}" role="status"><x-icon :name="$status === 'sent' ? 'check' : 'info'" /> {{ $messages[$status] }}</p>
          @endif
          <input type="hidden" name="action" value="cobble_contact">
          {!! wp_nonce_field('cobble_contact', 'cobble_contact_nonce', true, false) !!}
          <div class="sr" aria-hidden="true"><label for="{{ $fid }}-web">{{ __('Leave this empty', 'cobbleandcandle') }}</label><input id="{{ $fid }}-web" type="text" name="cobble_website" tabindex="-1" autocomplete="off"></div>
          <fieldset class="topics">
            <legend>{{ __('Topic', 'cobbleandcandle') }}</legend>
            <div class="fchips">
              @foreach ($topics as $key => $topic)
                <label class="fchip"><input type="radio" name="cobble_topic" value="{{ $key }}" @checked($loop->first)><span>{{ $topic }}</span></label>
              @endforeach
            </div>
          </fieldset>
          <div class="form-grid">
            <div class="field"><label for="{{ $fid }}-name">{{ __('Full name', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-name" name="cobble_name" type="text" autocomplete="name" required></div>
            <div class="field"><label for="{{ $fid }}-email">{{ __('Email', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-email" name="cobble_email" type="email" autocomplete="email" required></div>
            <div class="field"><label for="{{ $fid }}-tel">{{ __('Phone', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><input id="{{ $fid }}-tel" name="cobble_phone" type="tel" autocomplete="tel"></div>
            @if ($locations)
              <div class="field"><label for="{{ $fid }}-loc">{{ __('Location', 'cobbleandcandle') }}</label><div class="select"><select id="{{ $fid }}-loc" name="cobble_location">@foreach ($locations as $l)<option value="{{ $l['id'] }}" @selected($l['id'] === ($current['id'] ?? 0))>{{ $l['name'] }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
            @endif
            <div class="field field--full"><label for="{{ $fid }}-msg">{{ __('Message', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><textarea id="{{ $fid }}-msg" name="cobble_message" rows="4" required></textarea></div>
          </div>
          <label class="check"><input type="checkbox" name="cobble_consent" value="1" required><span>
            @if ($privacy !== '')
              {!! wp_kses(sprintf(__('I agree to the <a href="%s">privacy policy</a>.', 'cobbleandcandle'), esc_url($privacy)), ['a' => ['href' => []]]) !!}
            @else
              {{ __('I agree to my details being used to reply to this message.', 'cobbleandcandle') }}
            @endif
            <span class="req" aria-hidden="true">*</span></span></label>
          <button class="btn btn--primary" type="submit">{{ __('Send message', 'cobbleandcandle') }}</button>
        </form>
      </div>
    </section>
  </div>
@endif
