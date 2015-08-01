<?php
/*
Plugin Name: WP Lumia Calender
Plugin URI: http://weblumia.com/
Description: Lumia Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage..
Version: 2.1.8
Author: Jinesh.P.V.
Author URI: http://weblumia.com/
*/
/**
	Copyright 2015-2016 Jinesh.P.V (email: jinuvijay5@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
 
if ( basename( $_SERVER['PHP_SELF'] ) == basename( __FILE__ ) ) {
	die( 'Sorry, but you cannot access this page directly.' );
}

if ( version_compare( PHP_VERSION, '5', '<' ) ) {
	$out = "<div id='message' style='width:94%' class='message error'>";
	$out .=	sprintf( "<p><strong>Your PHP version is '%s'.<br>The Ajax Event Calendar WordPress plugin requires PHP 5 or higher.</strong></p><p>Ask your web host how to enable PHP 5 on your site.</p>", PHP_VERSION );
	$out .=	"</div>";
	print $out;
}
 
 
require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
require_once( 'lumia-calendar.class.php' );
$plugin_url = plugins_url() . '/lumia-calender';

/**
 * Main Lumia_Calender Class
 *
 * @class Lumia_Calender
 * @version	2.1.8
 */
 
class Lumia_Calender {
	
	/**
	 * Lumia_Calender Constructor.
	 * @since 2.1.8
	 */
		 
	public function __construct() {
		
		// Default hook
		register_activation_hook( __FILE__, array( &$this, 'lumia_activation' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'lumia_deactivation' ) );
		register_uninstall_hook( __FILE__, array( 'lumia_portfolio', 'lumia_uninstall' ) );
		
		// Action  Hook
		add_action( 'admin_init', array( &$this, 'lumia_admin_scripts' ) );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_event_colorbox_js' ), 20 );
		add_action( 'wp_footer', array( &$this, 'load_event_colorbox_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'lumia_admin_scripts' ) );
		add_action( 'wp_ajax_nopriv_get_ajax_month_view', array( &$this, 'get_ajax_month_view' ) );
		add_action( 'wp_ajax_get_ajax_month_view', array( &$this, 'get_ajax_month_view' ) );
		add_action( 'trash_lumia_event', array( &$this, 'tash_lumia_event_dates' ) );
		add_action( 'untrash_post', array( &$this, 'untash_lumia_event_dates' ) );
		add_action( 'delete_post', array( &$this, 'delete_lumia_event_dates' ) );
		
		// Filter  Hook
		add_filter( 'page_template', array( &$this, 'event_page_template' ) );
		add_filter( 'single_template', array( &$this, 'single_event_template' ) );	
				
		// Shortcode  Hook
		add_shortcode( 'event_calender', array( &$this, 'show_event_calender' ) );
		add_shortcode( 'add_event', array( &$this, 'lumia_new_event' ) );
	}
	
	/**
	 * Init lumia calender when WordPress initialises.
	 * @since 2.1.8
	 */
	
	public function init(){
		self::lumia_calender_frontend_styles();
		self::create_calender_page();
	}
	
	/**
	 * lumia calender activation
	 * @since 2.1.8
	 */
		 
	public function lumia_activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
		self::init();
	}
	
	/**
	 * Dynamic creation for calender page
	 * @since 2.1.8
	 */
		 
	public function create_calender_page(){
		
		$event_calender		=	array(
			'post_title'    => 'Event Calender',
			'post_name'     => 'event-calender',
			'post_content'  => '[event_calender]',
			'post_type'     => 'page',
			'post_status'   => 'publish',
			'post_date'		=>	date('Y-m-d H:i:s'),
			'post_author'   => 1,
		);
		
		$add_event		=	array(
			'post_title'    => 'Add Event',
			'post_name'     => 'add-event',
			'post_content'  => '[add_event]',
			'post_type'     => 'page',
			'post_status'   => 'publish',
			'post_date'		=>	date('Y-m-d H:i:s'),
			'post_author'   => 1,
		);
		
		$event_calender_page_id = get_option( 'event_calender_page_id' );
		
		if( empty( $event_calender_page_id ) ){
			$event_calender_id = wp_insert_post( $event_calender );
			$add_event_id = wp_insert_post( $add_event );
			
			update_option( 'event_calender_page_id', $event_calender_id );
			update_option( 'add_event_page_id', $add_event_id );
		}
	}
	
	/**
	 * tash_lumia_event_dates
	 * @since 2.1.8
	 */
	 
	function tash_lumia_event_dates( $post_id ){
		
		global $wpdb, $table_prefix;
		$tbl_booking = $table_prefix . "lumia_calender";
		$query = "SELECT * FROM {$tbl_booking} WHERE post_id = {$post_id}";
		$posts  = $wpdb->get_results( $query );
		
		if( !empty( $posts ) ){
			$wpdb->query( $wpdb->prepare( "UPDATE {$tbl_booking} SET status = '0' WHERE post_id = %d", $post_id ) );;
		}
	}
	
	/**
	 * untash_lumia_event_dates
	 * @since 2.1.8
	 */
	 
	function untash_lumia_event_dates( $post_id ){
		
		global $wpdb, $table_prefix;
		$tbl_booking = $table_prefix . "lumia_calender";
		$query = "SELECT * FROM {$tbl_booking} WHERE post_id = {$post_id}";
		$posts = $wpdb->get_results( $query );
		
		if( !empty( $posts ) ){
			$wpdb->query( $wpdb->prepare( "UPDATE {$tbl_booking} SET status = '1' WHERE post_id = %d", $post_id ) );;
		}
	}
	
	/**
	 * delete_lumia_event_dates
	 * @since 2.1.8
	 */
	 
	function delete_lumia_event_dates( $post_id ){
		
		global $wpdb, $table_prefix;
		$tbl_booking = $table_prefix . "lumia_calender";
		$query = "SELECT * FROM {$tbl_booking} WHERE post_id = {$post_id}";
		$posts = $wpdb->get_results( $query );
		
		if( !empty( $posts ) ){
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_booking} WHERE post_id = %d", $post_id ) );;
		}
	}
	
	/**
	 * lumia_calender_frontend_styles
	 * @since 2.1.8
	 */	 
	
	public function lumia_calender_frontend_styles() {
		
		if( !is_admin() ) { 
			wp_enqueue_style( 'bootstrap', 'http://getbootstrap.com/dist/css/bootstrap.min.css' );
			wp_enqueue_style( 'font-awesome-min', 'http://netdna.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css?ver=4.2.2' );
			wp_enqueue_style( 'datepicker', plugins_url( 'style/datepicker.css', __FILE__ ) );
			wp_enqueue_style( 'colorbox', plugins_url( 'style/colorbox.css', __FILE__ ) );
			wp_enqueue_style( 'main-style', plugins_url( 'style/style.css', __FILE__ ) );
		}
	}
	
	/**
	 * lumia_admin_scripts
	 * @since 2.1.8
	 */	
	 
	public function lumia_admin_scripts() {
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'datepicker', plugins_url( '/js/datepicker.js', __FILE__ ), '1.4.2' );
		wp_enqueue_script( 'core', plugins_url( '/js/core.js', __FILE__ ), '1.0.2' );
	}
	
	/**
	 * load_event_colorbox_js
	 * @since 2.1.8
	 */	
	 
	public function load_event_colorbox_js(){
		
		if( !is_admin() ){
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'colorbox', plugins_url( '/js/jquery.colorbox.js', __FILE__ ), '1.4.24' );
		}
	}
	
	/**
	 * load_event_colorbox_scripts
	 * @since 2.1.8
	 */	
	 
	public function load_event_colorbox_scripts(){
		
		if( !is_admin() ){
			?>
			<script type="text/javascript">
				jQuery( window ).load( function(){ 
					jQuery( ".event" ).colorbox({inline:true, width:"50%", height:"50%"}); 				
				});
					
				function getMonthHTML( month, year ){
					jQuery( ".ajax_loader" ).show();
					jQuery( ".lumia_event_wrapper" ).css( { "background-color":"#fff", "opacity":"0.1" } );
					jQuery.ajax({  
						type: 'POST',  
						url: '<?php echo admin_url( 'admin-ajax.php' );?>',  
						data: {  
							action: 'get_ajax_month_view',  
							curr_month: month,  
							curr_year: year  
						},  
						success: function( data, textStatus, XMLHttpRequest ){
							jQuery( ".ajax_loader" ).hide();
							jQuery( ".lumia_event_wrapper" ).css( { "background-color":"#fff", "opacity":"1" } ).html( '' ).html( data );
							jQuery( ".event" ).colorbox({inline:true, width:"50%", height:"50%" });
							
						},  
						error: function( MLHttpRequest, textStatus, errorThrown ){  
							alert( errorThrown );  
						}  
					});  
				}
			</script>
		<?php 
		}
    }
	
	/**
	 * get_ajax_month_view
	 * @since 2.1.8
	 */	
	 
	public function get_ajax_month_view(){
		
		$ObjCalendar = new Calendar;
		$g = 1;
		$month = $_POST['curr_month'];
		$year =	$_POST['curr_year'];
		$calendar =	$ObjCalendar->getMonthHTML( $month, $year, $g );
		
		echo $calendar;
		die();
	}
	
	/**
	 * lumia_deactivation
	 * @since 2.1.8
	 */
	 
	public function lumia_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}
	
	/**
	 * uninstall
	 * @since 2.1.8
	 */
	 
	public function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		if ( __FILE__ != WP_UNINSTALL_PLUGIN )
			return;

		check_admin_referer( 'bulk-plugins' );

		global $wpdb;

		self::lumia_delete_calender_dates();

		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->options . "`" );
		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->postmeta . "`" );
		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->posts . "`" );
	}
	
	/**
	 * lumia_delete_calender_dates
	 * @since 2.1.8
	 */
	 
	public static function lumia_delete_calender_dates() {
		
		global $wpdb;
		
		$tbl_booking = $table_prefix . "lumia_calender";
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lumia_event'";
		$posts = $wpdb->get_results( $query );
		
		$del_query = "DELETE FROM {$tbl_booking}";
		$posts = $wpdb->query( $wpdb->prepare( $del_query ) );
		
		foreach( $posts as $post ) {
			$post_id = $post->ID;
			self::lumia_delete_attachments( $post_id );

			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * lumia_delete_attachments
	 * @since 2.1.8
	 */
	 
	public static function lumia_delete_attachments( $post_id = false ) {
		global $wpdb;

		$post_id = $post_id ? $post_id : 0;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = {$post_id}";
		$attachments = $wpdb->get_results( $query  );

		foreach( $attachments as $attachment ) {
			// true is force delete
			wp_delete_attachment( $attachment->ID, true );
		}
	}
	
	/**
	 * show_event_calender
	 * @since 2.1.8
	 */
	 
	public static function show_event_calender(){
		
		global $plugin_url;
		
		$ObjCalendar = new Calendar;
		
		$g = 1;
		$month = isset( $_REQUEST["month"] ) ? $_REQUEST["month"] : '';
		$year =	isset( $_REQUEST["year"] ) ? $_REQUEST["year"] : '';
		$method =	isset( $_REQUEST["method"] ) ? $_REQUEST["method"] : '';
		$calender_url		=	get_permalink( get_option( 'add_event_page_id' ) );
		
		$date =	getdate( time() );
		
		if ( $month == "" ):
			$month = $date["mon"];
		endif;
		
		if ( $year == "" ):
			$year =	$date["year"];
		endif;
	 	
		$calendar =	$ObjCalendar->getMonthHTML( $month, $year, $g );
		
		if( $method == 'addevent' ){
			self::lumia_new_event();
		} else  {
			echo '<div class="lumia_event_wrapper">
					<div class="row">
						<div class="col-md-6">
							<div class="notification">
								<ul>
									<li><img src="' . $plugin_url . '/images/today.png" alt="Today" width="20" /><span> - Today</span></li>
									<li><img src="' . $plugin_url . '/images/event_day.png" alt="Event Days" width="20" /><span> - Event Days</span></li>
								</ul>
								<div class="clear"></div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="addevent_box">
								<a href=' . $calender_url . ' class="addevent">Add new event</a>
							</div>
						</div>
					</div> 
					' . $calendar . '
				  </div>';
		}
	}
	
	/**
	 * getDatesFromNo
	 * @since 2.1.8
	 */
	 
	public function getDatesFromNo( $startDate, $endDate ){

		global $wpdb, $table_prefix;
		$date =	'';
		
		$tbl_booking = $table_prefix . "lumia_calender";
		$select = sprintf( "SELECT * FROM {$tbl_booking} WHERE date >= '%s' AND date <= '%s' AND status = '1'", $startDate, $endDate );
		$execute = $wpdb->get_results( $select ) ;

		if( $execute ):

			$i =	1;
			foreach( $execute as $dates ):

				$ext = $i != sizeof( $execute ) ? ", " : "";
				$resultDates = explode( "-", $dates->date );
				$date .= $resultDates[2] . $ext;
			endforeach;
		else:

			$date =	'';
		endif;

		return $date;
	}
	
	/**
	 * getProjectIds
	 * @since 2.1.8
	 */
	 
	public function getProjectIds( $day, $month, $year ){
		
		global $wpdb, $table_prefix;
		
		$html =	'';
		$date =	$year . '-' . $month . '-' . $day;
		$tbl_booking = $table_prefix . "lumia_calender";
		
		$select = sprintf( "SELECT post_id FROM {$tbl_booking} WHERE date = '%s' AND status = '1'", $date );
		$execute = $wpdb->get_results( $select ) ;

		if( $execute ): 
			foreach( $execute as $dates ):
				
				$wlec_events = get_post_meta( $dates->post_id, '_wlec_events', true );
				$html .= '<li>
							<a href="' . get_permalink( $dates->post_id ) . '">
								' . get_the_title( $dates->post_id );
								if( !empty( $wlec_events['location'] ) ) {
				$html 				.= ' at ' . $wlec_events['location'] . '<br/>';
								} else {
				$html 				.= '<br/>';					
								}
				$html 		.= '<span>Date - ' . $wlec_events['from'] . ' to ' . $wlec_events['to'];
								if( !empty( $wlec_events['org_name'] ) ) {
				$html 				.= ' organized by ' . $wlec_events['org_name'] . '<span>';
								}
				 
				$html 	.= '</a>
						</li> ';
			endforeach;
		else:
			$html =	'';
		endif;

		return $html;
	}
	
	/**
	 * lumia_event_slug
	 * @since 2.1.8
	 */
	 
	public function lumia_event_slug( $post_ID ) {
		
		$post_data = get_post( $post_ID, ARRAY_A );
		$slug =	$post_data['post_name'];
		
		return $slug;
	}
	
	/**
	 * get_single_event
	 * @since 2.1.8
	 */
	 
	public function get_single_event(){
		
		global $wpdb, $wp_query;
		
		ob_start();
		$slug =	$wp_query->query_vars['name'];
		
		$eventObj =	$wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE post_name = '{$slug}' AND post_status = 'publish' AND post_type = 'lumia_event'" );		
		$event_data =	get_post_meta( $eventObj->ID, '_wlec_events', true );
		
		$from =	( empty( $event_data['from'] ) ) ? '' : $event_data['from'];
		$to = ( empty( $event_data['to'] ) ) ? '' : $event_data['to'];
		$location =	( empty( $event_data['location'] ) ) ? '' :	$event_data['location'];
		$address =	( empty( $event_data['address'] ) ) ? '' : $event_data['address'];
		$city = ( empty( $event_data['city'] ) ) ? '' : $event_data['city'];
		$state = ( empty( $event_data['state'] ) ) ? '' : $event_data['state'];
		$postal_code =	( empty( $event_data['postal_code'] ) ) ? '' : $event_data['postal_code'];
		$org_name = ( empty( $event_data['org_name'] ) ) ? '' : $event_data['org_name'];
		$org_email = ( empty( $event_data['org_email'] ) ) ? '' : $event_data['org_email'];
		$org_phone = ( empty( $event_data['org_phone'] ) ) ? '' : $event_data['org_phone'];
		$email = ( empty( $event_data['email'] ) ) ? '' : $event_data['email'];
		$url =	( empty( $event_data['url'] ) ) ? '' :	$event_data['url'];
		$currency =	( empty( $event_data['currency'] ) ) ? '' : $event_data['currency'];
		$cost = ( empty( $event_data['cost'] ) ) ? '' : $event_data['cost'];
		
		if( !empty( $location ) ) $full_address = $location;
		if( !empty( $address ) ) $full_address .= ', ' . $address;
		if( !empty( $city ) ) $full_address .= ', ' . $city;
		if( !empty( $state ) ) $full_address .= ', ' . $state;
		if( !empty( $postal_code ) ) $full_address .= ', ' . $postal_code;
		?>
        <h1><?php echo get_the_title( $eventObj->ID );?></h1>
        <div class="google_map">
			<iframe width="100%" height="100%" frameborder="0" scrolling="no"  marginheight="0" marginwidth="0" src="https://maps.google.com/maps?&amp;q=<?php echo urlencode( $full_address );?>&amp;output=embed"></iframe>
        </div>
        <div class="event-header-box">
            <div class="row">
                <div class="col-md-12 content">
					<?php echo apply_filters( 'the_content', $eventObj->post_content );?>
                </div>
			</div>
		</div>            
        <div class="event-header-box">
            <div class="row">
                <div class="col-md-4 class1">
                    <div class="box-event-info">
                        <i class="fa fa-calendar"></i>
                        <p>
							<?php _e( 'Start Date: ', 'lumia_calender' );?><?php echo esc_attr( $from );?><br>
							<?php _e( 'End Date: ', 'lumia_calender' );?><?php echo esc_attr( $to );?>
                        </p>
                        <div class="clear"></div>
                    </div>
                </div>
                <div class="col-md-4 class2">
                    <div class="box-event-info">
                        <i class="fa fa-location-arrow"></i>
                        <p><?php if( !empty( $location ) ) echo $location;?><?php if( !empty( $city ) ) echo ', ' . esc_attr( $city );?></p>
                        <div class="clear"></div>
                    </div>
                </div>
                <div class="col-md-4 class3">
                    <div class="box-event-info">
                        <i class="fa fa-map-marker"></i>
                        <p>
							<?php if( !empty( $address ) ) echo esc_attr( $address );?>
                            <?php if( !empty( $state ) ) echo ', ' . esc_attr( $state );?>
                            <?php if( !empty( $postal_code ) ) echo ', ' . esc_attr( $postal_code );?>
                        </p>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>	
        <div class="event-header-box">
            <div class="row">
                <div class="col-md-4 class4">
                    <div class="box-event-info">
                        <i class="fa fa-user"></i>
                        <p>
							<?php _e( 'Organizer Name: ', 'lumia_calender' );?><br> 
							<?php if( !empty( $org_name ) ) echo esc_attr( $org_name );?></p>
                        <div class="clear"></div> 
                    </div>
                </div>
                <div class="col-md-4 class5">
                    <div class="box-event-info">
                        <i class="fa fa-envelope"></i>
                        <p>
							<?php _e( 'Email: ', 'lumia_calender' );?><br>
							<?php if( !empty( $org_email ) )?><a href="mailto:<?php echo esc_attr( $org_email );?>"><?php echo esc_attr( $org_email );?></a>
                        </p>
                        <div class="clear"></div>
                    </div>
                </div>
                <div class="col-md-4 class6">
                    <div class="box-event-info">
                        <i class="fa fa-phone"></i>
                        <p><?php _e( 'Phone: ', 'lumia_calender' );?><br><?php if( !empty( $org_phone ) ) echo esc_attr( $org_phone );?></p>
                        <div class="clear"></div> 
                    </div>
                </div>
            </div>
        </div>
        <div class="event-header-box">
            <div class="row">
                <div class="col-md-4 class7">
                    <div class="box-event-info">
                        <i class="fa fa-globe"></i>
                        <p><?php _e( 'Website: ', 'lumia_calender' );?><br> 
                        <?php if( !empty( $url ) )?><a href="<?php if ( !preg_match( "~^( ?:f|ht)tps?://~i", $url ) ) echo esc_attr( "http://" . $url ); else echo esc_attr( $url );?>" target="_blank"><?php echo esc_attr( $url );?></a>
                        </p>
                        <div class="clear"></div> 
                    </div>
                </div>
                <div class="col-md-4 class8">
                    <div class="box-event-info">
                        <i class="fa fa-envelope"></i>
                        <p>
                        	<?php _e( 'Email: ', 'lumia_calender' );?><br>
                        	<?php if( !empty( $email ) )?><a href="mailto:<?php echo esc_attr( $email );?>"><?php echo esc_attr( $email );?></a>
                        </p>
                        <div class="clear"></div>
                    </div>
                </div>
                <div class="col-md-4 class9">
                    <div class="box-event-info">
                        <i class="fa fa-dollar"></i>
                        <p>
							<?php _e( 'Price: ', 'lumia_calender' );?><br>
							<?php if( !empty( $currency ) ) echo esc_attr( $currency );?><?php if( !empty( $cost ) ) echo esc_attr( $cost );?>
                        </p>
                        <div class="clear"></div> 
                    </div>
                </div>
            </div>
        </div>							
		<?php
		return ob_get_contents();
	}
	
	/**
	 * lumia_new_event
	 * @since 2.1.8
	 */
	 
	public function lumia_new_event(){
		
		global $plugin_url;
		
		$return_url = get_permalink( get_option( 'event_calender_page_id' ) );
		$permalink = ( get_option( 'permalink_structure' ) == '' ) ? '&amp;' : '?' ;
		$empty = isset( $_REQUEST['field'] ) ? $_REQUEST['field'] : '';
		
		if( is_user_logged_in() ){
			if( $_POST ){
				$current_user =	wp_get_current_user();
				$user_id =	$current_user->ID;
				$new_event = array(
									'post_title'		=>	$_POST['event_title'],
									'post_content'		=>	$_POST['event_description'],
									'post_type'			=>	'lumia_event',
									'post_status'		=>	'publish',
									'post_date'			=>	date('Y-m-d H:i:s'),
									'post_author'		=>	$user_id,
								);
													
				$event_data['location'] = $_POST['event_location'];
				$event_data['from'] = $_POST['event-from'];
				$event_data['to'] =	$_POST['event-to'];
				
				if( $new_event && $_POST['event_title'] != '' && $_POST['event_description'] != '' && $new_event && $_POST['event-from'] != '' && $_POST['event-to'] != '' ):
					$post_ID = wp_insert_post( $new_event );
					add_post_meta( $post_ID, '_wlec_events', $event_data );
					self::save_lumia_calender_dates( $post_ID, $event_data['from'], $event_data['to'] );
					$redirect_to = $return_url;
				else: 
					$redirect_to = $return_url . $permalink . 'method=addevent&field=empty';
				endif;
			} else {
			?>
			<div class='addevent_box'>
				<div class="event_box">
                	<?php if( $empty == 'empty' ){?><p class="error">*Please filled the required fields</p><?php }?>
					<h3>Add new event</h3>
					<div class='form_wrapper'>
						<form name='frmAddEvent' id='frmAddEvent' method='post' action=''>
							<p><label>Event Title <span class="required">*</span></label><input type='text' name='event_title' id='event_title' /></p>
							<p><label>Location <span class="required">*</span></label><input type='text' name='event_location' id='event_location' /></p>
							<p><label>When </label>
								From <span class="required">*</span> <input type='text' name='event-from' id='event-from' class='small' />
								To <span class="required">*</span> <input type='text' name='event-to' id='event-to' class='small' style='margin-right:0px;'/>
							</p>
							<?php wp_editor( '', 'event_description', array( 'editor_height' => 315, "textarea_rows" => 8, "tabindex" => 4 ) );?>
							<p><label></label><input type='submit' name='btnsubmit' id='btnsubmit' value="Add Event" /></p>
						</form>
					</div>
				</div>
			</div>
			<?php 
			}
		} else { 
			$redirect_to = $login_url;
		}
		
		wp_safe_redirect( $redirect_to );
		exit();
	}
	
	/**
	 * save_lumia_calender_dates
	 * @since 2.1.8
	 */
	 
	public function save_lumia_calender_dates( $post_id, $from, $to ){
		
		global $wpdb, $table_prefix;
		
		
		
		$event_start_date =	explode( '-', $from );
		$event_end_date = explode( '-', $to );
		
		$event_start_year =	$event_start_date[0];		
		$event_start_month = $event_start_date[1];		
		$event_start_day = $event_start_date[2];	
			
		$event_end_year = $event_end_date[0];		
		$event_end_month = $event_end_date[1];		
		$event_end_day = $event_end_date[2];
		$tbl_booking = $table_prefix . "lumia_calender";
		
		if( $from != '' && $to != '' ) {
			if( $event_start_day > $event_end_day ) {
				
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_booking} WHERE post_id = %d", $post_id ) );	
								
				for( $i = $event_start_day; $i <= 31; $i++ ){
								
					$year =	( $event_start_year == $event_end_year ) ? $event_start_year : $event_end_year;
					$month = $event_start_month;
					$date =	$year . '-' . $month . '-' . $i;
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s', status = '1'", $post_id, $date ) );
				}
				for( $k = 1; $k <= $event_end_day; $k++ ) {
								
					$kyear = ( $event_start_year == $event_end_year ) ? $event_start_year :	$event_end_year;
					$kmonth =$event_end_month;
					$kdate = $kyear . '-' . $kmonth . '-' . $k;
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s', status = '1'", $post_id, $kdate ) );
				}
			} else {
				
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_booking} WHERE post_id = %d", $post_id ) );	
								
				for( $i = $event_start_day; $i <= $event_end_day; $i++ ) {
								
					$year =	( $event_start_year == $event_end_year ) ? $event_start_year : $event_end_year;
					$month = ( $event_start_month == $event_end_month ) ? $event_start_month : $event_end_month;
					$date =	$year . '-' . $month . '-' . $i;
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s', status = '1'", $post_id, $date ) );
				}
			}
		}
	}
	
	/**
	 * event listing page template
	 * @since 2.1.8
	 */
	 
	public function event_page_template( $page_template ) { 
		
		$calender_page_id = absint( get_option( 'event_calender_page_id' ) );
		$event_page_id = absint( get_option( 'add_event_page_id' ) );
		
		/* Checks for page template by page name */
		if ( is_page( $calender_page_id ) ) {
			if( file_exists( dirname( __FILE__ ) . '/templates/event-page-template.php' ) )
				$page_template = dirname( __FILE__ ) . '/templates/event-page-template.php';
		}
		
		if ( is_page( $event_page_id ) ) {
			if( file_exists( dirname( __FILE__ ) . '/templates/add-new-event.php' ) )
				$page_template = dirname( __FILE__ ) . '/templates/add-new-event.php';
		}
		return $page_template;
	}
	
	/**
	 * single event template
	 * @since 2.1.8
	 */
	 
	public function single_event_template ( $page_template ) {
		
		global $post;
		
		/* Checks for single template by post type */
		if ( $post->post_type == "lumia_event" ){
			if( file_exists( dirname( __FILE__ ) . '/templates/single-event-template.php' ) )
				$page_template = dirname( __FILE__ ) . '/templates/single-event-template.php';
		}
		return $page_template;
	}
}

/**
 * init_calender_post_type
 * @since 2.1.8
 */
 
add_action( 'init', 'init_calender_post_type' );

function init_calender_post_type() {
	$label						=	'Event Calender';
	$labels = array(
		'name' 					=>	_x( $label, 'post type general name' ),
		'singular_name' 		=>	_x( $label, 'post type singular name' ),
		'add_new'				=>	_x( 'Add New', 'lumia-calender' ),
		'add_new_item' 			=>	__( 'Add New Event', 'lumia-calender' ),
		'edit_item' 			=>	__( 'Edit Event', 'lumia-calender'),
		'new_item' 				=>	__( 'New Event' , 'lumia-calender' ),
		'view_item' 			=>	__( 'View Event', 'lumia-calender' ),
		'search_items'			=>	__( 'Search ' . $label ),
		'not_found'				=>	__( 'Nothing found' ),
		'not_found_in_trash'	=>	__( 'Nothing found in Trash' ),
		'parent_item_colon'		=>	''
	);
	
	register_post_type( 'lumia_event', 
					   		array(
								'labels'				=>	$labels,
								'public'				=>	true,
								'publicly_queryable'	=>	true,
								'show_ui'				=>	true,
								'exclude_from_search'	=>	true,
								'query_var'				=>	true,
								'rewrite'				=>	array( 'slug' => 'event', 'with_front' => false ),
								'capability_type'		=>	'post',
								'has_archive'			=>	false,
								'hierarchical'			=>	false,
								'menu_position'			=>	65,
								'supports'				=>	array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
								'menu_icon'				=>	plugins_url( '/', __FILE__ ) . 'images/calendar.png',
								'register_meta_box_cb'	=> 'lumia_event_meta_boxes',
								)
						);
	flush_rewrite_rules();
}

/**
 * lumia_event_meta_boxes
 * @since 2.1.8
 */
 
function lumia_event_meta_boxes() {
	add_meta_box( 	
					'display_lumia_event_meta_box',
					'Event Calender Details',
					'display_lumia_event_meta_box',
					'lumia_event',
					'normal', 
					'high'
				 );
}

/**
 * display_lumia_event_meta_box
 * @since 2.1.8
 */
 
function display_lumia_event_meta_box() {
	
	global $LumiaCalender;
	
	$post_id =	get_the_ID();
	$event_data = get_post_meta( $post_id, '_wlec_events', true );
	
	//Event Time and Date
	$from =	( empty( $event_data['from'] ) ) ? '' :	$event_data['from'];
	$to = ( empty( $event_data['to'] ) ) ? ''	: $event_data['to'];
	
	//Event Location Details
	$location =	( empty( $event_data['location'] ) ) ? '' :	$event_data['location'];
	$address = ( empty( $event_data['address'] ) ) ? '' : $event_data['address'];	
	$city =	( empty( $event_data['city'] ) ) ? '' :	$event_data['city'];	
	$state = ( empty( $event_data['state'] ) ) ? '' : $event_data['state'];	
	$postal_code = ( empty( $event_data['postal_code'] ) ) ? '' : $event_data['postal_code'];	
	
	//Event Organizer Details
	$org_name =	( empty( $event_data['org_name'] ) ) ? '' : $event_data['org_name'];	
	$org_email = ( empty( $event_data['org_email'] ) ) ? '' : $event_data['org_email'];	
	$org_phone = ( empty( $event_data['org_phone'] ) ) ? '' : $event_data['org_phone'];	
	
	//Event Email and URL
	$email = ( empty( $event_data['email'] ) ) ? '' : $event_data['email'];	
	$url = ( empty( $event_data['url'] ) ) ? '' : $event_data['url'];	
	
	//Event Cost
	$currency =	( empty( $event_data['currency'] ) ) ? '' :	$event_data['currency'];	
	$cost =	( empty( $event_data['cost'] ) ) ? '' :	$event_data['cost'];			 
	
	wp_nonce_field( 'lumia_event', 'lumia_event' );
	?>
    <h3>Event Time</h3>
    <table class="widefat" style="border:none;">
        <tr>
            <td style="width:20%">Start Date: </td>
            <td><input type="text" class="widefat" name="event[from]" value="<?php echo $from; ?>" id="event-from" /></td>
        </tr>
        <tr>
            <td style="width:20%">End Date: </td>
            <td><input type="text" class="widefat" name="event[to]" value="<?php echo $to; ?>" id="event-to" /></td>
        </tr>
    </table>
    <h3>Event Location Details</h3>
    <table class="widefat" style="border:none;">
        <tr>
            <td style="width:20%">Location  : </td>
            <td><input type="text" class="widefat" name="event[location]" value="<?php echo $location; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">Address: </td>
            <td><input type="text" class="widefat" name="event[address]" value="<?php echo $address; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">City: </td>
            <td><input type="text" class="widefat" name="event[city]" value="<?php echo $city; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">State: </td>
            <td><input type="text" class="widefat" name="event[state]" value="<?php echo $state; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">Postal Code: </td>
            <td><input type="text" class="widefat" name="event[postal_code]" value="<?php echo $postal_code; ?>" /></td>
        </tr>        
    </table>
    <h3>Event Organizer Details</h3>
    <table class="widefat" style="border:none;">
        <tr>
            <td style="width:20%">Organizer Name : </td>
            <td><input type="text" class="widefat" name="event[org_name]" value="<?php echo $org_name; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">Email: </td>
            <td><input type="text" class="widefat" name="event[org_email]" value="<?php echo $org_email; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">Phone: </td>
            <td><input type="text" class="widefat" name="event[org_phone]" value="<?php echo $org_phone; ?>" /></td>
        </tr>       
    </table>
    <h3>Event Email and URL</h3>
    <table class="widefat" style="border:none;">
        <tr>
            <td style="width:20%">Email: </td>
            <td><input type="text" class="widefat" name="event[email]" value="<?php echo $email; ?>" /></td>
        </tr>      
        <tr>
            <td style="width:20%">URL : </td>
            <td><input type="text" class="widefat" name="event[url]" value="<?php echo $url; ?>" /></td>
        </tr>  
    </table>
    <h3>Event Cost</h3>
    <table class="widefat" style="border:none;">
        <tr>
            <td style="width:20%">Currency Symbol : </td>
            <td><input type="text" class="widefat" name="event[currency]" value="<?php echo $currency; ?>" /></td>
        </tr>
        <tr>
            <td style="width:20%">Cost: </td>
            <td><input type="text" class="widefat" name="event[cost]" value="<?php echo $cost; ?>" /></td>
        </tr>    
    </table>
	<?php
} 

/**
 * lumia_event_save_post
 * @since 2.1.8
 */
 
add_action( 'save_post', 'lumia_event_save_post' );

function lumia_event_save_post( $post_id ) {
	
	global $LumiaCalender;
	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( ! empty( $_POST['lumia_event'] ) && ! wp_verify_nonce( $_POST['lumia_event'], 'lumia_event' ) )
		return;

	if ( ! empty( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) )
			return;
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
	}
	
	if ( ! empty( $_POST['event'] ) ) {
		
		//Event Time and Date
		$event_data['from'] = ( empty( $_POST['event']['from'] ) ) ? '' : sanitize_text_field( $_POST['event']['from'] );
		$event_data['to'] = ( empty( $_POST['event']['to'] ) ) ? ''	: sanitize_text_field( $_POST['event']['to'] );
		
		//Event Location Details
		$event_data['location'] = ( empty( $_POST['event']['location'] ) ) ? '' : sanitize_text_field( $_POST['event']['location'] );
		$event_data['address'] = ( empty( $_POST['event']['address'] ) ) ? '' : sanitize_text_field( $_POST['event']['address'] );	
		$event_data['city'] = ( empty( $_POST['event']['city'] ) ) ? '' : sanitize_text_field( $_POST['event']['city'] );	
		$event_data['state'] = ( empty( $_POST['event']['state'] ) ) ? '' : sanitize_text_field( $_POST['event']['state'] );	
		$event_data['postal_code'] = ( empty( $_POST['event']['postal_code'] ) ) ? '' : sanitize_text_field( $_POST['event']['postal_code'] );	
		
		//Event Organizer Details
		$event_data['org_name'] = ( empty( $_POST['event']['org_name'] ) ) ? '' : sanitize_text_field( $_POST['event']['org_name'] );	
		$event_data['org_email'] = ( empty( $_POST['event']['org_email'] ) ) ? '' : sanitize_text_field( $_POST['event']['org_email'] );	
		$event_data['org_phone'] = ( empty( $_POST['event']['org_phone'] ) ) ? '' : sanitize_text_field( $_POST['event']['org_phone'] );	
		
		//Event Email and URL
		$event_data['email'] = ( empty( $_POST['event']['email'] ) ) ? '' : sanitize_text_field( $_POST['event']['email'] );	
		$event_data['url'] = ( empty( $_POST['event']['url'] ) ) ? '' : sanitize_text_field( $_POST['event']['url'] );	
		
		//Event Cost
		$event_data['currency'] = ( empty( $_POST['event']['currency'] ) ) ? '' : sanitize_text_field( $_POST['event']['currency'] );	
		$event_data['cost'] = ( empty( $_POST['event']['cost'] ) ) ? '' : sanitize_text_field( $_POST['event']['cost'] );		
		
		$LumiaCalender->save_lumia_calender_dates( $post_id, $event_data['from'], $event_data['to'] );
		update_post_meta( $post_id, '_wlec_events', $event_data );
	} else {
		delete_post_meta( $post_id, '_wlec_events' );
	}
}

/**
 * lumia_event_edit_columns
 * @since 2.1.8
 */
 
add_filter( 'manage_edit-lumia_event_columns', 'lumia_event_edit_columns' );

function lumia_event_edit_columns( $columns ) {
	$columns = array(
		'cb'						=>	'<input type="checkbox" />',
		'title'						=>	'Title',
		'event-date'  				=>	'Event Date',
		'event-location'			=>	'Location',
		'event-organizer'			=>	'Organizer Name',
		'event-email'				=>	'Organizer Email',
		'event-cost'				=>	'Cost',
		'date'						=>	'Date'
	);

	return $columns;
}

/**
 * lumia_event_columns
 * @since 2.1.8
 */
 
add_action( 'manage_posts_custom_column', 'lumia_event_columns', 10, 2 );

function lumia_event_columns( $column, $post_id ) {
	
	$event_data = get_post_meta( $post_id, '_wlec_events', true );
	switch ( $column ) {
		case 'event-date':
			if ( ! empty( $event_data['from'] ) && ! empty( $event_data['to'] ) )
				echo 'Start Date : ' . $event_data['from'] . '<br/>End Date : ' . $event_data['from'];
			break;
		case 'event-location':
			if ( ! empty( $event_data['location'] ) )
				echo $event_data['location'];
			break;					
		case 'event-organizer':
			if ( ! empty( $event_data['org_name'] ) )
				echo $event_data['org_name'];
			break;
		case 'event-email':
			if ( ! empty( $event_data['org_email'] ) )
				echo $event_data['org_email'];
			break;
		case 'event-cost':
			if ( ! empty( $event_data['cost'] ) && ! empty( $event_data['currency'] )  )
				echo $event_data['currency'] . $event_data['cost'];
			break;
	}
}

global $table_prefix, $wpdb;
$lumia_calender = $table_prefix . "lumia_calender";

if( $wpdb->get_var( "show tables like '$lumia_calender'" ) != $lumia_calender ) {

	if ( $wpdb->supports_collation() ) {
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
	}
	
	$createbBooking = "CREATE TABLE IF NOT EXISTS " . $lumia_calender . "(
							id INT( 15 ) NOT NULL AUTO_INCREMENT ,
							post_id INT( 15 ) NOT NULL,
							date DATE NOT NULL,
							status tinyint( 1 ) NOT NULL,
							PRIMARY KEY ( id )
							) " . $charset_collate . ";";
	
	dbDelta( $createbBooking );
}

$LumiaCalender = new Lumia_Calender;
?>