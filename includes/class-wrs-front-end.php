<?php
/**
 * Front End Functionality
 *
 * @author 		Sagar Pansare
 * @category 	Front End
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WRSFront' ) ) :

	class WRSFront {

		static $load_script_css;
		static $slideshow_data = array();
		private $post_type = WRS_POST_TYPE;
		

		public function __construct() {
			//Shortcode
			add_shortcode( 'WRS', array( $this, 'wrs_shortcode_handler' ) );

			//Load Font Awesome
			add_action( 'wp_footer', array( $this, 'load_font_awesome' ) );

			//Enqueue Scripts and Style
			add_action( 'wp_footer', array( $this, 'enqueue_style_scripts') );


		}

		public function load_font_awesome() {
			if( self::$load_script_css == true ) {
	        	?>  
			        <style type="text/css">
			            @font-face{font-family:'FontAwesome';src:url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.eot?v=4.1.0');src:url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.eot?#iefix&v=4.1.0') format('embedded-opentype'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.woff?v=4.1.0') format('woff'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.ttf?v=4.1.0') format('truetype'),url('<?php echo WRS_PLUGIN_URL ?>/assets/fonts/fontawesome-webfont.svg?v=4.1.0#fontawesomeregular') format('svg');font-weight:normal;font-style:normal}
			        </style>
		    	<?php
		    }
	    }

		public function enqueue_style_scripts() {
			if( self::$load_script_css == true ) {
				wp_enqueue_style('dashicons');
				wp_enqueue_style( 'wrs-front-style',
				WRS_PLUGIN_URL.'/front_css_js/wrs-front-style.css',
				array('dashicons'), WRS_PLUGIN_VERSION );

				wp_enqueue_script('backbone');
				wp_enqueue_script('underscore');

				wp_enqueue_script( 'wrs-front-script',
				WRS_PLUGIN_URL.'/front_css_js/wrs-front-script.js',
				array('jquery'), WRS_PLUGIN_VERSION , true);

				$slider_script = $this->get_script_js("wrs-slider-script");
				
				$script_data = array( 
					'slider_script' => $slider_script,
					'slideshow_data' => self::$slideshow_data,
				);
				wp_localize_script( 'wrs-front-script', 'slideshow_object', $script_data );
			}
		}

		/**
		* WRS Shortcode 
		*/
		public function wrs_shortcode_handler( $atts ) {
			self::$load_script_css = true;

			$parameters = shortcode_atts( array(
			    'slideshow_id' => '',
			), $atts );

			$slideshow_id = $parameters['slideshow_id'];
			self::$slideshow_data[] = array( $slideshow_id, "slideshow" );

			ob_start();
			$slideshow_media = get_post_meta($slideshow_id, 'slideshow_media', true);
			$slideshow_media = unserialize($slideshow_media);
			$slideshow_media_caption = get_post_meta($slideshow_id, 'slideshow_media_caption', true);
			$slideshow_media_caption = unserialize($slideshow_media_caption);
			$slideshow_video_type = get_post_meta($slideshow_id, 'slideshow_video_type', true);
			$slideshow_video_type = unserialize($slideshow_video_type);
			$slideshow_video_image_id = get_post_meta($slideshow_id, 'slideshow_video_image_id', true);
			$slideshow_video_image_id = unserialize($slideshow_video_image_id);
			$slideshow_settings = unserialize(get_post_meta($slideshow_id, 'slideshow_settings', true));
			$autoplaytimeout = $slideshow_settings['autoplaytimeout'];
			$autoplay = $slideshow_settings['autoplay'];
			$loop = $slideshow_settings['loop'];
			$navigation = $slideshow_settings['navigation'];
			$navigationtext = $slideshow_settings['navigationtext'] ? $slideshow_settings['navigationtext'] : 'PREV | NEXT';
			$dotsnavigation = $slideshow_settings['dotsnavigation'];

			$navtextarray = explode("|", $navigationtext);
			$prev = $navtextarray[0];
			$next = $navtextarray[1];
			$thumb_id = 0;
			?>
			<div class="wrs_slideshow owl-carousel" id="wrs_slideshow_<?php echo $slideshow_id; ?>" data-loop="<?php echo $loop; ?>" data-autoplay="<?php echo $autoplay; ?>" data-autoplay-timeout="<?php echo $autoplaytimeout; ?>" data-navigation="<?php echo $navigation; ?>" data-dots-navigation="<?php echo $dotsnavigation; ?>" data-nav-prev-text="<?php echo $prev; ?>" data-nav-next-text="<?php echo $next; ?>">
				<?php 
				foreach( $slideshow_media as $media ) { 
					$video_type = $slideshow_video_type[$media];
					$media_id = $media;
					$caption = $slideshow_media_caption[$media_id];
					$is_video = "item";
					$video_play_button = "";
					$video_data = "";
					if( !$video_type ) {
						$image_attributes = wp_get_attachment_image_src( $media_id, 'full' );	
						$image_url = $image_attributes[0];
					} else {
						$video_image_id = $slideshow_video_image_id[$media_id];
						if($video_image_id!="") {
							//Fetch Saved Video Image from DB
							$image_attributes = wp_get_attachment_image_src( $video_image_id, 'full' );	
							$image_url = $image_attributes[0];
						} else {
							//Fetch Video Image From Youtube or Vimeo	
							if($video_type=="youtube") {
								$image_url = "http://img.youtube.com/vi/" . $media_id . "/hqdefault.jpg";
							} else {
								$hash = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/$media_id.php"));
								$image_url = $hash[0]['thumbnail_large'];
							}
						}
						$is_video = "item video";
						$video_play_button = "<div class='video_play_button'><span class='fa play_icon'></span></div>";
						$video_data = "data-video-id='".$media_id."' data-video-type='".$video_type."' data-slider-id='wrs_slideshow_".$slideshow_id."' data-thumb-id='".$thumb_id."'";
					}

					if($caption) {
						$slidecaption = '<div class="image-caption"><div class="inner-caption">'.$caption.'</div></div>';
					} else {
						$slidecaption = '';
					}
					?>
					<div class="<?php echo $is_video; ?>" <?php echo $video_data; ?>>
						<div class="lazy-loader"><span class="fa fa-cog fa-spin"></span></div>
						<?php 
							echo $video_play_button; 
							echo $slidecaption;
						?>
			
						<img class="owl-lazy" data-src="<?php echo $image_url; ?>" alt="<?php echo $caption; ?>" />
					</div>
					<?php
					$thumb_id++;
				}	
				?>	
			</div>
			<?php
			echo $this->get_script_template("template-play-video-popup");
			return ob_get_clean();
		}

		/**
		* Get Script Templates
	 	*/
	 	public function get_script_template($template) {
	 		return file_get_contents(WRS_PLUGIN_URL.'/includes/templates/'.$template.'.html');
	 	}

	 	/**
	 	* Get Script from JS Files
	 	*/
	 	public function get_script_js($script) {
	 		return file_get_contents(WRS_PLUGIN_URL.'/front_css_js/'.$script.'.js');
	 	}



	}


endif;
