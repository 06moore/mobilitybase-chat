<?php
/**
 * Google reviews strip. Renders nothing until reviews are added in inc/data.php.
 * $args['limit'] – how many to show (default 3). $args['grey'] – grey background.
 * $args['topic'] – install|service|repair|removal: matching reviews are shown first.
 */
$reviews = mb_reviews();
if ( ! $reviews ) {
	return;
}
$limit   = (int) ( $args['limit'] ?? 3 );
$topic   = $args['topic'] ?? '';
if ( $topic ) {
	$match   = array_filter( $reviews, fn( $r ) => in_array( $topic, $r[5] ?? array(), true ) );
	$reviews = array_merge( $match, array_diff_key( $reviews, $match ) );
}
$summary = mb_review_summary();
$stars   = function ( $n ) {
	$n = max( 0, min( 5, (int) round( (float) $n ) ) );
	return '<span class="mb-stars" aria-label="' . esc_attr( $n . ' out of 5 stars' ) . '">' . str_repeat( '★', $n ) . '<span class="mb-stars__off">' . str_repeat( '★', 5 - $n ) . '</span></span>';
};
?>
<section class="mb-section mb-reviews<?php echo empty( $args['grey'] ) ? '' : ' mb-section--grey'; ?>">
	<div class="mb-wrap">
		<h2 class="mb-title">What our customers say</h2>
		<?php if ( $summary['rating'] ) : ?>
			<p class="mb-reviews__summary">
				<?php echo $stars( $summary['rating'] ); // phpcs:ignore ?>
				<strong><?php echo esc_html( $summary['rating'] ); ?> out of 5</strong>
				<?php if ( $summary['count'] ) : ?>from <?php echo esc_html( $summary['count'] ); ?> Google reviews<?php endif; ?>
			</p>
		<?php endif; ?>
		<div class="mb-reviews__grid">
			<?php foreach ( array_slice( $reviews, 0, $limit ) as $r ) : ?>
				<figure class="mb-review">
					<?php echo $stars( $r[1] ); // phpcs:ignore ?>
					<?php
					$mb_paras = preg_split( '/\n\s*\n/', $r[2] );
					$mb_long  = count( $mb_paras ) > 1 && mb_strlen( $r[2] ) > 600;
					?>
					<blockquote>
						<?php foreach ( $mb_paras as $mb_i => $mb_para ) : ?>
							<p<?php echo $mb_long && $mb_i > 0 ? ' class="mb-review__rest" hidden' : ''; ?>><?php echo esc_html( $mb_para ); ?></p>
						<?php endforeach; ?>
					</blockquote>
					<?php if ( $mb_long ) : ?><button type="button" class="mb-review__more" aria-expanded="false">Read the full review</button><?php endif; ?>
					<figcaption><strong><?php echo esc_html( $r[0] ); ?></strong><?php echo ! empty( $r[4] ) ? ' · ' . esc_html( $r[4] ) : ''; ?><?php echo ! empty( $r[3] ) ? '<br><span>' . esc_html( $r[3] ) . ' · Google review</span>' : ''; ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( $summary['url'] ) : ?>
			<p class="mb-center"><a class="mb-textlink" href="<?php echo esc_url( $summary['url'] ); ?>" rel="noopener" target="_blank">Read all our reviews on Google <?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></a></p>
		<?php endif; ?>
	</div>
</section>
