<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lmc-skip" href="#main">Skip to content</a>
<header class="lmc-header">
	<div class="lmc-wrap lmc-header__inner">
		<a class="lmc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Lift and Mobility Careers – home">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-primary-reversed.svg' ); ?>" width="236" height="64" alt="Lift and Mobility Careers">
		</a>
		<button class="lmc-nav-toggle" aria-expanded="false" aria-controls="lmc-nav"><span></span><span></span><span></span><span class="screen-reader-text">Menu</span></button>
		<nav id="lmc-nav" class="lmc-nav" aria-label="Main">
			<?php lmc_theme_nav(); ?>
			<div class="lmc-nav__cta">
				<?php if ( is_user_logged_in() && lmc_is_employer() ) : ?>
					<a class="lmc-nav__account" href="<?php echo esc_url( lmc_page_url( 'dashboard' ) ); ?>">My dashboard</a>
				<?php else : ?>
					<a class="lmc-nav__account" href="<?php echo esc_url( lmc_page_url( 'login' ) ); ?>">Employer sign in</a>
				<?php endif; ?>
				<a class="lmc-btn lmc-btn--primary lmc-btn--small" href="<?php echo esc_url( lmc_page_url( 'advertise' ) ); ?>">Advertise a Vacancy</a>
			</div>
		</nav>
	</div>
</header>
<main id="main" class="lmc-main">
