{{-- Private Dining: copy + rooms typed on the canvas, photo, and the inquiry form (Core plugin). --}}
<div {!! $wrapper !!}>
  <section class="section section--alt texture" id="private-dining">
    <div class="container pd">
      <div class="pd-c">
        {!! $content !!}
        <x-media :image-id="$attributes['imageId']" kind="table" ratio="r-16x9" class="pd-m" />
      </div>
      @includeWhen(function_exists('cobble_inquiry_form_url'), 'partials.inquiry-form')
    </div>
  </section>
</div>
