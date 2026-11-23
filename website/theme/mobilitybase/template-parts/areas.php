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
		<p class="mb-section__intro">Our engineers work across Kent, London and the South East. Here are some of the towns we cover — if yours isn’t listed, we almost certainly still cover it.</p>
		<?php foreach ( $groups as $g ) : ?>
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
		<?php endforeach; ?>
	</div>
</section>
