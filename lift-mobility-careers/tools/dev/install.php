<?php
// Installs WordPress, activates LMC plugin + theme, seeds clearly-labelled demo data.
[$_, $wpdir, $url] = $argv;
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST); $_SERVER['REQUEST_URI'] = '/';
require $wpdir . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
wp_install('Lift and Mobility Careers', 'admin', 'admin@example.test', true, '', 'admin-dev-password');
update_option('permalink_structure', '/%postname%/');
update_option('timezone_string', 'Europe/London');
activate_plugin('sqlite-database-integration/load.php');
switch_theme('lmc');
$r = activate_plugin('lmc-core/lmc-core.php');
if (is_wp_error($r)) { fwrite(STDERR, $r->get_error_message() . "\n"); exit(1); }
foreach (get_posts(["post_type"=>["post","page"],"post_status"=>"any","numberposts"=>-1]) as $p) { if (in_array($p->post_name, ["hello-world","sample-page"], true) || ($p->post_type==="page" && !in_array($p->ID, array_map(fn($k)=>(int)get_option($k), array_filter(array_keys(wp_load_alloptions()), fn($k)=>str_starts_with($k,"lmc_page_"))), true))) wp_delete_post($p->ID, true); }
do_action("lmc_dev_seed");
flush_rewrite_rules();
echo "Installed.\n";
