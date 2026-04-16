<?php
/**
 * Site header.
 *
 * @package StarterKit
 */

$header_layout = starterkit()->layout_resolver()->resolve( 'header' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<?php if ( $header_layout && ! empty( $header_layout['template'] ) ) : ?>
		<?php include get_template_directory() . '/' . $header_layout['template']; ?>
	<?php endif; ?>
