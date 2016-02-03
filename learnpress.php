<?php
/*
Plugin Name: LearnPress
Plugin URI: http://thimpress.com/learnpress
Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
Author: ThimPress
Version: 0.9.19
Author URI: http://thimpress.com
Requires at least: 3.5
Tested up to: 4.3

Text Domain: learn_press
Domain Path: /lang/
*/

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !defined( 'LP_PLUGIN_PATH' ) ) {
	//define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );

	$upload_dir = wp_upload_dir();
	define( 'LP_PLUGIN_FILE', __FILE__ );
	define( 'LP_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'LP_LOG_PATH', $upload_dir['basedir'] . '/learn-press-logs/' );
	define( 'LEARNPRESS_VERSION', '1.0' );
	define( 'LEARNPRESS_DB_VERSION', '1.0' );
	//add_action( 'plugins_loaded', 'learn_press_defines', - 100 );
}

if ( !class_exists( 'LearnPress' ) ) {
	/**
	 * Class LearnPress
	 *
	 * Version 1.0
	 */
	class LearnPress {

		/**
		 * Current version of the plugin
		 *
		 * @var string
		 */
		public $version = LEARNPRESS_VERSION;

		/**
		 * Current version of database
		 *
		 * @var string
		 */
		public $db_version = LEARNPRESS_DB_VERSION;

		/**
		 * The single instance of the class
		 *
		 * @var LearnPress object
		 */
		private static $_instance = null;

		/**
		 * Store the file that define LearnPress
		 *
		 * @var null|string
		 */
		public $plugin_file = null;

		/**
		 * Store the url of the plugin
		 *
		 * @var string
		 */
		public $plugin_url = null;

		/**
		 * Store the path of the plugin
		 *
		 * @var string
		 */
		public $plugin_path = null;

		/**
		 * Store the session class
		 *
		 * @var array
		 */
		public $session = null;

		/**
		 * Course Post Type
		 *
		 * @var string
		 */
		public $course_post_type = 'lp_course';

		/**
		 * Lesson Post Type
		 *
		 * @var string
		 */
		public $lesson_post_type = 'lp_lesson';

		/**
		 * Quiz Post Type
		 *
		 * @var string
		 */
		public $quiz_post_type = 'lp_quiz';

		/**
		 * Question Post Type
		 *
		 * @var string
		 */
		public $question_post_type = 'lp_question';

		/**
		 * Order Post Type
		 *
		 * @var string
		 */
		public $order_post_type = 'lp_order';

		/**
		 * Teacher Role
		 *
		 * @var string
		 */
		public $teacher_role = 'lp_teacher';

		/**
		 * @var LP_Cart object
		 */
		public $cart = false;

		public $query_vars = array();

		/**
		 * LearnPress constructor
		 */
		public function __construct() {
			//echo "[LearnPress loaded]";
			$this->_setup_post_types();
			// defines const
			$this->define_const();

			$this->define_tables();
			// Define the url and path of plugin
			$this->plugin_file = LP_PLUGIN_FILE;
			//$this->plugin_url  = LP_PLUGIN_URL;
			$this->plugin_path = LP_PLUGIN_PATH;

			// includes
			$this->includes();

			// hooks
			$this->init_hooks();

			// let third parties know that we're ready
			//do_action( 'learn_press_loaded' );
			do_action( 'learn_press_ready' );
			//do_action( 'learn_press_register_add_ons' );
		}

		function __get( $key ) {
			if ( empty( $this->{$key} ) ) {
				switch ( $key ) {
					case 'email':
						$this->{$key} = LP_Email::instance();
						break;
					case 'checkout':
						$this->{$key} = LP_Checkout::instance();
						break;
					case 'course':
						if ( is_course() ) {
							$this->{$key} = LP_Course::get_course( get_the_ID() );
						}
						break;
					case 'quiz':
						if ( is_quiz() ) {
							$this->{$key} = LP_Quiz::get_quiz( get_the_ID() );
						}
						break;
				}
			}
			return !empty( $this->{$key} ) ? $this->{$key} : false;
		}

		/**
		 * Rollback to old custom post type if current db version is outdated
		 * And,
		 */
		private function _setup_post_types() {
			/**
			 * If db version is not set
			 */

			if ( !get_option( 'learnpress_db_version' ) ) {

				$this->_remove_notices();
				$this->course_post_type   = 'lpr_course';
				$this->lesson_post_type   = 'lpr_lesson';
				$this->quiz_post_type     = 'lpr_quiz';
				$this->question_post_type = 'lpr_question';
				$this->order_post_type    = 'lpr_order';
				$this->teacher_role       = 'lpr_teacher';
			}
		}

		/**
		 * Remove all notices from old version
		 */
		private function _remove_notices() {
			remove_action( 'network_admin_notices', 'learn_press_edit_permalink' );
			remove_action( 'admin_notices', 'learn_press_edit_permalink' );
		}

		/**
		 * Main plugin Instance
		 *
		 * @static
		 * @return object Main instance
		 *
		 * @since  1.0
		 * @author
		 */
		public static function instance() {

			if ( !self::$_instance ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define constant if not already set
		 *
		 * @param  string      $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( !defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Define constants used by this plugin
		 *
		 */
		function define_const() {

			//$this->define( 'LEARNPRESS_VERSION', $this->version );
			//$this->define( 'LEARNPRESS_DB_VERSION', $this->db_version );

			$this->define( 'LP_PLUGIN_FILE', __FILE__ );
			//$this->define( 'LP_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
			//$this->define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );



			// Custom post type name
			$this->define( 'LP_COURSE_CPT', $this->course_post_type );
			$this->define( 'LP_LESSON_CPT', $this->lesson_post_type );
			$this->define( 'LP_QUESTION_CPT', $this->question_post_type );
			$this->define( 'LP_QUIZ_CPT', $this->quiz_post_type );
			$this->define( 'LP_ORDER_CPT', $this->order_post_type );
		}

		function define_tables() {
			global $wpdb;
			$tables = array(
				'learnpress_sections',
				'learnpress_section_items',
				'learnpress_user_courses',
				'learnpress_order_itemmeta',
				'learnpress_order_items',
				'learnpress_quiz_questions',
				'learnpress_question_answers',
				'learnpress_user_quizzes',
				'learnpress_user_quizmeta',
				'learnpress_review_logs'
			);
			foreach ( $tables as $table_name ) {
				$wpdb->{$table_name} = $wpdb->prefix . $table_name;
			}
		}

		/**
		 * Include custom post types
		 */
		function include_post_types() {
			// Register custom-post-type and taxonomies
			require_once 'inc/custom-post-types/course.php';
			require_once 'inc/custom-post-types/quiz.php';
			require_once 'inc/custom-post-types/question.php';
			require_once 'inc/custom-post-types/lesson.php';
			require_once 'inc/custom-post-types/order.php';
		}

		/**
		 * Initial common hooks
		 */
		function init_hooks() {

			$plugin_file = WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );
			register_activation_hook( __FILE__, array( 'LP_Install', 'install' ) );

			//LP_Install::install();

			add_action( 'plugins_loaded', array( $this, '_define_plugin_url' ), -100);
			// initial some tasks before page load
			add_action( 'init', array( $this, 'init' ), 15 );

			add_action( 'template_redirect', 'learn_press_handle_purchase_request' );

			add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );

		}

		function _define_plugin_url(){
			if(!defined( 'LP_PLUGIN_URL' ) ) {
				$this->define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
				$this->define( 'LP_JS_URL', LP_PLUGIN_URL . 'assets/js/' );
				$this->define( 'LP_CSS_URL', LP_PLUGIN_URL . 'assets/css/' );
			}
			$this->plugin_url = LP_PLUGIN_URL;
		}

		/**
		 * Init LearnPress when WP initialises
		 */
		function init() {

			if ( $this->is_request( 'frontend' ) ) {
				$this->cart = LP_Cart::instance();
			}

			$this->get_session();
			$this->get_user();
			$this->gateways = LP_Gateways::instance()->get_available_payment_gateways();
			//$this->question_factory = LP_Question_Factory::instance();

			LP_Emails::init_email_notifications();

			if ( get_transient( 'learn_press_install' ) == 'yes' ) {
				flush_rewrite_rules();
				delete_transient( 'learn_press_install' );
			}
		}

		function get_session() {
			if ( !$this->session ) {
				$this->session = LP_Session::instance();
			}
			return $this->session;
		}

		function get_user() {
			if ( !$this->user ) {
				$this->user = learn_press_get_current_user();
			}
			return $this->user;
		}

		/**
		 * Check type of request
		 *
		 * @param string $type ajax, frontend or admin
		 *
		 * @return bool
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( !is_admin() || defined( 'DOING_AJAX' ) ) && !defined( 'DOING_CRON' );
			}
		}

		/**
		 * Get the template folder in the theme.
		 *
		 * @access public
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'learn_press_template_path', 'learnpress/' );
		}

		/**
		 * Includes needed files
		 */
		function includes() {

			require_once 'inc/lp-deprecated.php';
			// include core functions
			require_once 'inc/lp-core-functions.php';
			require_once 'inc/lp-add-on-functions.php';
			// auto include file for class if class doesn't exists
			require_once 'inc/class-lp-autoloader.php';
			require_once 'inc/class-lp-install.php';
			require_once 'inc/lp-webhooks.php';
			require_once 'inc/class-lp-request-handler.php';
			if ( is_admin() ) {

				require_once 'inc/admin/class-lp-admin-notice.php';
				if ( !class_exists( 'RWMB_Meta_Box' ) ) {
					require_once 'inc/libraries/meta-box/meta-box.php';
				}

				require_once 'inc/admin/class-lp-admin.php';
				//require_once 'inc/admin/class-lp-admin-settings.php';

				require_once( 'inc/admin/settings/class-lp-settings-base.php' );
				require_once( 'inc/admin/class-lp-admin-assets.php' );


			} else {

			}
			$this->settings = LP_Settings::instance();

			require_once 'inc/class-lp-assets.php';
			require_once 'inc/question/abstract-lp-question.php';
			require_once 'inc/question/class-lp-question-factory.php';

			$this->include_post_types();

			// course
			require_once 'inc/course/lp-course-functions.php';
			require_once 'inc/course/abstract-lp-course.php';
			require_once 'inc/course/class-lp-course.php';
			// quiz
			require_once 'inc/quiz/lp-quiz-functions.php';
			require_once 'inc/quiz/class-lp-quiz.php';


			// question
			//require_once 'inc/question/lp-question.php';

			// order
			require_once 'inc/order/lp-order-functions.php';
			require_once 'inc/order/class-lp-order.php';

			// user API
			require_once 'inc/user/lp-user-functions.php';
			require_once 'inc/user/abstract-lp-user.php';
			require_once 'inc/user/class-lp-user.php';

			// others
			require_once 'inc/class-lp-session.php';
			require_once 'inc/admin/class-lp-profile.php';
			require_once 'inc/admin/class-lp-email.php';
			// assets


			if ( is_admin() ) {

				//Include pointers
				require_once 'inc/admin/pointers/pointers.php';
			} else {

				// shortcodes
				require_once 'inc/class-lp-shortcodes.php';
				// Include short-code file
				require_once 'inc/shortcodes/profile-page.php';
				require_once 'inc/shortcodes/archive-courses.php';
			}

			// include template functions
			require_once( 'inc/lp-template-functions.php' );
			require_once( 'inc/lp-template-hooks.php' );
			// settings
			//require_once 'inc/class-lp-settings.php';
			// simple cart
			require_once 'inc/cart/class-lp-cart.php';
			// payment gateways
			require_once 'inc/gateways/class-lp-gateway-abstract.php';
			require_once 'inc/gateways/class-lp-gateways.php';

			//add ajax-action
			require_once 'inc/admin/class-lp-admin-ajax.php';
			require_once 'inc/class-lp-ajax.php';
			require_once 'inc/class-lp-multi-language.php';

			if ( !empty( $_REQUEST['debug'] ) ) {
				require_once( 'inc/debug.php' );
			}


		}

		/**
		 * Get the plugin url.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_url( $sub_dir = '' ) {
			return $this->plugin_url . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Get the plugin path.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_path( $sub_dir = '' ) {
			return $this->plugin_path . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Include a file from plugin path
		 *
		 * @param           $file
		 * @param string    $folder
		 * @param bool|true $include_once
		 *
		 * @return bool
		 */
		public function _include( $file, $folder = 'inc', $include_once = true ) {
			if ( file_exists( $include = $this->plugin_path( "{$folder}/{$file}" ) ) ) {
				if ( $include_once ) {
					include_once $include;
				} else {
					include $include;
				}
				return true;
			}
			return false;
		}

		function checkout() {
			return LP_Checkout::instance();
		}

		function setup_theme() {
			if ( !current_theme_supports( 'post-thumbnails' ) ) {
				add_theme_support( 'post-thumbnails' );
			}
			add_post_type_support( 'lp_course', 'thumbnail' );

			$sizes = apply_filters( 'learn_press_image_sizes', array( 'single_course', 'course_thumbnail' ) );

			foreach ( $sizes as $image_size ) {
				$size           = LP()->settings->get( $image_size . '_image_size', array() );
				$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
				$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
				$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

				add_image_size( $image_size, $size['width'], $size['height'], $size['crop'] );
			}
		}
	} // end class
}

/**
 * Main instance of plugin
 *
 * @return LearnPress
 * @since  1.0
 * @author thimpress
 */
function LearnPress() {
	_deprecated_function( __FUNCTION__ . '()', '1.0', 'LP()' );
	return LearnPress::instance();
}

function LP() {
	static $learnpress = false;
	if ( !$learnpress ) {
		$learnpress = LearnPress::instance();
	}
	return $learnpress;
}

/**
 * Load the main instance of plugin after all plugins have been loaded
 *
 * @author      ThimPress
 * @package     LearnPress/Functions
 * @since       1.0
 */
function load_learn_press() {
	$GLOBALS['learn_press'] = array();
	$GLOBALS['LearnPress']  = LP();
}

// Done! entry point of the plugin
load_learn_press();
/************************************/

function test_mail() {
	$user = learn_press_get_user( 1 );

	//do_action( 'learn_press_course_submit_rejected', 1673, $user );
	//do_action( 'learn_press_course_submit_approved', 1673, $user );
	//do_action( 'learn_press_course_submit_for_reviewer', 1673, $user );
	//do_action( 'learn_press_user_enrolled_course', $user, 1673, 3 );
	//do_action( 'learn_press_order_status_pending_to_processing' );
	//do_action( 'learn_press_order_status_pending_to_completed' );
	//do_action( 'learn_press_order_status_processing_to_completed' );*/
	//do_action( 'learn_press_course_submitted', 920, $user );
	//do_action( 'learn_press_course_approved', 920, $user );
}

add_action( 'admin_footer', 'test_mail' );

function learn_press_addon_notice( $notice ) {
	$notices                             = !empty( $GLOBALS['learn_press_addon_notice'] ) ? (array) $GLOBALS['learn_press_addon_notice'] : array();
	$notices[]                           = $notice;
	$GLOBALS['learn_press_addon_notice'] = $notices;
}

function learn_press_print_addon_notice() {
	$notices = !empty( $GLOBALS['learn_press_addon_notice'] ) ? (array) $GLOBALS['learn_press_addon_notice'] : array();
	if ( $notices ) foreach ( $notices as $notice ) {
		printf( '<div class="error"><p>%s</p></div>', $notice );
	}
}

add_action( 'admin_notices', 'learn_press_print_addon_notice' );

