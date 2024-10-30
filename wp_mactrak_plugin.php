<?php defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

/*
  Plugin Name: MacTrak for FindMeSpot (Spot Tracker)
  Plugin URI: http://www.ShaunMcCance.com/tracker/wp-mactrak-plugin
  Description: ... See it at: www.ChasingTheSunrise.org/tracker ... A WP plugin to save Find Me Spot (Spot Tracker) location pings to your wordpress blog and display them on a Google Maps via shortcode.  Spot will process data pings but will not save them beyond a few days. This Plugin will save them from the Spot servers at regular intervals to your wordpress site and allow them to be displayed to the public or downloaded by site admins for backup. I developed this plugin for the interactive map of my journey Chasing the Sunrise; any problems or suggestions email me, if it works and you like it please consider buying me a coffee and help me keep cycling :-)
  Version: 1.0
  Author: Shaun McCance
  Author URI: http://www.ShaunMcCance.com/buymeacoffee
  License: GPLv2+
  Text Domain: wp_mactrak_plugin
*/


//Debug functions
//include_once("_debug_functions.php");


class wp_mactrak_plugin {  
	// Properties
	private $wp_mactrak_fms = array();
	private $mactrak_version = 1.4;
	
	public function __construct() {
		// Initialize Variables
		$this->plugin_name = plugin_basename(__FILE__);
		$pluginFolder = explode("/", $this->plugin_name);
		$this->pluginBaseURI = plugins_url() . "/" . $pluginFolder[0] . "/";
		
		//Generic Actions etc
		add_action('init', array($this, 'mactrak_settings_init')); // Includes call to Spot
		
		// Admin Actions etc
		add_action('admin_menu', array(&$this,'mactrak_admin_menu'));
		add_action('admin_enqueue_scripts', array(&$this, 'mactrak_add_admin_scripts'));
		add_action('admin_init', array($this, 'mactrak_admin_settings_init')); // Includes call to Spot
		
		add_action('wp_ajax_mactrak_rtnspotdata', array($this, 'ajax_remote_query'));
	 	//add_action('wp_ajax_mactrak_rtncsvexport', array($this, 'export_csv_data'));
	 	
		// Public Actions etc
		add_action('wp_enqueue_scripts', array(&$this, 'mactrak_add_public_scripts'));		
		
		//include($this->pluginBaseURI . 'inc/wp_mactrak_widget.php');
		//include($this->pluginBaseURI . 'inc/wp_mactrak_shortcode.php');
		
		//add_action('wp_head', array(&$this, 'mactrack_update'));
		
		register_activation_hook($this->plugin_name, array($this, 'plugin_activate')); //activate hook
		register_deactivation_hook($this->plugin_name, array($this, 'plugin_deactivate')); //deactivate hook
		register_uninstall_hook($this->plugin_name, 'mactrak_plugin_uninstall'); //uninstall hook
	}

	public function mactrack_update() {
		// Check for update code...
		
	}
	
	
	public function mactrak_settings_init() {	
		$this->mactrak_display_options = get_option('mactrak_display_options');
		$this->mactrak_preset_options = get_option('mactrak_preset_options');
		
		$this->run_remote_query();
		
		add_shortcode('mactrak_map', array($this,'mactrak_shortcode_map'));
	}
	
	public function mactrak_admin_settings_init() {
		$this->userAllowableTags = array(
		    'a' => array(
		        'href' => array(),
		        'title' => array()
		    ),
		    'br' => array(),
		    'em' => array(),
		    'h1' => array(),
		    'h2' => array(),
		    'h3' => array(),
		    'p' => array(),
		    'strong' => array(),
		);
		
	}
	
	public function mactrak_add_admin_scripts() {
		wp_enqueue_media(); // Enqueues all media JS scripts
		wp_enqueue_style('mactrak_admin_css', $this->pluginBaseURI.'/css/wp_mactrak_admin_styles.css');
		// Alt one line form of below: wp_enqueue_script('mactrak_admin_js', $this->pluginBaseURI.'/js/wp_mactrak_admin_script.js', array(), "1.0", false);
		wp_register_script('mactrak_admin_js', $this->pluginBaseURI.'/js/wp_mactrak_admin_script.js', array('jquery'));
		wp_enqueue_script( 'mactrak_admin_js' );
	}
	
	public function mactrak_add_public_scripts() {
		wp_enqueue_style('mactrak_public_css', $this->pluginBaseURI.'/css/wp_mactrak_public_styles.css');
		wp_enqueue_script('mactrak_public_js', $this->pluginBaseURI.'/js/wp_mactrak_public_script.js', array(), "1.0", false);
	}
	
	public function mactrak_admin_menu() {
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( 'MacTrak - the WP FindMeSpot Interface', 'MacTrak', 'edit_posts', 'wp_mactrak_admin_menu', array($this,'mactrak_admin_main'),'dashicons-location-alt', 30);
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
		add_submenu_page( 'wp_mactrak_admin_menu', 'MacTrak - the WP FindMeSpot Interface', 'My Tracker Map', 'edit_posts', 'wp_mactrak_admin_menu', array($this,'mactrak_admin_main'));
		add_submenu_page( 'wp_mactrak_admin_menu', 'MacTrak - FindMeSpot Data', 'FindMeSpot Data', 'edit_posts', 'wp_mactrak_admin_fmsdata', array($this,'mactrak_admin_fmsdata'));
		add_submenu_page( 'wp_mactrak_admin_menu', 'MacTrak - Custom Map Data', 'Custom Map Data', 'edit_posts', 'wp_mactrak_admin_markers', array($this,'mactrak_admin_markers'));
		add_submenu_page( 'wp_mactrak_admin_menu', 'MacTrak - Current Location', 'Current Location', 'edit_posts', 'wp_mactrak_admin_currentloc', array($this,'mactrak_admin_currentloc'));
		add_submenu_page( 'wp_mactrak_admin_menu', 'MacTrak - Settings', 'Settings', 'edit_pages', 'wp_mactrak_admin_settings', array($this,'mactrak_admin_settings'));
	}

	public function mactrak_admin_main() {
		if ( !current_user_can( 'edit_posts' ) ) wp_die( __( 'Please login as Contributor or above to view MacTrak admin pages..' ) );
		include(plugin_dir_path(__FILE__) . 'inc/wp_mactrak_admin_main.php');
	}
	public function mactrak_admin_fmsdata() {
		if ( !current_user_can( 'edit_posts' ) ) wp_die( __( 'Please login as Contributor or above to view MacTrak admin pages.' ) );
		include(plugin_dir_path(__FILE__) . 'inc/wp_mactrak_admin_fmsdata.php');
	}
	public function mactrak_admin_markers() {
		if ( !current_user_can( 'edit_posts' ) ) wp_die( __( 'Please login as Contributor or above to view MacTrak admin pages.' ) );
		include(plugin_dir_path(__FILE__) . 'inc/wp_mactrak_admin_markers.php');
	}
	public function mactrak_admin_currentloc() {
		if ( !current_user_can( 'edit_posts' ) ) wp_die( __( 'Please login as Contributor or above to view MacTrak admin pages.' ) );
		include(plugin_dir_path(__FILE__) . 'inc/wp_mactrak_admin_currentloc.php');
	}
	public function mactrak_admin_settings() {
		if ( !current_user_can( 'edit_pages' ) ) wp_die( __( 'Please login as Editor or above to change MacTrak settings.' ) );
		include(plugin_dir_path(__FILE__) . 'inc/wp_mactrak_admin_settings.php');
	}


	/*
	 * WP Activation Hook
	 */public function plugin_activate(){ // Called from activation_hook
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		//print_pre("here".$wpdb);
		// Add MacTrak Default Options
		$mactrakDisplayOptions = array(
			"color" => "#006600", 
			"track_no" => 0, 
			"gc_line" => 0, 
			"current_marker_option" => 2, 
			"fixed_lat" => 51.483, 
			"fixed_lng" => -5.123, 
			"route_delay" => 0, 
			"destination_line" => 0, 
			"destination_lat" => 0, 
			"destination_lng" => 0,
			"destination_color" => "#00FFFF",
			"recent_color" => "#666666"
			);
		$mactrakPresetOptions = array(
			"mactrak_version" => $this->mactrak_version,
			"SpotID" => "...", 
			"MapID" => "...", 
			"strapline" => 1,
			"update_freq" => 60, 
			"uninstall_delete_data" => 2, 
			"last_update_time" => 0
			);
		
		add_option('mactrak_display_options', $mactrakDisplayOptions);
		add_option('mactrak_preset_options', $mactrakPresetOptions);
		
		
		// Set up Custom table structure and some initial data
		$this_table_name = $wpdb->prefix . "mactrak_route"; 
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE `$this_table_name` (
			  `Ser` int(11) NOT NULL AUTO_INCREMENT,
			  `Route` int(11) NOT NULL DEFAULT '0',
			  `UNIXTime` int(11) NOT NULL,
			  `Latitude` float NOT NULL,
			  `Longitude` float NOT NULL,
			  `Type` varchar(30) NULL DEFAULT NULL,
			  `DeviceName` varchar(50) NULL DEFAULT NULL,
				`@clientUnixTime` int(11) NULL DEFAULT NULL,
				`id` int(11) NULL DEFAULT NULL,
				`messengerId` varchar(10) NULL DEFAULT NULL,
				`modelId` varchar(8) NULL DEFAULT NULL,
				`showCustomMsg` varchar(5) NULL DEFAULT NULL,
				`messageDetail` varchar(30) NULL DEFAULT NULL,
				`batteryState` varchar(5) NULL DEFAULT NULL,
				`hidden` tinyint(1) NULL DEFAULT NULL,
				`messageContent` varchar(50) NULL DEFAULT NULL,
				`altitude` int(11) NULL DEFAULT NULL,
			  `Color` varchar(7) NULL DEFAULT NULL,
			  `Arc` tinyint(1) NOT NULL DEFAULT '0',
			  `Deleted` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY  (`Ser`)
			) ENGINE=InnoDB $charset_collate;";
		
		dbDelta($sql);
		
		// Custom Lines - eg. Flight Paths etc
		
		$this_table_name = $wpdb->prefix . "mactrak_customlines";
		
		$sql = "CREATE TABLE `$this_table_name` (
			  `Ser` int(11) NOT NULL AUTO_INCREMENT,
			  `Date` int(11) NULL DEFAULT NULL,
			  `Name` varchar(50) NULL DEFAULT NULL,
			  `StartLat` float NOT NULL,
			  `StartLong` float NOT NULL,
			  `FinishLat` float NOT NULL,
			  `FinishLong` float NOT NULL,
			  `Color` varchar(7) NOT NULL DEFAULT '#FF0000',
			  `Arc` tinyint(1) NOT NULL DEFAULT '0',
			  `Deleted` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY  (`Ser`)
			) ENGINE=InnoDB $charset_collate;";
		
		dbDelta($sql);
		
		
		// Marker Types
		
		$this_table_name = $wpdb->prefix . "mactrak_markertypes"; 
		$unique_key_name = $wpdb->prefix . "mactrak_markertypes_key";
		
		$sql = "CREATE TABLE `$this_table_name` (
				  `Ser` int(11) NOT NULL AUTO_INCREMENT,
				  `Name` varchar(15) NOT NULL,
				  `Image` varchar(30) NOT NULL,
				  `PinX` smallint NOT NULL,
				  `PinY` smallint NOT NULL,
				  `Opacity` smallint NOT NULL,
				  `Scale` smallint NOT NULL,
				  PRIMARY KEY  (`Ser`),
				  UNIQUE KEY $unique_key_name (`Name`)
			) ENGINE=InnoDB $charset_collate;";				  
		
		dbDelta($sql);
		
		$sql_query = "SELECT `Name` FROM $this_table_name WHERE `Name` = 'generic'";
		if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Name' => 'generic',
				'Image' => 'img:cross',
				'PinX' => '10',
				'PinY' => '10',
				'Opacity' => '10',
				'Scale' => '100'
			) 
		);		
		
		$sql_query = "SELECT `Name` FROM $this_table_name WHERE `Name` = 'overnight'";
		if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Name' => 'overnight',
				'Image' => 'img:camp',
				'PinX' => '0',
				'PinY' => '10',
				'Opacity' => '10',
				'Scale' => '80'
			) 
		);
	
		$sql_query = "SELECT `Name` FROM $this_table_name WHERE `Name` = 'blog'";
		if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Name' => 'blog',
				'Image' => 'img:book',
				'PinX' => '0',
				'PinY' => '10',
				'Opacity' => '10',
				'Scale' => '80'
			) 
		);
	
		$sql_query = "SELECT `Name` FROM $this_table_name WHERE `Name` = 'photo'";
		if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Name' => 'photo',
				'Image' => 'img:camera',
				'PinX' => '0',
				'PinY' => '10',
				'Opacity' => '10',
				'Scale' => '80'
			) 
		);

		$sql_query = "SELECT `Name` FROM $this_table_name WHERE `Name` = 'current'";
		if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Name' => 'current',
				'Image' => 'img:current',
				'PinX' => '49',
				'PinY' => '2',
				'Opacity' => '8',
				'Scale' => '65'
			) 
		);

		$markertypes_table = $this_table_name;
		
		// Markers
		$this_table_name = $wpdb->prefix . "mactrak_markers"; 
		$fkey = $wpdb->prefix . "mactrak_markertype_fkey";
		
		$sql = "CREATE TABLE `$this_table_name` (
			  `Ser` int(11) NOT NULL AUTO_INCREMENT,
			  `Type` varchar(12) NOT NULL DEFAULT 'generic',
			  `UNIXTime` int(11) NULL DEFAULT NULL,
			  `Route` int(11) NULL DEFAULT NULL,
			  `Latitude` float NOT NULL,
			  `Longitude` float NOT NULL,
			  `Title` varchar(30) NULL DEFAULT NULL,
			  `Image` varchar(15) NULL DEFAULT NULL,
			  `Content` TEXT NULL DEFAULT NULL,
			  `Deleted` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY  (`Ser`)
			) ENGINE=InnoDB $charset_collate;
			ALTER TABLE `$this_table_name` 
				ADD CONSTRAINT `$fkey` 
				FOREIGN KEY (`Type`) 
				REFERENCES `$markertypes_table`(`Name`) 
				ON DELETE RESTRICT ON UPDATE CASCADE;";
		
		dbDelta($sql);
		
		//$sql_query = "SELECT `Type` FROM `wp_mactrak_markers` WHERE `Type` = 'overnight' AND `Title` = 'Greenwich' AND `Longitude` = 0 AND `Content` = 'The Centre of the Universe'";
		
		/*if ($wpdb->get_row($sql_query) == null) $wpdb->insert( $this_table_name, array( 
				'Type' => 'overnight',
				'Route' => '0',
				'Latitude' => '51.4826',
				'Longitude' => '0',
				'Title' => 'Greenwich',
		 		'Content' => '&lt;p&gt;The Centre of the Universe&lt;/p&gt;'
			) 
		);
		*/
	
		//flush permalinks
		flush_rewrite_rules();
	}
	
	/*
	 * WP Deactivation Hook
	 */public function plugin_deactivate(){ // Called from deactivation_hook
		if ($this->mactrak_preset_options['uninstall_delete_data'] == 1) $this->remove_tables();
	 		
		//flush permalinks
		flush_rewrite_rules();
	}
	
	public function remove_tables() {
		global $wpdb;
				
    	$table_name = $wpdb->prefix . 'mactrak_route';
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
		
		$table_name = $wpdb->prefix . 'mactrak_customlines';
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);

		$table_name = $wpdb->prefix . 'mactrak_markers';
		$fkey = $wpdb->prefix . "mactrak_markertype_fkey";
    	$wpdb->query("ALTER TABLE `$table_name` DROP FOREIGN KEY `$fkey`;");
    	
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
		
		
    	$table_name = $wpdb->prefix . 'mactrak_markertypes';
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
		
		delete_option('mactrak_display_options');
		delete_option('mactrak_preset_options');		
	}
	
	
	/*
	 * Raw Data (csv) Import functionality
	 */public function mactrak_fmsdata_import() {

		// Load data file	
		//$lines = file($this->pluginBaseURI."manual_location_upload.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$lines = file($_FILES['mactrak_upload_route']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); 
		$gpsDataArray = array();
		$startImport = false;
		
		foreach ($lines as $linetext) {
			if ($startImport) $gpsDataArray[] = explode(",", $linetext);	
			if ($linetext == "____________________________Add Data below here____________________________") $startImport = true;
		}
		
		
		// Add data to 'wp_mactrak_route' table
		global $wpdb;
		$route_table_name = $wpdb->prefix . "mactrak_route"; 
		  
		foreach ($gpsDataArray as $gpsDataRow) {
			if (!isset($gpsDataRow[5])) $gpsDataRow[5] = $this->mactrak_display_options[track_no];
			$wpdb->insert( $route_table_name, array( 
						'Route' => intval($gpsDataRow[5]),
						'UNIXTime' => intval($gpsDataRow[3]),
						'Latitude' => floatval($gpsDataRow[0]),
						'Longitude' => floatval($gpsDataRow[1]),
						'Type' => substr(sanitize_text_field($gpsDataRow[4]), 0, 30),
						'DeviceName' => substr(sanitize_text_field($gpsDataRow[2]), 0, 50),
						'Color' => $this->mactrak_display_options['color'],
						'Arc' => $this->mactrak_display_options['gc_line']
					) 
				);
		}
	}


	/*
	 * Generate display table for Route Data
	 */private function mactrak_routedata_display() {
	 	global $wpdb;
	 	$data_rowcount = 1;
		$route_table_name = $wpdb->prefix . "mactrak_route"; 
	 	$fmsdataObj = $wpdb->get_results("SELECT 
	 			`Ser`, `Route`, `UNIXTime`, `Latitude`, `Longitude`, `Type`, `DeviceName`, `Color`, `Arc` 
	 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Route` ASC, `UNIXTime` ASC");// LIMIT 1300, 200");
		
		
		foreach ($fmsdataObj as $fmsdataRow) {
			echo "<tr class=\"".(($data_rowcount % 2 == 0) ? "mactrak_row1" : "mactrak_row2")."\">";
			
			echo "<th class=\"mactrak_row_head\">";
			echo "<input id=\"rowselect_".$fmsdataRow->Ser."\" disabled name=\"rowselect_".$fmsdataRow->Ser."\" value=\"".$fmsdataRow->Ser."\" type=\"checkbox\" />";		
			echo "&nbsp".$data_rowcount."</th>";
			
			echo "<td>".$fmsdataRow->Route."</td>";
			
			// Date
			echo "<td>".date("d/M/y H:i:s", $fmsdataRow->UNIXTime)."</td>";
			
			// Latitude
			if ($fmsdataRow->Latitude >= 0) $latitudeSuffix = "&deg;N";
				else $latitudeSuffix = "&deg;S";
			echo "<td>".abs($fmsdataRow->Latitude).$latitudeSuffix."</td>";
			
			// Longitude
			if ($fmsdataRow->Longitude >= 0) $longitudeSuffix = "&deg;E";
				else $longitudeSuffix = "&deg;W";		
			echo "<td>".abs($fmsdataRow->Longitude).$longitudeSuffix."</td>";
	
			echo "<td>".$fmsdataRow->DeviceName."</td>";
			echo "<td>".$fmsdataRow->Type."</td>";
			
			echo "<td style=\"background-color:".$fmsdataRow->Color.";\">".($fmsdataRow->Color == NULL ? "Default" : "&nbsp;")."</td>";
			
			// Line Type
			if ($fmsdataRow->Arc == 1) $lineTypeText = "Great Circle";
				else $lineTypeText = "Straight"; 
			echo "<td>".$lineTypeText."</td>";
			
			// Options Icons
			echo "<td class=\"mactrak_options\">";
			
			
			if (current_user_can('edit_pages')) {
				//echo "<input id=\"mactrak-trigger_convert_route_".$fmsdataRow->Ser."\" name=\"mactrak-trigger_convert_route_".$fmsdataRow->Ser."\" type=\"image\" alt=\"Convert to Marker\" title=\"Convert to Marker\" src=\"".$this->pluginBaseURI."img/icon_convert.gif\" > ";
				echo "<input id=\"mactrak-trigger_delete_route_".$fmsdataRow->Ser."\" name=\"mactrak-trigger_delete_route_".$fmsdataRow->Ser."\" type=\"image\" alt=\"Remove\" title=\"Remove\" src=\"".$this->pluginBaseURI."img/icon_delete.gif\" /> ";
				//echo "<input id=\"mactrak-trigger_edit_route_".$fmsdataRow->Ser."\" name=\"mactrak-trigger_edit_route_".$fmsdataRow->Ser."\" type=\"image\" alt=\"Edit\" title=\"Edit\" src=\"".$pluginBaseURI."img/icon_edit.gif\" /> ";
				//echo "<input id=\"mactrak-trigger_add-above_route_".$fmsdataRow->Ser."\" name=\"mactrak-trigger_add-above_route_".$fmsdataRow->Ser."\" type=\"image\" alt=\"Add Above\" title=\"Add Point Above\" src=\"".$pluginBaseURI."img/icon_add_above.gif\" /> ";
				//echo "<input id=\"mactrak-trigger_add-below_route_".$fmsdataRow->Ser."\" name=\"mactrak-trigger_add-below_route_".$fmsdataRow->Ser."\" type=\"image\" alt=\"Add Below\" title=\"Add Point Below\" src=\"".$pluginBaseURI."img/icon_add_below.gif\" > ";
			} else echo "n/a";
			
			echo "</td>";
			
			echo "</tr>";
			$data_rowcount++;
		}
	}


	/*
	 * Generate display table for Custom Lines
	 */private function mactrak_customlinesdata_display() {
	 	global $wpdb;
	 	$data_rowcount = 1;
		$route_table_name = $wpdb->prefix . "mactrak_customlines"; 
	 	$customlinesdataObj = $wpdb->get_results("SELECT 
	 			`Ser`, `Date`, `Name`, `StartLat`, `StartLong`, `FinishLat`, `FinishLong`, `Color`, `Arc`
	 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Date` DESC, `Name` ASC");// LIMIT 1300, 200");
		
		// Print Header Row
		echo "<thead><tr>
				<th class=\"mactrak_col_head\">&nbsp;</th>
				<th class=\"mactrak_col_head\">Date</th>
				<th class=\"mactrak_col_head\">Name</th>
				<th class=\"mactrak_col_head\">From</th>
				<th class=\"mactrak_col_head\">To</th>
				<th class=\"mactrak_col_head\">Colour</th>
				<th class=\"mactrak_col_head\">Line Type</th>
				<th class=\"mactrak_col_head\">Options</th>
			</tr></thead>";
		
		// Generate Table Content
		echo "<tbody>";
		foreach ($customlinesdataObj as $customlinedataRow) {
			echo "<tr class=\"".(($data_rowcount % 2 == 0) ? "mactrak_row1" : "mactrak_row2")."\">";
			
			echo "<th class=\"mactrak_row_head\">";
			echo "<input id=\"rowselect_".$customlinedataRow->Ser."\" disabled name=\"rowselect_".$customlinedataRow->Ser."\" value=\"".$customlinedataRow->Ser."\" type=\"checkbox\" />";		
			echo "&nbsp".$data_rowcount."</th>";
					
			// Date
			if (isset($customlinedataRow->Date)) $dateStr = date("d-m-Y", $customlinedataRow->Date);
				else $dateStr = "<em>None</em>";
			echo "<td>".$dateStr."</td>";
			
			echo "<td>".$customlinedataRow->Name."</td>";
			
			// From Position
			if ($customlinedataRow->StartLat >= 0) $latitudeSuffix = "&deg;N";
				else $latitudeSuffix = "&deg;S";
			if ($customlinedataRow->StartLong >= 0) $longitudeSuffix = "&deg;E";
				else $longitudeSuffix = "&deg;W";		
			echo "<td>".abs($customlinedataRow->StartLat).$latitudeSuffix."&nbsp;&nbsp;&nbsp;&nbsp;".abs($customlinedataRow->StartLong).$longitudeSuffix."</td>";
	
			// To Position
			if ($customlinedataRow->FinishLat >= 0) $latitudeSuffix = "&deg;N";
				else $latitudeSuffix = "&deg;S";
			if ($customlinedataRow->FinishLong >= 0) $longitudeSuffix = "&deg;E";
				else $longitudeSuffix = "&deg;W";		
			echo "<td>".abs($customlinedataRow->FinishLat).$latitudeSuffix."&nbsp;&nbsp;&nbsp;&nbsp;".abs($customlinedataRow->FinishLong).$longitudeSuffix."</td>";
			
			echo "<td style=\"background-color:".$customlinedataRow->Color.";\">&nbsp;</td>";
			
			// Line Type
			if ($customlinedataRow->Arc == 1) $lineTypeText = "Great Circle";
				else $lineTypeText = "Straight"; 
			echo "<td>".$lineTypeText."</td>";
			
			// Options Icons
			echo "<td class=\"mactrak_options\">";
			if (current_user_can('edit_pages')) {
				echo "<input id=\"mactrak-trigger_delete_customlines_".$customlinedataRow->Ser."\" name=\"mactrak-trigger_delete_customlines_".$customlinedataRow->Ser."\" type=\"image\" alt=\"Remove\" title=\"Remove\" src=\"".$this->pluginBaseURI."img/icon_delete.gif\" /> ";
				//echo "<input id=\"mactrak-trigger_edit_customlines_".$customlinedataRow->Ser."\" name=\"mactrak-trigger_edit_customlines_".$customlinedataRow->Ser."\" type=\"image\" alt=\"Edit\" title=\"Edit\" src=\"".$this->pluginBaseURI."img/icon_edit.gif\" /> ";
			} else echo "n/a";
			echo "</td>";
			
			echo "</tr>";
			$data_rowcount++;
		}

		echo "</tbody>";
	}

	/*
	 * Generate display table for Markers
	 */private function mactrak_markerdata_display() {
	 	global $wpdb;
	 	$data_rowcount = 1;
		$this_type = "";
		$route_table_name = $wpdb->prefix . "mactrak_markers"; 
	 	$markerdataObj = $wpdb->get_results("SELECT 
	 			`Ser`, `Type`, `UNIXTime`, `Route`, `Latitude`, `Longitude`, `Title`, `Image`, `Content`
	 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Type` ASC, `UNIXTime` DESC, `Title` ASC");// LIMIT 1300, 200");
		
		// Generate Table Content
		foreach ($markerdataObj as $markerdataRow) {
			// Add Header Row if Marker Type is different
			if ($markerdataRow->Type != $this_type) {
				$imgSrcStr = $this->pluginBaseURI."img/mactrak_marker_".$markerdataRow->Type.".png";
				echo "<thead><tr><td colspan=\"7\" class=\"mactrak_header\"><h3><img src=\"".$imgSrcStr."\">".ucfirst($markerdataRow->Type)."</h3></td></tr>";
				echo "<tr><th class=\"mactrak_col_head\">&nbsp;</th><th class=\"mactrak_col_head\">Date</th><th class=\"mactrak_col_head\">Position</th><th class=\"mactrak_col_head\">Title</th><th class=\"mactrak_col_head\">Attachment</th><th class=\"mactrak_col_head\"  style=\"max-width: 50%;\">Content</th><th class=\"mactrak_col_head\">Options</th></tr></thead><tbody>";
			}
			
			echo "<tr class=\"".(($data_rowcount % 2 == 0) ? "mactrak_row1" : "mactrak_row2")."\">";
			
			echo "<th class=\"mactrak_row_head\">";
			echo "<input id=\"rowselect_".$markerdataRow->Ser."\" disabled name=\"rowselect_".$markerdataRow->Ser."\" value=\"".$markerdataRow->Ser."\" type=\"checkbox\" />";		
			echo "&nbsp".$data_rowcount."</th>";
			
			// Date
			if (isset($markerdataRow->UNIXTime)) $dateStr = date("d/M/y H:i", $markerdataRow->UNIXTime);
				else $dateStr = "<em>None</em>";
			echo "<td>".$dateStr."</td>";
			
			// Position
			if ($markerdataRow->Latitude >= 0) $latitudeSuffix = "&deg;N";
				else $latitudeSuffix = "&deg;S";
			if ($markerdataRow->Latitude >= 0) $longitudeSuffix = "&deg;E";
				else $longitudeSuffix = "&deg;W";		
			echo "<td>".abs($markerdataRow->Latitude).$latitudeSuffix."&nbsp;&nbsp;&nbsp;&nbsp;".abs($markerdataRow->Longitude).$longitudeSuffix."</td>";
			
			
			echo "<td>".$markerdataRow->Title."</td>";
			
			// Image
			echo "<td>".((isset($markerdataRow->Image)) ? $markerdataRow->Image : "<em>None</em>")."</td>";
			
			
			// Content
			if (isset($markerdataRow->Content)) {
				if (strlen($markerdataRow->Content) > 303) $tempContentStr = substr($markerdataRow->Content, 0, 300)."...";
				else $tempContentStr = $markerdataRow->Content;
				
				$tempContentStr = "<span class=\"mactrak_content_small\">"
					.preg_replace("/&#039;|&quot;/", '\'', $tempContentStr)
					."</span>";
			}
			else $tempContentStr = "<em>Not Set</em>";
			echo "<td style=\"max-width: 50%;\">".$tempContentStr."</td>";
				
				
				
			// Options Icons
			echo "<td class=\"mactrak_options\">";
			if (current_user_can('edit_pages')) {
				echo "<input id=\"mactrak-trigger_delete_markers_".$markerdataRow->Ser."\" name=\"mactrak-trigger_delete_markers_".$markerdataRow->Ser."\" type=\"image\" alt=\"Remove\" title=\"Remove\" src=\"".$this->pluginBaseURI."img/icon_delete.gif\" /> ";
				//echo "<input id=\"mactrak-trigger_edit_markers_".$markerdataRow->Ser."\" name=\"mactrak-trigger_edit_markers_".$markerdataRow->Ser."\" type=\"image\" alt=\"Edit\" title=\"Edit\" src=\"".$this->pluginBaseURI."img/icon_edit.gif\" /> ";
			} else echo "n/a";
			echo "</td>";
			
			echo "</tr>";
			$data_rowcount++;
			
			
			if ($markerdataRow->Type != $this_type) {
				echo "</tbody>";
				$this_type = $markerdataRow->Type;
			}
		}
	}
	
	/*
	 * 
	 */public function export_csv_data() {
		//Ajax route export function not used
	}
		
	/*
	 * 
	 */public function ajax_remote_query() {
	 	if (! current_user_can( 'edit_pages' ) ) die('Please login as Editor or above to view live Spot data.');
		check_ajax_referer('mactrak_spot_data', 'security');
		
		$jsonArray = $this->load_remote_data();
		
		if ($jsonArray != false) {
			echo "Spot Message feed:\n";
			print_r($jsonArray);
		} else {
			echo "Spot GLID key not set or connection not made...";
		}
		wp_die();	
	 }

	/*
	 * 
	 */public function run_remote_query() {
	 	$tempTime = time() - 3; // Ensure 3 Second Delay to protect Spot server fair usage (Spot suggest max 2.5sec)
		
		// Debugging Info - displays before <html> in source: (Leaving this in will crash media Library)
		//echo "<!-- Timestamp Comp"."\n";
		//echo "Last Update:              ".date ("y-m-d H:i:s ", $this->mactrak_preset_options['last_update_time'])."\n"; 
		//echo "Trigger Time (Temp-Freq): ".date ("y-m-d H:i:s ", ($tempTime - ($this->mactrak_preset_options['update_freq']*60)))."\n-->";
		// End Debugging Info
		
		if ($this->mactrak_preset_options['last_update_time'] < ($tempTime - ($this->mactrak_preset_options['update_freq']*60))) {
				
			// Read remote data via object function
			$jsonArray = $this->load_remote_data();
			
			if ($jsonArray != false) {
				global $wpdb;
				
				$route_table_name = $wpdb->prefix . "mactrak_route"; 
				
				if ($jsonArray[response][feedMessageResponse][totalCount] > 1) 
					foreach ($jsonArray[response][feedMessageResponse][messages][message] as $pingJSON) $this->mactrak_add_fmsdata($route_table_name, $pingJSON);
				else  $this->mactrak_add_fmsdata($route_table_name, $jsonArray[response][feedMessageResponse][messages][message]);
				
				$this->mactrak_preset_options['last_update_time'] = max($tempTime, $this->mactrak_preset_options['last_update_time']);
				update_option('mactrak_preset_options', $this->mactrak_preset_options);
			} else {
				// Catch Error... GLID not set
			}
	
		}
	}
	
	private function load_remote_data() {
		if (isset($this->mactrak_preset_options['SpotID']) && strlen($this->mactrak_preset_options['SpotID']) > 8) {
			$jsonResponse = wp_remote_get( 'https://api.findmespot.com/spot-main-web/consumer/rest-api/2.0/public/feed/'.$this->mactrak_preset_options['SpotID'].'/message.json' );
			return json_decode(wp_remote_retrieve_body($jsonResponse), true);
		} else {
			return false;
		}			
	}

	/*
	 * Add Individual remote data message to database
	 */public function mactrak_add_fmsdata($table, $jsonSingleMessageArray) {
		global $wpdb; 
		
		$sql_query = "SELECT `Ser` FROM `$table` WHERE 
			`UNIXTime` = '".(int)$jsonSingleMessageArray['unixTime']."' ";
	
		if ($wpdb->get_row($sql_query) == null && (int)$jsonSingleMessageArray['unixTime'] > 0) $wpdb->insert($table, array( 
				'Route' => (int)$this->mactrak_display_options['track_no'],
				'UNIXTime' => (int)$jsonSingleMessageArray['unixTime'],
				'Latitude' => (float)$jsonSingleMessageArray['latitude'],
				'Longitude' => (float)$jsonSingleMessageArray['longitude'],
				'Type' => substr(sanitize_text_field($jsonSingleMessageArray['messageType']), 0, 30),
				'DeviceName' => substr(sanitize_text_field($jsonSingleMessageArray['messengerName']), 0, 50),
				'Arc' => $this->mactrak_display_options['gc_line']
			));
				//'Color' => $this->mactrak_display_options['color'],
			
/*
 * Other data to add
						'Route' => intval($gpsDataRow[5]),
						'UNIXTime' => intval($gpsDataRow[3]),
						'Latitude' => floatval($gpsDataRow[0]),
						'Longitude' => floatval($gpsDataRow[1]),
						'Type' => substr(sanitize_text_field($gpsDataRow[4]), 0, 30),
						'DeviceName' => substr(sanitize_text_field($gpsDataRow[2]), 0, 50),
						'Color' => $this->mactrak_display_options['color'],
						'Arc' => $this->mactrak_display_options['gc_line']
                [@clientUnixTime] => 0
                [id] => 818303176
                [messengerId] => 0-2854861
                [modelId] => SPOT3
                [showCustomMsg] => N
                [dateTime] => 2017-08-27T12:39:27+0000
                [messageDetail] => 
                [batteryState] => LOW
                [hidden] => 0
                [messageContent] => Shaun's Tracker
                [altitude] => 0
 */			
			
	}

	/*
	 * 
	 */private function mactrak_rtn_current_image_object () {
	 	global $wpdb;
		$mactrak_marker_table = $wpdb->prefix."mactrak_markertypes";
		
		// Get Marker settings data, runs after update from submit so returns current data
		$mactrak_current_settings = $wpdb->get_row("SELECT `Image`, `PinX`, `PinY`, `Opacity`, `Scale`  FROM `".$mactrak_marker_table."` WHERE `Name` = 'current' LIMIT 1;");
		
		
		if (strpos($mactrak_current_settings->Image, "imgno") !== FALSE) {
			$mactrak_current_settings->Image = (int) substr($mactrak_current_settings->Image, 6);
			$mactrak_current_settings->ImageData = wp_get_attachment_image_src($mactrak_current_settings->Image, 'full');
		} else {
			$mactrak_current_settings->ImageData = [$this->pluginBaseURI."img/mactrak_marker_generic.png", 98, 156]; 
			$mactrak_current_settings->Image = null;
		}
		
		return $mactrak_current_settings;
	 }

	/*
	 * Shortcode Hook for [mactrak_map]
	 */public function mactrak_shortcode_map ($atts) {
		$atts = shortcode_atts( array(
				"width" => '100%',
				"height" => '400px',
				"delay_hours" => $this->mactrak_display_options['route_delay'],
			), $atts, 'mactrak_map' );
		
		if ($this->mactrak_preset_options['strapline'] == 1) $strapline = <<<STRAPLINE

		<p class="mactrak_strapline">Powered by MacTrak &copy; <a href="http://www.shaunmccance.com/tracker/wp-mactrak-plugin" target="_blank">www.ShaunMcCance.com</a></p>

STRAPLINE;
		else $strapline = "";

		return $this->mactrak_return_map($atts['width'], $atts['height'], $atts['delay_hours'], false).$strapline;
	}
	 
	 
	 /*
	  * Build Google Map
	  */public function mactrak_return_map ($mactrakmap_width = "100%", $mactrakmap_height= "400px", $route_delay=24, $admin_display=false) {
		global $wpdb;
		
		// Build Array of JS Route Line Objects and set Lat/Lng for current posn 
		$jsRouteStr = (string) "[\"#000000\"";
		$route_table_name = $wpdb->prefix . "mactrak_route"; 
		$fmsdataObj = $wpdb->get_results("SELECT 
		 			`Route`, `UNIXTime`, `Latitude`, `Longitude`, `Color`
		 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Route` ASC, `UNIXTime` ASC");
			
		$lastRoute = -1;
		$lastRouteColor = 0;
		$lastLat= 0;
		$lastLng = 0;
		
		// Variables for Recent route plot
		$jsRouteStrRecent = (string) "[\"".$this->mactrak_display_options['recent_color']."\"";
		$firstRecentPoint = TRUE;
		
		foreach ($fmsdataObj as $fmsDataRow) {
			if ($fmsDataRow->Color == NULL) $fmsDataRow->Color = $this->mactrak_display_options['color'];
			
			if ($fmsDataRow->UNIXTime < (time()-($route_delay*60*60)) ) {
				if ($lastRoute <> $fmsDataRow->Route || $lastRouteColor <> $fmsDataRow->Color) {					
					$jsRouteStr .= "],[\"".$fmsDataRow->Color."\""; // Close prev line obj, open new with color
					
					if ($lastRoute == $fmsDataRow->Route) $jsRouteStr .= ",{lat: ".$lastLat.", lng: ".$lastLng."}"; // if same route append previous LatLng
					
					$lastRoute = $fmsDataRow->Route; // Update comparators
					$lastRouteColor = $fmsDataRow->Color;
				}
				
				$jsRouteStr .= ",{lat: ".$fmsDataRow->Latitude.", lng: ".$fmsDataRow->Longitude."}"; // Add data points

				// Note recode: This doesnt account for backtracking to add to previous routes, i.e. time difference eg, after route 5 adding to route 4 
				$lastLat = $fmsDataRow->Latitude;
				$lastLng = $fmsDataRow->Longitude;
								
			} elseif ($admin_display) { // Calls for recent track
				if ($firstRecentPoint) { // Run once to set up obj string
					$jsRouteStrRecent .= ",{lat: ".$lastLat.", lng: ".$lastLng."}";
					$firstRecentPoint = FALSE;
				}
				
				if ($lastRoute <> $fmsDataRow->Route) {					
					$jsRouteStrRecent .= "],[\"".$this->mactrak_display_options['recent_color']."\""; // Close prev line obj, open new with color
					
					$lastRoute = $fmsDataRow->Route; // Update comparator
				}
				
				$jsRouteStrRecent .= ",{lat: ".$fmsDataRow->Latitude.", lng: ".$fmsDataRow->Longitude."}"; // Add data point
				
				// Note recode: This doesnt account for backtracking to add to previous routes, i.e. time difference eg, after route 5 adding to route 4 
				$lastLat = $fmsDataRow->Latitude;
				$lastLng = $fmsDataRow->Longitude;
				
			}
			

		} 
		$jsRouteStr .= "]";
		if ($admin_display) $jsRouteStr .= ",".$jsRouteStrRecent."]";
		
		if ($this->mactrak_display_options['current_marker_option'] == 1) {
			$this->mactrak_display_options['fixed_lat'] = $lastLat; //$fmsDataRow->Latitude;
			$this->mactrak_display_options['fixed_lng'] = $lastLng; //$fmsDataRow->Longitude;
		}
		// End build JS route String
		
		
		// Get Current Loc Marker Variables
		$current_img_obj = $this->mactrak_rtn_current_image_object();
		
		$imgurlmyposn = $current_img_obj->ImageData[0];
		$myposn_w = $current_img_obj->ImageData[1] * $current_img_obj->Scale/100;
		$myposn_h = $current_img_obj->ImageData[2] * $current_img_obj->Scale/100;
		$myposnpin_x = $current_img_obj->PinX * $current_img_obj->Scale/100;
		$myposnpin_y = ($current_img_obj->ImageData[2] - $current_img_obj->PinY) * $current_img_obj->Scale/100;
		$myposn_opacity = $current_img_obj->Opacity/10;
	

		
		// Build Array of Flight Lines
		$jsCustomlinesStr = (string) "";
		
		$route_table_name = $wpdb->prefix . "mactrak_customlines"; 
		$customlinesdataObj = $wpdb->get_results("SELECT 
		 			`StartLat`, `StartLong`, `FinishLat`, `FinishLong`, `Color`, `Arc`
		 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Date` ASC");
			
		foreach ($customlinesdataObj as $customlinesRow) {
			$jsCustomlinesStr .= "[
				[
					{lat: ".$customlinesRow->StartLat.", lng: ".$customlinesRow->StartLong."}, 
					{lat: ".$customlinesRow->FinishLat.", lng: ".$customlinesRow->FinishLong."}
				],
				'".$customlinesRow->Color."', 
				".$customlinesRow->Arc."
				],";
		}
		// End build JS customlines String
		
		
		// Add Destination Flightline to customlines
		if ($this->mactrak_display_options['destination_line'] == 1) {
			$jsCustomlinesStr .= "[
				[
					{lat: ".$this->mactrak_display_options['fixed_lat'].", lng: ".$this->mactrak_display_options['fixed_lng']."}, 
					{lat: ".$this->mactrak_display_options['destination_lat'].", lng: ".$this->mactrak_display_options['destination_lng']."}
				],
				'".$this->mactrak_display_options['destination_color']."', 
				".(1)."
				],";
		} 
		// End build JS customlines String
			
					
		// Build Array of Marker points
		$jsOvernightsStr = (string) "";
		$jsBlogStr = (string) "";
		$jsPhotoStr = (string) "";
		
		$route_table_name = $wpdb->prefix . "mactrak_markers"; 
		$markersdataObj = $wpdb->get_results("SELECT 
		 			`Type`, `Latitude`, `Longitude`, `Title`, `Image`, `Content`
		 			FROM `$route_table_name` WHERE `Deleted` = 0 ORDER BY `Ser` ASC");
			
		foreach ($markersdataObj as $markersRow) {
			$tempTitleStr = isset($markersRow->Title) ? "<h1>".$markersRow->Title."</h1>" : "";
			$imageArray = wp_get_attachment_image_src($markersRow->Image);
			$tempImageStr = isset($markersRow->Image) ? "<img src=\"".$imageArray[0]."\">" : "";
			$tempContentStr = preg_replace("/&#039;|&quot;/", '\'', wp_specialchars_decode($markersRow->Content));
			$tempStr = "[{lat: ".$markersRow->Latitude.", lng: ".$markersRow->Longitude."}, '".$tempTitleStr."', '".$tempImageStr."', \"".$tempContentStr."\", '".$markersRow->Title."'],";
			switch ($markersRow->Type) {
				case "overnight": $jsOvernightsStr .= $tempStr; break;
				case "blog": $jsBlogStr .= $tempStr; break;
				case "photo": $jsPhotoStr .= $tempStr; break;
				
				default: break;
			}	
		}
		// End build JS marker Strings
		
		// Simplified Variables for heredoc
		$hd_routegeodesic = ($this->mactrak_display_options['gc_line'] == 1) ? "true" : "false";
		$hd_myposn = "{lat: ".($this->mactrak_display_options['fixed_lat']*1).", lng: ".($this->mactrak_display_options['fixed_lng']*1)."}";
		$hd_homeposn = "{lat: ".($this->mactrak_display_options['fixed_lat']+10).", lng: ".($this->mactrak_display_options['fixed_lng'])."}";
		
return <<<MAPSHOW

<div id="mactrakmap" style="width:{$mactrakmap_width};height:{$mactrakmap_height};"></div>
<script>
	
	var routeCoordinates = [{$jsRouteStr}];
	var routegeodesic = {$hd_routegeodesic};
	
	var showmyposn = {$this->mactrak_display_options['current_marker_option']};
	var myposn = {$hd_myposn};
	var homeposn = {$hd_homeposn};
	var imgurlmyposn = '{$imgurlmyposn}';
	
	var myposn_w = {$myposn_w};
	var myposn_h = {$myposn_h};
	var myposnpin_x = {$myposnpin_x};
	var myposnpin_y	= {$myposnpin_y};
	var myposn_opacity = {$myposn_opacity};
	
	var flightPlanCoordinates = [{$jsCustomlinesStr}];
	
	var overnightPoints = [{$jsOvernightsStr}];
	var blogPoints = [{$jsBlogStr}];
	var photoPoints = [{$jsPhotoStr}];
	
	var overnightIcon = "{$this->pluginBaseURI}img/mactrak_marker_overnight.png";
	var blogIcon = "{$this->pluginBaseURI}img/mactrak_marker_blog.png";
	var photoIcon = "{$this->pluginBaseURI}img/mactrak_marker_photo.png";
	
	var markerArrayBlogs = [];
	var markerArrayPhotos = [];
	var markerArrayOvernights = [];

    var infoWindowArrayBlogs = [];
	var infoWindowArrayPhotos = [];
	var infoWindowArrayOvernights = [];
    
	function initMap() {
		var mactrak_map = new google.maps.Map(document.getElementById('mactrakmap'), {
			zoom: 3,
			center: homeposn,
			mapTypeId: 'terrain'
		});
		
		setRoute(mactrak_map);
		setMarkers(mactrak_map);
		if (showmyposn != 0) setCurrentPosn(mactrak_map);
	}
	
	function setRoute(map) {
		//alert("route");
		routeCoordinates.forEach(function(coordinateSet){
			var linecolor = coordinateSet.shift();
			var mactrak_route = new google.maps.Polyline({
			  path: coordinateSet,
			  geodesic: routegeodesic,
			  strokeColor: linecolor,
			  strokeOpacity: 1.0,
			  strokeWeight: 3
			});
			
			mactrak_route.setMap(map);
		});
		
		flightPlanCoordinates.forEach(function(coordinateSet){
			var flightPath = new google.maps.Polyline({
			  path: coordinateSet[0],
			  geodesic: coordinateSet[2],
			  strokeColor: coordinateSet[1],
			  strokeOpacity: 0.6,
			  strokeWeight: 2
			});
			
			flightPath.setMap(map);
		});
	}
	
	function setCurrentPosn(map) {
		//alert("current");
		var latestPosnMarker = new google.maps.Marker({
			position: myposn,
			map: map,
			icon: {
			    url: imgurlmyposn, // url
			    scaledSize: new google.maps.Size(myposn_w, myposn_h), // scaled size
			    origin: new google.maps.Point(0,0), // origin
			    anchor: new google.maps.Point(myposnpin_x, myposnpin_y) // anchor
			},
			opacity: myposn_opacity
		});			
	}


    
	function setMarkers(map) {
		//alert("markers");
		overnightPoints.forEach(function(detailArray){
			var tempArrayPosn = markerArrayOvernights.length;
			markerArrayOvernights[tempArrayPosn] = new google.maps.Marker({
				position: detailArray[0],
				map: map,
				icon: {
				    url: overnightIcon, // url
				    scaledSize: new google.maps.Size(32, 32), // scaled size
				    origin: new google.maps.Point(0,0), // origin
				    anchor: new google.maps.Point(0, 31) // anchor
				},
				title: detailArray[4],
				opacity: 0.8
			});
			
			var infoWindowContent = detailArray[1]+
				"<div style='width:100%'>"+detailArray[2]+"<br clear='both'>"+
				detailArray[3]+"</div>";
		
			infoWindowArrayOvernights[tempArrayPosn] = new google.maps.InfoWindow({
	 			content: infoWindowContent
	 		});		
				
			markerArrayOvernights[tempArrayPosn].addListener('click', function() {
		    	infoWindowArrayOvernights[tempArrayPosn].open(map, markerArrayOvernights[tempArrayPosn]);
	       	});		
		
		
		
		
		});

		blogPoints.forEach(function(detailArray){
			var tempArrayPosn = markerArrayBlogs.length;
			markerArrayBlogs[tempArrayPosn] = new google.maps.Marker({
				position: detailArray[0],
				map: map,
				icon: {
				    url: blogIcon, // url
				    scaledSize: new google.maps.Size(32, 32), // scaled size
				    origin: new google.maps.Point(0,0), // origin
				    anchor: new google.maps.Point(0, 31) // anchor
				},
				title: detailArray[4],
				opacity: 0.8
			});
			
			var infoWindowContent = detailArray[1]+
				"<div style='width:100%'>"+detailArray[2]+"<br clear='both'>"+
				detailArray[3]+"</div>";
		
			infoWindowArrayBlogs[tempArrayPosn] = new google.maps.InfoWindow({
	 			content: infoWindowContent
	 		});		
				
			markerArrayBlogs[tempArrayPosn].addListener('click', function() {
		    	infoWindowArrayBlogs[tempArrayPosn].open(map, markerArrayBlogs[tempArrayPosn]);
	       	});	
			
		});
		
		
		photoPoints.forEach(function(detailArray){
			var tempArrayPosn = markerArrayPhotos.length;
			markerArrayPhotos[tempArrayPosn] = new google.maps.Marker({
				position: detailArray[0],
				map: map,
				icon: {
				    url: photoIcon, // url
				    scaledSize: new google.maps.Size(32, 32), // scaled size
				    origin: new google.maps.Point(0,0), // origin
				    anchor: new google.maps.Point(0, 31) // anchor
				},
				title: detailArray[4],
				opacity: 1
			});
			
			var infoWindowContent = detailArray[1]+
				"<div style='width:100%'>"+detailArray[2]+"<br clear='both'>"+
				detailArray[3]+"</div>";
		
			infoWindowArrayPhotos[tempArrayPosn] = new google.maps.InfoWindow({
	 			content: infoWindowContent
	 		});		
				
			markerArrayPhotos[tempArrayPosn].addListener('click', function() {
		    	infoWindowArrayPhotos[tempArrayPosn].open(map, markerArrayPhotos[tempArrayPosn]);
	       	});	
			
		});

	} 

</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={$this->mactrak_preset_options['MapID']}&callback=initMap"></script>
		
MAPSHOW;

		// End of Map Display function
	} 
}

$mactrak_plugin = new wp_mactrak_plugin;

/*
 * WP Unintall Hook - Called from uninstall_hook, cannot be cotained in Class
 */function mactrak_plugin_uninstall(){ 
	$mactrak_preset_options = get_option('mactrak_preset_options');
	if ($mactrak_preset_options['uninstall_delete_data'] == 2) wp_mactrak_plugin::remove_tables();
}
 
?>