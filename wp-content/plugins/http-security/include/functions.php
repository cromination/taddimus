<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

function http_security_isSecure() {
  return( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off ');
}

function http_security_issues_count() {
	$count = 0;

	if ( ! ( http_security_isSecure() ) ) {
		$count++;
	}
	if ( username_exists( 'admin' ) ) {
		$count++;
	}
	return $count;
}