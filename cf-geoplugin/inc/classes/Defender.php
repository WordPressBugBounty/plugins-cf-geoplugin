<?php

/**
 * Defender
 *
 * @link          http://infinitumform.com/
 * @since         8.0.0
 *
 * @package       cf-geoplugin
 *
 * @author        Ivijan-Stefan Stipic
 *
 * @version       2.0.0
 */
// If someone try to called this file directly via URL, abort.
if (!defined('WPINC')) {
    die("Don't mess with us.");
}

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CFGP_Defender', false)) : class CFGP_Defender extends CFGP_Global
{
    public function __construct()
    {
        $this->add_action('init', 'tor_protection', 1);
        $this->add_action('init', 'protect', 2);
    }

    /*
     * TOR Network Control
     *
     * Control the access of TOR network visitors.
     */
    public function tor_protection()
    {
        switch ((int)CFGP_Options::get('block_tor_network', 0)) {

            // TOR Access: Unrestricted
            default:
            case 0:
                return;
                break;

                // TOR Access: Denied
            case 1:
                if ((int)CFGP_U::api('is_tor') === 1) {
                    if (function_exists('http_response_code')) {
                        http_response_code(403);
                    } else {
                        header('HTTP/1.0 403 Forbidden', true, 403);
                    }

                    die(wp_kses_post(wpautop(html_entity_decode(stripslashes(apply_filters(
                        'cfgp/defender/tor/denied/message',
                        sprintf(
                            '<h1>%s</h1><p>%s</p>',
                            __('403 Forbidden', 'cf-geoplugin'),
                            __('Sorry, users accessing via the TOR network are not allowed on this site.', 'cf-geoplugin')
                        )
                    ))))));
                }
                break;

                // TOR Access: Exclusive
            case 2:
                if ((int)CFGP_U::api('is_tor') === 0) {
                    if (function_exists('http_response_code')) {
                        http_response_code(403);
                    } else {
                        header('HTTP/1.0 403 Forbidden', true, 403);
                    }

                    die(wp_kses_post(wpautop(html_entity_decode(stripslashes(apply_filters(
                        'cfgp/defender/tor/exclusive/message',
                        sprintf(
                            '<h1>%s</h1><p>%s</p>',
                            __('403 Forbidden', 'cf-geoplugin'),
                            __('This site is accessible exclusively to TOR network users. Please access via the TOR network.', 'cf-geoplugin')
                        )
                    ))))));
                }
                break;
        }
    }

    // Protect site from visiting
    public function protect()
    {
        // Defender is disabled ???
        if (CFGP_Options::get('enable_defender', 0) == 0) {
            return;
        }

        // Browser is allow to access to website
        if (CFGP_U::check_defender_cookie()) {
            return;
        }

        $ip = CFGP_U::api('ip');

        // Block on error
        if (empty($ip) || CFGP_U::api('error')) {
            return;
        }

        // Set cookie
        if (
            is_admin()
            && CFGP_U::request_bool('save_defender')
            && wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), CFGP_NAME.'-save-defender') !== false
            && isset($_POST['block_proxy'])
        ) {
            CFGP_U::set_defender_cookie();
        }

        if (
            isset($_REQUEST['cfgp_admin_access'])
            && (
                CFGP_U::request_string('cfgp_admin_access') === str_rot13(substr(CFGP_U::KEY(), 3, 32))
            )
        ) {
            CFGP_U::set_defender_cookie();
        }

        // Whitelist IP addresses
        $whitelist_ips = preg_split('/[,;\n|]+/', CFGP_Options::get('ip_whitelist'));
        $whitelist_ips = array_map('trim', $whitelist_ips);
        $whitelist_ips = array_filter($whitelist_ips);
        $whitelist_ips = apply_filters('cfgp/defender/whitelist/ip', $whitelist_ips, $ip);

        if (
            !empty($whitelist_ips)
            && is_array($whitelist_ips)
            && in_array($ip, $whitelist_ips, true) !== false
        ) {
            return;
        }

        // Check settings
        if ($this->check()) {
            if (function_exists('http_response_code')) {
                http_response_code(403);
            } else {
                header('HTTP/1.0 403 Forbidden', true, 403);
            }

            die(wp_kses_post(wpautop(html_entity_decode(stripslashes(CFGP_Options::get('block_country_messages'))))));
        }

        // Block Spam
        if (
            CFGP_Options::get('enable_spam_ip', 0)
            && CFGP_License::level(CFGP_Options::get('license_sku')) > 0
        ) {
            if (CFGP_U::api('is_spam')) {
                if (function_exists('http_response_code') && version_compare(PHP_VERSION, '5.4', '>=')) {
                    http_response_code(403);
                } else {
                    header($this->header, true, 403);
                }

                die(wp_kses_post(wpautop(html_entity_decode(stripslashes(CFGP_Options::get('block_country_messages'))))));
            }
        }
    }

    // Check what to do with user
    public function check()
    {
        // Bots need to see this website
        if (CFGP_U::is_bot()) {
            return false;
        }

        // Let's block proxy
        if (CFGP_Options::get('block_proxy', 0) && CFGP_U::api('is_proxy') == 1) {
            return true;
        }

        // Explode all IP's and block them... Yeah baby!
        $ips = preg_split('/[,;\n|]+/', CFGP_Options::get('block_ip', '') ?? '');
        $ips = array_map('trim', $ips);
        $ips = array_filter($ips);

        if (in_array(CFGP_U::api('ip'), $ips, true) !== false) {
            return true;
        }

        // Get countries
        $block_country = self::process_block_data('block_country');

        // Get regions
        $block_region = self::process_block_data('block_region');

        // Get cities
        $block_city = self::process_block_data('block_city');

        // Generate redirection mode
        $mode = [ null, 'country', 'region', 'city' ];
        $mode = $mode[ count(array_filter(array_map(
            function ($obj) {
                return !empty($obj);
            },
            [
                $block_country,
                $block_region,
                $block_city,
            ]
        ))) ];

        if (empty($block_region) && !empty($block_city)) {
            $mode = 'country_city';
        }

        // Switch mode
        switch ($mode) {
            case 'country':
                if (CFGP_U::check_user_by_country($block_country)) {
                    return true;
                }
                break;
            case 'region':
                if (
                    CFGP_U::check_user_by_region($block_region)
                    && CFGP_U::check_user_by_country($block_country)
                ) {
                    return true;
                }
                break;
            case 'city':
                if (
                    CFGP_U::check_user_by_city($block_city)
                    && CFGP_U::check_user_by_region($block_region)
                    && CFGP_U::check_user_by_country($block_country)
                ) {
                    return true;
                }
                break;
            case 'country_city':
                if (
                    CFGP_U::check_user_by_city($block_city)
                    && CFGP_U::check_user_by_country($block_country)
                ) {
                    return true;
                }
                break;
        }

        // Hey, we are all good. Right?
        return false;
    }

    private static function process_block_data($option_key)
    {
        $block_data = CFGP_Options::get($option_key, []);

        if (!empty($block_data) && !is_array($block_data) && preg_match('/\]|\[/', $block_data)) {
            $block_data = explode(']|[', $block_data);
            $block_data = array_map(function ($match) {
                return trim($match, ' [],');
            }, $block_data);
        }

        if (!empty($block_data) && is_array($block_data)) {
            $block_data = array_filter($block_data);
            $block_data = array_unique($block_data);
        }

        return $block_data;
    }

    /*
     * Instance
     * @verson    1.0.0
     */
    public static function instance()
    {
        $class    = self::class;
        $instance = CFGP_Cache::get($class);

        if (!$instance) {
            $instance = CFGP_Cache::set($class, new self());
        }

        return $instance;
    }
}
endif;
