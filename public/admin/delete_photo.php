<?php

require_once("../../includes/initialize.php");
if(!$session->is_logged_in()){ redirect_to("login.php");}

// must have an ID
if(empty($_GET['id'])){
	$session->message("No photograph ID was provided.");
	redirect_to('list_photos.php');
}
//var_dump(intval($_GET['id']));
$photo = Photograph::find_by_id($_GET['id']);
// $photo = Photograph::find_by_sql("SELECT * FROM photographs WHERE id=". $_GET['id']);
//var_dump($photo);

if($photo && $photo->destroy()) {
	$session->message("The photo was deleted.");
	redirect_to('list_photos.php');
} else {
	$session->message("The photo could not be deleted.");
	redirect_to('list_photos.php');
}

?>

<?php if(isset($database)) { $database->close_connection(); } ?>
