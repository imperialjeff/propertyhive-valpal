<?php
/**
 * Plugin Name: Property Hive ValPal Add On
 * Plugin Uri: https://wp-property-hive.com/addons/valpal-instant-valuation/
 * Description: Add On for Property Hive allowing you to integrate ValPal's instant valuation tool
 * Version: 2.0.0
 * Author: PropertyHive
 * Author URI: https://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_ValPal' ) ) :

final class PH_ValPal {

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var Property Hive The single instance of the class
     */
    protected static $_instance = null;

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var string
     */
    public $label = '';
    
    /**
     * Main Property Hive ValPal Instance
     *
     * Ensures only one instance of Property Hive ValPal is loaded or can be loaded.
     *
     * @static
     * @return Property Hive ValPal - Main instance
     */
    public static function instance() 
    {
        if ( is_null( self::$_instance ) ) 
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {

        $this->id    = 'valpal';
        $this->label = __( 'ValPal', 'propertyhive' );

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        add_action( 'plugins_loaded', array( $this, 'check_can_be_used'), 1 );
    }

    public function check_can_be_used()
    {
        // check they're running at least version 2.0.3 of Property Hive when this filter was introduced
        if ( class_exists( 'PropertyHive' ) && version_compare(PH()->version, '2.0.3', '>=') )
        {
            if ( apply_filters( 'propertyhive_add_on_can_be_used', true, 'propertyhive-valpal' ) === FALSE )
            { 
                add_action( 'admin_notices', array( $this, 'invalid_license_notice') );
                return;
            }
        }

        add_action( 'admin_notices', array( $this, 'valpal_error_notices') );

        add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
        add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );

        add_shortcode( 'valpal', array( $this, 'valpal_shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_valpal_styles' ) );

        add_action( 'wp_ajax_do_postcode_lookup', array( $this, 'do_postcode_lookup' ) );
        add_action( 'wp_ajax_nopriv_do_postcode_lookup', array( $this, 'do_postcode_lookup' ) );

        add_action( 'wp_ajax_do_paf_lookup', array( $this, 'do_paf_lookup' ) );
        add_action( 'wp_ajax_nopriv_do_paf_lookup', array( $this, 'do_paf_lookup' ) );

        add_action( 'wp_ajax_do_val_request', array( $this, 'do_val_request' ) );
        add_action( 'wp_ajax_nopriv_do_val_request', array( $this, 'do_val_request' ) );

        add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'plugin_add_settings_link' ) );

        if ( is_admin() && file_exists( dirname( __FILE__ ) . '/propertyhive-valpal-update.php' ) )
        {
            include_once( dirname( __FILE__ ) . '/propertyhive-valpal-update.php' );
        }
    }

    public function invalid_license_notice()
    {
        if ( !current_user_can('manage_options') )
        {
            return;
        }

        if ( isset($_GET['page']) && $_GET['page'] == 'ph-settings' && isset($_GET['tab']) && $_GET['tab'] == 'licensekey' )
        {
            return;
        }

        $message = __( 'The Property Hive ' . $this->label . ' add-on will not function as <a href="' . admin_url('admin.php?page=ph-settings&tab=licensekey') . '">no valid license</a> was found. Please <a href="' . admin_url('admin.php?page=ph-settings&tab=features') . '">disable this feature</a> or enter a valid license.', 'propertyhive' );
        echo"<div class=\"error\"> <p>$message</p></div>";
    }

    public function plugin_add_settings_link( $links )
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=ph-settings&tab=valpal') . '">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    /**
     * Define PH ValPal Constants
     */
    private function define_constants() 
    {
        define( 'PH_VALPAL_PLUGIN_FILE', __FILE__ );
        define( 'PH_VALPAL_VERSION', $this->version );
    }

    private function includes()
    {
        //include_once( dirname( __FILE__ ) . "/includes/class-ph-valpal-install.php" );
    }

    /**
     * Output error messages
     */
    public function valpal_error_notices() 
    {
        if (!is_plugin_active('propertyhive/propertyhive.php'))
        {
            $message = __( "The Property Hive plugin must be installed and activated before you can use the Property Hive ValPal add-on", 'propertyhive' );
            echo "<div class=\"error\"> <p>$message</p></div>";
        }
        elseif ( !function_exists('curl_version' ) )
        {
            $message = __( "cURL must be enabled on the server in order to perform ValPal API request", 'propertyhive' );
            echo "<div class=\"error\"> <p>$message</p></div>";
        }
    }

    public function load_valpal_styles() {

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_style( 
            'ph-valpal', 
            $assets_path . 'css/ph-valpal.css', 
            array(), 
            PH_VALPAL_VERSION
        );
    }

    public function valpal_shortcode() 
    {
        $current_settings = get_option( 'propertyhive_valpal', array() );

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        ob_start();

        wp_enqueue_style( 'ph-valpal' );

        $api_key = get_option('propertyhive_google_maps_api_key');
        wp_register_script('googlemaps', '//maps.googleapis.com/maps/api/js?' . ( ( $api_key != '' && $api_key !== FALSE ) ? 'key=' . $api_key : '' ), false, '3');
        wp_enqueue_script('googlemaps');

        wp_register_script( 
            'ph-valpal', 
            $assets_path . 'js/ph-valpal.js', 
            array('jquery'), 
            PH_VALPAL_VERSION,
            true
        );

        $translation_array = apply_filters( 'propertyhive_valpal_translation_array', array(
            'ajax_url'        => admin_url( 'admin-ajax.php', 'relative' ),
            'address_lookup'  => ( (isset($current_settings['address_lookup']) && $current_settings['address_lookup'] == '1') ? '1' : '' ),
            'sales_min_amount_percentage_modifier' => 0,
            'sales_actual_amount_percentage_modifier' => 0,
            'sales_max_amount_percentage_modifier' => 0,
            'lettings_min_amount_percentage_modifier' => 0,
            'lettings_actual_amount_percentage_modifier' => 0,
            'lettings_max_amount_percentage_modifier' => 0,
            'show_map_in_results' => apply_filters( 'propertyhive_valpal_show_map_in_results', true ),
            'show_street_view_in_results' => apply_filters( 'propertyhive_valpal_show_street_view_in_results', true ),
        ) );
        wp_localize_script( 'ph-valpal', 'ph_valpal', $translation_array );

        wp_enqueue_script('ph-valpal');

        $this->output_valpal_form();
        $this->output_valpal_results();

        return ob_get_clean();
    }

    private function output_valpal_form()
    {
        $current_settings = get_option( 'propertyhive_valpal', array() );

        $active_departments = array();
        if ( get_option( 'propertyhive_active_departments_sales', '' ) == 'yes') {
            $active_departments[] = 'sales';
        }
        if ( get_option( 'propertyhive_active_departments_lettings', '' ) == 'yes') {
            $active_departments[] = 'lettings';
        }

        $template = locate_template( array('propertyhive/valpal-form.php') );
        if ( !$template )
        {
            include( dirname( PH_VALPAL_PLUGIN_FILE ) . '/templates/valpal-form.php' );
        }
        else
        {
            include( $template );
        }
    }

    private function output_valpal_results()
    {
?>
<div id="valuation_results" style="display:none;">
                
    <div class="amounts">

        <div class="min-amount-label">Min Valuation</div>
        <div class="actual-amount-label">Valuation</div>
        <div class="max-amount-label">Max Valuation</div>

        <div class="min-amount"><span></span></div>
        <div class="actual-amount">
            <span></span>
        </div>
        <div class="max-amount"><span></span></div>

        <div style="clear:both;"></div>

    </div>

    <div class="info-container">

        <div class="container">

            <?php if ( apply_filters( 'propertyhive_valpal_show_map_in_results', true ) === true ) { ?>
            <div class="map-view" id="map_canvas" style="height:400px;"></div>
            <?php } ?>

            <?php if ( apply_filters( 'propertyhive_valpal_show_street_view_in_results', true ) === true ) { ?>
            <div class="street-view" id="street_map_canvas" style="height:400px;"></div>
            <?php } ?>

            <div class="area-info"></div>

        </div>

    </div>

</div>
<?php
    }

    public function do_postcode_lookup()
    {
        $current_settings = get_option( 'propertyhive_valpal', array() );

        $postcode = $_POST['postcode'];

        $contents = file_get_contents('https://www.valpal.co.uk/addressfinder/stepone.php?postcode=' . urlencode($postcode) . '&username=' . $current_settings['address_lookup_username'] . '&password=' . $current_settings['address_lookup_password']);

        echo $contents;

        die();
    }

    public function do_val_request()
    {
        $return = array(
            'error' => ''
        );

        $current_settings = get_option( 'propertyhive_valpal', array() );

        if ( isset($current_settings['address_lookup']) && $current_settings['address_lookup'] == '1' ) 
        {
            $property = $_POST['property'];

            $contents = file_get_contents('https://www.valpal.co.uk/addressfinder/steptwo.php?lstResults=' . $property . '&username=' . $current_settings['address_lookup_username'] . '&password=' . $current_settings['address_lookup_password']);

            $body = json_decode($contents, true);

            if ( $body === FALSE )
            {
                $return = array(
                    'error' => 'Failed to obtain full address'
                );

                echo json_encode($return);

                die();
            }

            if ( isset($body[2]['results']) )
            {
                $buildname = $body[2]['results']['building'];
                $subBname = $body[2]['results']['sub_building'];
                $number = $body[2]['results']['number'];
                $street = $body[2]['results']['street'];
                $depstreet = $body[2]['results']['dependent_street'];
                $postcode = $body[2]['results']['postcode'];
            }
            else
            {
                $return = array(
                    'error' => 'No address returned'
                );

                echo json_encode($return);

                die();
            }
        }
        else
        {
            $buildname = '';
            $subBname = '';
            $number = $_POST['number'];
            $street = $_POST['street'];
            $depstreet = '';
            $postcode = $_POST['postcode'];
        }

        $allowed_postcodes = array();
        $allowed_postcodes = apply_filters( 'propertyhive_valpal_allowed_postcodes', $allowed_postcodes );

        if ( !empty($allowed_postcodes) )
        {
            $found_postcode = false;
            // we're restricting postcodes
            foreach ( $allowed_postcodes as $allowed_postcode )
            {
                if ( strpos( strtolower(trim($postcode)), strtolower(trim($allowed_postcode)) ) !== false )
                {
                    $found_postcode = true;
                    break;
                }
            }

            if ( !$found_postcode )
            {
                $return['error'] = "The postcode entered doesn't appear to be one we operate in.";
                echo json_encode($return);
                die();
            }
        }

        $args = array(
            'method'      => 'POST',
            'body'        => array(
                'username' => $current_settings['username'],
                'pass' => $current_settings['password'],
                'type' => $_POST['type'],
                'reference' => '',
                'buildname' => $buildname,
                'subBname' => $subBname,
                'number' => $number,
                'street' => $street,
                'depstreet' => $depstreet,
                'postcode' => $postcode,
                'bedrooms' => $_POST['bedrooms'],
                'propertytype' => $_POST['propertytype'],
                'name' => $_POST['name'],
                'emailaddress' => $_POST['email'],
                'phone' => $_POST['telephone'],
            ),
            'sslverify' => false,
        );

        $response = wp_remote_post('https://www.valpal.co.uk/apiforvaluation', $args);

        if ( is_wp_error( $response ) ) 
        {
            $error_message = $response->get_error_message();
            $return['error'] = "Something went wrong: $error_message";
            echo json_encode($return);
            die();
        }
        else
        {
            $body = $response['body'];
            $body = json_decode($body, TRUE);

            if ( $body === FALSE )
            {
                $error_message = $response->get_error_message();
                $return['error'] = "Failed to decode response: " . print_r($response['body'], TRUE);
                echo json_encode($return);
                die();
            }

            if ( isset($body[0]['status']) && $body[0]['status'] == 'success' )
            {
                $result = $body[2]['results'];

                $return = array(
                    'error' => '',
                    'minvaluation' => html_entity_decode($result['minvaluation']),
                    'valuation' => html_entity_decode($result['valuation']),
                    'maxvaluation' => html_entity_decode($result['maxvaluation']),
                    'propertytype' => $result['propertytype'],
                    'tenure' => $result['tenure'],
                    'bedrooms' => $result['bedrooms'],
                    'yearofpropertyconstruction' => $result['yearofpropertyconstruction'],
                    'buildname' => $buildname,
                    'subBname' => $subBname,
                    'number' => $number,
                    'street' => $street,
                    'depstreet' => $depstreet,
                    'postcode' => $postcode,
                );

                $to = get_option( 'admin_email', '' );
                if ( isset($current_settings['email_recipient']) && sanitize_email( $current_settings['email_recipient'] ) != '' )
                {
                    $to = sanitize_email( $current_settings['email_recipient'] );
                }
                $subject = 'New Instant Online Valuation';
                $body = "A new instant online valuation was just completed. Please find details below:\n\n";
                
                $body .= "Name: " . $_POST['name'] . "\n";
                $body .= "Email Address: " . $_POST['email'] . "\n";
                $body .= "Telephone Number: " . $_POST['telephone'] . "\n\n";

                $body .= "Comments: " . $_POST['comments'] . "\n\n";

                $body .= "buildname: " . $buildname . "\n";
                $body .= "subBname: " . $subBname . "\n";
                $body .= "number: " . $number . "\n";
                $body .= "street: " . $street . "\n";
                $body .= "depstreet: " . $depstreet . "\n";
                $body .= "postcode: " . $postcode . "\n\n";

                $body .= "The results of the instant valuation were as follows (some information may be unavailable):\n\n";

                $body .= "Min Valuation: " . html_entity_decode($result['minvaluation']) . "\n";
                $body .= "Valuation: " . html_entity_decode($result['valuation']) . "\n";
                $body .= "Max Valuation: " . html_entity_decode($result['maxvaluation']) . "\n";
                $body .= "Property Type: " . $result['propertytype'] . "\n";
                $body .= "Tenure: " . $result['tenure'] . "\n";
                $body .= "Bedrooms: " . $result['bedrooms'];
                 
                wp_mail( $to, $subject, $body );

                do_action( 'propertyhive_valpal_send_success', $_POST, $return );
            }
            else
            {
                if ( isset($body[1]['errors']) && is_array($body[1]['errors']) && !empty($body[1]['errors']) )
                {
                    $return['error'] = $body[1]['errors']['message'] . ' Please check the address entered';
                    echo json_encode($return);
                    die();
                }
                else
                {
                    $return['error'] = "Valuation lookup failed, but no errors provided";
                    echo json_encode($return);
                    die();
                }
            }
        }

        echo json_encode($return);

        die();
    }

    /**
     * Add a new settings tab to the Property Hive settings tabs array.
     *
     * @param array $settings_tabs Array of Property Hive setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of Property Hive setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs[$this->id] = $this->label;
        return $settings_tabs;
    }

    /**
     * Uses the Property Hive admin fields API to output settings.
     *
     * @uses propertyhive_admin_fields()
     * @uses self::get_settings()
     */
    public function output() {

        global $current_section;
        
        propertyhive_admin_fields( self::get_valpal_settings() );
    }

    /**
     * Uses the Property Hive options API to save settings.
     *
     * @uses propertyhive_update_options()
     * @uses self::get_settings()
     */
    public function save() {

        $propertyhive_valpal = array(
            'username' => ( (isset($_POST['username'])) ? ph_clean($_POST['username']) : '' ),
            'password' => ( (isset($_POST['password'])) ? ph_clean($_POST['password']) : '' ),
            'email_recipient' => ( (isset($_POST['email_recipient'])) ? sanitize_email( $_POST['email_recipient'] ) : '' ),
            'address_lookup' => ( (isset($_POST['address_lookup'])) ? '1' : '' ),
            'address_lookup_username' => ( (isset($_POST['address_lookup_username'])) ? ph_clean($_POST['address_lookup_username']) : '' ),
            'address_lookup_password' => ( (isset($_POST['address_lookup_password'])) ? ph_clean($_POST['address_lookup_password']) : '' ),
            'valuation_form_disclaimer' => ( (isset($_POST['valuation_form_disclaimer'])) ? wp_kses_post( trim( stripslashes($_POST['valuation_form_disclaimer'] ) ) ) : '' ),
        );

        update_option( 'propertyhive_valpal', $propertyhive_valpal );
    }

    /**
     * Get ValPal settings
     *
     * @return array Array of settings
     */
    public function get_valpal_settings() {

        $current_settings = get_option( 'propertyhive_valpal', array() );

        $settings = array(

            array( 'title' => __( 'ValPal Settings', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'valpal_settings' )

        );

        $settings[] = array(
            'title'     => __( 'ValPal Username', 'propertyhive' ),
            'id'        => 'username',
            'type'      => 'text',
            'default'   => ( isset($current_settings['username']) ? $current_settings['username'] : ''),
        );

        $settings[] = array(
            'title'     => __( 'ValPal Password', 'propertyhive' ),
            'id'        => 'password',
            'type'      => 'text',
            'default'   => ( isset($current_settings['password']) ? $current_settings['password'] : ''),
        );

        $settings[] = array(
            'title'     => __( 'Send Email To', 'propertyhive' ),
            'id'        => 'email_recipient',
            'type'      => 'email',
            'default'   => ( isset($current_settings['email_recipient']) ? $current_settings['email_recipient'] : ''),
            'desc'      => 'The users details and valuation results will be emailed to this address. If left blank it will default to ' . get_option('admin_email', '')
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'valpal_settings');

        $settings[] = array( 'title' => __( 'Address Lookup Settings', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'address_lookup_settings' );

        $settings[] = array(
            'title'     => __( 'Use Address Lookup Service', 'propertyhive' ),
            'id'        => 'address_lookup',
            'type'      => 'checkbox',
            'default'   => ( (isset($current_settings['address_lookup']) && $current_settings['address_lookup'] == '1') ? 'yes' : ''),
            'desc'      => 'ValPal have an address lookup service available meaning users can find their address by only entering their postcode. ValPal will provide a separate username and password for this service which can be entered below.',
        );

        $settings[] = array(
            'title'     => __( 'Address Lookup Username', 'propertyhive' ),
            'id'        => 'address_lookup_username',
            'type'      => 'text',
            'default'   => ( isset($current_settings['address_lookup_username']) ? $current_settings['address_lookup_username'] : ''),
        );

        $settings[] = array(
            'title'     => __( 'Address Lookup Password', 'propertyhive' ),
            'id'        => 'address_lookup_password',
            'type'      => 'text',
            'default'   => ( isset($current_settings['address_lookup_password']) ? $current_settings['address_lookup_password'] : ''),
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'address_lookup_settings');

        $settings[] = array( 'title' => __( 'GDPR Settings', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'gdpr_settings' );

        $settings[] = array(
            'title'   => __( 'ValPal Valuation Form Disclaimer', 'propertyhive' ),
            'id'      => 'valuation_form_disclaimer',
            'type'    => 'wysiwyg',
            'default' => ( isset($current_settings['valuation_form_disclaimer']) ? $current_settings['valuation_form_disclaimer'] : ''),
            'desc'    => __( 'Add disclaimer text, including a link to a privacy policy, that will appear on the ValPal instant valuation form.', 'propertyhive' )
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'gdpr_settings');

        $settings[] = array( 'title' => __( 'Shortcode', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'shortcode' );

        $settings[] = array(
            'title'     => __( 'Shortcode', 'propertyhive' ),
            'type'      => 'html',
            'html'      => 'Simply use the shortcode <em>[valpal]</em> where you wish the instant valuation form and results to appear',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'shortcode');

        return $settings;
    }
}

endif;

/**
 * Returns the main instance of PH_ValPal to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return PH_ValPal
 */
function PHVP() {
    return PH_ValPal::instance();
}

PHVP();