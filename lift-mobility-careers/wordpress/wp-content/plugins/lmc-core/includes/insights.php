<?php
/**
 * Data-driven content: salary insights computed ONLY from real adverts on the board
 * (live + closed in the last 12 months). A figure is shown only when at least 5 adverts
 * support it, so the site never publishes invented or misleading salary data.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Insights {

	const MIN_SAMPLE = 5;

	public static function init() {
		add_shortcode( 'lmc_salary_insights', array( __CLASS__, 'sc_salary' ) );
		add_shortcode( 'lmc_guide_list', array( __CLASS__, 'sc_guides' ) );
	}

	/** Annualised mid-point salaries grouped by taxonomy term. */
	public static function salaries( $tax, $term_slug = '' ) {
		$key = 'lmc_sal_' . md5( $tax . $term_slug );
		$c   = get_transient( $key );
		if ( false !== $c ) {
			return $c;
		}
		$args = array( 'post_type' => 'lmc_job', 'post_status' => array( 'publish', 'lmc_expired', 'lmc_closed' ), 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( array( 'after' => '12 months ago' ) ) );
		if ( $term_slug ) {
			$args['tax_query'] = array( array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => $term_slug ) );
		}
		$groups = array();
		foreach ( get_posts( $args ) as $id ) {
			$per = lmc_job_meta( $id, 'salary_period' ) ?: 'YEAR';
			$min = (float) lmc_job_meta( $id, 'salary_min' );
			$max = (float) lmc_job_meta( $id, 'salary_max' ) ?: $min;
			if ( ! $min ) {
				continue;
			}
			$mid = LMC_Search::annualise( ( $min + $max ) / 2, $per );
			foreach ( lmc_job_terms( $id, $tax ) as $t ) {
				$groups[ $t->name ][] = $mid;
			}
		}
		$out = array();
		foreach ( $groups as $name => $vals ) {
			if ( count( $vals ) < self::MIN_SAMPLE ) {
				continue;
			}
			sort( $vals );
			$n   = count( $vals );
			$med = $n % 2 ? $vals[ intdiv( $n, 2 ) ] : ( $vals[ $n / 2 - 1 ] + $vals[ $n / 2 ] ) / 2;
			$out[ $name ] = array( 'n' => $n, 'median' => (int) round( $med, -2 ), 'low' => (int) round( $vals[ (int) floor( ( $n - 1 ) * 0.25 ) ], -2 ), 'high' => (int) round( $vals[ (int) ceil( ( $n - 1 ) * 0.75 ) ], -2 ) );
		}
		ksort( $out );
		set_transient( $key, $out, 6 * HOUR_IN_SECONDS );
		return $out;
	}

	public static function table( $rows, $label ) {
		if ( ! $rows ) {
			return '';
		}
		$h = '<div class="lmc-table-wrap"><table class="lmc-table lmc-salary-table"><thead><tr><th>' . esc_html( $label ) . '</th><th>Typical range</th><th>Median</th><th>Adverts</th></tr></thead><tbody>';
		foreach ( $rows as $name => $r ) {
			$h .= '<tr><td data-label="' . esc_attr( $label ) . '">' . esc_html( $name ) . '</td><td data-label="Typical range">£' . number_format( $r['low'] ) . ' – £' . number_format( $r['high'] ) . '</td><td data-label="Median">£' . number_format( $r['median'] ) . '</td><td data-label="Adverts">' . (int) $r['n'] . '</td></tr>';
		}
		return $h . '</tbody></table></div>';
	}

	public static function sc_salary() {
		$by_role   = self::salaries( 'lmc_role' );
		$by_sector = self::salaries( 'lmc_sector' );
		if ( ! $by_role && ! $by_sector ) {
			return '<div class="lmc-panel"><p><strong>Salary data is building up.</strong> We publish figures once at least ' . self::MIN_SAMPLE . ' adverts support them, so they reflect real vacancies rather than guesses. Meanwhile, every <a href="/jobs/">live vacancy</a> shows its salary.</p></div>';
		}
		return '<p class="lmc-muted">Annual equivalent of the mid-point of each advertised range, from vacancies in the last 12 months. "Typical range" is the middle half of adverts. Updated automatically.</p>'
			. ( $by_role ? '<h2>By job category</h2>' . self::table( $by_role, 'Job category' ) : '' )
			. ( $by_sector ? '<h2>By sector</h2>' . self::table( $by_sector, 'Sector' ) : '' );
	}

	/** One-line salary summary for a sector/role page, or '' if not enough data. */
	public static function term_summary( $term ) {
		$rows = self::salaries( $term->taxonomy, $term->slug );
		if ( empty( $rows[ $term->name ] ) ) {
			return '';
		}
		$r = $rows[ $term->name ];
		return 'Based on ' . (int) $r['n'] . ' adverts in the last 12 months, typical ' . strtolower( $term->name ) . ' salaries are £' . number_format( $r['low'] ) . ' – £' . number_format( $r['high'] ) . ' (median £' . number_format( $r['median'] ) . ').';
	}

	public static function sc_guides() {
		$parent = (int) get_option( 'lmc_page_guides' );
		$pages  = get_pages( array( 'parent' => $parent, 'sort_column' => 'menu_order,post_title' ) );
		$h      = '<div class="lmc-guides">';
		foreach ( $pages as $p ) {
			$h .= '<a class="lmc-guide" href="' . esc_url( get_permalink( $p ) ) . '"><span class="lmc-guide__title">' . esc_html( $p->post_title ) . '</span><span class="lmc-guide__desc">' . esc_html( get_post_meta( $p->ID, '_lmc_seo_desc', true ) ) . '</span></a>';
		}
		return $h . '</div>';
	}

	public static function is_guide( $post_id ) {
		return (int) wp_get_post_parent_id( $post_id ) === (int) get_option( 'lmc_page_guides' ) && get_option( 'lmc_page_guides' );
	}
}
LMC_Insights::init();
