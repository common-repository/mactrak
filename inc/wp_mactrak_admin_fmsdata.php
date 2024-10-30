<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! current_user_can( 'edit_posts' ) ) die('Please login as Contributor or above to view MacTrak admin pages or Editor for full functionality.');

$mactrak_submit_success = null;

//	check_admin_referer( 'mactrak_spot_data', 'mactrak_nonce_spot' );  Should only be processed by Ajax call to other function

// Import Data form trigger
if (isset($_POST) && isset($_POST['submit']) && $_POST['submit'] == "Import Data" && 
	isset($_POST['mactrak_import_data']) && $_POST['mactrak_import_data'] == "gobabygo" && // Ensure user has clicked checkbox
	current_user_can('edit_pages')) { 

	check_admin_referer( 'mactrak_import_data', 'mactrak_nonce_import' );
	
	if ($_FILES['mactrak_upload_route']['error'] == UPLOAD_ERR_OK // Upload Error?
		&& is_uploaded_file($_FILES['mactrak_upload_route']['tmp_name'])) { // Upload successful?
		$this->mactrak_fmsdata_import();
	} else $mactrak_submit_success = 3;	
}
	
// Export Data form trigger
if (isset($_POST) && isset($_POST['submit']) && $_POST['submit'] == "Download CSV Data File" && 
	current_user_can('edit_pages')) { 
	

}

// Runs on image click, ie. Submit button not pressed as such is an option
if (isset($_POST) && !isset($_POST['submit']) && isset($_POST['mactrak_table_content']) &&
	current_user_can('edit_pages')) { 
	
	if ($_POST['mactrak_table_content'] == "route")	$tableSuffix = "route";
	else die("Invalid content trigger");
	
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
	
	global $wpdb;
 	$route_table_name = $wpdb->prefix . "mactrak_" . $tableSuffix; 
 	$marker_table_name = $wpdb->prefix . "mactrak_markers"; 
 	
	foreach ($processArray as $key => $serArray) {
		switch ($key) {
			case 'convert':
				foreach ($serArray as $lineNo) {
					$fmsConvertObj = $wpdb->get_row("SELECT 
			 			`Route`, `UNIXTime`, `Latitude`, `Longitude` FROM `{$route_table_name}` WHERE `Ser` = {$lineNo}");
					$wpdb->insert( $marker_table_name, array( 
						'UNIXTime' => $fmsConvertObj->UNIXTime,
						'Route' => $fmsConvertObj->Route,
						'Latitude' => $fmsConvertObj->Latitude,
						'Longitude' => $fmsConvertObj->Longitude)
					);
				}
				break;
			case 'delete':
				foreach ($serArray as $lineNo) $wpdb->update($route_table_name, array('Deleted' => 1), array('Ser' => $lineNo)); // Ready for batch process
				break;
			case 'edit':
				//foreach ($serArray as $lineNo) $wpdb->update($route_table_name, array('Deleted' => 1), array('Ser' => $lineNo));				
				break;
			case 'add-above':
				
				break;
			case 'add-below':
				
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
		<p>Settings Saved.</p>
	</div><?php break;
		
	case 2: ?>
	<div class="notice notice-error">
		<p>Error, settings not saved.</p>
	</div><?php break;
		
	case 3: ?>
	<div class="notice notice-error">
		<p>Error, Data Import Error.</p>
	</div><?php break;
} ?>

<h1>MacTrak <em style="font-size: 0.7em">- The Spot Tracker interface for WP</em></h1>
<h2>FindMeSpot (Spot Tracker) Data</h2>
<form method="post" action="" onsubmit="">
	<input type="hidden" name="mactrak_table_content" value="route" >
	<?php wp_nonce_field( 'mactrak_update_route', 'mactrak_nonce_route' ); ?>
	
	<table>
		<thead>
			<tr>
				<th class="mactrak_col_head">&nbsp;</th>
				<th class="mactrak_col_head">Track Number</th>
				<th class="mactrak_col_head">Date</th>
				<th class="mactrak_col_head">Latitude</th>
				<th class="mactrak_col_head">Longitude</th>
				<th class="mactrak_col_head">Device Name</th>
				<th class="mactrak_col_head">Message Type</th>
				<th class="mactrak_col_head">Colour</th>
				<th class="mactrak_col_head">Line Type</th>
				<th class="mactrak_col_head">Options</th>
			</tr>
		</thead>
		<tbody><?php $this->mactrak_routedata_display(); ?></tbody>
	</table>
</form>

<p><em>(Edit options available soon)<br>
	(Note: Removing points hides them rather than deleting so points will not reimport; show/undelete options coming soon)
</em></p>
 

<?php // Add User Privilages note
if (! current_user_can('edit_pages')) { ?>
<p><em>Options to change data are only available to users with Editor privilages and above.</em></p>
<?php } ?>


<h3><a href="javascript:return false;" id="mactrak_view_live_spot" class="mactrak_indent" >
	<img src="<?php echo $this->pluginBaseURI ?>img/icon_plus.gif">View Live Spot Data</a></h3>
	
<form class="mactrak_indent" id="mactrak_view_live_spot_content"  style="display:none;" onsubmit="return false;">
	<?php wp_nonce_field( 'mactrak_spot_data', 'mactrak_nonce_spot' ); ?>
	<textarea class="mactrak_spot_data" onfocus="this.select();">Loading Please Wait..</textarea>
</form>

<h3><a href="javascript:return false;" id="mactrak_import_spot" class="mactrak_indent" >
	<img src="<?php echo $this->pluginBaseURI ?>img/icon_plus.gif">Import Route Data</a></h3>

<form class="mactrak_indent" id="mactrak_import_spot_content"  style="display:none;" method="post" action="" onsubmit=""  enctype="multipart/form-data">
<?php if (current_user_can('edit_pages')) { 
	wp_nonce_field( 'mactrak_import_data', 'mactrak_nonce_import' ); ?>
	
	<ul>	
		<li>Step 1: <a href="<?php echo $this->pluginBaseURI ?>manual_route_upload.txt" download="manual_route_upload.txt">Download</a> the <em>manual_route_upload.txt</em> data file outline,</li>
		<li>Step 2: Format your CSV data and add (cut and paste) as text to the downloaded file as per contained instructions,</li>
		<li>Step 3: Upload your edited <em>manual_route_upload.txt</em> file:
			<input id="mactrak_upload_route" name="mactrak_upload_route" type="file" /></li>
		<li><label for="mactrak_import_data">Step 4: Confirm ready to import:</label> <input name="mactrak_import_data" id="mactrak_import_data" type="checkbox" value="gobabygo" onfocus="" onblur="" ></li>
		<li>Step 5: Click Import Data below.<br>
			Note: for large volumes of data the import process may take a while, do not refresh browser or navigate away.</li>
	</ul>
<?php submit_button( 'Import Data' );
} else echo "<p><em>Import function requires Editor privilages or above.</em></p>"; ?>

</form>

<h3><a href="javascript:return false;" id="mactrak_export_route" class="mactrak_indent" >
	<img src="<?php echo $this->pluginBaseURI ?>img/icon_plus.gif">Export MacTrak Route Data</a></h3>

<form class="mactrak_indent" id="mactrak_export_route_data"  style="display:none;" method="post" action="" onsubmit=""  enctype="multipart/form-data">
<p>Download function coming soon...</p>
<?php if (current_user_can('edit_pages')) { 
	//wp_nonce_field( 'mactrak_export_data', 'mactrak_nonce_export' );
	//submit_button( 'Download CSV Data File' );
} else echo "<p><em>Export function requires Editor privilages or above.</em></p>"; ?>
</form>

</div>