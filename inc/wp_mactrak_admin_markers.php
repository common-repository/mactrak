<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! current_user_can( 'edit_posts' ) ) die('Please login as Contributor or above to view MacTrak admin pages or Editor for full functionality.');

// wp_kses_data to strip title and content input
//print_pre($this->mactrak_display_options);
//print_pre($_POST);
$mactrak_submit_success = null;

if (isset($_POST) && $_POST['submit'] == "Add Line..." && current_user_can('edit_posts')) {
	check_admin_referer( 'mactrak_new_customline', 'mactrak_nonce_new_customline' );
	
	// Add Custom Line Script
	$temp_POSTvar = null;
	$clean_Insert = null;
	
	if (isset($_POST['mactrak_newline_dateval'])) {
		//echo '<br>1.';print_pre($temp_POSTvar);
		$temp_POSTvar = wp_kses_data($_POST['mactrak_newline_dateval']);
		$temp_POSTvar = explode("-", $temp_POSTvar);
		if (checkdate($temp_POSTvar[1], $temp_POSTvar[0], $temp_POSTvar['2'])) 
			$clean_Insert['Date'] = gmmktime(0, 0, 0, $temp_POSTvar[1], $temp_POSTvar[0], $temp_POSTvar['2']);
		$temp_POSTvar = null;
	}
	
	if (isset($_POST['mactrak_newline_name'])) $clean_Insert['Name'] = substr(sanitize_text_field($_POST['mactrak_newline_name']), 0, 50);
	
	if (isset($_POST['mactrak_newline_from_lat'])) $clean_Insert['StartLat'] = (float) $_POST['mactrak_newline_from_lat'];
		else $clean_Insert['mactrak_newline_from_lat'] = 0;
    if (isset($_POST['mactrak_newline_from_lng'])) $clean_Insert['StartLong'] = (float) $_POST['mactrak_newline_from_lng'];
		else $clean_Insert['mactrak_newline_from_lng'] = 0;
    if (isset($_POST['mactrak_newline_to_lat'])) $clean_Insert['FinishLat'] = (float) $_POST['mactrak_newline_to_lat'];
		else $clean_Insert['mactrak_newline_to_lat'] = 100;
    if (isset($_POST['mactrak_newline_to_lng'])) $clean_Insert['FinishLong'] = (float) $_POST['mactrak_newline_to_lng'];
		else $clean_Insert['mactrak_newline_to_lng'] = 100;

	if (isset($_POST['mactrak_newline_color'])) {
		$temp_POSTvar = preg_replace("/[^A-Fa-f0-9]/i", "", $_POST['mactrak_newline_color']);
		$temp_POSTvar = substr($temp_POSTvar, 0, 6);
		if (strlen($temp_POSTvar) != 6) $temp_POSTvar = substr($temp_POSTvar, 0, 3);
		$clean_Insert['Color'] = strtoupper("#".$temp_POSTvar);
	}

	if (isset($_POST['mactrak_newline_linetype'])) {
		$temp_POSTvar = (int) $_POST['mactrak_newline_linetype'];
		$clean_Insert['Arc'] = ($temp_POSTvar == 1) ? 1 : 0;
	} 

	//print_pre($clean_Insert);

	global $wpdb; 
	$customlines_table_name = $wpdb->prefix . "mactrak_customlines"; 
	$wpdb->insert($customlines_table_name, $clean_Insert);

}

if (isset($_POST) && $_POST['submit'] == "Add Marker..." && current_user_can('edit_posts')) {
	check_admin_referer( 'mactrak_new_marker', 'mactrak_nonce_new_marker' );
	
	// Add Marker Point Script
	$temp_POSTvar = null;
		
}

if (isset($_POST) && !isset($_POST['submit']) && isset($_POST['mactrak_table_content']) &&
	current_user_can('edit_pages')) { // Runs on image click, ie. Submit button not pressed as such is an option
	
	$tableSuffix = ($_POST['mactrak_table_content'] == "customlines") ? "customlines" : "markers";
	check_admin_referer( 'mactrak_update_'.$tableSuffix, 'mactrak_nonce_'.$tableSuffix );
			
	$postKeys = array_keys($_POST);
	$processArray = array();
	
	foreach ($postKeys as $keyName) {
		if (strpos($keyName, 'mactrak-trigger') !== false) {
			$process = explode("_", $keyName);
			
			if (!array_key_exists($process[1], $processArray))// Build array of delete/edit etc keys not sure if i'll need this as call is likely to be only one of these action types 
				$processArray[$process[1]] = array(); 
			if (!in_array($process[3], $processArray[$process[1]] )) // If number for action doesnt exist in array - i.e. dont process twice for x-y variables
				$processArray[$process[1]][] = $process[3]; // Add number to array
		}
	}
	
	//print_pre($processArray, "Process Array");
	
	global $wpdb;
 	$table = $wpdb->prefix . "mactrak_" . $tableSuffix; 
	
	foreach ($processArray as $key => $serArray) {
		switch ($key) {
			case 'delete':
				foreach ($serArray as $lineNo) $wpdb->update($table, array('Deleted' => 1), array('Ser' => $lineNo)); // Ready for batch process
				break;
			case 'edit':
				//foreach ($serArray as $lineNo) $wpdb->update($table, array('Deleted' => 1), array('Ser' => $lineNo));				
				break;
			default: break;
		}
	}
	
}

?>

<div class="wrap mactrak">
<?php switch ($mactrak_submit_success) {
	case 1: ?>
	<div class="notice notice-success is-dismissible">
		<p>Saved.</p>
	</div><?php break;
		
	case 2: ?>
	<div class="notice notice-error">
		<p>Error, not saved.</p>
	</div><?php break;
} ?>

<h1>MacTrak <em style="font-size: 0.7em">- The Spot Tracker interface for WP</em></h1>
<h2>Custom Flight Lines</h2>

<p>Use Custom Flight Lines, ie. custom line segments, to join tracks, display overview routes or link destinations. <em>(Edit option available soon)</em></p>

<form method="post" action="">
	<input type="hidden" name="mactrak_table_content" value="customlines" >
	<?php wp_nonce_field( 'mactrak_update_customlines', 'mactrak_nonce_customlines' ); ?>
	<table><?php $this->mactrak_customlinesdata_display(); ?></table>
</form>

<?php if (! current_user_can('edit_pages')) { ?>
<p><em>Options to change data are only available to users with Editor privilages and above.</em></p>
<?php } ?>

<h3 class="mactrak_indent"><a href="javascript:return false;" id="mactrak_add_customline_start" >
	<img src="<?php echo $this->pluginBaseURI ?>img/icon_plus.gif">Add New Line...</a></h3>

<form class="add_item" id="mactrak_add_customline" style="display:none;" method="post" action="">
<?php wp_nonce_field( 'mactrak_new_customline', 'mactrak_nonce_new_customline' ); ?>

<p><label for="mactrak_newline_dateval">Date (dd-mm-yyyy):</label> <input name="mactrak_newline_dateval" id="mactrak_newline_dateval" type="text" size="15" maxlength="10" value="<?php echo date("d-m-Y"); ?>" onfocus="this.select();" onblur="" >
</p>


<p><label for="mactrak_newline_name">Name:</label> <input name="mactrak_newline_name" id="mactrak_newline_name" type="text" size="50" maxlength="50" value="Across the Pond." onfocus="this.select();" onblur="" >
</p>

<p>From<br>
		<label for="mactrak_newline_from_lat" class="mactrak_indent">Latitude:</label> <input name="mactrak_newline_from_lat" id="mactrak_newline_from_lat" type="text" size="15" maxlength="12" value="51.5000" onfocus="this.select();" onblur="" > <em>Positive = North, Negative = South</em><br>
		<label for="mactrak_newline_from_lng" class="mactrak_indent">Longitude:</label> <input name="mactrak_newline_from_lng" id="mactrak_newline_from_lng" type="text" size="15" maxlength="12" value="0.0000" onfocus="this.select();" onblur="" > <em>Positive = East, Negative = West</em><br>
To<br>
		<label for="mactrak_newline_to_lat" class="mactrak_indent">Latitude:</label> <input name="mactrak_newline_to_lat" id="mactrak_newline_to_lat" type="text" size="15" maxlength="12" value="47.6000" onfocus="this.select();" onblur="" > <em>Positive = North, Negative = South</em><br>
		<label for="mactrak_newline_to_lng" class="mactrak_indent">Longitude:</label> <input name="mactrak_newline_to_lng" id="mactrak_newline_to_lng" type="text" size="15" maxlength="12" value="-52.8000" onfocus="this.select();" onblur="" > <em>Positive = East, Negative = West</em><br>
</p>

<p><label for="mactrak_newline_color">Colour: </label><span class="mactrak_colordisp" id="mactrak_newline_colordisp" style="background-color: <?php echo $this->mactrak_display_options['color']; ?>">&nbsp;</span><input name="mactrak_newline_color" id="mactrak_newline_color" type="text" size="10" maxlength="7" value="<?php echo $this->mactrak_display_options['color']; ?>" onfocus="this.select();" onblur="" > <em>Hexadecimal Colour Reference, eg. #FF0000; use 3 or 6 digit notation, errors will be truncated.</em>
</p>

<p>Line Type<br>
	<input name="mactrak_newline_linetype" id="mactrak_newline_linetype_st" class="mactrak_indent" type="radio" value="0" <?php if ($this->mactrak_display_options['gc_line'] == 0) echo "checked"; ?>><label for="mactrak_newline_linetype_st">Straight Line</label><br>
	<input name="mactrak_newline_linetype" id="mactrak_newline_linetype_gc" class="mactrak_indent" type="radio" value="1" <?php if ($this->mactrak_display_options['gc_line'] == 1) echo "checked"; ?>><label for="mactrak_newline_linetype_gc">Great Circle Line</label>
</p>

<p><em>nb: If a Custom Flight Line corresponds to a segment of Spot track, a 'Custom Line' can also be achieved by editing the corresponding data point to show the desired style and colour.<br>
		It should be noted however, that this alternative method will require Spot Data to use the same MacTrak Track Number, hence this may not be an ideal option.</em></p>

<?php submit_button( 'Add Line...' ); ?>


</form>

<br>

<h2><strike>Custom Map Markers</strike></h2>
<p>Coming Soon - Custom Markers, Labels and Images</p>
<!--p>Whereas Spot data is automatically downloaded and can be shown as a route track, markers are user defined points that will have a pin point and extra data attached,br>
You can import Spot data to markers from the FindMeSpot admin page or create custom positions from scratch
</p-->

<form method="post" action="">
	<input type="hidden" name="mactrak_table_content" value="markers" >
	<?php wp_nonce_field( 'mactrak_update_markers', 'mactrak_nonce_markers' ); ?>
	<table><?php $this->mactrak_markerdata_display(); ?></table>
</form>

<h3 class="mactrak_indent"><a href="javascript:return false;" id="mactrak_add_marker_start" >
	<img src="<?php echo $this->pluginBaseURI ?>img/icon_plus.gif">Add New Marker...</a></h3>
<form class="add_item" id="mactrak_add_marker" style="display:none;" method="post" action="">
	<?php wp_nonce_field( 'mactrak_new_marker', 'mactrak_nonce_new_marker' ); ?>
	
	Markers coming soon...
	<?php submit_button( 'Add Marker...' ); ?>
</form>

</div>

