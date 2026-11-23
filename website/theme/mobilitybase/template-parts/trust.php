<?php
/** Strip of reasons to trust us, shown under heroes. Only facts from the business. */
?>
<div class="mb-trust">
	<ul class="mb-wrap mb-trust__list">
		<?php $mb_rs = mb_review_summary(); if ( $mb_rs['rating'] ) : ?>
			<li><span class="mb-trust__rating" aria-hidden="true">★</span> <?php echo esc_html( $mb_rs['rating'] ); ?>/5 on Google<?php echo $mb_rs['count'] ? ' (' . esc_html( $mb_rs['count'] ) . ' reviews)' : ''; ?></li>
		<?php endif; ?>
		<li><?php echo mb_icon( 'shield' ); // phpcs:ignore ?> Family-run since <?php echo esc_html( mb_biz( 'founded' ) ); ?></li>
		<li><?php echo mb_icon( 'pin' ); // phpcs:ignore ?> DBS-checked engineers based in Kent</li>
		<li><?php echo mb_icon( 'check' ); // phpcs:ignore ?> Fully insured</li>
		<li><?php echo mb_icon( 'star' ); // phpcs:ignore ?> Every lift guaranteed for at least 1 year</li>
	</ul>
</div>
