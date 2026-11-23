<?php
/**
 * "Areas we cover" links to the town pages. Helps visitors and Google find the local pages.
 * $args['type']: 'removal' | 'installation' | 'all'
 */
$type    = $args['type'] ?? 'all';
$current = $args['current'] ?? '';
$groups  = array();
if ( 'removal' !== $type ) {
	$groups['installation'] = array( 'Stairlift installation', 'stairlift-installation-' );
}
if ( 'installation' !== $type ) {
	$groups['removal'] = array( 'Stairlift removal', 'stairlift-removal-' );
}
if ( 'removal' === $type ) {
	$groups = array( 'removal' => $groups['removal'] );
}
?>
<section class="mb-section mb-section--grey mb-areas">
	<div class="mb-wrap">
		<h2 class="mb-title">Areas we cover</h2>
		<p class="mb-section__intro">We’re based in East Peckham, near Tonbridge, and our engineers carry out installations, servicing, repairs and removals across Kent, London and the South East. If your town isn’t listed, we almost certainly still cover it.</p>
		<?php if ( count( $groups ) > 1 ) : ?>
			<ul class="mb-towns">
				<?php foreach ( mb_towns() as $town_slug => $town ) :
					$links = array();
					foreach ( $groups as $key => $g ) {
						if ( get_page_by_path( $g[1] . $town_slug ) ) {
							$links[] = '<a href="' . esc_url( home_url( '/' . $g[1] . $town_slug ) ) . '">' . esc_html( 'installation' === $key ? 'Installation' : 'Removal' ) . '<span class="mb-sr"> in ' . esc_html( $town ) . '</span></a>';
						}
					}
					if ( ! $links ) {
						continue;
					}
					?>
					<li><strong><?php echo esc_html( $town ); ?></strong><span><?php echo implode( ' · ', $links ); // phpcs:ignore — escaped above ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php else : foreach ( $groups as $g ) : ?>
			<h3 class="mb-areas__title"><?php echo esc_html( $g[0] ); ?></h3>
			<ul class="mb-areas__list">
				<?php foreach ( mb_towns() as $town_slug => $town ) :
					$slug = $g[1] . $town_slug;
					if ( ! get_page_by_path( $slug ) ) {
						continue;
					}
					?>
					<li><a href="<?php echo esc_url( home_url( '/' . $slug ) ); ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $town ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( home_url( '/' . $g[1] . 'kent' ) ); ?>">All of Kent</a></li>
			</ul>
		<?php endforeach; endif; ?>
	</div>
</section>
