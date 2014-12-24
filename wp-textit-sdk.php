<?php
/*
Plugin Name: Wordpress TextIt SDK
Plugin URI: 
Description: A super simple SDK. Makes using the TextIt API V1 in your custom Wordpress plugins easy
Version: 0.1
Author: Amber Gregory
Author Email: amber.gregory@kopernik.info
License:

  Copyright 2011 Amber Gregory (amber.gregory@kopernik.info)

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

class WordpressTextItSDK {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Wordpress TextIt SDK';
	const slug = 'wp_textit_sdk';
	static $settings;
	static $error;
	static $textit;
	
	/**
	 * Constructor
	 */
	function __construct() {
		add_filter( 'query_vars', array($this, 'add_query_vars'), 0 );
		add_action( 'parse_request', array($this, 'sniff_requests'), 0 );
		add_action( 'init', array( $this, 'add_endpoint'), 0 );
		add_action( 'init', array( &$this, 'init_wp_textit_sdk' ) );
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_wp_textit_sdk() {
	
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		add_action('admin_init', array(__CLASS__, 'adminInit'));
		add_action('admin_menu', array(__CLASS__, 'adminMenu'));
		  
	}
	
	/** Add public query vars
	*	@param array $vars List of current public query vars
	*	@return array $vars 
	*/
	public function add_query_vars($vars){
		$vars[] = 'textit-webhook-receiver';
		return $vars;
	}
	
	/** Add API Endpoint
	*	@return void
	*/
	public function add_endpoint(){
		add_rewrite_rule('^textit-webhook-receiver','index.php?textit-webhook-receiver=1','top');
	}
	
	/**	Sniff Requests
	*	@return die if API request
	*/
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['textit-webhook-receiver'])){
			$this->handle_request();
			exit;
		}
	}
	
	/** Handle Requests
	*	@return void 
	*/
	protected function handle_request() {
		//put some protection here
		do_action( 'textit_webhook_event', $_POST );
	}
	
	/**
	 * Sets up options page and sections.
	 */
	static function adminInit() {
		
		add_filter('plugin_action_links',array(__CLASS__,'showPluginActionLinks'), 10,5);
		
		register_setting('wp_textit_sdk', 'wp_textit_sdk', array(__CLASS__,'formValidate'));
		add_settings_section('wp_textit_sdk-api', __('API Settings', 'wp_textit_sdk'), '__return_false', 'wp_textit_sdk');
		add_settings_field('api-token', __('API Token', 'wp_textit_sdk'), array(__CLASS__, 'askAPIToken'), 'wp_textit_sdk', 'wp_textit_sdk-api');
	}
	
	/**
	 * Creates option page's entry in Settings section of menu.
	 */
	static function adminMenu() {

		self::$settings = add_options_page( 
		                            __('TextIt SDK Settings', 'wp_textit_sdk'), 
		                            __('Wordpress TextIt SDK', 'wp_textit_sdk'), 
		                            'manage_options', 
		                            'wp_textit_sdk', 
		                            array(__CLASS__,'showOptionsPage')
		                        );
        
	}
	
	/**
	 * Adds link to settings page in list of plugins
	 */
	static function showPluginActionLinks($actions, $plugin_file) {		
		static $plugin;

		if (!isset($plugin))
			$plugin = plugin_basename(__FILE__);

		if ($plugin == $plugin_file) {

			$settings = array('settings' => '<a href="options-general.php?page=wp_textit_sdk">' . __('Settings', 'wp_textit_sdk') . '</a>');
			$actions = array_merge($settings, $actions);
			
		}
		
		return $actions;
	}
	
	/**
	 * Generates source of options page.
	 */
	static function showOptionsPage() {		
		if (!current_user_can('manage_options'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
    ?>
		<div class="wrap">
			<h2><?php _e('Wordpress TextIt SDK Settings', 'wp_textit_sdk'); ?></h2>
			
			<form method="post" action="options.php">

									<?php settings_fields('wp_textit_sdk'); ?>
									<?php do_settings_sections('wp_textit_sdk'); ?>
					
				<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></p>
			</form>
			
			<h2><?php _e('How it works', 'wp_textit_sdk'); ?></h2>
			
			<ol>
				<li><?php _e('Create your <a href="https://textit.in">TextIt</a> account', 'wp_textit_sdk'); ?></li>
				<li><?php _e('Go to your TextIt account page and enter {your wordpress url}/textit-webhook-receiver as your WebHook URL', 'wp_textit_sdk'); ?></li>
				<li><?php _e('Paste the generated API token above and save changes', 'wp_textit_sdk'); ?></li>
				<li><?php _e('Go to Settings > Permalinks and save to refresh the Wordpress rewrite rules', 'wp_textit_sdk'); ?></li>
			</ol>
			
			<p><?php echo __('Now you can use the <code>textit_webhook_event</code> hook in your custom plugin or theme to respond to TextIt webhook events, and <code>WordpressTextItSDK::textItDo()</code> to call the TextIt REST API', 'wp-textit-sdk'); ?></p>
			
			<h2><?php _e('TextIt API', 'wp_textit_sdk'); ?></h2>
			<p><?php _e('Full documentation is available: <a href="https://textit.in/api/v1" target="_blank">TextIt API Documentation</a>', 'wp_textit_sdk'); ?></p>
			<p><strong><?php _e('Quick method reference:', 'wp_textit_sdk'); ?></strong></p>
			
			<ul>
				<li>contacts - To list or modify contacts.</li>
				<li>fields - To list or modify contact fields.</li>
				<li>messages - To list and create new SMS messages.</li>
				<li>relayers - To list, create and remove new Android phones.</li>
				<li>calls - To list incoming, outgoing and missed calls as reported by the Android phone.</li>
				<li>flows - To list active flows.</li>
				<li>runs - To list or start flow runs for contacts.</li>
				<li>campaigns - To list or modify campaigns on your account.</li>
				<li>events - To list or modify campaign events on your account.</li>
				<li>boundaries - To retrieve the geometries of the administrative boundaries on your account.</li>
			</ul>
			
			<h2><?php _e('textItDo()', 'wp_textit_sdk'); ?></h2>
			<p><?php _e('To call the TextIt API, use <code>WordpressTextItSDK::textItDo($method, $args, $http)</code>', 'wp_textit_sdk'); ?></p>
			<p><strong>$method</strong> - string - <?php _e('One of the TextIt API methods listed above', 'wp_textit_sdk'); ?></p>
			<p><strong>$args</strong> - array - <?php _e('2 dimensional array containing argument names and values. Details of accepted arguments in the TextIt documentation', 'wp_textit_sdk'); ?></p>
			<p><strong>$http</strong> - string - <?php _e('Either GET or POST, depending on whether you want to list or add / modify data'); ?></p>
			
			<p><?php _e('The return value will be either an array with a TextIt API response or an exception', 'wp_textit_sdk'); ?></p>
			
			<p><?php _e('E.g. <code>WordpressTextItSDK::textItDo( \'contacts\', array(), \'GET\' )</code> would return a list of contacts from your TextIt account', 'wp_textit_sdk'); ?></p>
			
			<h2><?php _e('textit_webhook_event', 'wp_textit_sdk'); ?></h2>
			
			<p><?php _e('To response to TextIt webhook events (e.g. Incoming Messages, Outgoing Messages, Incoming Calls, Outgoing Calls, Relayer Alarms) use <code>add_action( \'textit_webhook_event\', \'my_custom_function\', 2, 1 )</code>', 'wp_textit_sdk'); ?></p>
			
			<p><?php _e('Your custom function should accept a single argument - <code>$event</code> - an array containing the data TextIt posted via the webhook'); ?></p>
    
		</div>
		<?php
	}
	
	/**
	 * Generates source of API token field.
	 */
	static function askAPIToken() {
        
		$api_token = self::getOption('api_token');
		?><p><input id='api_token' name='wp_textit_sdk[api_token]' size='45' type='text' value="<?php echo $api_token; ?>" /></p><?php
		
		if ( empty($api_token) ) {
		    ?><br/><span class="setting-description"><small><em><?php _e('To get your API token, please visit your TextIt account page', 'wp_textit_sdk'); ?></em></small></span><?php
		} else {
		   //test api key
		}
		
	}
	
	/**
	 * Processes submitted settings from.
	 */
	static function formValidate($input) {
	    
        if ( empty($input['api_token']) ) {
            add_settings_error(  
                                'wp_textit_sdk',
                                'api_token',
                                __('You must define a valid api token.', 'wp_textit_sdk'),
                                'error'
            );
            
            
            $input['api_token'] = '';
        }
     
		$response = array_map('wp_strip_all_tags', $input); 
		return $response;
	}
	
	/******************************************************************
		HELPER FUNCTIONS
	*******************************************************************/
	
	/**
	* @return boolean
	*/
	static function isConnected() {
		return isset(self::$textit);
	}
	
	static function getConnected() {
		
			if ( !isset(self::$textit) ) {
					try {
							require_once( plugin_dir_path( __FILE__ ) . '/lib/textit.class.php');
							self::$textit = new TextIt( self::getAPIToken() );
					} catch ( Exception $e ) {}
			}
	}
	
	/**
	 * @return string|boolean
	 */
	static function getAPIToken() {

		return self::getOption('api_token');
	}
	
	/**
	 * @return mixed
	 */
	static function getOption( $name, $default = false ) {

		$options = get_option('wp_textit_sdk');
        
		if( isset( $options[$name] ) )
			return $options[$name];

		return $default;
	}
	
	static function getUserAgent() {
    global $wp_version;
    	
    if ( ! function_exists( 'get_plugins' ) ) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
		$plugin_file = basename( ( __FILE__ ) );

    	$me 	= $plugin_folder[$plugin_file]['Version'];
    	$php 	= phpversion();
    	$wp 	= $wp_version;
    	
    	return "wpTextItSDK/$me (PHP/$php; WP/$wp)";
  }
	
	/**
	 * @param string $method API method name
	 * @param array $args query arguments
	 * @param string $http GET or POST request type
	 * @return array|string|TextIt_Exception
	 */
	static function textItDo($method, $args = array(), $http = 'POST') {
	
		$response = false;
	
		self::getConnected();
		if (self::isConnected()) {	
			$response = self::$textit->request($method, $args, $http);
		}
		
		return $response;
	}

  
} // end class
new WordpressTextItSDK();

?>