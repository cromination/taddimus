<?php
/**
 * Information about GD library displayed in server configuration widget.
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h4>gd</h4>
<?php if ( ! extension_loaded( 'gd' ) ) : ?>
	<p>-</p>
<?php else : ?>
	<?php ( new \ReflectionExtension( 'gd' ) )->info(); ?>
<?php endif; ?>
