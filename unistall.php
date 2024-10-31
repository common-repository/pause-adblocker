<?php

function pause_uninstall() {
	if ( get_option( 'pause_remove_uninstall' ) === 'on' ) {
		delete_option( 'pause_enabled' );
		delete_option( 'pause_heading' );
		delete_option( 'pause_refresh' );
		delete_option( 'pause_description' );
		delete_option( 'pause_logoimage' );
		delete_option( 'pause_bghead' );
		delete_option( 'pause_bgdesc' );
		delete_option( 'pause_bgbtntxt' );
		delete_option( 'pause_bgbtn' );
		delete_option( 'pause_customstyles' );
		delete_option( 'pause_popup_style' );
		delete_option( 'pause_unistall' );
	}
}
register_uninstall_hook( __FILE__, 'pause_uninstall' );
?>