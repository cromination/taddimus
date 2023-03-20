<?php
/**
 * Information about Imagick library displayed in server configuration widget.
 *
 * @package Converter for Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h4>imagick</h4>
<?php if ( ! extension_loaded( 'imagick' ) ) : ?>
	<p>-</p>
<?php else : ?>
	<?php ( new \ReflectionExtension( 'imagick' ) )->info(); ?>
<?php endif; ?>
