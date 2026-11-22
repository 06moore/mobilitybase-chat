<?php
/**
 * Articles (blog index) and fallback for archives/search.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle mb-pagetitle--caps">
			<?php
			if ( is_search() ) {
				printf( 'Search: %s', esc_html( get_search_query() ) );
			} elseif ( is_category() || is_tag() ) {
				single_term_title();
			} else {
				echo 'Advice &amp; articles';
			}
			?>
		</h1>
		<?php if ( is_home() ) : ?>
			<p class="mb-section__intro">Practical guides from our engineers on stairlift costs, fault codes, servicing and choosing the right lift for your home.</p>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<div class="mb-cards mb-cards--3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/post-card' );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination( array(
				'mid_size'  => 1,
				'prev_text' => 'Newer',
				'next_text' => 'Older',
				'class'     => 'mb-pagination',
			) );
			?>
		<?php else : ?>
			<p class="mb-center">Nothing found.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
