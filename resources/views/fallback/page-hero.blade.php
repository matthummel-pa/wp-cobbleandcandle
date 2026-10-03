{{-- Plain page heading while Cobble & Candle Core is not active. --}}
<div class="container" style="padding-block:48px 16px">
  <h1 class="h1 h1--page">{{ is_singular() ? \App\plain_title(get_post()) : wp_strip_all_tags(get_the_archive_title()) }}</h1>
</div>
