<?php
//check if reader posted like or dislike
if(isset($_POST) && !empty($_POST) && $_POST['action'] == 'ewbpl_update_likes'){
	ewbpl_update_likes($_POST);
}

?>