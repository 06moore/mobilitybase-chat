<?php
/**
 * Single article.
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'mb-article' ); ?>>
		<header class="mb-article__head mb-wrap mb-wrap--narrow">
			<p class="mb-crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>">Articles</a></p>
			<h1><?php the_title(); ?></h1>
			<p class="mb-article__meta">By the Mobilitybase team · <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time> · <?php echo (int) max( 1, round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 220 ) ); ?> min read</p>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="mb-article__hero mb-wrap mb-wrap--narrow"><?php the_post_thumbnail( 'large', array( 'loading' => false, 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>
		<div class="mb-wrap mb-wrap--narrow mb-prose">
			<?php the_content(); ?>
		</div>
		<aside class="mb-wrap mb-wrap--narrow">
			<?php
			$slug = get_post_field( 'post_name' );
			if ( preg_match( '/removal/', $slug ) ) {
				$cta = array( 'Need a stairlift removed?', 'We remove straight and curved stairlifts of any make across Kent and the South East' . ( mb_prices()['removal_straight'] ? ' — from £' . mb_prices()['removal_straight'] . ' + VAT' : '' ) . '.', 'Get a removal quote', '/stairlift-removal-kent#quote' );
			} elseif ( preg_match( '/fault|beep|charging|codes|error/', $slug ) ) {
				$cta = array( 'Still not working?', 'Our engineers repair stairlifts and homelifts of all major makes across Kent, London and the South East. Our Silver and Gold service plans include priority breakdown cover.', 'Book a repair', '/lift-repairs-kent#quote' );
			} else {
				$cta = array( 'Thinking about a stairlift or home lift?', 'Book a free, no-obligation home survey with one of our Kent-based engineers.', 'Book a free survey', '/book-your-visit' );
			}
			?>
			<div class="mb-articlecta">
				<h2><?php echo esc_html( $cta[0] ); ?></h2>
				<p><?php echo esc_html( $cta[1] ); ?></p>
				<p><a class="mb-btn" href="<?php echo esc_url( home_url( $cta[3] ) ); ?>"><?php echo esc_html( $cta[2] ); ?></a> <?php echo mb_tel( 'Call ' . mb_biz( 'phone' ), 'mb-btn mb-btn--outline' ); // phpcs:ignore ?></p>
			</div>
		</aside>
	</article>
	<?php
	$related = new WP_Query( array( 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ), 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
	if ( $related->have_posts() ) :
		?>
		<section class="mb-section mb-section--grey">
			<div class="mb-wrap">
				<h2 class="mb-title">More articles</h2>
				<div class="mb-cards mb-cards--3">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						get_template_part( 'template-parts/post-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;
get_footer();
