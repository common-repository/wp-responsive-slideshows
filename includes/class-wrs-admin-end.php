<?php
/**
 * Admin Dashboard
 *
 * @author 		Sagar Pansare
 * @category 	Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WRSAdmin' ) ) :

class WRSAdmin {

	private $post_type = WRS_POST_TYPE;

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		add_action( 'admin_head', array( $this, 'load_font_awesome' ) );

		add_action( 'init', array( $this, 'wrs_register_slideshows_cpt' ) );

		/** Add and remove meta boxes **/
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );

		/** Add Shortcode Column to Slideshow CPT  **/
		add_filter('manage_posts_columns',  array( $this, 'slideshows_columns_heading') );
		add_action('manage_posts_custom_column', array( $this, 'slideshows_columns_data'), 10, 2);

		/** Add Slideshow Shortcode Button to Editor **/
		$pages_with_editor_button = array( 'post.php', 'post-new.php' );
		foreach ( $pages_with_editor_button as $editor_page ) {
		      add_action( "load-{$editor_page}", array( $this, 'slideshow_shortcode_editor_button') );
		}
		add_action( "admin_head", array( $this, 'slideshow_shortcode_button_style' ) );
		add_action( "wp_ajax_fetch_slideshow_shortcodes", array( $this, "fetch_slideshow_shortcodes_ajax") );

		/** Save the slideshow images and settings data into database **/
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'wp_ajax_update_post', array( $this, 'update_post_ajax' ) );

		/** Insert Image By URL using AJAX **/
		add_action( 'wp_ajax_insert_image_by_url', array( $this, 'insert_image_by_url_ajax' ) );

		/** Custom Admin Update Messages **/
		add_filter( 'post_updated_messages', array( $this, 'custom_update_messages' ) );

		/** Store Thumbnail Images of Videos on Server by AJAX **/
		add_action( 'wp_ajax_store_video_thumbnail_images', array( $this, 'store_video_thumbnail_images_ajax' ) ); 

	}

	/**
	* Validate WRS ADMIN Page
	*/
	public function validate_wrs_admin_page() {
		global $post, $pagenow;
		
		$post_type = "";

		if ( isset( $_GET['post_type'] ) ) 
			$post_type = $_GET['post_type'];

		if ( get_post_type( $post ) === $this->post_type && $pagenow == "post.php")
			return TRUE;

		if ( $post_type === $this->post_type && $pagenow == "post-new.php")
			return TRUE;

		if ( get_post_type( $post ) === $this->post_type && $pagenow == "edit.php")
			return FALSE;

		return FALSE;
	}

	/**
	* Load Font Awesome in WRS Admin
	*/
	public function load_font_awesome() {
		?>  
			<style type="text/css">
			    @font-face{font-family:'FontAwesome';src:url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.eot?v=4.1.0');src:url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.eot?#iefix&v=4.1.0') format('embedded-opentype'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.woff?v=4.1.0') format('woff'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.ttf?v=4.1.0') format('truetype'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.svg?v=4.1.0#fontawesomeregular') format('svg');font-weight:normal;font-style:normal}
			</style>
		<?php
	}

	/**
	* Register Admin Style and Script
	*/
	public function admin_enqueue() {

		if ( ! $this->validate_wrs_admin_page() )
			return FALSE;

		wp_enqueue_style( 'wrs-admin-style',
			WRS_PLUGIN_URL.'/admin_css_js/wrs-admin-style.css',
			array(), WRS_PLUGIN_VERSION );
		
		wp_enqueue_script('backbone');
		wp_enqueue_script('underscore');
		wp_enqueue_media();

		wp_enqueue_script('sortables');

		wp_enqueue_script( 'wrs-admin-script',
			WRS_PLUGIN_URL.'/admin_css_js/wrs-admin-script.js',
			array('jquery', 'media-upload', 'media-views'), WRS_PLUGIN_VERSION , true);

		wp_dequeue_script( 'autosave' );

		$script_data = array( 'ajaxurl' => admin_url('admin-ajax.php') );
		wp_localize_script( 'wrs-admin-script', 'wrs_script', $script_data );
		
	}

	/**
	*	Register Slideshows Custom Post Type
	*/
	public function wrs_register_slideshows_cpt() {

		$labels = array(
			'name' => _x( 'Slideshows', 'post type general name' ),
			'singular_name' => _x( 'Slideshow', 'post type singular name' ),
			'add_new' => _x( 'Add Slideshow', 'Popup' ),
			'add_new_item' => __( 'Add New Slideshow' ),
			'edit_item' => __( 'Edit Slideshow' ),
			'new_item' => __( 'New Slideshow' ),
			'all_items' => __( 'All Slideshows' ),
			'view_item' => __( 'View Slideshow' ),
			'search_items' => __( 'Search Slideshows' ),
			'not_found' =>  __( 'No Slideshows found' ),
			'not_found_in_trash' => __( 'No Slideshows found in Trash' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'WRS Slideshows' )

		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => false,
			'rewrite' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_icon'	=> 'dashicons-images-alt2',
			'menu_position' => null,
			'supports' => array( 'title' )
		);

		register_post_type( $this->post_type, $args );

	}

	/**
	* Slideshow CPT Custom Columns
	*/
	public function slideshows_columns_heading( $defaults ) {
		if( $_GET['post_type'] === $this->post_type ) {
			$defaults['slideshow_shortcode'] = 'Shortcode';
			unset($defaults['date']);
		    $defaults['date'] = 'Date';
		}
	    return $defaults;
	}
	public function slideshows_columns_data( $column_name, $post_ID ) {
		if( $_GET['post_type'] === $this->post_type ) {
		    if ($column_name == 'slideshow_shortcode') {
		        $shortcode = "[WRS slideshow_id=".$post_ID."]";
		        echo $shortcode;
		    }
		}
	}

	/**
	* Slideshow Shortcode Tinymce Editor Button
	*/
	public function slideshow_shortcode_editor_button() {
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		   return;
		if ( get_user_option('rich_editing') == 'true') {
		    add_filter('mce_external_plugins', array( $this, 'add_slideshow_shortcode_tinymce_plugin') );
		    add_filter('mce_buttons', array( $this, 'register_slideshow_shortcode_button') );
		}
	}
	public function register_slideshow_shortcode_button($buttons) {
	   array_push($buttons, "|", "slideshow_shortcode_button");
	   return $buttons;
	}
	public function add_slideshow_shortcode_tinymce_plugin($plugin_array) {
		if ( ! $this->validate_wrs_admin_page() ) {		
	   		$plugin_array['slideshow_shortcode_button'] = WRS_PLUGIN_URL.'/admin_css_js/wrs-admin-tinymce-button.js';
		}
		return $plugin_array;
	}
	public function slideshow_shortcode_button_style() {
		if ( ! $this->validate_wrs_admin_page() ) {
		?>
		<style>
		#slideshow-shortcodes-table thead th.shortcode_head { width:70%; padding:20px; }
	    #slideshow-shortcodes-table thead th.shortcode_action_head { width:30%; padding:20px; }
    	.readonly_slideshow_shortcode { text-align: center; }
    	.done_edit_slideshow_post{
    			background: none repeat scroll 0 0 oldlace!important;
			    color: green!important;
			    float: none;
			    font-weight: bold;
			    display: block!important;
			    margin: 0 auto!important;
			    text-align: center;
			    width: 100px!important;
    	}
    	#wrs_popup_overlay {
    		background: none repeat scroll 0 0 #000000;
		    height: 100%;
		    left: 0;
		    opacity: 0.5;
		    position: fixed;
		    right: 0;
		    top: 0;
		    width: 100%;
		    display: none;
		    z-index:9999;
    	}
    	#edit_slideshow_post_popup {
		    background: none repeat scroll 0 0 seashell;
		    bottom: 50px;
		    left: 50px;
		    right: 50px;
		    top: 50px;
		    margin: 0 auto;
		    padding: 10px;
		    position: fixed;
		    z-index: 99999;
		    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    -webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    -moz-box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    display:none;
		    overflow:auto;
		}
		.slideshow_shortcodes_popup {
			background: #fff;
		    bottom: 50px;
		    left: 150px;
		    right: 150px;
		    top: 50px;
		    margin: 0 auto;
		    padding: 30px;
		    position: fixed;
		    z-index: 99999;
		    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    -webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    -moz-box-shadow: 0 5px 15px rgba(0, 0, 0, 0.698);
		    display:none;
		    overflow:hidden;
		}
		.wrs_shortcodes_table { overflow: auto; height:390px; }
		.edit_slideshow_post_popup_loader:before { content:"\f013" }
    	.edit_slideshow_post_popup_loader {
    		z-index: 99999;
    		position: fixed;
    		left:50%;
    		top:40%;
    		display:none;font-family:FontAwesome;
    		font-style:normal;font-weight:normal;line-height:1;
    		font-size:70px;
    		color:#fff;
    		-webkit-font-smoothing:antialiased;
    		-moz-osx-font-smoothing:grayscale;
    		-webkit-animation:spin 2s infinite linear;-moz-animation:spin 2s infinite linear;-o-animation:spin 2s infinite linear;animation:spin 2s infinite linear}
    		@-moz-keyframes spin{0%{-moz-transform:rotate(0deg)}100%{-moz-transform:rotate(359deg)}}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0deg)}100%{-webkit-transform:rotate(359deg)}}@-o-keyframes spin{0%{-o-transform:rotate(0deg)}100%{-o-transform:rotate(359deg)}}@keyframes spin{0%{-webkit-transform:rotate(0deg);transform:rotate(0deg)}100%{-webkit-transform:rotate(359deg);transform:rotate(359deg);}}
    	}
    	</style>
    	<script type='text/javascript'>
	    /* <![CDATA[ */
	    var wrs_script = {"ajaxurl":"<?php echo admin_url('admin-ajax.php') ?>", "WRS_PLUGIN_URL":"<?php echo WRS_PLUGIN_URL; ?>"};
	    /* ]]> */
	    </script>
	    <div id="wrs_popup_overlay"></div>
	    <div class='edit_slideshow_post_popup_loader'></div>
	    <div id="edit_slideshow_post_popup">
	    	<div class="done_edit_slideshow_post button">DONE</div>
	    	<div class="edit_slideshow_inner"></div>
	    </div>
		<?php
		}
	}
	public function fetch_slideshow_shortcodes_ajax() {
	    $result = "";
	    global $wpdb;
	    $posts_array = get_posts( array('post_type' => $this->post_type) );
	    $shortcodes = "";
	    foreach ($posts_array as $data) {
	       $shortcodes .= '<tr>';
	       $shortcodes .= '<td>[WRS slideshow_id=' . $data->ID . ']</td>';
	       $shortcodes .= '<td>';
	       $shortcodes .= '<div class="edit_slideshow_shortcode button" style="margin-bottom:10px; width:100%; text-align:center;" edit_url="'.get_edit_post_link( $data->ID ).'">Edit Shortcode</div>';
	       $shortcodes .= '<div class="insert_custom_shortcode button" style="width:100%; text-align:center;">Insert Shortcode</div>';
	       $shortcodes .= '</td>';
	       $shortcodes .= '</tr>';
	    }
	    echo json_encode($shortcodes);  
	    die($result);
	}

	/**
	* Custom WRS Admin Update Messages
	*/
	public function custom_update_messages( $messages ) {

		global $post;

		$messages[$this->post_type] = array(
			0 => '',
			1 =>  __( 'Slideshow updated.' ),
			2 => __( 'Custom field updated.' ),
			3 => __( 'Custom field deleted.' ),
			4 => __( 'Slideshow updated.' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Slideshow restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Slideshow created.' ),
			7 => __( 'Slideshow saved.' ),
			8 => '',
			9 => sprintf( __( 'Slideshow scheduled for: <strong>%1$s</strong>.' ),
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Slideshow draft updated.' )
		);

		return $messages;

	}

	/**
	* Add Meta Boxes for WRS Admin Page
	*/
	public function add_meta_boxes() {	
		global $post;
		$post_status = get_post_status( $post->ID );

		//Custom publish or save box
		add_meta_box(
			'wrs_custom_publish_meta_box',
			__( 'Save', WRS_PLUGIN_PREFIX ),
			array( $this, 'custom_publish_meta_box' ),
			$this->post_type,
			'side'
		);

		//Shortcode box
		if( $post_status == "publish" ) {
			add_meta_box(
				'wrs_shortcode_meta_box',
				__( 'Slideshow Shortcode', WRS_PLUGIN_PREFIX ),
				array( $this, 'shortcode_meta_box' ),
				$this->post_type,
				'normal'
			);
		}

		//Manage WRS Slideshow Media box
		add_meta_box(
			'wrs_manage_media_meta_box',
			__( 'Manage Slideshow Media', WRS_PLUGIN_PREFIX ),
			array( $this, 'manage_media_meta_box' ),
			$this->post_type,
			'normal'
		);

		//Edit WRS Slideshow Media box
		add_meta_box(
			'wrs_edit_media_meta_box',
			__( 'Edit Slideshow Media', WRS_PLUGIN_PREFIX ),
			array( $this, 'edit_media_meta_box' ),
			$this->post_type,
			'normal'
		);

		//Manage WRS Slideshow Settings box
		add_meta_box(
			'wrs_manage_settings_meta_box',
			__( 'Manage Slideshow Settings', WRS_PLUGIN_PREFIX ),
			array( $this, 'manage_settings_meta_box' ),
			$this->post_type,
			'normal'
		);

	}

	/**
	* Remove WP Default Submit Meta Box
	*/
	public function remove_meta_boxes() {
		remove_meta_box( 'submitdiv', $this->post_type, 'side' );
	}

	/**
	* Custom WRS Submit/Save Meta Box	
	*/
	public function custom_publish_meta_box($post) {

		$slideshow_id = $post->ID;
		$post_status = get_post_status( $slideshow_id );
		$delete_link = get_delete_post_link( $slideshow_id );
		$nonce = wp_create_nonce( 'wrs_nonce' );
	?>	
		<div class="submitbox" id="submitpopup">
		<div id="misc-publishing-actions">
		<div class="misc-pub-section"> 
		</div>
		</div>

		<div id="major-publishing-actions">
			
			<?php if ( $post_status == 'publish'  ): ?>
				<div id="delete-action">
					<a class="submitdelete deletion" href="<?php echo $delete_link ?>">
						<?php echo __( 'Move to Trash', 'isell' ); ?>
					</a>
				</div>
			<?php endif; ?>
			
			<div id="publishing-action">
				<?php 
					if ( $post_status != 'publish'  ):
						echo "<span class='fa fa-cog fa-spin' id='save_slideshow_loader'></span>";
						submit_button( __( 'Create Slideshow', WRS_PLUGIN_PREFIX ), 'primary', 'publish', false );
					else:
						echo "<span class='fa fa-cog fa-spin' id='save_slideshow_loader'></span>";
						submit_button( __( 'Update Slideshow', WRS_PLUGIN_PREFIX ), 'primary', 'submit', false );
					endif;
				?>
			</div>
			
		</div>

		<div class="clear"></div>

		</div>
		<input type="hidden" name="post_type_is_wrs_slideshow" value="yes" />
		<input type="hidden" name="wrs_nonce" value="<?php echo $nonce ?>" />
	<?php	
	}	

	/**
	* Slideshow Shortcode Meta Box
	*/
	public function shortcode_meta_box() {
		global $post;
		$shortcode = "[WRS slideshow_id=".$post->ID."]";
		?>
		<input type="text" value="<?php echo $shortcode; ?>" class="shortcode_input" readonly />
		<?php
	}
	
	/**
	* Manage Slideshow Images Meta Box
	*/
	public function manage_media_meta_box( $post ) {
		$slideshow_media = get_post_meta($post->ID, 'slideshow_media', true);
		$slideshow_media = unserialize($slideshow_media);
		$slideshow_media_caption = get_post_meta($post->ID, 'slideshow_media_caption', true);
		$slideshow_media_caption = unserialize($slideshow_media_caption);
		$slideshow_video_type = get_post_meta($post->ID, 'slideshow_video_type', true);
		$slideshow_video_type = unserialize($slideshow_video_type);
		$slideshow_video_image_id = get_post_meta($post->ID, 'slideshow_video_image_id', true);
		$slideshow_video_image_id = unserialize($slideshow_video_image_id);
	?>

		<!-- <div class="media-modal">
			sagar
		</div> -->
		<div class="slideshows_medias">
			<div class="slideshows_media_inner">
				<?php
					echo $this->fetch_saved_slideshow_media($slideshow_media, $slideshow_media_caption, $slideshow_video_type, $slideshow_video_image_id);
				?>
			</div>
			<div class="clear"></div>	
		</div>	
		
		<button class="button insert_slideshow_video">Add Slideshow Video</button>
		<button class="button insert_slideshow_images">Add Slideshow Image</button>
		<div class="clear"></div>
	<?php		
		echo $this->get_script_templates("template-add-new-media");
		echo $this->get_script_templates("template-play-video-popup");
	}

	public function fetch_saved_slideshow_media($slideshow_media, $slideshow_media_caption, $slideshow_video_type, $slideshow_video_image_id) {
		ob_start();
		if(is_array($slideshow_media)) {
			foreach( $slideshow_media as $media ) {
				$video_type = $slideshow_video_type[$media];
				if( !$video_type ) {
					$image_id = $media;
					$image_attributes = wp_get_attachment_image_src( $image_id, 'full' );	
					$image_url = $image_attributes[0];
					$caption = $slideshow_media_caption[$image_id];
					?>
						<div class="slideshow_media" data-media-id="<?php echo $image_id; ?>" data-media-type="image" id="slideshow_media_<?php echo $image_id; ?>">
							<div class="edit_media dashicons dashicons-edit" title="Edit Image"></div>
							<div class="remove_media" title="Remove Image">X</div>
							<img src="<?php echo $image_url; ?>" />
							<input type="hidden" value="<?php echo $image_id; ?>" name="slideshow_media[]" class="slideshow_media_val" />
							<input type="hidden" value="<?php echo $caption; ?>" name="slideshow_media_caption[<?php echo $image_id; ?>]" class="slideshow_media_caption" />
						</div>
					<?php
				} else {
					$video_img_url = "";
					$video_image_id = $slideshow_video_image_id[$media];
					$download_video_image_button = '<div class="download_video_image fa fa-download" title="Download Video Image on Server"></div>';
					if($video_image_id!="") {
						$media_id = $media;
						$image_attributes = wp_get_attachment_image_src( $video_image_id, 'full' );	
						if($image_attributes=="") {
							$video_img_url = $this->fetch_video_images($video_type, $media_id);
						} else {
							$video_img_url = $image_attributes[0];
							$download_video_image_button = '';
						}
					} else {
						$media_id = $media;
						$video_id = $media_id;
						$video_img_url = $this->fetch_video_images($video_type, $media_id);
					}
					$caption = $slideshow_media_caption[$media_id];
					?>
					<div class="slideshow_media" data-video-type="<?php echo $video_type; ?>" data-media-id="<?php echo $media_id; ?>" data-video-id="<?php echo $media_id; ?>" data-media-type="video" id="slideshow_media_<?php echo $media_id; ?>">
						<i class="fa fa-cog fa-fw fa-spin download_video_image_loader"></i>
						<?php echo $download_video_image_button; ?>
						<div class="play_video dashicons dashicons-admin-collapse" title="Play Video"></div>
						<div class="edit_media dashicons dashicons-edit" title="Edit Media"></div>
						<div class="remove_media" title="Remove Media">X</div>
						<img src="<?php echo $video_img_url; ?>" />
						<input type="hidden" value="<?php echo $media_id; ?>" name="slideshow_media[]" class="slideshow_media_val" />
						<input type="hidden" value="<?php echo $caption; ?>" name="slideshow_media_caption[<?php echo $media_id; ?>]" class="slideshow_media_caption" />
						<input type="hidden" value="<?php echo $video_type; ?>" name="slideshow_video_type[<?php echo $media_id; ?>]" class="slideshow_video_type" />
						<input type="hidden" value="<?php echo $video_image_id; ?>" name="slideshow_video_image_id[<?php echo $media_id; ?>]" class="slideshow_video_image_id" />
					</div>
					<?php
				}	
			}
		}
		$html = ob_get_clean();
		return $html;
		ob_end_clean();
	}

	/**
	* Get Video Images from Youtube and Vimeo Servers
	*/
	public function fetch_video_images($video_type, $media_id) {
		if($video_type=="youtube") {
			$video_img_url = "http://img.youtube.com/vi/" . $media_id . "/hqdefault.jpg";
		} else {
			$hash = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/$media_id.php"));
			$video_img_url = $hash[0]['thumbnail_large'];
		}
		return $video_img_url;
	}

	/**
	* Edit Slideshow Images Meta Box
	*/
	public function edit_media_meta_box() {

		echo $this->get_script_templates("template-edit-media");
	}

	/**
	* Manage Slideshow Settings Meta Box 
	*/
	public function manage_settings_meta_box() {
		global $post;
		$slideshow_settings = unserialize(get_post_meta($post->ID, 'slideshow_settings', true));
		$autoplay = $slideshow_settings['autoplay'];
		$autoplaytimeout = $slideshow_settings['autoplaytimeout'];
		$loop = $slideshow_settings['loop'];
		$navigation = $slideshow_settings['navigation'];
		$dotsnavigation = $slideshow_settings['dotsnavigation'];
		$navigationtext = $slideshow_settings['navigationtext'] ? $slideshow_settings['navigationtext'] : 'PREV | NEXT';
		
		$autoplayoptions = array("true"=>"Yes", "false"=>"No");
		$loopoptions = array("true"=>"Yes", "false"=>"No");
		$navigationoptions = array("true"=>"Yes", "false"=>"No");
		$dotsnavigationoptions = array("true"=>"Yes", "false"=>"No");
		
		?>
		<div class="slideshow_settings">
			<div class="field-row">
				<label for="autoplay">Autoplay</label>
				<select name="slideshow_settings[autoplay]" id="autoplay">
					<?php 
					foreach($autoplayoptions as $key => $option) { 
					$selected = "";
					if($key == $autoplay) { $selected = "selected=selected"; }
					?>
						<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $option ?></option>
					<?php 
					} 
					?>
				</select>	
			</div>

			<div class="field-row">
				<label for="autoplaytimeout">Autoplay Timeout</label>
				<input type="text" name="slideshow_settings[autoplaytimeout]" id="autoplaytimeout" value="<?php echo $autoplaytimeout; ?>" placeholder="Default : 8000" />
			</div>

			<div class="field-row">
				<label for="loop">Loop</label>
				<select name="slideshow_settings[loop]" id="loop">
					<?php 
					foreach($loopoptions as $key => $option) { 
					$selected = "";
					if($key == $loop) { $selected = "selected=selected"; }
					?>
						<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $option ?></option>
					<?php 
					} 
					?>
				</select>	
			</div>

			<div class="field-row">
				<label for="navigation">Navigation</label>
				<select name="slideshow_settings[navigation]" id="navigation">
					<?php 
					foreach($navigationoptions as $key => $option) { 
					$selected = "";
					if($key == $navigation) { $selected = "selected=selected"; }
					?>
						<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $option ?></option>
					<?php 
					} 
					?>
				</select>	
			</div>

			<div class="field-row">
				<label for="navigationtext">Navigation Text</label>
				<input type="text" name="slideshow_settings[navigationtext]" id="navigationtext" value="<?php echo $navigationtext; ?>" placeholder="Default: PREV | NEXT" />
			</div>

			<div class="field-row">
				<label for="dotsnavigation">Dots Navigation</label>
				<select name="slideshow_settings[dotsnavigation]" id="dotsnavigation">
					<?php 
					foreach($dotsnavigationoptions as $key => $option) { 
					$selected = "";
					if($key == $dotsnavigation) { $selected = "selected=selected"; }
					?>
						<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $option ?></option>
					<?php 
					} 
					?>
				</select>	
			</div>		

		</div>
		<?php	
	}

	/**
	* Get Script Templates
 	*/
 	public function get_script_templates($template) {
 		return file_get_contents(WRS_PLUGIN_URL.'/includes/templates/'.$template.'.html');
 	}	


 	/**
 	* Save Slideshow Post
 	*/
 	public function save_post( $post_id ) {

		if ( ! $this->validate_wrs_admin_page() )
			return FALSE;

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return FALSE;

		if ( wp_is_post_revision( $post_id ) )
			return FALSE;
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if ( ! isset( $_POST['wrs_nonce'] ) )
			return FALSE;

		if ( ! wp_verify_nonce( $_POST['wrs_nonce'], 'wrs_nonce' ) )
			return FALSE;

		$slideshow_id = $post_id;

		$slideshow_media = $_POST['slideshow_media'];
		$slideshow_media_caption = $_POST['slideshow_media_caption'];
		$slideshow_video_type = $_POST['slideshow_video_type'];
		$slideshow_video_image_id = $_POST['slideshow_video_image_id'];
		$slideshow_settings = $_POST['slideshow_settings'];

		if(isset($slideshow_media)) {
			update_post_meta( $slideshow_id, 'slideshow_media', serialize($slideshow_media) );
			update_post_meta( $slideshow_id, 'slideshow_media_caption', serialize($slideshow_media_caption) );
			update_post_meta( $slideshow_id, 'slideshow_video_type', serialize($slideshow_video_type) );
			update_post_meta( $slideshow_id, 'slideshow_video_image_id', serialize($slideshow_video_image_id) );
			update_post_meta( $slideshow_id, 'slideshow_settings', serialize($slideshow_settings) );
		}				

		do_action( 'wrs_save_slideshow_data', $slideshow_id );

	}

	/**
 	* Update Slideshow Post by AJAX
 	*/
 	public function update_post_ajax() {

 			
 		parse_str($_POST[postData], $postData);

 		$post_id = $postData['post_ID'];

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return FALSE;

		if ( wp_is_post_revision( $post_id ) )
			return FALSE;
		
		if ( ! isset( $postData['wrs_nonce'] ) )
			return FALSE;

		if ( ! wp_verify_nonce( $postData['wrs_nonce'], 'wrs_nonce' ) )
			return FALSE;


		$slideshow_id = $post_id;

		wp_update_post( array( 'ID' => $slideshow_id, 'post_title' => $postData['post_title'] ) );

		$slideshow_media = $postData['slideshow_media'];
		$slideshow_media_caption = $postData['slideshow_media_caption'];
		$slideshow_video_type = $postData['slideshow_video_type'];
		$slideshow_video_image_id = $postData['slideshow_video_image_id'];
		$slideshow_settings = $postData['slideshow_settings'];

		if(isset($slideshow_media)) {
			update_post_meta( $slideshow_id, 'slideshow_media', serialize($slideshow_media) );
			update_post_meta( $slideshow_id, 'slideshow_media_caption', serialize($slideshow_media_caption) );
			update_post_meta( $slideshow_id, 'slideshow_video_type', serialize($slideshow_video_type) );
			update_post_meta( $slideshow_id, 'slideshow_video_image_id', serialize($slideshow_video_image_id) );
			update_post_meta( $slideshow_id, 'slideshow_settings', serialize($slideshow_settings) );
		}				

		echo json_encode(array("response"=>"success"));

		die(0);

	}

	/**
	* Store External Server Images 
	*/
	public function store_external_server_images($image_url, $image_name) {
		$id = "";
		
		//Build Image File Name By Image Title
		if($image_name!="") {
			$image_name = $image_name;
			$image_name = strtolower($image_name);
			$image_name = explode(' - ', $image_name);
			$image_name = str_replace(" ", "-", $image_name[0]);
			$ext = pathinfo($image_url, PATHINFO_EXTENSION);		
			$image_file_name = $image_name.".".$ext;
		} else {
			$image_name = @basename($image_url);
			$image_file_name = $image_name;
		}	
		

		$tmp = download_url( $image_url );
	    $file_array = array(
	        'name' => $image_file_name,
	        'tmp_name' => $tmp
	    );

	    // Check for download errors
	    if ( is_wp_error( $tmp ) ) {
	        @unlink( $file_array[ 'tmp_name' ] );
	        return $tmp;
	    }

	    $id = media_handle_sideload( $file_array, 0 );
	    // Check for handle sideload errors.
	    if ( is_wp_error( $id ) ) {
	        @unlink( $file_array['tmp_name'] );
	        return $id;
	    }
	    return $id;
	}

	/**
	* Store Video Thumbnail Images by AJAX
	*/
	public function store_video_thumbnail_images_ajax() {
		$result = "";
		$image_url = $_POST['image_url'];
		$video_id = $_POST['video_id'];
		$image_name = $_POST['image_name'];
		$id = $this->store_external_server_images($image_url, $image_name);
		$image = wp_get_attachment_url( $id );
	   	echo json_encode( array("image_url"=>$image, "id"=>$id ) );
		die($result);
	}

	/**
	* Store Video Thumbnail Images by AJAX
	*/
	public function insert_image_by_url_ajax() {
		$image_url = $_POST['image_url'];
		$id = $this->store_external_server_images($image_url, '');
		$image = wp_get_attachment_image_src( $id, 'medium' ); 
		echo json_encode( array("image_url"=>$image[0], "id"=>$id ) );
		die(0);
	}


}

endif;
