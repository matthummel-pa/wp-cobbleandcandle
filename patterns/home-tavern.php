<?php
/**
 * Title: Home — Tavern
 * Slug: cobbleandcandle/home-tavern
 * Categories: cobbleandcandle, featured
 * Description: A tavern or bar home page: hero, hours, the bar menu and cellar board, what’s on, bar plates, story, reviews, gallery and hiring the snug.
 * Keywords: home, landing, tavern, bar, pub
 * Block Types: core/post-content
 * Viewport Width: 1440
 */
?>
<!-- wp:cobbleandcandle/hero {"align": "full", "caption": "The taproom at last orders"} -->
<!-- wp:paragraph {"className": "eyebrow eyebrow--hero"} -->
<p class="eyebrow eyebrow--hero"><?php echo esc_html(\App\brand('est') !== ''
    /* translators: %s: year established (Settings → Restaurant) */
    ? sprintf(__('Ale & embers · Since %s', 'cobbleandcandle'), \App\brand('est'))
    : __('Ale & embers', 'cobbleandcandle')); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 1, "className": "h1 hero-h"} -->
<h1 class="wp-block-heading h1 hero-h"><?php echo wp_kses_post(__('Good ale, <em>long tables</em> and a fire that never goes out.', 'cobbleandcandle')); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className": "lede"} -->
<p class="lede"><?php echo esc_html__('Cask ales from the harbour breweries, a cellar of old-world wine, and plates built for sharing. Kitchen till ten, bar till late, music on the weekend.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/reservations/')); ?>"><?php echo esc_html__('Book a table', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/events/')); ?>"><?php echo esc_html__('See what’s on', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/hero -->

<!-- wp:cobbleandcandle/hours-strip {"align": "full", "tonightNote": "<?php echo esc_attr__('Kitchen till 10 · Bar till late', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/menu-teaser {"align": "full", "eyebrow": "<?php echo esc_attr__('The bar', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('From the taps and the cellar', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Six casks on rotation, a wall of bottles and a wine list the cellarman argues about daily.', 'cobbleandcandle'); ?>", "boardEyebrow": "<?php echo esc_attr__('On tonight', 'cobbleandcandle'); ?>", "boardTitle": "<?php echo esc_attr__('The board', 'cobbleandcandle'); ?>", "boardFoot": "<?php echo esc_attr__('Ask at the bar', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/events-list {"align": "full", "eyebrow": "<?php echo esc_attr__('Music & nights', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('What’s on this week', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Quartets in the parlour, quiz on Tuesdays and the odd late lock-in for the regulars.', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/chef-picks {"align": "full", "eyebrow": "<?php echo esc_attr__('Bar plates', 'cobbleandcandle'); ?>", "title": "<?php echo esc_attr__('Plates for the long table', 'cobbleandcandle'); ?>", "intro": "<?php echo esc_attr__('Three things to order with a pint, because nobody has ever regretted them.', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/story {"align": "full", "reverse": true, "caption": "<?php echo esc_attr__('The cellarman · Keeper of the casks since 1998', 'cobbleandcandle'); ?>"} -->
<!-- wp:paragraph {"className": "eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('The house', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 2, "className": "h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('A sailors’ tavern, still open past the last tide', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('The Wharf poured its first pint in 1888 for crews coming off the evening boats. The long oak table came off a wreck; the fire has been lit every night since.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Today the casks come from three harbour breweries, the cellar from older friends across the water, and the kitchen keeps the same rule as always: nothing on a plate that doesn’t go with a drink.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:quote {"className": "pull"} -->
<blockquote class="wp-block-quote pull"><!-- wp:paragraph -->
<p><?php echo esc_html__('A tavern is a kitchen with better conversation.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html__('Tom Ellery, landlord', 'cobbleandcandle'); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className": "is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/story/')); ?>"><?php echo esc_html__('Read our story', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/story -->

<!-- wp:cobbleandcandle/reviews {"align": "full", "title": "<?php echo esc_attr__('Word at the bar', 'cobbleandcandle'); ?>"} -->
<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('The best-kept cask in the Old Town, and a fire you can sit at until they turn the lamps down.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>The Old Town Courier</strong> ★★★★★ · Pub of the Year 2025', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Came for one pint on a Tuesday, left after the quiz with a table of new friends.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Dan R., regular</strong> ★★★★★ · Google review', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html__('Scotch eggs, a quartet in the corner and a landlord who remembers your name. That is the whole review.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --><cite><?php echo wp_kses_post(__('<strong>Harbour &amp; Hearth Magazine</strong> Critic’s choice', 'cobbleandcandle')); ?></cite></blockquote>
<!-- /wp:quote -->
<!-- /wp:cobbleandcandle/reviews -->

<!-- wp:cobbleandcandle/gallery-mosaic {"align": "full", "title": "<?php echo esc_attr__('Inside the tavern', 'cobbleandcandle'); ?>"} /-->

<!-- wp:cobbleandcandle/private-dining {"align": "full"} -->
<!-- wp:paragraph {"className": "eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('Hire the snug', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level": 2, "className": "h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('Your own corner of the tavern', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Birthdays, wakes, leaving dos and the occasional wedding breakfast. A room, a fire, a bar of your own and a menu we agree over a pint.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className": "rooms"} -->
<ul class="wp-block-list rooms"><!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Snug</strong> Seats 10', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Long Table</strong> Seats 22', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo wp_kses_post(__('<strong>The Cellar Bar</strong> Standing 60', 'cobbleandcandle')); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- /wp:cobbleandcandle/private-dining -->
