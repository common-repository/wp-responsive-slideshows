<?php
/**
 * Plugin Name: WP Responsive Slideshows
 * Plugin URI: 
 * Description: WP Responsive Slideshows Plugin for Adding Multiple Resposive Slideshow into WP Sites.
 * Version: 1.0.0
 * Author: Sagar Pansare
 * Author URI: 
 * License: A "Slug" license name e.g. GPL2
 */

/*  Copyright 2014  Sagar Pansare  (email : sagarprince2012@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'WRS_PLUGIN_VERSION', '1.0.0' );
define( 'WRS_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) );
define( 'WRS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WRS_PLUGIN_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );
define( 'WRS_PLUGIN_PREFIX', 'wrs' );
define( 'WRS_POST_TYPE', WRS_PLUGIN_PREFIX.'_slideshow' );

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WRSAdmin' ) ) require_once( 'includes/class-wrs-admin-end.php' );

if ( ! class_exists( 'WRSFront' ) ) require_once( 'includes/class-wrs-front-end.php' );

if ( ! class_exists( 'WPResponsiveSlideshow' ) ) :

/**
 * Main WPResponsiveSlideshow Class
 *
 * @class WPResponsiveSlideshow
 * @version 1.0.0
 */
final class WPResponsiveSlideshow {

    /**
    * WPResponsiveSlideshow Constructor.
    * @access public
    * @return WPResponsiveSlideshow
    */
    public function __construct() {

        //WRS ADMIN END
        if ( class_exists( 'WRSAdmin' ) ) {
           new WRSAdmin();
        }

        //WRS FRONT END 
        if ( class_exists( 'WRSFront' ) ) {
           new WRSFront();
        }

    }

    

}   

new WPResponsiveSlideshow();

endif;