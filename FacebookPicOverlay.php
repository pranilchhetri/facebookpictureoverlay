<?php
/*************************************************
 * Facebook Picture Overlay Script
 * Version: 2.1.0
 * Coded by: Shane Chism <http://shanechism.com>
 * Updates: http://shanechism.com/code/static/16
 * Distributed under the GNU Public License
 *************************************************/
 
/** \brief Facebook Picture Overlay Script
 * Allows for image overlay to uploaded files based on Facebook's standards
 * @author Shane Chism <schism@acm.org>
**/
class FacebookPicOverlay {
	
	// --------------------------------------------------
	// CONFIGURATION SECTION - MANDATORY
	// --------------------------------------------------
	// Modify the values in this section according to
	// your own needs. Pay close attention to your
	// directory structure.
	
	# Path to the directory containing the resources and processed folders:
	var $rootPath    		= "";
	
	# Resources folder name:
	# (default: "resources/")
	var $resourcesFolder   = "resources/";      	
	
	# Folder you would like the processed images saved to:
	# (default: "resources/processed/")
	var $processedFolder	= "resources/processed/";
	
	// ** FACEBOOK DEFAULT CONFIGURATION
	# Set the maximum height and width of the Facebook profile picture here:
	# (default: 200 x 600) -- Facebook may have changed them after this script was released
	var $fbWidth 	= 200;
	var $fbHeight	= 600;
	
	// ** OVERLAY MODE CONFIGURATION
	
	# Overlay image filename and extension (must be placed in the resources folder):
	# (default: "overlay.png")
	var $overlay     = "overlay.png";
	
	# Overlay offset from bottom. This will change based on your overlay image.
	# 0 means your overlay image will be placed on the direct bottom of the image,
	# Negative numbers bring the image up, positive ones push it down:
	# (default: 0)
	var $offset = 0;
	
	# Throw Exceptions?
	# true = User errors will generate an Exception() error
	# false = User errors will return overlay as false, and save error to $this->error
	var $throwExceptions = true;
	
	// --------------------------------------------------
	
	// --------------------------------------------------
	// CONFIGURATION SECTION - OPTIONAL
	// --------------------------------------------------
	// You can fine tune the tagger to your needs,
	// though these options can remain the same and your
	// tagging should still work.
	
	# Maximum image size allowed for upload (in MB):
	# (default: 20)
	var $maxFileSize	= 20;
	
	# Save images at this quality (percentage):
	# (smaller quality has a smaller file size but looks worse)
	# (default: 100)
	var $quality 		= 100;
	
	# Save images in this file format:
	# (options: "jpg", "jpeg", "JPG", "JPEG")
	# (default: "jpg")
	var $extension 		= "jpg";
	
	// --------------------------------------------------
	
	var $uploaded, $uploadedInfo, $error;
	
	function __construct(){
		
		$this->checkConfig();
		
	}
	
	private function checkConfig(){
		
		if( substr( $this->resourcesFolder, 1 ) == '/' )
			$this->resourcesFolder = substr( $this->resourcesFolder, 1, ( strlen( $this->resourcesFolder ) - 1 ) );
		if( substr( $this->resourcesFolder, -1 ) != '/' )
			$this->resourcesFolder .= "/";
			
		if( substr( $this->processedFolder, 1 ) == '/' )
			$this->processedFolder = substr( $this->processedFolder, 1, ( strlen( $this->processedFolder ) - 1 ) );
		if( substr( $this->processedFolder, -1 ) != '/' )
			$this->processedFolder .= "/";
		
		if( !file_exists( $this->rootPath . $this->resourcesFolder ) )
			$this->printErr( "The resources folder path you have specified is invalid. Please check it and try again (configuration section: <code>\$rootPath</code> and <code>\$resourcesFolder</code>)." );
		if( !file_exists( $this->rootPath . $this->processedFolder ) )
			$this->printErr( "The processed folder path you have specified is invalid. Please check it and try again (configuration section: <code>\$rootPath</code> and <code>\$processedFolder</code>)." );
		
		$overlay = $this->rootPath . $this->resourcesFolder . $this->overlay;
		if( !file_exists( $overlay ) )
			$this->printErr( "The \"overlay\" image you specified in the configuration section (<code>\$overlay</code>) does not exist. Please correct and try again." );
		
		$overlaySize = getimagesize( $overlay );
		if( $overlaySize[0] > $this->fbWidth || $overlaySize[1] > $this->fbHeight )
			$this->printErr( "Your overlay image is larger than Facebook will allow on a profile picture (max is " . $this->fbWidth . "w X " . $this->fbHeight . "h)." );
		
	}
	
	private function printErr( $text ){
		die( "<h3>FBT Error:</h3> " . $text );
	}
	
	private function throwErr( $err ){
		$this->error = $err;
		if( $this->throwExceptions )
			throw new Exception( $err );
	}
 
	# Places your overlay image on the picture and returns the hyperlink
	public function overlay( $uploaded ){
	
		$this->checkConfig();
		$this->uploaded = $uploaded;
	
		$overlay = $this->rootPath . $this->resourcesFolder . $this->overlay;
		$overlaySize = getimagesize( $overlay );
		
		if( empty( $this->uploaded ) || $uploaded['size'] < 1 ){
			$this->throwErr( "You have not chosen an image to upload!" );
			return false;
		}
		
		$this->uploadedInfo = getimagesize( $this->uploaded['tmp_name'] );
		
		if( $this->uploaded['size'] > ( $this->maxFileSize * 1000000 ) || filesize( $this->uploaded['tmp_name'] ) > ( $this->maxFileSize * 1000000 ) ){
			$this->throwErr( "The file you have chosen to upload is too big." );
			return false;
		}
		
		if( $this->uploadedInfo['mime'] != "image/jpeg" && $this->uploadedInfo['mime'] != "image/jpg" ){
			$this->throwErr( "The file you have chosen to upload is the wrong file type. Please choose a JPG or JPEG file only." );
			return false;
		}
		
		$new = array();
		
		$new[0] = $this->fbWidth;	
		$new[1] = ( $new[0] * $this->uploadedInfo[1] ) / $this->uploadedInfo[0];
		
		if( ( $new[1] + $overlaySize[1] ) > $this->fbHeight )
			$canvasH = $this->fbHeight;
		else
			$canvasH = $new[1];
		
		$src = imagecreatefromjpeg( $this->uploaded['tmp_name'] );
		
		$tmp = imagecreatetruecolor( $new[0], $canvasH );
		imagecopyresampled( $tmp, $src, 0, 0, 0, 0, $new[0], $new[1], $this->uploadedInfo[0], $this->uploadedInfo[1] );
		
		imagealphablending( $tmp, true );
		$overlayRes = imagecreatefrompng( $overlay );
		
		do{
			$filename = time() . "-processed.jpg";
			$file = $this->rootPath . $this->processedFolder . $filename;
		}while( file_exists( $file ) );
		
		imagecopy( $tmp, $overlayRes, 0, ( ( $new[1] + $this->offset ) - $overlaySize[1] ), 0, 0, $overlaySize[0], $overlaySize[1] );
		imagejpeg( $tmp, $file, $this->quality );
		
		if( !file_exists( $file ) )
			$file = $this->rootPath . $this->resourcesFolder . $this->oops;
			
		imagedestroy( $src );
		imagedestroy( $tmp );
		imagedestroy( $overlayRes );
		
		return ( $this->processedFolder . $filename );
		
	}
	
	# Deletes all files in the processed folder that were created before $timestamp
	# Defaults to 2 days ago
	public function maintenance( $timestamp = NULL ){
		
		$this->checkConfig();
		
		if( $timestamp == NULL )
			# Defaults to 2 days ago
			$timestamp = strtotime( "-2 days" );
			
		if( $timestamp > time() )
			$this->printErr( "You are trying to perform maintenance on files created in the future. This is beyond the script's abilities, please install a time machine to continue." );
		
		if( $handle = opendir( $this->rootPath . $this->processedFolder ) ){
			
			while( false !== ( $filename = readdir( $handle ) ) ){
				
				if( substr( $filename, ( -1 * ( 1 + strlen( $this->extension ) ) ) ) == ( "." . $this->extension ) ){
 
					$file = $this->rootPath . $this->processedFolder . $filename;
					
					if( filectime( $file ) < $timestamp )
						@unlink( $file );
				}
			
			}
			
			closedir( $handle );
			
		}else{
			$this->printErr( "Unable to access the processed folder. Check your <code>\$rootPath</code> and <code>\$processedFolder</code> settings in the configuration section." );
		}
		
	}
	
}
 
?>