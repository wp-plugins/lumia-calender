<?php
/*
Plugin Name: WP Lumia Calender
Plugin URI: http://offshorent.com/
Description: Lumia Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage..
Version: 2.1.4
Author: Jinesh.P.V, Offshorent Software Pvt Ltd.
Author URI: http://www.offshorent.com/
*/
/**
	Copyright 2013 Jinesh.P.V (email: jinuvijay5@gmail.com)

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
require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
require_once( 'lumia-calendar.class.php' );
$plugin_url 			=	plugins_url() . '/wp-lumia-calender';

class Lumia_Calender {
	
	/* constructor function for class*/
	public function __construct() {
		
		add_action( 'init', array( &$this, 'init' ) );
		register_activation_hook( __FILE__, array( &$this, 'lumia_activation' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'lumia_deactivation' ) );
		register_uninstall_hook( __FILE__, array( 'lumia_portfolio', 'lumia_uninstall' ) );
		add_action( 'admin_init', array( &$this, 'lumia_admin_scripts' ) );
		add_shortcode( 'event_calender', array( &$this, 'show_event_calender' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_event_colorbox_js' ), 20 );
		add_action( 'wp_footer', array( &$this, 'load_event_colorbox_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'lumia_admin_scripts' ) );
	}
	
	/* init function for lumia portfolios*/
	
	public function init(){
		self::lumia_admin_stylesheet();
		self::create_portfolio_page();
	}
	
	
	public function lumia_activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		$plugin					=	isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
		self::init();

		flush_rewrite_rules();
	}
	
	public function create_portfolio_page(){
		
		global $wpdb;
		
		$pageArray		=	$wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_content = '[event_calender]' AND post_status = 'publish' AND post_type = 'page'" );
		$portfolio		=	array(
			'post_title'    => 'Event Calender',
			'post_content'  => '[event_calender]',
			'post_type'     => 'page',
			'post_status'   => 'publish',
			'post_date'		=>	date('Y-m-d H:i:s'),
			'post_author'   => 1,
		);
		
		if( count( $pageArray ) < 0 || count( $pageArray ) == 0 ){
			$post_id		=	wp_insert_post( $portfolio );
		}
	}
	
	public function lumia_admin_stylesheet() {
		
		$calenderstylesheetURL 			= 	plugins_url( 'style/style.css', __FILE__ );
		$calenderstylesheet 			= 	dirname( __FILE__ )  . '/style/style.css';
		
		if ( file_exists( $calenderstylesheet ) ) {
			
			wp_register_style( 'lumia-calender-stylesheets', $calenderstylesheetURL );
			wp_enqueue_style( 'lumia-calender-stylesheets' );
		}
		
		$colorpickerstylesheetURL 		= 	plugins_url( 'style/datepicker.css', __FILE__ );
		$colorpickerstylesheet 			= 	dirname( __FILE__ )  . '/style/datepicker.css';
		
		if ( file_exists( $colorpickerstylesheet ) ) {
			
			wp_register_style( 'lumia-datepicker-stylesheets', $colorpickerstylesheetURL );
			wp_enqueue_style( 'lumia-datepicker-stylesheets' );
		}
		
		$colorboxstylesheetURL 			= 	plugins_url( 'style/colorbox.css', __FILE__ );
		$colorboxstylesheet 			= 	dirname( __FILE__ )  . '/style/colorbox.css';
		
		if ( file_exists( $colorboxstylesheet ) ) {
			
			wp_register_style( 'lumia-colorbox-stylesheets', $colorboxstylesheetURL );
			wp_enqueue_style( 'lumia-colorbox-stylesheets' );
		}
	}
	
	public function lumia_admin_scripts() {
	
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'datepicker', plugins_url( '/js/datepicker.js', __FILE__ ), '1.4.2' );
		wp_enqueue_script( 'core', plugins_url( '/js/core.js', __FILE__ ), '1.0.2' );
		
	}
	
	public function load_event_colorbox_js(){
		if( !is_admin() ){
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'colorbox', plugins_url( '/js/jquery.colorbox.js', __FILE__ ), '1.4.24' );
		}
	}
	
	public function load_event_colorbox_scripts(){
		if( !is_admin() ){
			?>
			<script type="text/javascript">
				jQuery( window ).load( function(){ 
					jQuery( ".event" ).colorbox({inline:true, width:"30%"}); 				
					jQuery( "#click" ).click(function(){ 
						jQuery('#click').css({"background-color":"#f00", "color":"#fff", "cursor":"inherit"}).text("Open this window again and this message will still be here.");
						return false;
					});
				});	
			</script>
		<?php
		}
    }
	
	public function lumia_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );

		flush_rewrite_rules();
	}
	
	public function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		if ( __FILE__ !=	WP_UNINSTALL_PLUGIN )
			return;

		check_admin_referer( 'bulk-plugins' );

		global $wpdb;

		self::lumia_delete_portfolios();

		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->options . "`" );
		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->postmeta . "`" );
		$wpdb->query( "OPTIMIZE TABLE `" . $wpdb->posts . "`" );
	}
	
	public static function lumia_delete_portfolios() {
		global $wpdb;

		$query					= "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'portfolio'";
		$posts					= $wpdb->get_results( $query );

		foreach( $posts as $post ) {
			$post_id			= $post->ID;
			self::lumia_delete_attachments( $post_id );

			wp_delete_post( $post_id, true );
		}
	}


	public static function lumia_delete_attachments( $post_id = false ) {
		global $wpdb;

		$post_id				= $post_id ? $post_id : 0;
		$query					= "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = {$post_id}";
		$attachments			= $wpdb->get_results( $query );

		foreach( $attachments as $attachment ) {
			// true is force delete
			wp_delete_attachment( $attachment->ID, true );
		}
	}
	
	public static function show_event_calender(){
		
		$ObjCalendar						=   new Calendar;
		$g									=	1;
		$month								=	@$_REQUEST["month"];
		$year								=	@$_REQUEST["year"];
		$date 								=	getdate( time() );
		
		if ( $month == "" ):
			$month 							=	$date["mon"];
		endif;
		
		if ( $year == "" ):
			$year 							=	$date["year"];
		endif;
	
		$calaender							=	$ObjCalendar->getMonthHTML( $month, $year, $g );
		
		if( @$_GET['event'] ){
			echo self::get_single_event( @$_GET['event'] );
		}else if( @$_GET['method'] == 'addevent' ){
			self::lumia_new_event();
		} else  {
			echo $calaender;
		}
	}
	
	public function getDatesFromNo( $startDate, $endDate ){

		global $wpdb, $table_prefix;
		$date								=	'';
		
		$tbl_booking						=	$table_prefix . "lumia_calender";
		$selectSQL							=	sprintf( "SELECT * FROM {$tbl_booking} WHERE date >= '%s' AND date <= '%s'", $startDate, $endDate );
		$executeSQL							=	$wpdb->get_results( $selectSQL ) ;

		if( $executeSQL ):

			$i                              =	1;
			foreach( $executeSQL as $dates ):

				$ext						=	$i != sizeof( $executeSQL ) ? ", " : "";
				$resultDates				=	explode( "-", $dates->date );
				$date						.=	$resultDates[2] . $ext;
			endforeach;
		else:

			$date							=	'';
		endif;

		return $date;
	}
	
	public function getProjectIds( $day, $month, $year ){
		
		global $wpdb, $table_prefix;
		
		$html								=	'';
		$date								=	$year . '-' . $month . '-' . $day;
		$tbl_booking						=	$table_prefix . "lumia_calender";
		
		$selectSQL							=	sprintf( "SELECT post_id FROM {$tbl_booking} WHERE date = '%s'", $date );
		$executeSQL							=	$wpdb->get_results( $selectSQL ) ;
		$permalink							=	( get_option( 'permalink_structure' ) == '' ) ? '&amp;' : '?' ;
		$get_event_calender_link			=	self::get_event_calender_link() . $permalink;


		if( $executeSQL ):
			foreach( $executeSQL as $dates ):

				$html						.=	'<li><a href="' . $get_event_calender_link . 'event=' . self::lumia_event_slug( $dates->post_id ) . '">' . get_the_title( $dates->post_id ) . '</a></li> ';
			endforeach;
		else:
			$html							=	'';
		endif;

		return $html;
	}
	
	public function lumia_event_slug( $post_ID ) {
		
		$post_data							=	get_post( $post_ID, ARRAY_A );
		$slug								=	$post_data['post_name'];
		
		return $slug;
	}
	
	public function get_event_calender_link(){
		
		global $wpdb;
		$pageArray							=	$wpdb->get_row( "SELECT ID FROM $wpdb->posts WHERE post_content = '[event_calender]' AND post_status = 'publish' AND post_type = 'page'" );
		
		return get_permalink( $pageArray->ID );
	}
	
	public function get_single_event( $event_slug ){
		
		global $wpdb;
		
		$eventObj						=	$wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE post_name = '{$event_slug}' AND post_status = 'publish' AND post_type = 'lumia_event'" );
		
			
		$event_data						=	get_post_meta( $eventObj->ID, '_wlec_events', true );
		$location						=	( empty( $event_data['location'] ) )               	?	''	:	$event_data['location'];
		$from							=	( empty( $event_data['from'] ) )    				?	''	:	$event_data['from'];
		$to								=	( empty( $event_data['to'] ) )    					?	''	:	$event_data['to'];
	
		$html							=	'<h1 class="page-title">' . get_the_title( $eventObj->ID ) . '</h1>
											<p>';
		if( $location ){
			$html						.=		'<span><label>Location : </label>' . $location . '</span>';
		}
		if( $from && $to ){
			$html						.=		'<span><label>Date : </label>' . $from . ' - ' . $to . '</span>';
		}
		$html							.=		'<span><label>Posted By : </label>' . get_the_author_meta( 'display_name', $eventObj->post_author ) . '</span>
											</p>
											' . apply_filters( 'the_content', $eventObj->post_content );
		
		return $html;
	}
	
	public function lumia_new_event(){
		
		global $plugin_url;
		$return_url							=	self::get_event_calender_link();
		$permalink							=	( get_option( 'permalink_structure' ) == '' ) ? '&amp;' : '?' ;
		
		if( is_user_logged_in() ){
			if( $_POST ){
				$current_user				=	wp_get_current_user();
				$user_id					=	$current_user->ID;
				$new_event					=	array(
														'post_title'		=>	$_POST['event_title'],
														'post_content'		=>	$_POST['event_description'],
														'post_type'			=>	'lumia_event',
														'post_status'		=>	'publish',
														'post_date'			=>	date('Y-m-d H:i:s'),
														'post_author'		=>	$user_id,
													);
													
				$event_data['location']		=	$_POST['event_location'];
				$event_data['from']			=	$_POST['event-from'];
				$event_data['to']			=	$_POST['event-to'];
				
				if( $new_event && $_POST['event_title'] != '' && $_POST['event_description'] != '' && $new_event && $_POST['event-from'] != '' && $_POST['event-to'] != '' ):
					$post_ID		=	wp_insert_post( $new_event );
					add_post_meta( $post_ID, '_wlec_events', $event_data );
					self::save_lumia_calender_dates( $post_ID, $event_data['from'], $event_data['to'] );
					$redirect_to			=	$return_url;
				else: 
					$redirect_to			=	$return_url . $permalink . 'method=addevent&field=empty';
				endif;
			} else {
			?>
			<div class='addevent_box'>
				<div class="event_box">
                	<?php if( $_REQUEST['field'] == 'empty' ){?><p class="error">*Please filled the required fields</p><?php }?>
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
			$redirect_to		=	$login_url;
		}
		
		wp_safe_redirect( $redirect_to );
		exit();
	}
	
	public function save_lumia_calender_dates( $post_id, $from, $to ){
		
		global $wpdb, $table_prefix;
		
		$event_start_date				=	explode( '-', $from );
		$event_end_date					=	explode( '-', $to );
		
		$event_start_year				=	$event_start_date[0];		
		$event_start_month				=	$event_start_date[1];		
		$event_start_day				=	$event_start_date[2];	
			
		$event_end_year					=	$event_end_date[0];		
		$event_end_month				=	$event_end_date[1];		
		$event_end_day					=	$event_end_date[2];	
		$tbl_booking					=	$table_prefix . "lumia_calender";
		
		if( $from != '' && $to != '' ){
			if( $event_start_day > $event_end_day ){
				for( $i = $event_start_day; $i <= 31; $i++ ){
								
					$year				=	( $event_start_year == $event_end_year ) 			?	$event_start_year		:	$event_end_year;
					$month				=	$event_start_month;
					$date				=	$year . '-' . $month . '-' . $i;
					$querySQL			=	sprintf( "SELECT * FROM {$tbl_booking} WHERE post_id = %d AND date = '%s'", $post_id, $date );
					$selectSQL			=	$wpdb->get_row( $querySQL );
					
					if(  count( $selectSQL ) < 0 || count( $selectSQL ) == 0  ){
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s'", $post_id, $date ) );
					} else {
						$wpdb->query( $wpdb->prepare( "UPDFATE {$tbl_booking} SET date = '%s' WHERE post_id = %d", $date, $post_id ) );
					}
				}
				for( $k = 1; $k <= $event_end_day; $k++ ){
								
					$kyear				=	( $event_start_year == $event_end_year ) 			?	$event_start_year		:	$event_end_year;
					$kmonth				=	$event_end_month;
					$kdate				=	$kyear . '-' . $kmonth . '-' . $k;
					$querySQL			=	sprintf( "SELECT * FROM {$tbl_booking} WHERE post_id = %d AND date = '%s'", $post_id, $kdate );
					$selectSQL			=	$wpdb->get_row( $querySQL );
					
					if(  count( $selectSQL ) < 0 || count( $selectSQL ) == 0  ){
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s'", $post_id, $kdate ) );
					} else {
						$wpdb->query( $wpdb->prepare( "UPDFATE {$tbl_booking} SET date = '%s' WHERE post_id = %d", $kdate, $post_id ) );
					}
				}
			} else {
				for( $i = $event_start_day; $i <= $event_end_day; $i++ ){
								
					$year				=	( $event_start_year == $event_end_year ) 			?	$event_start_year		:	$event_end_year;
					$month				=	( $event_start_month == $event_end_month ) 			?	$event_start_month		:	$event_end_month;
					$date				=	$year . '-' . $month . '-' . $i;
					$querySQL			=	sprintf( "SELECT * FROM {$tbl_booking} WHERE post_id = %d AND date = '%s'", $post_id, $date );
					$selectSQL			=	$wpdb->get_row( $querySQL );
					
					if(  count( $selectSQL ) < 0 || count( $selectSQL ) == 0  ){
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$tbl_booking} SET post_id = %d, date = '%s'", $post_id, $date ) );
					} else {
						$wpdb->query( $wpdb->prepare( "UPDFATE {$tbl_booking} SET date = '%s' WHERE post_id = %d", $date, $post_id ) );
					}
				}
			}
		}
	}
}


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
								'rewrite'				=>	true,
								'capability_type'		=>	'post',
								'has_archive'			=>	false,
								'hierarchical'			=>	false,
								'menu_position'			=>	65,
								'supports'				=>	array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
								'menu_icon'				=>	plugins_url( '/', __FILE__ ) . 'images/calendar.png',
								'register_meta_box_cb'	=> 'lumia_event_meta_boxes',
								)
						);
}

function lumia_event_meta_boxes() {
	add_meta_box( 	
					'display_lumia_event_meta_box',
					'Event Calender Meta',
					'display_lumia_event_meta_box',
					'lumia_event',
					'normal', 
					'high'
				 );
}

function display_lumia_event_meta_box() {
	global $LumiaCalender;
	$post_id					=	get_the_ID();
	$event_data					=	get_post_meta( $post_id, '_wlec_events', true );
	$location					=	( empty( $event_data['location'] ) )               	?	''	:	$event_data['location'];
	$from						=	( empty( $event_data['from'] ) )    				?	''	:	$event_data['from'];
	$to							=	( empty( $event_data['to'] ) )    					?	''	:	$event_data['to'];
	
	$LumiaCalender->save_lumia_calender_dates( $post_id, $from, $to ); 
	
	wp_nonce_field( 'lumia_event', 'lumia_event' );
	?>
    <table>
        <tr>
            <td style="width: 150px">Location  : </td>
            <td><input type="text" size="130" name="event[location]" value="<?php echo $location; ?>" /></td>
        </tr>
        <tr>
            <td style="width: 150px">When: </td>
            <td>
                From : <input type="text" size="30" name="event[from]" value="<?php echo $from; ?>" id="event-from" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                To : <input type="text" size="30" name="event[to]" value="<?php echo $to; ?>" id="event-to"  />
            </td>
        </tr>
        
    </table>
	<?php
} 

add_action( 'save_post', 'lumia_event_save_post' );

function lumia_event_save_post( $post_id ) {
	
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
		$event_data['location']		=	( empty( $_POST['event']['location'] ) )            ? ''	:	sanitize_text_field( $_POST['event']['location'] );
		$event_data['from']			=	( empty( $_POST['event']['from'] ) )				? ''	:	sanitize_text_field( $_POST['event']['from'] );
		$event_data['to']			=	( empty( $_POST['event']['to'] ) )					? ''	:	sanitize_text_field( $_POST['event']['to'] );
		
		update_post_meta( $post_id, '_wlec_events', $event_data );
	} else {
		delete_post_meta( $post_id, '_wlec_events' );
	}
}

add_filter( 'manage_edit-lumia_event_columns', 'lumia_event_edit_columns' );

function lumia_event_edit_columns( $columns ) {
	$columns = array(
		'cb'						=>	'<input type="checkbox" />',
		'title'						=>	'Title',
		'event-location'			=>	'Location',
		'event-date'  				=>	'Event Date',
		'date'						=>	'Date'
	);

	return $columns;
}

add_action( 'manage_posts_custom_column', 'lumia_event_columns', 10, 2 );

function lumia_event_columns( $column, $post_id ) {
	
	$event_data			=	get_post_meta( $post_id, '_wlec_events', true );
	switch ( $column ) {
		case 'event-location':
			if ( ! empty( $event_data['location'] ) )
				echo $event_data['location'];
			break;
		case 'event-date':
			if ( ! empty( $event_data['from'] ) && ! empty( $event_data['to'] ) )
				echo 'Start Date : ' . $event_data['from'] . '<br/>End Date : ' . $event_data['from'];
			break;
	}
}

global $table_prefix;
$lumia_calender 		=	$table_prefix . "lumia_calender";

if( $wpdb->get_var( "show tables like '$lumia_calender'" ) != $lumia_calender ) {

	if ( $wpdb->supports_collation() ) {
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
	}
	
	$createbBookingSQL 	=	"CREATE TABLE IF NOT EXISTS " . $lumia_calender . "(
							id INT( 15 ) NOT NULL AUTO_INCREMENT ,
							post_id INT( 15 ) NOT NULL,
							date DATE NOT NULL,
							PRIMARY KEY ( id )
							) ".$charset_collate.";";
	
	dbDelta( $createbBookingSQL );
}

$LumiaCalender		=	new Lumia_Calender;
?>