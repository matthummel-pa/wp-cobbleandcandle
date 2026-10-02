<?php
/**
 * Title: About page
 * Slug: cobbleandcandle/page-about
 * Categories: cobbleandcandle
 * Block Types: core/post-content
 * Post Types: page
 * Description: Your story, a short history, the chef, what you believe, every house, and a call to book.
 */
?>
<!-- wp:cobbleandcandle/story {"caption":"","art":"room","reverse":true,"showStats":false,"align":"full"} -->
<!-- wp:paragraph {"className":"dropcap"} -->
<p class="dropcap"><?php echo esc_html__('In 1888 the Wharf’s lamplighter turned his front parlour into a supper room for sailors coming off the evening tide. The brass lamps he polished every dusk still hang above table four.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Today Chef Margot Ellery cooks from the same coast and the same walled gardens, with a kitchen that runs on wood, patience and a very old copper stockpot.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('We still keep the original ledgers, the brass and the stubborn habit of doing things slowly. What has changed is the cooking: lighter, seasonal and rooted in growers we know by name.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:cobbleandcandle/story -->

<!-- wp:cobbleandcandle/timeline {"align":"full"} /-->

<!-- wp:cobbleandcandle/story {"caption":"Margot Ellery · Chef-patron since 2014","reverse":true,"showStats":false,"align":"full"} -->
<!-- wp:paragraph {"className":"eyebrow"} -->
<p class="eyebrow"><?php echo esc_html__('The kitchen', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('Meet Margot Ellery', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:quote {"className":"pull pull--lg"} -->
<blockquote class="wp-block-quote pull pull--lg"><!-- wp:paragraph -->
<p><?php echo esc_html__('We cook the way the house is lit: slowly, warmly, and with nothing to hide.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph --></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Trained in old harbour kitchens and two seasons abroad, our chef writes the menu each morning with the growers on the phone. The brigade is small, the stockpot is older than any of us, and staff meal is taken seriously.', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sig"} -->
<p class="sig"><?php echo esc_html__('Margot Ellery', 'cobbleandcandle'); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:cobbleandcandle/story -->

<!-- wp:cobbleandcandle/values {"align":"full"} /-->

<!-- wp:cobbleandcandle/houses {"align":"full"} /-->

<!-- wp:cobbleandcandle/cta-band {"align":"full"} -->
<!-- wp:heading {"className":"h2"} -->
<h2 class="wp-block-heading h2"><?php echo esc_html__('Your table is waiting.', 'cobbleandcandle'); ?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(\App\page_link('/reservations/')); ?>"><?php echo esc_html__('Reserve a table', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(home_url('/#private-dining')); ?>"><?php echo esc_html__('Private dining', 'cobbleandcandle'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:cobbleandcandle/cta-band -->
