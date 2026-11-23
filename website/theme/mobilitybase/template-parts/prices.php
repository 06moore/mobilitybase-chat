<?php
/**
 * Price tables. Pass array( 'tables' => mb_price_tables( $slug ) ).
 */
$tables = $args['tables'] ?? array();
if ( ! $tables ) {
	return;
}
$money = fn( $v ) => '£' . number_format_i18n( (float) $v );
?>
<section class="mb-section mb-prices" id="prices">
	<div class="mb-wrap">
		<h2 class="mb-title">Our prices</h2>
		<div class="mb-prices__grid">
			<?php foreach ( $tables as $t ) : ?>
				<div class="mb-prices__table">
					<h3><?php echo esc_html( $t['title'] ); ?></h3>
					<div class="mb-prices__scroll">
						<table>
							<thead><tr>
								<?php foreach ( $t['cols'] as $i => $col ) : ?>
									<th scope="col"<?php echo 0 === $i ? ' class="mb-sr-cell"' : ''; ?>><?php echo $col ? esc_html( $col ) : '<span class="mb-sr">Item</span>'; ?></th>
								<?php endforeach; ?>
							</tr></thead>
							<tbody>
								<?php foreach ( $t['rows'] as $row ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( array_shift( $row ) ); ?></th>
										<?php foreach ( $row as $i => $v ) : ?>
											<td data-label="<?php echo esc_attr( $t['cols'][ $i + 1 ] ); ?>"><?php echo esc_html( $money( $v ) ); ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ( ! empty( $t['note'] ) ) : ?>
						<p class="mb-small"><?php echo wp_kses( $t['note'], array( 'a' => array( 'href' => array() ) ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mb-small mb-prices__vat"><?php echo esc_html( mb_vat_note() ); ?></p>
	</div>
</section>
