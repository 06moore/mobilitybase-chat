<article class="mb-card mb-card--post">
	<a class="mb-card__img" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'mb-card', array( 'loading' => 'lazy', 'alt' => '' ) );
		} else {
			echo mb_img( 'f377b01ade614859.webp', '' ); // phpcs:ignore
		}
		?>
	</a>
	<div class="mb-card__body">
		<p class="mb-card__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
		<h3 class="mb-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="mb-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
	</div>
</article>
