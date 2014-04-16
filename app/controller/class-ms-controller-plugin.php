<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

/**
 * Primary controller for Membership Plugin.
 *
 * Responsible for flow control, navigation and invoking other controllers.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Controller_Plugin extends MS_Controller {

	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var model
	 */
	private $model;
	
	/**
	 * Instance of MS_View_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var view
	 */
	private $view;	

	/**
	 * Pointer array for other controllers.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var controllers
	 */	
	private $controllers = array();
	
	/**
	 * Pointer array for all Admin pages.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var admin_pages
	 */
	private $admin_pages = array();	
		
	/** 
	 * Constructs the primary Plugin controller.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		/** Instantiate Plugin model */
		$this->model = apply_filters( 'membership_plugin_model', new MS_Model_Plugin() );
		/** Instantiate Plugin view */
		$this->view = apply_filters( 'membership_plugin_view', new MS_View_Plugin( array( 'test'=>'two' )) );

		/** Setup plugin admin UI */
		$this->add_action( 'admin_menu', 'add_menu_pages' );
		
		/** Enque admin styles (CSS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_styles' );
		
		/** Enque admin scripts (JS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_scripts' );
		
		//FJ: we need to define if we will use class name MS_Controller_Membership and membership_controller_membership filter or
		//MS_Membership_Controller and membership_membership_controller
		/** Membership controller */
		$this->controllers['membership'] = apply_filters( 'membership_membership_controller', new MS_Controller_Membership() );		

		/** Rule controller */
		$this->controllers['rule'] = apply_filters( 'membership_rule_controller', new MS_Controller_Rule() );		
	}

	
	/**
	 * Adds Dashboard navigation menus.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */
	public function add_menu_pages() {
		$pages = array();
		/** 
		 * Primary navigation page.
		 *
		 * Dashboard page to be configured in instance of MS_Controller_Membership
		 *
		 * @uses MS_Controller_Membership
		 */
		$pages[] = add_menu_page( __( 'Membership', MS_TEXT_DOMAIN ), __( 'Membership', MS_TEXT_DOMAIN ), 'membershipadmindashboard', 'membership', array( $this->controllers['membership'], 'membership_dashboard' ) );
		
		//RK: Perhaps as addon? Core will only have 1 or 2 memberships.
		/** Lists all memberships. */
		$pages[] = add_submenu_page( 'membership', __( 'All Memberships', MS_TEXT_DOMAIN ), __( 'All Memberships', MS_TEXT_DOMAIN ), 'manage_options', 'all-memberships', array( $this->controllers['membership'], 'admin_membership_list' ) );
		
		/** Manage membership */
		$pages[] = add_submenu_page( 'all-memberships', __( 'New Membership', MS_TEXT_DOMAIN ), __( 'New Membership', MS_TEXT_DOMAIN ), 'manage_options', 'membership-edit', array( $this->controllers['membership'], 'membership_edit' ) );

		/** Manage membership rules */
		$pages[] = add_submenu_page( 'all-memberships', __( 'Edit Membership Rules', MS_TEXT_DOMAIN ), __( 'Edit Membership Rules', MS_TEXT_DOMAIN ), 'manage_options', 'membership-edit-rules', array( $this->controllers['rule'], 'membership_edit_rules' ) );
		
		/** Global Membership Plugin settings. */
		$pages[] = add_submenu_page( 'membership', __( 'Members', MS_TEXT_DOMAIN ), __( 'Membership Settings', MS_TEXT_DOMAIN ), 'manage_options', 'membership-settings', array( $this->view, 'render' ) );
		
	}

	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_styles() {
		wp_register_style( 'membership_admin_css', MS_Plugin::instance()->url. 'app/assets/css/settings.css' );
		wp_enqueue_style( 'membership_admin_css' );
	}
	
	/**
	 * Adds JavasSript for Membership settings pages.
	 *
	 * @todo Perhaps remove this method if we don't have global JS to apply to plugin
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_scripts() {
		// wp_register_script( 'membership_admin_js', MS_Plugin::instance()->url . 'app/assets/js/settings.js' );
		// wp_enqueue_script( 'membership_admin_js' );
	}
	
	
}