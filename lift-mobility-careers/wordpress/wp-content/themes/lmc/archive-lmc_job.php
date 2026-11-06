<?php
/** Job search results – also used for sector, role and location pages (see taxonomy.php). */
get_header();
global $wp_query;
$term    = is_tax() ? get_queried_object() : null;
$filters = LMC_Search::filters();
$heading = $term ? ( 'lmc_location' === $term->taxonomy ? 'Lift engineer jobs in ' . $term->name : ( 'lmc_role' === $term->taxonomy ? $term->name . ' jobs' : $term->name . ' engineer jobs' ) ) : 'Browse engineering jobs';
?>
<section class="lmc-page-head">
	<div class="lmc-wrap">
		<nav class="lmc-crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Jobs</a><?php echo $term ? ' / ' . esc_html( $term->name ) : ''; ?></nav>
		<h1><?php echo esc_html( $heading ); ?></h1>
		<?php lmc_theme_search_form( 'bar' ); ?>
	</div>
</section>
<div class="lmc-wrap lmc-results">
	<aside class="lmc-filters" aria-label="Filter results">
		<form method="get" action="">
			<?php foreach ( array( 'q', 'location', 'sector' ) as $keep ) : ?>
				<?php if ( $filters[ $keep ] ) : ?><input type="hidden" name="<?php echo esc_attr( $keep ); ?>" value="<?php echo esc_attr( $filters[ $keep ] ); ?>"><?php endif; ?>
			<?php endforeach; ?>
			<h2>Refine</h2>
			<?php if ( $filters['location'] && ! LMC_Location::match_term( $filters['location'] ) ) : ?>
				<label>Distance<select name="radius"><?php foreach ( array( 10, 25, 50, 75, 100 ) as $r ) : ?><option value="<?php echo (int) $r; ?>" <?php selected( $filters['radius'], $r ); ?>>Within <?php echo (int) $r; ?> miles</option><?php endforeach; ?></select></label>
			<?php endif; ?>
			<?php if ( ! $term || 'lmc_role' !== $term->taxonomy ) : ?>
			<label>Job category<select name="role"><option value="">Any</option><?php foreach ( LMC_Forms::term_options( 'lmc_role' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['role'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
			<?php endif; ?>
			<label>Employment type<select name="type"><option value="">Any</option><?php foreach ( LMC_Forms::term_options( 'lmc_type' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['type'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
			<label>Experience level<select name="experience"><option value="">Any</option><?php foreach ( LMC_Forms::term_options( 'lmc_experience' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filters['experience'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
			<label>Minimum salary<select name="salary_min"><option value="">Any</option><?php foreach ( array( 25000, 30000, 35000, 40000, 50000 ) as $s ) : ?><option value="<?php echo (int) $s; ?>" <?php selected( $filters['salary_min'], $s ); ?>>£<?php echo esc_html( number_format( $s ) ); ?>+</option><?php endforeach; ?></select></label>
			<label>Sort by<select name="sort"><option value="">Most relevant</option><option value="date" <?php selected( $filters['sort'], 'date' ); ?>>Newest</option><option value="salary" <?php selected( $filters['sort'], 'salary' ); ?>>Highest salary</option></select></label>
			<button class="lmc-btn lmc-btn--primary" type="submit">Apply filters</button>
			<?php if ( LMC_Search::has_filters() ) : ?><a class="lmc-clear" href="<?php echo esc_url( $term ? get_term_link( $term ) : get_post_type_archive_link( 'lmc_job' ) ); ?>">Clear all</a><?php endif; ?>
		</form>
	</aside>
	<div class="lmc-results__main">
		<p class="lmc-results__count" role="status"><?php echo (int) $wp_query->found_posts; ?> live <?php echo esc_html( _n( 'vacancy', 'vacancies', $wp_query->found_posts ) ); ?><?php echo $filters['location'] ? ' near ' . esc_html( $filters['location'] ) : ''; ?></p>
		<?php if ( have_posts() ) : ?>
			<div class="lmc-job-list">
				<?php while ( have_posts() ) : the_post(); lmc_render_job_card( get_the_ID() ); endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '← Previous', 'next_text' => 'Next →' ) ); ?>
		<?php else : ?>
			<div class="lmc-panel lmc-center">
				<h2>No vacancies match your search right now</h2>
				<p>Try a wider distance, remove a filter, or browse all <a href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">live vacancies</a>. New roles are added every week.</p>
			</div>
		<?php endif; ?>
		<?php if ( $term && $term->description && ! is_paged() ) : ?>
			<section class="lmc-term-intro">
				<h2><?php echo esc_html( 'lmc_sector' === $term->taxonomy ? 'Working in ' . strtolower( $term->name ) : 'About ' . strtolower( $term->name ) . ' roles' ); ?></h2>
				<?php echo wp_kses_post( wpautop( $term->description ) ); ?>
			</section>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
