<?php

# Check to see if the form has been submitted or not:
if( !isset( $_POST['submit'] ) ){
	
	# Form has not been submitted, show our uploader form and stop the script
	require_once( "uploader.html" );
	exit();
	
}else{
	
	# Form has been submitted, begin processing data
	
	# Include the function file then call it with the uploaded picture:
	# TIP: The "../../ portion is a relative path. You will need to change this
	#      path to fit your website's directory structure.
	require_once( 'FacebookPicOverlay.php' );
	
	# Create the FacebookTagger object using our upload value given to us by uploader.html
	$fbt = new FacebookPicOverlay();
	
	# Let's say we're using this script to do an image overlay. Let's invoke the
	# overlay method, which will then return the image file relative to the resources
	# folder (ex: will return resources/processed/imagename.jpg).
	try {
		$image = $fbt->overlay( $_FILES['picture_upload'] );
	}catch( Exception $e ){
		print( "<b>Oops!</b> " . $e->getMessage() );
		print( "<br /><br /><a href=\"javascript:history.go(-1)\">Please go back and try again</a>" );
		exit();
	}
	
	# This will delete all images created more than two days ago (by default).
	# This is helpful in keeping our processed folder at a reasonable file size.
	$fbt->maintenance();
	
	require_once( "success.html" );
	
}	

# That's all, folks!
?>