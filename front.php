<?php
add_action("wp_footer","pauseadblocker");
function pauseadblocker(){
 
	if(get_option("pause_enabled")=="on"){

	wp_enqueue_script( 'dedect', plugins_url( '/inc/pause-front.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_style( 'wpajans_dedect', plugins_url( '/inc/pause-front.css', __FILE__ ));
  
	echo'<div id="modal" class="'.get_option("pause_popup_style").' modal-bg" style="-webkit-filter: blur(0px);">
			<div class="modal-container">
				<div class="box modal-box text-center" style="font-size:'.get_option("pause_bgdesc").'">
					<div id="adblocktitle" style="background:'.get_option("pause_bghead").'">
						<img src="'.get_option("pause_logoimage").'" width="250">
						<h1>'.get_option("pause_heading").'</h1>
					</div>
					
					<div class="description" style="background:'.get_option("pause_bgdesc").'">
						<p>'.get_option("pause_description").'</p>
						<button name="data" class="refresh" onclick="history.go(0)" style="color:'.get_option("pause_bgbtntxt").';background:'.get_option("pause_bgbtn").';">'.get_option("pause_refresh").'</button>
					</div>
				</div>
			</div>
		</div>';	
?>
<style>
<?php echo get_option("pause_customstyles"); ?> 
</style>

<script type="text/javascript">
function noNeedToBlock() {
}
function adBlockDetected() {
jQuery(window).load(function() {

var modal = jQuery("#modal.modal-bg"),
yesBtn = jQuery("#yes");
modal.fadeIn(250);
yesBtn.on("click", function() {
modal.fadeOut(150);
});

}())}
jQuery(document).ready(function(){
var pauseAdBlock = new PauseAdBlock({
checkOnLoad: true,
resetOnEnd: true
});
pauseAdBlock.onDetected(adBlockDetected);
pauseAdBlock.onNotDetected(noNeedToBlock);
});
</script>
<?php
}else{
	
	}
}
?>