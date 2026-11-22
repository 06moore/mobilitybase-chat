<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mb-skip" href="#main">Skip to content</a>
<header class="mb-header">
	<div class="mb-wrap mb-header__inner">
		<div class="mb-brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mb-logo" aria-label="Mobilitybase home">
				<img class="mb-logo__img" src="<?php echo esc_url( MB_URI . '/assets/img/logo.webp' ); ?>" alt="Mobilitybase" width="1101" height="219" fetchpriority="high">
			</a>
			<p class="mb-callus">Call us today on <?php echo mb_tel( null, 'mb-callus__num' ); // phpcs:ignore ?></p>
		</div>
		<button class="mb-navtoggle" type="button" aria-expanded="false" aria-controls="mb-nav">
			<span class="mb-navtoggle__open"><?php echo mb_icon( 'menu' ); // phpcs:ignore ?></span>
			<span class="mb-navtoggle__close"><?php echo mb_icon( 'close' ); // phpcs:ignore ?></span>
			<span class="mb-sr">Menu</span>
		</button>
		<nav class="mb-nav" id="mb-nav" aria-label="Main">
			<?php mb_menu( 'primary', mb_default_menu(), 'mb-nav__list' ); ?>
			<a class="mb-btn mb-btn--sm mb-nav__cta" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Free Quote</a>
		</nav>
	</div>
</header>
<?php if ( ! is_front_page() ) : ?><div class="mb-greenbar" aria-hidden="true"></div><?php endif; ?>
<main id="main" class="mb-main">
