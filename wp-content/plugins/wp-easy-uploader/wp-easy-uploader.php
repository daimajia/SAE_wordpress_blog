<?php

/*
Plugin Name: WP Easy Uploader
Plugin URI: http://gaarai.com/wp-easy-uploader/
Description: Easily upload any type of content without the need for FTP. You can even upload plugin and theme archives and have the files automatically extract into the plugin or theme folder.
Version: 1.0.11
Author: Chris Jean
Author URI: http://gaarai.com/
*/

/*
Installation

1. Download and unzip the latest release zip file
2. Upload the entire wp-easy-uploader directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
*/

/*
Using

1. Activate the plugin
2. Click on "Tools"
3. Click on "Upload Files"

/*
Version History

1.0.1 - 2008-06-27
	Initial release version
1.0.2 - 2008-06-28
	Updated to support WordPress Mu
1.0.3 - 2008-06-30
	Added support for PHP 4
1.0.4 - 2008-07-02
	Fixed compatibility bug with the BackUpWordPress plugin
1.0.5 - 2008-07-02
	Internationalized the plugin. Now it's ready for translators.
1.0.6 - 2008-07-06
	Added Turkish and Spanish translations
	Fixed compatibility issue with the WeatherIcon plugin
	Fixed parsing error with Windows servers
1.0.7 - 2008-07-08
	Added mainland Chinese and Brazilian Portuguese translations
	Added 2.6 support
1.0.7a - 2008-07-10
	Added German translation
1.0.7b - 2008-07-21
	Added Indonesian translation
1.0.8 - 2008-09-27
	Added file required by PEAR in fringe cases
1.0.8a - 2009-07-17
	Updated listed compatibility to WordPress version 2.8.1
1.0.9 - 2009-07-17
	WordPress no longer likes letters in versions, so here's version 1.0.9. :)
1.0.10 - 2009-07-20
	Updated listed compatibility to WordPress version 2.8.2
1.0.11 - 2009-07 -20
	readme.txt update
*/

/*
Todo

- Customizable permissions to allow for users with privileges
  below Administrator to upload files. My idea is for
  Administrators to have full access to all the uploader's
  functionality while people below Administrator won't have
  access to upload themes, plugins, or to manual paths and won't
  be able to overwrite files or extract archives.
- If the above is implemented, I'd like to provide a menu of
  options for tailoring what minimum access level is required to
  access each function.
- Also if the first item is implemented, I'd like to add logging
  in order to keep track of who uploaded what and when. This
  would be useful for keeping track of anyone (such as a
  contributor) who is using the file space inappropriately.
*/

/*
Notes

- The file extraction is done through File_Archive library by Pablo
  Fischer (http://pear.php.net/package/File_Archive/). I have yet
  to test this library extensively on multiple platforms. It has
  been tested on a number of different Linux-based platforms
  without any apparent problems. If you have any issues using the
  extraction feature, please contact me at gaarai@gaarai.com.
*/

/*
Copyright 2008 Chris Jean (email: gaarai@gaarai.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( !class_exists( 'WPEasyUploader' ) ) {
	class WPEasyUploader {
		var $_var = "wp-easy-uploader";
		var $_name = "WP Easy Uploader";
		var $_class = '';
		var $_initialized = false;
		var $_options = array();
		var $_userID = 0;
		var $_usedInputs = array();
		var $_selectedVars = array();
		var $_pluginPath = '';
		var $_pluginRelativePath = '';
		var $_pluginURL = '';
		
		
		
		function WPEasyUploader() {
			add_action( 'plugins_loaded', array( $this, 'init' ), -10 );
		}
		
		function init() {
			if ( current_user_can('manage_options') ) {
				if( ! $this->_initialized ) {
					$this->_setVars();
					$this->load();
					
					load_plugin_textdomain( $this->_var, $this->_pluginRelativePath . '/lang' );
					
					add_action( 'admin_menu', array( $this, 'addPages' ) );
					
					$this->_initialized = true;
				}
			}
		}
		
		function addPages() {
			if ( function_exists( 'add_management_page' ) )
				add_management_page( __( 'WP Easy Uploader', $this->_var ), __( 'Upload Files', $this->_var ), 'manage_options', __FILE__, array( $this, 'uploadsPage' ) );
		}
		
		function _setVars() {
			$this->_class = get_class( $this );
			
			$user = wp_get_current_user();
			$this->_userID = $user->ID;
			
			
			// Thanks Ozh
			// http://planetozh.com/blog/2008/07/what-plugin-coders-must-know-about-wordpress-26/
			if ( !defined( 'WP_CONTENT_URL' ) )
				define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content');
			if ( !defined( 'WP_CONTENT_DIR' ) )
				define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
			
			$this->_pluginPath = WP_CONTENT_DIR . '/plugins/' . plugin_basename( dirname( __FILE__ ) );
			$this->_pluginRelativePath = str_replace( ABSPATH, '', $this->_pluginPath );
			
			$this->_pluginURL = WP_CONTENT_URL . '/plugins/' . plugin_basename( dirname( __FILE__ ) );
		}
		
		
		// Options Storage ////////////////////////////
		
		function initializeOptions() {
			$this->_options['placeholder'] = 1;
			
			$this->save();
		}
		
		function save() {
			$data = @get_option( $this->_var );
			
			if ( isset( $data ) && ( $data === $this->_options ) )
				return true;
			
			$data = $this->_options;
			
			return update_option( $this->_var, $data );
		}
		
		function load() {
			$data = @get_option( $this->_var );
			
			if ( is_array( $data ) )
				$this->_options = $data;
			else
				$this->initializeOptions();
		}
		
		
		// Pages //////////////////////////////////////
		
		function uploadsPage() {
			$error = false;
			
			if ( ! empty( $_POST['upload'] ) ) {
				check_admin_referer( $this->_var . '-nonce' );
				
				
				$uploads = array();
				$file = array();
				
				if ( 'plugin' == $_POST[$this->_var]['destinationSelection'] )
					$uploads = array( 'path' => trailingslashit( WP_CONTENT_DIR ) . '/plugins/', 'url' => trailingslashit( WP_CONTENT_URL ) . 'plugins/', 'subdir' => '', 'error' => false );
				elseif ( 'theme' == $_POST[$this->_var]['destinationSelection'] )
					$uploads = array( 'path' => get_theme_root(), 'url' => get_theme_root_uri(), 'subdir' => '', 'error' => false );
				elseif ( 'manual' == $_POST[$this->_var]['destinationSelection'] ) {
					if ( preg_match( '/^[\/\\\\]/', $_POST[$this->_var]['destinationPath'] ) )
						$file['error'] = __( 'The Manual Path must be relative (cannot begin with \ or /).', $this->_var );
					elseif ( preg_match( '/\.\./', $_POST[$this->_var]['destinationPath'] ) )
						$file['error'] = __( 'Previous directory paths (..) are not permitted in the Manual Path.', $this->_var );
					else {
						if ( empty( $_POST[$this->_var]['destinationPath'] ) ) {
							$path = ABSPATH;
							$url = get_option( 'siteurl' );
						}
						else {
							$path = path_join( ABSPATH, $_POST[$this->_var]['destinationPath'] );
							$url = trailingslashit( get_option( 'siteurl' ) ) . $_POST[$this->_var]['destinationPath'];
						}
						
						if ( ! wp_mkdir_p( $path ) )
							$file['error'] = sprintf( __( 'Unable to create path %s. Ensure that the web server has permission to write to the parent of this folder.', $this->_var ), $path );
						else 
							$uploads = array( 'path' => $path, 'url' => $url, 'subdir' => '', 'error' => false );
					}
				}
				
				$overwriteFile = ( ! empty( $_POST[$this->_var]['overwriteFile'] ) ) ? true : false;
				$renameIfExists = ( ! empty( $_POST[$this->_var]['renameIfExists'] ) ) ? true : false;
				
				if ( empty( $file['error'] ) ) {
					if ( ! empty( $_POST[$this->_var]['uploadURL'] ) )
						$file = $this->getFileFromURL( $_POST[$this->_var]['uploadURL'], $uploads, $overwriteFile, $renameIfExists );
					elseif ( ! empty( $_FILES['uploadFile']['name'] ) )
						$file = $this->getFileFromPost( 'uploadFile', $uploads, $overwriteFile, $renameIfExists );
					else
						$file['error'] = __( 'You must either provide a URL or a system file to upload.', $this->_var );
				}
				
				
				if ( false === $file['error'] ) {
					$this->showStatusMessage( __( 'File successfully uploaded', $this->_var ) );
					
					$extracted = false;
					
					if ( ! empty( $_POST[$this->_var]['extract'] ) ) {
						$forceExtractionFolder = ( ! empty( $_POST[$this->_var]['forceExtractionFolder'] ) ) ? true : false;
						
						$result = $this->extractArchive( $file, $forceExtractionFolder );
						
						if ( true === $result['extracted'] ) {
							$path = str_replace( '/', '\\/', ABSPATH );
							$destination = preg_replace( '/^' . $path . '/', '', $result['destination'] );
							
							$this->showStatusMessage( sprintf( __( 'Archive successfully extracted to %s', $this->_var ), $destination ) );
							
							$extracted = true;
							
							if ( ! empty( $_POST[$this->_var]['removeArchive'] ) ) {
								if ( unlink( $file['path'] ) )
									$this->showStatusMessage( __( 'Archive removed', $this->_var ) );
								else {
									$this->showErrorMessage( __( 'Unable to remove archive', $this->_var ) );
									$error = true;
								}
							}
						}
						elseif ( false !== $result['error'] ) {
							$this->showErrorMessage( $result['error'] );
							$error = true;
						}
					}
					
					if ( ! $extracted ) {
						ini_set( 'display_errors', '1' );
						error_reporting( E_ALL );
						
						$path = ABSPATH;
						$path = str_replace( '\\', '\\\\', $path );
						$path = str_replace( '/', '\\/', $path );
						
						$destination = preg_replace( '/^' . $path . '/', '', $file['path'] );
						
						$message = '<p>' . sprintf( __( 'Path: %s', $this->_var ), $destination ) . '</p>';
						$message .= '<p>' . sprintf( __( 'URL: <a href="%s" target="newUpload">%s</a>', $this->_var ), $file['url'], $file['url'] ) . '</p>';
						
						$this->showStatusMessage( $message );
					}
				}
				else {
					$this->showErrorMessage( $file['error'] );
					$error = true;
				}
			}
			
			
?>
	<div class="wrap">
		<h2><?php _e( 'Upload Files', $this->_var ); ?></h2>
		
		<form enctype="multipart/form-data" method="post" action="<?php echo $this->getBackLink() ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
				<tr><th scope="row"><?php _e( 'Upload File', $this->_var ); ?></th>
					<td>
						<label for="<?php echo $this->_var; ?>-uploadURL">
							<?php _e( 'From URL:', $this->_var ); ?> <input type="text" size="70" name="<?php echo $this->_var; ?>[uploadURL]" id="<?php echo $this->_var; ?>-uploadURL" value="<?php if ( $error ) echo $_POST[$this->_var]['uploadURL']; ?>" />
						</label><br />
						<?php _e( '- or -', $this->_var ); ?><br />
						<label for="uploadFile">
							<?php _e( 'Select From System:', $this->_var ); ?> <input type="file" name="uploadFile" id="uploadFile" />
						</label>
					</td>
				</tr>
				<tr><th scope="row"><?php _e( 'Destination', $this->_var ); ?></th>
					<td>
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'uploads', __( 'Uploads', $this->_var ), false ); ?><br />
						<div style="margin-left:18px; width:400px;">
							<?php _e( 'The Uploads destination is the same area used for uploading images on the add post or add page editors. This location should be used for all standard file uploads.', $this->_var ); ?>
						</div>
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'plugin', __( 'Plugin', $this->_var ) ); ?><br />
							<div style="margin-left:18px; width:400px;">
								<?php _e( 'Use this destination if you are uploading a new plugin.', $this->_var ); ?>
							</div>
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'theme', __( 'Theme', $this->_var ) ); ?><br />
							<div style="margin-left:18px; width:400px;">
								<?php _e( 'Use this destination if you are uploading a theme.', $this->_var ); ?>
							</div>
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'manual', __( 'Manual Path:', $this->_var ) ); ?>
							<input type="text" name="<?php echo $this->_var; ?>[destinationPath]" size="30" value="<?php if ( isset( $_POST[$this->_var]['destinationPath'] ) ) echo $_POST[$this->_var]['destinationPath']; ?>" /><br />
							<div style="margin-left:18px; width:400px;">
								<?php _e( "The manual path is relative to the root path of the WordPress installation. If the path doesn't exist, this program will attempt to create it before saving the file. Using previous directory (..) paths is not permitted.", $this->_var ); ?>
							</div>
					</td>
				</tr>
				<tr><th scope="row"><?php _e( 'Options', $this->_var ); ?></th>
					<td>
						<?php $this->addCheckBox( $this->_var, 'overwriteFile', 1, __( 'Overwrite existing file', $this->_var ) ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'renameIfExists', 1, __( 'Rename if file already exists (or if overwrite fails)', $this->_var ), false, ( empty( $_POST['upload'] ) ) ? true : false ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'extract', 1, __( 'Extract archived files', $this->_var ), false, ( empty( $_POST['upload'] ) ) ? true : false ); ?><br />
							<div style="margin-left:18px; width:400px;">
								<?php _e( 'The following extensions are supported: zip, tar, gz, tar.gz, tgz, tar.bz2, and tbz.', $this->_var ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'removeArchive', 1, __( 'Remove archive after successful extraction', $this->_var ) ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'forceExtractionFolder', 1, __( 'Force files to be extracted inside a folder', $this->_var ) ); ?>
								<div style="margin-left:18px; width:400px;">
									<?php _e( 'This places the extracted files into a folder with the same name as the archive file without the extension.', $this->_var ); ?>
								</div>
							</div>
					</td>
				</tr>
			</table>
			
			<?php $this->addSubmitButton( 'upload', __( 'Upload', $this->_var ) ); ?>
			<?php $this->addHidden( 'action', 'wp_handle_upload' ); ?>
		</form>
	</div>
<?php
		}
		
		
		// Form Handling Functions //////////////////
		
		function addHidden( $name, $value ) {
			if ( is_array( $value ) )
				$value = implode( ',', $value );
			
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
		
		function addSubmitButton( $name, $value, $disabled = false ) {
			echo '<p class="submit"><input type="submit" name="' . $name . '" value="' . $value . '" /></p>';
		}
		
		function addUsedInputs() {
			$usedInputs = '';
			
			foreach ( (array) $this->_usedInputs as $input ) {
				if ( ! empty( $usedInputs ) )
					$usedInputs .= ',';
				
				$usedInputs .= $input;
			}
			
			if ( ! empty( $usedInputs ) )
				echo '<input type="hidden" name="used-inputs" value="' . $usedInputs . '" />' . "\n";
		}
		
		function addShowHideLink( $elementID, $message, $hidden, $alterText = true ) {
			echo $this->getShowHideLink( $elementID, $message, $hidden, $alterText );
		}
		
		function getShowHideLink( $elementID, $message, $hidden, $alterText = true ) {
			if($hidden)
				$text = __( 'Show', $this->_var );
			else
				$text = __( 'Hide', $this->_var );
			
			if ( $alterText )
				return "<a id=\"$elementID-toggle\" href=\"javascript:{}\" onclick=\"if(document.getElementById('$elementID').style.display == 'none') { document.getElementById('$elementID').style.display = 'block'; document.getElementById('$elementID-toggle').innerHTML = 'Hide $message'; } else { document.getElementById('$elementID').style.display = 'none'; document.getElementById('$elementID-toggle').innerHTML = 'Show $message'; } return false;\">$text $message</a>";
			else
				return "<a id=\"$elementID-toggle\" href=\"javascript:{}\" onclick=\"if(document.getElementById('$elementID').style.display == 'none') { document.getElementById('$elementID').style.display = 'block'; document.getElementById('$elementID-toggle').innerHTML = '$message'; } else { document.getElementById('$elementID').style.display = 'none'; document.getElementById('$elementID-toggle').innerHTML = '$message'; } return false;\">$message</a>";
		}
		
		function addRadio( $base, $variable, $value, $message, $disabled = false ) {
			$selected = '';
			
			if ( isset( $_POST[$base][$variable] ) ) {
				if ( $value == $_POST[$base][$variable] )
					$selected = ' checked="checked"';
			}
			elseif ( empty( $this->_selectedVars[$variable] ) ) {
				$this->_selectedVars[$variable] = true;
				$selected = ' checked="checked"';
			}
			
?>
						<label for="<?php echo $base ?>-<?php echo $variable; ?>-<?php echo $value; ?>">
							<input name="<?php echo $base ?>[<?php echo $variable; ?>]" type="radio" id="<?php echo $base ?>-<?php echo $variable; ?>-<?php echo $value; ?>" value="<?php echo $value; ?>"<?php echo $selected ?><?php if ( $disabled ) echo ' disabled'; ?> />
							<?php echo $message; ?>
						</label>
<?php
			
			$this->_usedInputs[] = $variable;
		}
		
		function addCheckBox( $base, $variable, $value, $message, $disabled = false, $selected = false ) {
?>
						<label for="<?php echo $base ?>-<?php echo $variable; ?>">
							<input name="<?php echo $base ?>[<?php echo $variable; ?>]" type="checkbox" id="<?php echo $base ?>-<?php echo $variable; ?>" value="<?php echo $value; ?>"<?php if ( ( ! empty( $_POST[$base][$variable] ) && ( $value == $_POST[$base][$variable] ) ) || $selected ) echo ' checked="checked"'; ?><?php if ( $disabled ) echo ' disabled'; ?> />
							<?php echo $message; ?>
						</label>
<?php
			
			$this->_usedInputs[] = $variable;
		}
		
		function saveFormOptions() {
			if ( isset( $_POST['save'] ) && isset( $_POST[$this->_var] ) && is_array( $_POST[$this->_var] ) )
			{
				if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) )
					die( __( 'Cheatin uh?' ) );
				
				check_admin_referer( $this->_var . '-nonce' );
				
				if ( ! empty( $_POST['used-inputs'] ) ) {
					$usedInputs = explode( ',', $_POST['used-inputs'] );
					
					foreach ( (array) $usedInputs as $input ) {
						if ( isset( $_POST[$this->_var][$input] ) )
							$this->_options[$input] = $_POST[$this->_var][$input];
						elseif ( isset( $this->_options[$input] ) )
							unset( $this->_options[$input] );
					}
				}
				else
					foreach ( $_POST[$this->_var] as $key => $val )
						$this->_options[$key] = $val;
				
				
				if ( $this->save() )
					$this->showStatusMessage( __( 'Configuration updated', $this->_var ) );
				else {
					$this->showErrorMessage( __( 'Error while saving options', $this->_var ) );
					return false;
				}
			}
			
			return true;
		}
		
		
		// Plugin Functions ///////////////////////////
		
		// This is based off of code from the Google Sitemap Generator.
		// It was modified to support WordPress Mu.
		// Thanks Arne Brachhold :)
		function getBackLink() {
			return $_SERVER['REQUEST_URI'];
			
//			$page = basename( __FILE__ );
//			if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) )
//				$page = preg_replace( '[^a-zA-Z0-9\.\_\-]', '', $_GET['page'] );
//			
//			return $_SERVER['PHP_SELF'] . '?page=' .  $page;
		}
		
		function showStatusMessage( $message ) {
			
?>
	<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function showErrorMessage( $message ) {
			
?>
	<div id="message" class="error"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function getFileFromPost( $var, $uploads, $overwriteFile = false, $renameIfExists = true ) {
			$file = array();
			
			$overrides['overwriteFile'] = $overwriteFile;
			$overrides['renameIfExists'] = $renameIfExists;
			if ( ! empty( $uploads ) )
				$overrides['uploads'] = $uploads;
			
			$results = $this->handle_upload( $_FILES[$var], $overrides, $overwriteFile, $renameIfExists );
			
			if ( empty( $results['error'] ) ) {
				$file['path'] = $results['file'];
				$file['url'] = $results['url'];
				$file['originalName'] = preg_replace( '/\s+/', '_', $_FILES[$var]['name'] );
				$file['error'] = false;
			}
			else {
				$file['error'] = $results['error'];
			}
			
			return $file;
		}
		
		// I got this function from PlugInstaller (http://henning.imaginemore.de/pluginstaller/)
		// Thanks Henning Schaefer
		function getFileFromURL( $url, $uploads, $overwriteFile = false, $renameIfExists = true ) {
			$file = array();
			
			if ( preg_match( '/([^\/]+)$/', $url, $matches ) ) {
				$file['originalName'] = preg_replace( '/\s+/', '_', $matches[1] );
			}
			
			if ( false === ( $data = @file_get_contents( $url ) ) ) {
				$curl = curl_init( $url );
				curl_setopt( $curl, CURLOPT_HEADER, 0 );  // ignore any headers
				ob_start();  // use output buffering so the contents don't get sent directly to the browser
				curl_exec( $curl );  // get the file
				curl_close( $curl );
				$data = ob_get_contents();  // save the contents of the file into $file
				ob_end_clean();  // turn output buffering back off
			}
			
			$error = '';
			$message = '';
			
			if ( empty( $uploads ) )
				$uploads = wp_upload_dir();
			
			if ( false === $uploads['error'] ) {
				$filename = $this->unique_filename( $uploads['path'], $file['originalName'] );
				
				if ( file_exists( $uploads['path'] . '/' . $file['originalName'] ) ) {
					if ( $overwriteFile )
						$filename = $file['originalName'];
					elseif ( ! $renameIfExists )
						$error = __( 'The file already exists. Since overwriting and renaming are not permitted, the file was not added.', $this->_var );
				}
				
				
				if ( '' === $error ) {
					if ( false === $this->writeFile( $uploads['path'] . '/' . $filename, $data ) ) {
						if ( $renameIfExists ) {
							$filename = $this->unique_filename( $uploads['path'], $file['originalName'] );
							
							if ( false === $this->writeFile( $uploads['path'] . '/' . $filename, $data ) )
								$error = sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] . '/' . $filename );
							else
								$message = __( 'Unable to overwrite existing file. Since renaming is permitted, the file was saved with a new name.', $this->_var );
						}
						else
							$error = sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] . '/' . $filename );
					}
					else
						$message = __( 'Original file overwritten.', $this->_var );
				}
				
				$stat = stat( dirname( $uploads['path'] . '/' . $filename ) );
				$perms = $stat['mode'] & 0000666;
				@ chmod( $uploads['path'] . '/' . $filename, $perms );
				
				if ( ! empty( $error ) )
					$file['error'] = $error;
				else {
					$uploads['path'] = preg_replace( '/\/+/', '/', $uploads['path'] );
					
					$file['path'] = $uploads['path'] . '/' . $filename;
					$file['url'] = $uploads['url'] . '/' . $filename;
					$file['error'] = false;
					$file['message'] = '';
				}
			}
			
			
			return $file;
		}
		
		function writeFile( $path, $data ) {
			if ( false !== ( $destination = @fopen( $path, 'w' ) ) ) {
				if ( fwrite( $destination, $data ) ) {
					@fclose( $destination );
					
					return true;
				}
				
				@fclose( $destination );
			}
			
			return false;
		}
		
		// Customized version of wp_unique_filename from v2.5.1 wp-includes/functions.php
		// This version doesn't sanitize the file name
		function unique_filename( $dir, $filename ) {
			$ext = $this->getExtension( $filename );
			$name = basename( $filename, ".{$ext}" );
			
			// edge case: if file is named '.ext', treat as an empty name
			if( $name === ".$ext" )
				$name = '';
			
			$number = '';
			
			if ( empty( $ext ) )
				$ext = '';
			else
				$ext = strtolower( ".$ext" );
			
			$filename = str_replace( '%', '', $filename );
			$filename = preg_replace( '/\s+/', '_', $filename );
			
			while ( file_exists( $dir . '/' . $filename ) ) {
				if ( ! isset( $number ) ) {
					$number = 1;
					$filename = str_replace( $ext, $number . $ext, $filename );
				}
				else
					$filename = str_replace( $number . $ext, ++$number . $ext, $filename );
			}
			
			return $filename;
		}
		
		function getExtension( $filename ) {
			if ( preg_match( '/\.(tar\.\w+)$/' , $filename, $matches ) )
				return $matches[1];
			
			if ( preg_match( '/\.(\w+)$/', $filename, $matches ) )
				return $matches[1];
			
			return '';
		}
		
		// Customized version of wp_handle_upload from v2.5.1 wp-admin/includes/file.php
		function handle_upload( &$file, $overrides = false ) {
			// The default error handler.
			if (! function_exists( 'wp_handle_upload_error' ) ) {
				function wp_handle_upload_error( &$file, $message ) {
					return array( 'error'=>$message );
				}
			}
			
			// You may define your own function and pass the name in $overrides['upload_error_handler']
			$upload_error_handler = 'wp_handle_upload_error';
			
			// $_POST['action'] must be set and its value must equal $overrides['action'] or this:
			$action = 'wp_handle_upload';
			
			// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
			$upload_error_strings = array( false,
				__( "The uploaded file exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>.", $this->_var ),
				__( "The uploaded file exceeds the <em>MAX_FILE_SIZE</em> directive that was specified in the HTML form.", $this->_var ),
				__( "The uploaded file was only partially uploaded.", $this->_var ),
				__( "No file was uploaded.", $this->_var ),
				__( "Missing a temporary folder.", $this->_var ),
				__( "Failed to write file to disk.", $this->_var ) );
			
			// All tests are on by default. Most can be turned off by $override[{test_name}] = false;
			$test_form = true;
			$test_size = true;
			
			// If you override this, you must provide $ext and $type!!!!
			$test_type = true;
			$mimes = false;
			
			// Customizable overrides
			$uploads = wp_upload_dir();
			$overwriteFile = false;
			$renameIfExists = true;
			
			$message = '';
			
			// Install user overrides. Did we mention that this voids your warranty?
			if ( is_array( $overrides ) )
				extract( $overrides, EXTR_OVERWRITE );
			
			// A correct form post will pass this test.
			if ( $test_form && (!isset( $_POST['action'] ) || ($_POST['action'] != $action ) ) )
				return $upload_error_handler( $file, __( 'Invalid form submission.', $this->_var ) );
			
			// A successful upload will pass this test. It makes no sense to override this one.
			if ( $file['error'] > 0 )
				return $upload_error_handler( $file, $upload_error_strings[$file['error']] );
			
			// A non-empty file will pass this test.
			if ( $test_size && !($file['size'] > 0 ) )
				return $upload_error_handler( $file, __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini.', $this->_var ) );
			
			// A properly uploaded file will pass this test. There should be no reason to override this one.
			if (! @ is_uploaded_file( $file['tmp_name'] ) )
				return $upload_error_handler( $file, __( 'Specified file failed upload test.', $this->_var ) );
			
			// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
			if ( $test_type ) {
				$wp_filetype = wp_check_filetype( $file['name'], $mimes );
				
				extract( $wp_filetype );
				
				if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) )
					return $upload_error_handler( $file, __( 'File type does not meet security guidelines. Try another.', $this->_var ) );
				
				if ( !$ext )
					$ext = ltrim(strrchr($file['name'], '.'), '.');
				
				if ( !$type )
					$type = $file['type'];
			}
			
			// A writable uploads dir will pass this test. Again, there's no point overriding this one.
			if ( false !== $uploads['error'] )
				return $upload_error_handler( $file, $uploads['error'] );
			
			$uploads['path'] = untrailingslashit( $uploads['path'] );
			$uploads['path'] = preg_replace( '/\/+/', '/', $uploads['path'] );
			$uploads['url'] = untrailingslashit( $uploads['url'] );
			
			$file['name'] = preg_replace( '/\s+/', '_', $file['name'] );
			
			
			$filename = $this->unique_filename( $uploads['path'], $file['name'] );
			
			if ( file_exists( $uploads['path'] . '/' . $file['name'] ) ) {
				if ( $overwriteFile )
					$filename = $file['name'];
				elseif ( ! $renameIfExists )
					return $upload_error_handler( $file, __( 'The file already exists. Since overwriting and renaming are not permitted, the file was not added.', $this->_var ) );
			}
			
			if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) ) {
				if ( $overwriteFile ) {
					$filename = $this->unique_filename( $uploads['path'], $file['name'] );
					
					if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) )
						return $upload_error_handler( $file, sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] ) );
					else
						$message = __( 'Unable to overwrite existing file. Since renaming is permitted, the file was saved with a new name.', $this->_var );
				}
				else
					return $upload_error_handler( $file, sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] ) );
			}
			
			$stat = stat( dirname( $uploads['path'] . '/' . $filename ) );
			$perms = $stat['mode'] & 0000666;
			@ chmod( $uploads['path'] . '/' . $filename, $perms );
			
			// Compute the URL
			$url = $uploads['url'] . '/' . $filename;
			
			$return = apply_filters( 'wp_handle_upload', array( 'file' => $uploads['path'] . '/' . $filename, 'url' => $url, 'message' => $message, 'error' => false ) );
			
			return $return;
		}
		
		function extractArchive( $file, $forceExtractionFolder = true ) {
			$extensions = array( 'zip', 'tar', 'gz', 'tar.gz', 'tgz', 'tar.bz2', 'tbz' );
			$extension = $this->getExtension( $file['path'] );
			
			
			$originalIncludePath = ini_get( 'include_path' );
			
			ini_set( 'include_path', dirname(__FILE__) . '/pear' );
			
			if ( ! function_exists( 'file_archive_cleancache' ) )
				require_once( 'File/Archive.php' );
			
			
			$retval = array();
			
			if ( in_array( $extension, (array) $extensions ) ) {
				if ( is_callable( array( 'File_Archive', 'extract' ) ) && is_callable( array( 'File_Archive', 'read' ) ) ) {
					$backupCWD = getcwd();
					
					$path = dirname( $file['path'] );
					
					chdir( $path );
					
					$source = basename( $file['path'] ) . '/';
					
					if ( $forceExtractionFolder )
						$destination = basename( $file['path'], ".{$extension}" );
					else
						$destination = $path;
					
					$error = File_Archive::extract( $source, $destination );
					
					chdir( $backupCWD );
					
					if ( PEAR::isError( $error ) )
						$retval = array( 'extracted' => false, 'error' => sprintf( __( 'Extraction failed: %s', $this->_var ), $error->getMessage() ) );
					else
						$retval = array( 'destination' => path_join( $path, $destination ), 'extracted' => true, 'error' => false );
				}
				else
					$retval = array( 'extracted' => false, 'error' => __( 'Unable to execute File_Archive::extract', $this->_var ) );
			}
			else
				$retval = array( 'extracted' => false, 'error' => false );
			
			ini_set( 'include_path', $originalIncludePath );
			
			return $retval;
		}
	}
}


if ( class_exists( 'WPEasyUploader' ) ) {
	$wpeasyuploader = new WPEasyUploader();
}

?>
