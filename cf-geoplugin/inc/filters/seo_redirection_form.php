<?php

if (!defined('WPINC')) {
    die("Don't mess with us.");
}

if (!defined('ABSPATH')) {
    exit;
}

add_action('cfgp/page/seo_redirection/response', function () {
    CFGP_SEO::response_error();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        CFGP_SEO::response_success();
    }

    if (CFGP_U::request_string('action') == 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        CFGP_SEO::response_success();
    }
});

add_action('cfgp/page/seo_redirection/form/content', function () {

    $ID  = CFGP_U::request_int('id', 0);
    $get = CFGP_SEO::get($ID);

    if (!$get) {
        $get = (object)[
            'url'       => null,
            'country'   => null,
            'region'    => null,
            'city'      => null,
            'postcode'  => null,
            'http_code' => 302,
            'only_once' => 0,
            'active'    => 1,
        ];
    }
    $select_country  = CFGP_U::request_string('country', $get->country);
    $select_region   = CFGP_U::request_string('region', $get->region);
    $select_city     = CFGP_U::request_string('city', $get->city);
    $select_postcode = CFGP_U::request_string('postcode', $get->postcode);
    $http_code       = CFGP_U::request_string('http_code', $get->http_code);
    $only_once       = CFGP_U::request_int('only_once', $get->only_once);
    $redirect_enable = CFGP_U::request_int('redirect_enable', $get->active);
    $redirection_url = CFGP_U::request_string('url', $get->url);

    global $wpdb;
    ?>
<?php if (!CFGP_SEO_Table::table_exists()) : ?>
<div class="notice notice-error"> 
	<p><?php echo wp_kses_post(sprintf(__('The database table "%s" not exists! You can try to reactivate the Geo Controller to correct this error.', 'cf-geoplugin'), '<strong>' . esc_html($wpdb->cfgp_seo_redirection) . '</strong>')); ?></p>
</div>
<?php endif; ?>
<div class="postbox">
	<h3 class="hndle" style="margin-bottom:0;padding-bottom:0;"><span><?php esc_html_e('SEO Redirection Global Params', 'cf-geoplugin'); ?></span></h3><hr>
	<div class="inside">
    	<?php CFGP_Form::input('hidden', ['name' => 'id','value' => $ID]); ?>
    	<table class="form-table cfgp-form-table cfgp-country-region-city-multiple-form" role="presentation" id="cfgp-new-seo-redirection">
        	<tbody>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label><?php esc_html_e('Enable this redirection', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
                            CFGP_Form::radio(
                                [
                                    1 => __('Enable', 'cf-geoplugin'),
                                    0 => __('Disable', 'cf-geoplugin'),
                                ],
                                ['name' => 'redirect_enable'],
                                $redirect_enable
                            );
    ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="country"><?php esc_html_e('Select Country', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
        CFGP_Form::select_countries(['name' => 'country', 'class' => 'cfgp_select2'], $select_country);
    ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="region"><?php esc_html_e('Select Region', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
        CFGP_Form::select_regions(['name' => 'region', 'country_code' => $select_country, 'class' => 'cfgp_select2'], $select_region);
    ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="city"><?php esc_html_e('Select City', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
        CFGP_Form::select_cities(['name' => 'city', 'country_code' => $select_country, 'class' => 'cfgp_select2'], $select_city);
    ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="postcode"><?php esc_html_e('Select Postcode', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
        CFGP_Form::select_postcodes(['name' => 'postcode', 'class' => 'cfgp_select2'], $select_postcode);
    ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="url"><?php esc_html_e('Redirect URL', 'cf-geoplugin'); ?></label></th>
                    <td valign="top">
					<?php CFGP_Form::input('url', ['name' => 'url','value' => $redirection_url, 'style' => 'width:100%;max-width:50%;']); ?>
					<p class="description"><?php esc_html_e('If you want to pass one of the URL parameters, you can do it like this:', 'cf-geoplugin'); ?> <code>https://example.com/?pharam_1=*&pharam_2=*</code>.<br><?php esc_html_e('If any of defined parameters exist in the URL, they will be passed to the new redirect URL.', 'cf-geoplugin'); ?></p>
					</td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label for="http_code"><?php esc_html_e('HTTP Code', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php CFGP_Form::select_http_code(['name' => 'http_code'], $http_code); ?></td>
                </tr>
                <tr>
                    <th scope="row" valign="top" class="cfgp-label"><label><?php esc_html_e('Redirection', 'cf-geoplugin'); ?></label></th>
                    <td valign="top"><?php
        CFGP_Form::radio(
            [
                1 => __('only once', 'cf-geoplugin'),
                0 => __('always', 'cf-geoplugin'),
            ],
            ['name' => 'only_once'],
            $only_once
        );
    ?></td>
                </tr>
            </tbody>
        </table>
        <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save Redirection', 'cf-geoplugin'); ?></button><?php if ($ID): ?> <a href="<?php echo esc_url(CFGP_U::admin_url('admin.php?page='.CFGP_NAME.'-seo-redirection')); ?>" class="button" style="float:right"><?php esc_html_e('Go back to list', 'cf-geoplugin'); ?></a><?php endif; ?></p>
    </div>
</div>
<?php });

add_action('cfgp/page/seo_redirection/form', function () {
    $action = CFGP_U::request_string('action');
    ?>
<div class="wrap wrap-cfgp" id="<?php echo esc_attr(sanitize_title($_GET['page'] ?? null)); ?>">
	<h1 class="wp-heading-inline"><i class="cfa cfa-location-arrow"></i> <?php
            if ($action == 'edit') {
                esc_html_e('Edit SEO redirection', 'cf-geoplugin');
            } else {
                esc_html_e('Add new SEO redirection', 'cf-geoplugin');
            }
    ?></h1>
	<?php
        if ($action == 'edit') {
            echo wp_kses_post(sprintf(
                '<a href="%s" class="page-title-action button-cfgeo-seo-new"><i class="cfa cfa-plus"></i> %s</a> ',
                esc_url(CFGP_U::admin_url('admin.php?page=cf-geoplugin-seo-redirection&action=new&nonce='.wp_create_nonce(CFGP_NAME.'-seo-new'))),
                esc_html__('New SEO redirection', 'cf-geoplugin')
            ));
        }
    ?>
    <hr class="wp-header-end">
    <div id="post">
    	<div id="poststuff" class="metabox-holder has-right-sidebar">
            <form method="post">
                <div class="inner-sidebar" id="<?php echo esc_attr(CFGP_NAME); ?>-license-sidebar">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <?php
                            do_action('cfgp/page/seo_redirection/form/sidebar');
    do_action('cfgp/page/seo_redirection/sidebar');
    ?>
                    </div>
                </div>
    
                <div id="post-body">
                    <div id="post-body-content">
                        <?php do_action('cfgp/page/seo_redirection/form/content'); ?>
                    </div>
                </div>
            </form>
            <br class="clear">
        </div>
    </div>
</div>
<?php if ($action == 'new') : ?>
<script>;(function(jQ){jQ('#toplevel_page_cf-geoplugin-seo-redirection .wp-submenu').find('li:nth-child(3)').addClass('current');}(jQuery || window.jQuery));</script>
<?php endif; ?>
<?php });
