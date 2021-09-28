<?php

//check if reader posted like or dislike
if(isset($_POST) && !empty($_POST)){
	updateLikes($_POST);
}

?>