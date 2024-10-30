<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! current_user_can( 'edit_pages' ) ) die('Please login as Editor or above to change MacTrak settings.');

//print_pre($_POST);
//print_pre($this->mactrak_display_options);
//print_pre($this->mactrak_preset_options);

$mactrak_submit_success = null;

if (isset($_POST) && isset($_POST['submit']) && current_user_can('edit_pages')) {
	check_admin_referer( 'mactrak_update_settings', 'mactrak_nonce' );
	$temp_POSTvar = null;
	
	if (isset($_POST['mactrak_default_color'])) {
		$temp_POSTvar = preg_replace("/[^A-Fa-f0-9]/i", "", $_POST['mactrak_default_color']);
		// preg_match("/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i", $temp_POSTvar);
		$temp_POSTvar = substr($temp_POSTvar, 0, 6);
		if (strlen($temp_POSTvar) != 6) $temp_POSTvar = substr($temp_POSTvar, 0, 3);
		$this->mactrak_display_options['color'] = strtoupper("#".$temp_POSTvar);
	}
	
	if (isset($_POST['mactrak_default_tracknum'])) $this->mactrak_display_options['track_no'] = (int) $_POST['mactrak_default_tracknum'];
	
	if (isset($_POST['mactrak_show_strapline'])) $this->mactrak_preset_options['strapline'] = 1;
		else $this->mactrak_preset_options['strapline'] = 0;
	
	if (isset($_POST['mactrak_default_gcline'])) {
		$temp_POSTvar = (int) $_POST['mactrak_default_gcline'];
		$this->mactrak_display_options['gc_line'] = ($temp_POSTvar == 1) ? 1 : 0;
	}
	
	if (isset($_POST['mactrak_spot_key'])) $this->mactrak_preset_options['SpotID'] = sanitize_text_field($_POST['mactrak_spot_key']);
	if (isset($_POST['mactrak_maps_key'])) $this->mactrak_preset_options['MapID'] = sanitize_text_field($_POST['mactrak_maps_key']);
	
	if (isset($_POST['mactrak_update_freq'])) $this->mactrak_preset_options['update_freq'] = (int) $_POST['mactrak_update_freq'];
	
	if (isset($_POST['mactrak_default_delete'])) {
		$temp_POSTvar = (int) $_POST['mactrak_default_delete']; 
		if ($temp_POSTvar == 1) $this->mactrak_preset_options['uninstall_delete_data'] = 1;
		elseif ($temp_POSTvar == 2) $this->mactrak_preset_options['uninstall_delete_data'] = 2;
		else $this->mactrak_preset_options['uninstall_delete_data'] = 0;
	}
	
	update_option('mactrak_display_options', $this->mactrak_display_options);
	update_option('mactrak_preset_options', $this->mactrak_preset_options);
	
}

//print_pre($this->mactrak_display_options);
//print_pre($this->mactrak_preset_options);

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
<h2>Settings</h2>

<form method="post" action="">
<?php wp_nonce_field( 'mactrak_update_settings', 'mactrak_nonce' ); ?>

<h4>Google Maps API Key</h4>
<p>To display custom Google Maps you require a Google Maps Javascipt API Key. Instructions here: <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_new">https://developers.google.com/maps/documentation/javascript/get-api-key</a></p>
<p><label for="mactrak_maps_key">Key:</label> <input name="mactrak_maps_key" id="mactrak_maps_key" type="text" size="50" maxlength="50" value="<?php echo $this->mactrak_preset_options['MapID'] ?>" onfocus="" onblur="" >
</p>

<h4>Find Me Spot Share page GLID</h4>
<p>This is the ID number that can be found after creating a share page on the FindMeSpot website. Log in and create/access a Share page and find the web address for this.<br>
	It will be something like: http://share.findmespot.com/shared/faces/viewspots.jsp?glId=<strong>0ZkQYx678iDLyQwZLMbLXuN0PdVc</strong><br>
	Your GLID is the unique string of characters like that shown in bold above</p>
<p>Log in and set up a Spot Share Page here: <a href="https://login.findmespot.com/spot-main-web/myaccount/share/list.html" target="_new" >Find Me Spot Login</a></p>

<p><label for="mactrak_spot_key">GLID:</label> <input name="mactrak_spot_key" id="mactrak_spot_key" type="text" size="50" maxlength="50" value="<?php echo $this->mactrak_preset_options['SpotID'] ?>" onfocus="" onblur="" >
</p>

<h4>Current Track Number</h4>
<p>Every time a Spot ping gets recorded into your wordpress database via MacTrak a Track Number will be added to it by default. Spot locations pings are grouped by this track number.<br>
	Incrementing the Track number will cause a break point in the track line between the sequential location pings. This way you can show multiple separate tracks on the same map.<br> 
	To start a fresh track line change this number to something unique (eg. increment it by 1)<br>
	To continue a previous track line change this number to that of the track you wish to continue.</p>
<p><label for="mactrak_default_tracknum">Add subsequent tracker data with Track Number:</label> <input name="mactrak_default_tracknum" id="mactrak_default_tracknum" type="text" size="15" maxlength="3" value="<?php echo $this->mactrak_display_options['track_no'] ?>" onfocus="" onblur="" >
</p>

<h4>Show Strapline</h4>
<p><input name="mactrak_show_strapline" id="mactrak_show_strapline" type="checkbox" <?php if ($this->mactrak_preset_options['strapline'] == 1) echo "checked"; ?>> <label for="mactrak_show_strapline">Show Strapline on Public Maps </label><br>
	<?php if ($this->mactrak_preset_options['strapline'] == 0) echo "Please consider supporting MacTrak by adding a strapline to your public maps"; ?>
</p>

<h4>Default Track Color</h4>
<p> <label for="mactrak_default_color">Hexadecimal Color Reference: <strong>#</strong></label><span class="mactrak_colordisp" id="mactrak_default_colordisp" style="background-color: <?php echo $this->mactrak_display_options['color']; ?>">&nbsp;</span><input name="mactrak_default_color" id="mactrak_default_color" type="text" size="10" maxlength="7" value="<?php echo $this->mactrak_display_options['color'] ?>" onfocus="" onblur="" > <em>eg. #FF0000; use 3 or 6 digit notation, errors will be truncated.</em>
</p>

<h4>Great Circle Lines</h4>
<p>Over large distances Great Circle lines are straight in reality but show as curved sections on Google Maps (ie. on Mercator charts/maps).<br>
	Do you wish to show the track segments between Spot Pings as Great Circle lines or as visually straight lines?</p>
<p><label for="mactrak_default_gcline">Default Line Shape:</label> 
<select name="mactrak_default_gcline" id="mactrak_default_gcline">
  <option value="1" <?php if ($this->mactrak_display_options['gc_line'] == 1) echo "selected=\"selected\""; ?>>Great Circle</option>
  <option value="0" <?php if ($this->mactrak_display_options['gc_line'] == 0) echo "selected=\"selected\""; ?>>Straight</option>
</select>
</p>

<h4>Maximum Update Frequency</h4>
<p>Spot (FindMeSpot) have a fair usage policy for the use of their tracking server for data download. In the absence of a true Cron-Job facility in WordPress, Mactrak is designed to update whenever a viewer or adminisrator loads a MacTrak map.
	For sites with high traffic this can cause an excessive number of calls to the Spot servers. To protect against this a maximum update frequency can be set. </p>
<ul>
	<li>Updates every minute or hourly should be suitable for the automated route tracking,</li>
	<li>Daily updates should be suitable for manual message pings,</li>
	<li>Turning this off - ie. selecting the Instant setting - will ensure updates without delays but may cause the Spot servers to block calls.</li>
</ul>
<p><label for="mactrak_update_freq">Download Tracker data every:</label>
<select name="mactrak_update_freq" id="mactrak_update_freq">
  <option value="0" <?php if ($this->mactrak_preset_options['update_freq'] == 0) echo "selected=\"selected\""; ?>>Instant (per page load)</option>
  <option value="1" <?php if ($this->mactrak_preset_options['update_freq'] == 1) echo "selected=\"selected\""; ?>>Every Minute</option>
  <option value="60" <?php if ($this->mactrak_preset_options['update_freq'] == 60) echo "selected=\"selected\""; ?>>Hourly</option>
  <option value="1440" <?php if ($this->mactrak_preset_options['update_freq'] == 1440) echo "selected=\"selected\""; ?>>Daily</option>
</select>
</p>
<p>For sites with very low traffic, remember that Spot only stores messages/pings for 7 days. So please ensure that you visit a MacTrak map on your site at least once per week to trigger an update.<br>
	<em>Development Note: I hope to include a script for server-side Cron updates in MacTrak v2</em>.</p>

<h4>Delete Spot and User Data</h4>
<p>The MacTrak plugin will download your Find Me Spot location pings and save them to the WordPress database on your server whenever the plugin is activated and set-up with a Spot GLID. If you choose to uninstall the plugin then the Spot and User added data that is saved in the WordPress database structure will be removed as per this setting.</p>	
<p>	<input name="mactrak_default_delete" id="mactrak_default_delete0" type="radio" value="0" <?php if ($this->mactrak_preset_options['uninstall_delete_data'] == 0) echo "checked=\"checked\""; ?> ><label for="mactrak_default_delete0">Never Delete</label><br>
	<input name="mactrak_default_delete" id="mactrak_default_delete1" type="radio" value="1" <?php if ($this->mactrak_preset_options['uninstall_delete_data'] == 1) echo "checked=\"checked\""; ?> ><label for="mactrak_default_delete1">Delete on Plugin Deactivate</label><br>
	<input name="mactrak_default_delete" id="mactrak_default_delete2" type="radio" value="2" <?php if ($this->mactrak_preset_options['uninstall_delete_data'] == 2) echo "checked=\"checked\""; ?> ><label for="mactrak_default_delete2">Delete on Plugin Delete</label>
</p>
<p class="mactrak_warning">Warning: If you check this option you will loose all past location data when uninstalling MacTrak. You cannot re-download this from Spot - so if it's important to you make sure you have a backup.</p>
<p class="mactrak_warning">It is recommended to change this option to 'Never Delete' unless you have another backup of your route data. 
	
<?php submit_button( 'Save Settings' ); ?>

</form>
</div>