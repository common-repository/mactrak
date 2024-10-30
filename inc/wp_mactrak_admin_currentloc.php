<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! current_user_can( 'edit_posts' ) ) die('Please login as Contributor or above to view MacTrak admin pages or Editor for full functionality.');

global $wpdb;
$mactrak_submit_success = null;

$mactrak_marker_table = $wpdb->prefix."mactrak_markertypes";

// Form Submit Process -- To do: Needs Data alidtion and Security***
if (isset($_POST) && isset($_POST['submit']) && current_user_can('edit_pages')) {
	check_admin_referer( 'mactrak_update_currentloc', 'mactrak_nonce' );
	$temp_POSTvar = null;
	
	if (isset($_POST['mactrak_current'])) $this->mactrak_display_options['current_marker_option'] = (int) $_POST['mactrak_current'];
	if (isset($_POST['mactrak_current_lat'])) $this->mactrak_display_options['fixed_lat'] = (float) $_POST['mactrak_current_lat'];
	if (isset($_POST['mactrak_current_lng'])) $this->mactrak_display_options['fixed_lng'] = (float) $_POST['mactrak_current_lng'];
	if (isset($_POST['mactrak_delay_show'])) $this->mactrak_display_options['route_delay'] = (int) $_POST['mactrak_delay_show'];
	if (isset($_POST['mactrak_destination'])) $this->mactrak_display_options['destination_line'] = (int) $_POST['mactrak_destination'];
	if (isset($_POST['mactrak_destination_lat'])) $this->mactrak_display_options['destination_lat'] = (float) $_POST['mactrak_destination_lat'];
	if (isset($_POST['mactrak_destination_lng'])) $this->mactrak_display_options['destination_lng'] = (float) $_POST['mactrak_destination_lng'];
	
	if (isset($_POST['mactrak_destination_color'])) {
		$temp_POSTvar = preg_replace("/[^A-Fa-f0-9]/i", "", $_POST['mactrak_destination_color']);
		$temp_POSTvar = substr($temp_POSTvar, 0, 6);
		if (strlen($temp_POSTvar) != 6) $temp_POSTvar = substr($temp_POSTvar, 0, 3);
		$this->mactrak_display_options['destination_color'] = strtoupper("#".$temp_POSTvar);
	}
	
	if (isset($_POST['mactrak_recent_color'])) {
		$temp_POSTvar = preg_replace("/[^A-Fa-f0-9]/i", "", $_POST['mactrak_recent_color']);
		$temp_POSTvar = substr($temp_POSTvar, 0, 6);
		if (strlen($temp_POSTvar) != 6) $temp_POSTvar = substr($temp_POSTvar, 0, 3);
		$this->mactrak_display_options['recent_color'] = strtoupper("#".$temp_POSTvar);
	}
	
	update_option('mactrak_display_options', $this->mactrak_display_options);
	
	$tempVal = abs(intval($_POST['mactrak_custom_image_id']));
	$mactrak_update_data = array (
		"Image" => ($tempVal > 0) ? "imgno:".$tempVal : "img:current", 
		"PinX" => floatval($_POST['mactrak_current_pinx']), 
		"PinY" => floatval($_POST['mactrak_current_piny']), 
		"Opacity" => intval($_POST['mactrak_current_opacity']), 
		"Scale" => intval($_POST['mactrak_current_scale'])
		);
		
	$mactrak_update_where = array("Name" => "current");
	
	$wpdb->update( $mactrak_marker_table, $mactrak_update_data, $mactrak_update_where);
	//echo "Last Error: ".$wpdb->last_error;
}

$current_img_obj = $this->mactrak_rtn_current_image_object();

//print_pre($current_img_obj);
?>

<div class="wrap mactrak">
<?php switch ($mactrak_submit_success) {
	case 1: ?>
	<div class="notice notice-success is-dismissible">
		<p>Settings Saved.</p>
	</div><?php break;
		
	case 2: ?>
	<div class="notice notice-error">
		<p>Error, settings not saved.</p>
	</div><?php break;
} ?>

<h1>MacTrak <em style="font-size: 0.7em">- The Spot Tracker interface for WP</em></h1>
<h2>My Current Location</h2>
<?php settings_errors(); ?>

<?php if (!current_user_can( 'edit_pages' )) { ?>
	<div class="notice notice-warning is-dismissible">
		<p><em>Unable to Save... Please login as Editor or above to change MacTrak settings.</em></p>
	</div> 
<?php } ?>


<form method="post" action="">
<?php wp_nonce_field( 'mactrak_update_currentloc', 'mactrak_nonce' ); ?>

<h4>Position for current location:</h4>
<p>	<input name="mactrak_current" id="mactrak_current_last" type="radio" value="1" <?php if ($this->mactrak_display_options['current_marker_option'] == 1) echo "checked"; ?>><label for="mactrak_current_last">Last GPS location from tracker</label><br>
	<input name="mactrak_current" id="mactrak_current_custom" type="radio" value="2" <?php if ($this->mactrak_display_options['current_marker_option'] == 2) echo "checked"; ?>><label for="mactrak_current_custom">User Defined:</label><br>
		<label for="mactrak_current_lat" class="mactrak_indent">Latitude:</label> <input name="mactrak_current_lat" id="mactrak_current_lat" type="text" size="15" maxlength="8" value="<?php echo $this->mactrak_display_options['fixed_lat']; ?>" onfocus="" onblur="" > <em>Positive = North, Negative = South</em><br>
		<label for="mactrak_current_lng" class="mactrak_indent">Longitude:</label> <input name="mactrak_current_lng" id="mactrak_current_lng" type="text" size="15" maxlength="8" value="<?php echo $this->mactrak_display_options['fixed_lng']; ?>" onfocus="" onblur="" > <em>Positive = East, Negative = West</em><br>
	<input name="mactrak_current" id="mactrak_current_none" type="radio" value="0" <?php if ($this->mactrak_display_options['current_marker_option'] == 0) echo "checked"; ?>><label for="mactrak_current_none">None - Do not show current Location</label>
</p>
 
 
<h4>Current Position Image</h4>
<div class="mactrak_current_image">
	<img id="mactrak_image_preview" src="<?php echo $current_img_obj->ImageData[0]; ?>">
</div>
<p>
		<input id="mactrak_upload_image_button" type="button" class="button" value="<?php _e( 'Change Image' ); ?>" />
		<input type="hidden" name="mactrak_custom_image_id" id="mactrak_custom_image_id" value="<?php echo $current_img_obj->Image; ?>">
	
</p>

<h4>Pin Point Reference</h4>
<p>This is the point of the image that is pinned to map. Measured from bottom left of image and specified in relation to original image dimensions<br>
	<label for="mactrak_current_pinx">Horizontal Position (x):</label> <input name="mactrak_current_pinx" id="mactrak_current_pinx" type="text" size="15" maxlength="10" value="<?php echo $current_img_obj->PinX ?>" onfocus="" onblur="" > Pixels - <em>Selected Image Original Width: <span id="mactrak_current_x"><?php echo $current_img_obj->ImageData[1] ?></span>px</em><br>
	<label for="mactrak_current_piny">Vertical Position (y):</label> <input name="mactrak_current_piny" id="mactrak_current_piny" type="text" size="15" maxlength="10" value="<?php echo $current_img_obj->PinY ?>" onfocus="" onblur="" > Pixels - <em>Selected Image Original Height:  <span id="mactrak_current_y"><?php echo $current_img_obj->ImageData[2] ?></span>px</em>
</p>

<h4>Transparency</h4>
<p> <label for="mactrak_current_opacity">Marker Transparency</label>
<select name="mactrak_current_opacity" id="mactrak_current_opacity">
  <option value="10" <?php if ($current_img_obj->Opacity == 10) echo "selected=\"selected\""; ?>>1 - Opaque</option>
  <option value="9" <?php if ($current_img_obj->Opacity == 9) echo "selected=\"selected\""; ?>>0.9</option>
  <option value="8" <?php if ($current_img_obj->Opacity == 8) echo "selected=\"selected\""; ?>>0.8</option>
  <option value="7" <?php if ($current_img_obj->Opacity == 7) echo "selected=\"selected\""; ?>>0.7</option>
  <option value="6" <?php if ($current_img_obj->Opacity == 6) echo "selected=\"selected\""; ?>>0.6</option>
  <option value="5" <?php if ($current_img_obj->Opacity == 5) echo "selected=\"selected\""; ?>>0.5</option>
  <option value="4" <?php if ($current_img_obj->Opacity == 4) echo "selected=\"selected\""; ?>>0.4</option>
  <option value="3" <?php if ($current_img_obj->Opacity == 3) echo "selected=\"selected\""; ?>>0.3</option>
  <option value="2" <?php if ($current_img_obj->Opacity == 2) echo "selected=\"selected\""; ?>>0.2</option>
  <option value="1" <?php if ($current_img_obj->Opacity == 1) echo "selected=\"selected\""; ?>>0.1 - Transparent</option>
</select>
</p>


<h4>Image Scaling</h4>
<p> <label for="mactrak_current_scale">Scale at which to show marker image (defined as a percentage of original image size):</label> 
	<input name="mactrak_current_scale" id="mactrak_current_scale" type="text" size="15" maxlength="3" value="<?php echo $current_img_obj->Scale ?>" onfocus="" onblur="" > % - <em>Scaled image will measure
		<span id="mactrak_current_scaled_x"><?php echo intval($current_img_obj->Scale/100*$current_img_obj->ImageData[1]) ?></span>px x 
		<span id="mactrak_current_scaled_y"><?php echo intval($current_img_obj->Scale/100*$current_img_obj->ImageData[2]) ?></span>px</em>
</p>

<h3>Track Point Delay</h3>
<p><label for="mactrak_delay_show">Delay display of track by:</label>
<select name="mactrak_delay_show" id="mactrak_delay_show">
  <option value="0" <?php if ($this->mactrak_display_options['route_delay'] == 1) echo "selected=\"selected\""; ?>>No Delay</option>
  <option value="6" <?php if ($this->mactrak_display_options['route_delay'] == 6) echo "selected=\"selected\""; ?>>6 Hours</option>
  <option value="12" <?php if ($this->mactrak_display_options['route_delay'] == 12) echo "selected=\"selected\""; ?>>12 Hours</option>
  <option value="24" <?php if ($this->mactrak_display_options['route_delay'] == 24) echo "selected=\"selected\""; ?>>1 Day</option>
  <option value="48" <?php if ($this->mactrak_display_options['route_delay'] == 48) echo "selected=\"selected\""; ?>>2 Days</option>
  <option value="72" <?php if ($this->mactrak_display_options['route_delay'] == 72) echo "selected=\"selected\""; ?>>3 Days</option>
  <option value="168" <?php if ($this->mactrak_display_options['route_delay'] == 168) echo "selected=\"selected\""; ?>>1 Week</option>
</select><br>
Track Point Delay sets the default show delay shortcodes only, delay times can be set locally in shortcodes by specifying a number of hours which overrides this default.<br>
Admin screen will show full track irrispective of delay setting.<br>
<p><label for="mactrak_recent_color">Recent Track Colour (for Admin Map): </label><span class="mactrak_colordisp" id="mactrak_recent_colordisp" style="background-color: <?php echo $this->mactrak_display_options['recent_color']; ?>">&nbsp;</span><input name="mactrak_recent_color" id="mactrak_recent_color" type="text" size="10" maxlength="7" value="<?php echo $this->mactrak_display_options['recent_color'] ?>" onfocus="" onblur="" > <em>Hexadecimal Colour Reference, eg. #FF0000; use 3 or 6 digit notation, errors will be truncated.</em>
</p>

<h3>Destination Line</h3>
<p>Show flightline linking current position to a destination point.<br>
	<input name="mactrak_destination" id="mactrak_destination_off" type="radio" value="0" <?php if ($this->mactrak_display_options['destination_line'] == 0) echo "checked"; ?>><label for="mactrak_destination_off">Hide</label><br>
	<input name="mactrak_destination" id="mactrak_destination_on" type="radio" value="1" <?php if ($this->mactrak_display_options['destination_line'] == 1) echo "checked"; ?>><label for="mactrak_destination_on">Show</label><br>
	<label for="mactrak_destination_lat" class="mactrak_indent">Destination Latitude: </label> <input name="mactrak_destination_lat" id="mactrak_destination_lat" type="text" size="15" maxlength="8" value="<?php echo $this->mactrak_display_options['destination_lat']; ?>" onfocus="" onblur="" > <em>Positive = North, Negative = South</em><br>
	<label for="mactrak_destination_lng" class="mactrak_indent">Destination Longitude: </label> <input name="mactrak_destination_lng" id="mactrak_destination_lng" type="text" size="15" maxlength="8" value="<?php echo $this->mactrak_display_options['destination_lng']; ?>" onfocus="" onblur="" > <em>Positive = East, Negative = West</em>
</p>
<p> <label for="mactrak_destination_color">Destination Line Colour: </label><span class="mactrak_colordisp" id="mactrak_destination_colordisp" style="background-color: <?php echo $this->mactrak_display_options['destination_color']; ?>">&nbsp;</span><input name="mactrak_destination_color" id="mactrak_destination_color" type="text" size="10" maxlength="7" value="<?php echo $this->mactrak_display_options['destination_color'] ?>" onfocus="" onblur="" > <em>Hexadecimal Colour Reference, eg. #FF0000; use 3 or 6 digit notation, errors will be truncated.</em>
</p>

<?php if (current_user_can( 'edit_pages' )) submit_button( 'Save Settings' );
else echo "<h4>Unable to Save... Please login as Editor or above to change MacTrak settings.</h4>"; ?>
	
</form>
</div>

