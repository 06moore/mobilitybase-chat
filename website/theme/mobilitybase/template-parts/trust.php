<?php
/** Strip of reasons to trust us, shown under heroes. Only facts from the business. */
?>
<div class="mb-trust">
	<ul class="mb-wrap mb-trust__list">
		<?php $mb_rs = mb_review_summary(); if ( $mb_rs['rating'] ) : ?>
			<li><span class="mb-trust__rating" aria-hidden="true">★</span> <?php echo esc_html( $mb_rs['rating'] ); ?>/5 on Google<?php echo $mb_rs['count'] ? ' (' . esc_html( $mb_rs['count'] ) . ' reviews)' : ''; ?></li>
		<?php endif; ?>
		<li><?php echo mb_icon( 'shield' ); // phpcs:ignore ?> Family-run since <?php echo esc_html( mb_biz( 'founded' ) ); ?></li>
		<li><?php echo mb_icon( 'pin' ); // phpcs:ignore ?> Engineers based in Kent</li>
		<li><?php echo mb_icon( 'wrench' ); // phpcs:ignore ?> All makes serviced &amp; repaired</li>
		<li><?php echo mb_icon( 'pound' ); // phpcs:ignore ?> Clear prices, no hidden costs</li>
	</ul>
</div>
