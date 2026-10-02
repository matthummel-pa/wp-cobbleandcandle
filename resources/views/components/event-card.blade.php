{{-- Event card (.ev) with datebox. --}}
@props(['event' => [], 'heading' => 'h3'])
<article class="ev">
  @if ($event['day'] !== '')
    <div class="datebox" aria-hidden="true"><span class="db-w">{{ $event['weekday'] }}</span><span class="db-d">{{ $event['day'] }}</span><span class="db-m">{{ $event['month'] }}</span></div>
  @endif
  <div class="ev-b">
    @if ($event['type'] !== '')
      <p class="ev-type">{{ $event['type'] }}</p>
    @endif
    <{{ $heading }} class="ev-t">{{ $event['title'] }}</{{ $heading }}>
    <p class="ev-meta">
      @if ($event['iso'] !== '')
        <x-icon name="clock" /><time datetime="{{ $event['iso'] }}">{{ $event['when'] }}</time>
      @endif
      @if ($event['location'] !== '')
        <br><x-icon name="pin" />{{ $event['location'] }}
      @endif
    </p>
    @if ($event['excerpt'] !== '')
      <p class="ev-d">{{ $event['excerpt'] }}</p>
    @endif
    <div class="ev-f">
      @if ($event['price'] !== '')
        <span class="ev-price">{{ $event['price'] }}</span>
      @endif
      @if ($event['availability'] !== '')
        <span class="chip chip--quiet">{{ $event['availability'] }}</span>
      @endif
      <a class="link-arrow" href="{{ $event['url'] }}">{{ __('Details', 'cobbleandcandle') }}<x-icon name="arrow" /><span class="sr"> {{ sprintf(__('about %s', 'cobbleandcandle'), $event['title']) }}</span></a>
    </div>
  </div>
</article>
