<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! current_user_can( 'edit_posts' ) ) die('Please login as Contributor or above to view MacTrak admin pages.');

//edit_posts means contributor and above can view
//edit_pages means Editor can, not contributor or author
//manage_options means Admin or Super Admin only

?>

<div class="wrap mactrak">
<h1>MacTrak <em style="font-size: 0.7em">- The Spot Tracker interface for WP</em></h1>

<?php if (!isset($this->mactrak_preset_options['SpotID']) || strlen($this->mactrak_preset_options['SpotID']) < 8 ||
	!isset($this->mactrak_preset_options['MapID']) || strlen($this->mactrak_preset_options['MapID']) < 8) { ?>
	<div class="notice notice-warning">
		<p>Please start by adding a Google Maps API Key and your Spot Tracker Share Page GLID via the <em>Settings</em> page.<br>
		<?php if (!isset($this->mactrak_preset_options['MapID']) || strlen($this->mactrak_preset_options['MapID']) < 8) {
			?> The MacTrak map (both below and in posts) will not show until a valid Google Maps API key has been saved to MacTrak.<br><?php 
		} 
		if (!current_user_can( 'edit_pages' )) echo "You must log in as an editor or above to complete initial setup."; ?>
		</p>
	</div><?php 
} 

if (!current_user_can( 'edit_pages' )) { ?>
	<div class="notice notice-info is-dismissible">
		<p>Privilages for this user account are restricted. User level has limited editing options. Log in as an Editor or above for full functionality</p>
	</div><?php
} ?>

<p>Hopefully you're finding this plugin useful, if you are then please consider buying me a coffee at <a href="http://www.ChasingTheSunrise.org/buymeacoffee" target="_blank">www.ChasingTheSunrise.org/buymeacoffee</a> and help me continue adventuring. Thanks in advance, Shaun</p>

<?php echo $this->mactrak_return_map("100%", "600px", $this->mactrak_display_options['route_delay'], TRUE); ?>


<h2>Shortcodes</h2>
<p><label>Shortcode (For Defaults) = </label><input type="text" value="[mactrak_map]" readonly="readonly" size="100" onfocus="this.select();">
</p>
<p><label>Shortcode (Custom Parameters) = </label><input type="text" value="[mactrak_map width='100%' height='400px' delay_hours=0]" readonly="readonly" size="100" onfocus="this.select();">
</p>

<p><em>Javascript shortcode generator under development.</em> Add the above shortcode to posts.<br>
	All attributes are optional, more coming soon:
</p>
<ul><li><pre>width</pre> can be defined as pixel dimension or percentage. For adaptive viewport use a percentage figure.</li>
	<li><pre>height</pre> should be defined in pixels.</li>
	<ul><li>Default dimensions if height/width parameters are omitted are as shown.</li></ul>
	<li><pre>delay_hours</pre> will override Current Location settings. Omit to use default.</li>
	<!--li><pre>show_tracks</pre> will define which tracks are displayed. Omit to show all.</li>
	<li><pre>hide_tracks</pre> will define which tracks are hidden. Omit to show all.</li>
		<ul><li>Note: <em>hide_tracks</em> will fail if <em>show_tracks</em> is used.</li></ul-->
</ul>

</div>