<?php
/**
 * Title: Home — Bed & Breakfast
 * Slug: cobbleandcandle/home-bnb
 * Categories: cobbleandcandle, featured
 * Description: A B&B or inn home page: hero, rooms with live availability, breakfast and supper hours, the morning table, story, what’s on, reviews and gallery.
 * Keywords: home, landing, bed and breakfast, inn, rooms
 * Block Types: core/post-content
 * Viewport Width: 1440
 */
?>
<!-- wp:cobbleandcandle/hero {"align": "full", "caption": "The Lamplighter room at first light"} -->
<!-- wp:paragraph {"className": "eyebrow eyebrow--hero"} -->
<p class="eyebrow eyebrow--hero"><?php echo esc_html(\App\brand('est') !== ''
    /* translators: %s: year established (Settings → Restaurant) */
    ? sprintf(__('Rooms & breakfast · Since %s', 'cobbleandcandle'), \App\brand('est'))
    : __('Rooms & breakfast', 'cobbleandcandle')); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 1, "className": "h1 hero-h"} -->
<h1 class="wp-block-heading h1 hero-h"><?php echo wp_kses_post(__('Sleep above the <em>supper room</em>, wake to the harbour.', 'cobbleandcandle')); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className": "lede"} -->
<p class="lede"><?php echo esc_html__('Four rooms under the old merchant’s roof, a breakfast cooked from the same kitchen as supper, and the whole Old Town on foot from the front door.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/rooms/')); ?>"><?php echo esc_html__('Check availability', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/reservations/')); ?>"><?php echo esc_html__('Book supper', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/hero -->

<!-- wp:cobbleandcandle/rooms-list {"align": "full", "count": 4, "eyebrow": "<?php echo esc_attr__('The rooms', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('Four rooms, one roof', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Beams, brass beds and the quiet of a street that closes at dusk. Breakfast is included; supper is downstairs.', 'cobbleandcandle'); ?>", "linkLabel": "<?php echo esc_attr__('Rooms and availability', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/hours-strip {"align": "full", "tonightNote": "<?php echo esc_attr__('Breakfast 7:30–10 · Supper from 6', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/chef-picks {"align": "full", "eyebrow": "<?php echo esc_attr__('Breakfast', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('The morning table', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Cooked to order from seven-thirty. Eggs from the walled garden, bread from the cellar oven, coffee from the roaster two doors down.', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/story {"align": "full", "art": "room", "caption": "<?php echo esc_attr__('The Hayloft · Restored 2019', 'cobbleandcandle'); ?>", "showStats": false} -->
<!-- wp:paragraph {"className": "eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('The house', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 2, "className": "h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('A merchant’s house with the lamps still lit', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('The rooms upstairs were the lamplighter’s family’s for a century. We kept the floors, the shutters and the view, and added the things a long day asks for: deep baths, good linen, and no clocks.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Guests eat downstairs by candlelight, and a table is held for every room on the first night. The harbour walk starts at the door; the cathedral bells will wake you if we don’t.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:quote {"className": "pull"} -->
<blockquote class="wp-block-quote pull"><!-- wp:paragraph -->
<p><?php echo esc_html__('A good inn is a good kitchen with beds above it.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html__('Margot Ellery', 'cobbleandcandle'); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/story/')); ?>"><?php echo esc_html__('Read our story', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/story -->

<!-- wp:cobbleandcandle/events-list {"align": "full", "eyebrow": "<?php echo esc_attr__('While you stay', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('Suppers and evenings', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Guests get first call on every cellar supper and concert in the parlour.', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/reviews {"align": "full", "title": "<?php echo esc_attr__('From the guest book', 'cobbleandcandle'); ?>"} -->
<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Dinner by candlelight, then up one flight to the best sleep of the year. We never found a reason to leave the building.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Anna &amp; Luis</strong> ★★★★★ · Stayed in The Lamplighter', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Breakfast alone is worth the booking. The eggs, the bread, the view of the boats going out.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Harbour &amp; Hearth Magazine</strong> Inn of the Year', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Quiet, warm, and five minutes from everything. Our room had a bath you could sail in.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Priya S., guest</strong> ★★★★★ · Google review', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->
<!-- /wp:cobbleandcandle/reviews -->

<!-- wp:cobbleandcandle/gallery-mosaic {"align": "full", "title": "<?php echo esc_attr__('Rooms and mornings', 'cobbleandcandle'); ?>"} /-->
