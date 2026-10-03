{{-- Private dining inquiry form (posts to the Core plugin; HANDOFF §3 form fields, §11 forms). --}}
@php
  $choices = cc_inquiry_choices();
  $locations = \App\locations();
  $current = \App\current_location();
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag after redirect.
  $status = isset($_GET['inquiry']) ? sanitize_key(wp_unslash($_GET['inquiry'])) : '';
  $messages = [
      'sent' => __('Thank you. We’ll be in touch within one working day.', 'cobbleandcandle'),
      'invalid' => __('Please check the highlighted details and try again.', 'cobbleandcandle'),
      'expired' => __('This form had expired. Please send it again.', 'cobbleandcandle'),
      'busy' => __('Too many messages from this connection. Please wait a few minutes or call us.', 'cobbleandcandle'),
      'error' => __('We couldn’t send your inquiry. Please call us instead.', 'cobbleandcandle'),
  ];
  $fid = wp_unique_id('pd-');
@endphp
<form class="card form pd-f" method="post" action="{{ cc_inquiry_form_url() }}" aria-labelledby="{{ $fid }}-h">
  <h3 class="h3" id="{{ $fid }}-h">{{ __('Send an inquiry', 'cobbleandcandle') }}</h3>
  @if (isset($messages[$status]))
    <p class="{{ $status === 'sent' ? 'form-ok' : 'form-error' }}" role="status"><x-icon :name="$status === 'sent' ? 'check' : 'info'" /> {{ $messages[$status] }}</p>
  @endif
  <input type="hidden" name="action" value="cc_inquiry">
  {!! wp_nonce_field('cc_inquiry', 'cc_inquiry_nonce', true, false) !!}
  <div class="sr" aria-hidden="true"><label for="{{ $fid }}-web">{{ __('Leave this empty', 'cobbleandcandle') }}</label><input id="{{ $fid }}-web" type="text" name="cc_website" tabindex="-1" autocomplete="off"></div>
  <div class="form-grid">
    <div class="field"><label for="{{ $fid }}-name">{{ __('Full name', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-name" name="cc_name" type="text" autocomplete="name" required></div>
    <div class="field"><label for="{{ $fid }}-email">{{ __('Email', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-email" name="cc_email" type="email" autocomplete="email" required></div>
    <div class="field"><label for="{{ $fid }}-date">{{ __('Preferred date', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><input id="{{ $fid }}-date" name="cc_date" type="date" min="{{ wp_date('Y-m-d') }}" required></div>
    <div class="field"><label for="{{ $fid }}-guests">{{ __('Guests', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><div class="select"><select id="{{ $fid }}-guests" name="cc_guests" required>@foreach ($choices['guests'] as $g)<option>{{ $g }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
    @if ($locations)
      <div class="field"><label for="{{ $fid }}-loc">{{ __('House', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><div class="select"><select id="{{ $fid }}-loc" name="cc_location" required>@foreach ($locations as $l)<option value="{{ $l['id'] }}" @selected($l['id'] === ($current['id'] ?? 0))>{{ $l['name'] }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
    @endif
    <div class="field"><label for="{{ $fid }}-occ">{{ __('Occasion', 'cobbleandcandle') }} <span class="req" aria-hidden="true">*</span></label><div class="select"><select id="{{ $fid }}-occ" name="cc_occasion" required>@foreach ($choices['occasions'] as $key => $o)<option value="{{ $key }}">{{ $o }}</option>@endforeach</select><x-icon name="chev-down" /></div></div>
    <div class="field field--full"><label for="{{ $fid }}-msg">{{ __('Tell us about your event', 'cobbleandcandle') }} <span class="opt">{{ __('(optional)', 'cobbleandcandle') }}</span></label><textarea id="{{ $fid }}-msg" name="cc_message" rows="4"></textarea></div>
  </div>
  <button class="btn btn--primary btn--block" type="submit">{{ __('Send inquiry', 'cobbleandcandle') }}</button>
  <p class="hint">{{ __('Our events team replies within one working day.', 'cobbleandcandle') }}</p>
</form>
