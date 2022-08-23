<?php
/**
 * Information about PHP configuration displayed in server configuration widget.
 *
 * @package WebP Converter for Media
 */

?>
<h4>PHP</h4>
<table>
	<tbody>
	<tr>
		<td class="e">Version</td>
		<td class="v">
			<?php echo esc_html( phpversion() ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">memory_limit</td>
		<td class="v">
			<?php echo esc_html( ini_get( 'memory_limit' ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e">max_execution_time</td>
		<td class="v">
			<?php echo esc_html( ini_get( 'max_execution_time' ) ?: '-' ); ?>
		</td>
	</tr>
	</tbody>
</table>
