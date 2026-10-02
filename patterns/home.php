<?php
/**
 * Title: Home
 * Slug: cobbleandcandle/home
 * Categories: cobbleandcandle, featured
 * Description: The full home page: hero, hours, signature dishes, menu teaser, story, events, reviews, gallery and private dining.
 * Keywords: home, landing, restaurant
 * Block Types: core/post-content
 * Viewport Width: 1440
 */
?>
<!-- wp:cobbleandcandle/hero {"align": "full"} -->
<!-- wp:paragraph {"className": "eyebrow eyebrow\u002d\u002dhero"} -->
<p class="eyebrow eyebrow--hero"><?php echo esc_html__('Supper by candlelight · Since 1888', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 1, "className": "h1 hero-h"} -->
<h1 class="wp-block-heading h1 hero-h"><?php echo wp_kses_post(__('Supper by <em>candlelight</em> on the old cobbles.', 'cobbleandcandle')); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className": "lede"} -->
<p class="lede"><?php echo esc_html__('A seasonal tasting menu served in three restored merchant houses, each lit as it was a century ago. Two seatings nightly.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/reservations/')); ?>"><?php echo esc_html__('Reserve a table', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/menu/')); ?>"><?php echo esc_html__('View the menu', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/hero -->

<!-- wp:cobbleandcandle/hours-strip {"align": "full"} /-->

<!-- wp:cobbleandcandle/chef-picks {"align": "full"} /-->

<!-- wp:cobbleandcandle/menu-teaser {"align": "full"} /-->

<!-- wp:cobbleandcandle/story {"align": "full"} -->
<!-- wp:paragraph {"className": "eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('Our story', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 2, "className": "h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('A lamplighter’s house, still lit by hand', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('In 1888 the Wharf’s lamplighter turned his front parlour into a supper room for sailors coming off the evening tide. The brass lamps he polished every dusk still hang above table four.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Today Chef Margot Ellery cooks from the same coast and the same walled gardens, with a kitchen that runs on wood, patience and a very old copper stockpot.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:quote {"className": "pull"} -->
<blockquote class="wp-block-quote pull"><!-- wp:paragraph -->
<p><?php echo esc_html__('We cook the way the house is lit: slowly, warmly, and with nothing to hide.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html__('Margot Ellery', 'cobbleandcandle'); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/story/')); ?>"><?php echo esc_html__('Read our story', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/story -->

<!-- wp:cobbleandcandle/events-list {"align": "full"} /-->

<!-- wp:cobbleandcandle/reviews {"align": "full"} -->
<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('The kind of room that makes you lower your voice and order another bottle. Every plate glowed.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>The Old Town Courier</strong> ★★★★★ · Restaurant of the Year 2025', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Ellery’s duck is reason enough to cross the harbour. The candlelight is just the bonus.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Harbour &amp; Hearth Magazine</strong> Critic’s choice', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('We celebrated our 30th anniversary in the cellar. Faultless, unhurried, unforgettable.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Eleanor W., guest</strong> ★★★★★ · Google review', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->
<!-- /wp:cobbleandcandle/reviews -->

<!-- wp:cobbleandcandle/gallery-mosaic {"align": "full"} /-->

<!-- wp:cobbleandcandle/private-dining {"align": "full"} -->
<!-- wp:paragraph {"className": "eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('Private dining &amp; events', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 2, "className": "h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('Private dining in the Lamp Room', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Up to 28 guests by candlelight, with a dedicated sommelier and a menu written for the occasion.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className": "rooms"} -->
<ul class="wp-block-list rooms"><!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Lamp Room</strong> Seats 28', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Cellar Table</strong> Seats 22', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Snug</strong> Seats 10', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- /wp:cobbleandcandle/private-dining -->
