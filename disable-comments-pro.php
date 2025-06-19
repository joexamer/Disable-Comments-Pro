<?php
/**
 * Plugin Name: Disable Comments Pro
 * Description: A plugin to disable comments, hide the comments tab, and provide settings for specific post types.
 * Version: 1.0
 * Author: DigaTopia, Yousef Amer
 * Author URI: https://github.com/joexamer
 * Plugin URI: https://github.com/joexamer/WFCM-Whatsapp-Checkout
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dcp_is_comments_disabled_for_post_type( $post_type ) {
    $options = get_option( 'dcp_settings' );
    if ( isset( $options['enabled'] ) && $options['enabled'] ) {
        if ( isset( $options['post_types'] ) && is_array( $options['post_types'] ) ) {
            return in_array( $post_type, $options['post_types'] );
        }
    }
    return false;
}

function dcp_disable_frontend_comments( $open, $post_id ) {
    $post_type = get_post_type( $post_id );
    if ( dcp_is_comments_disabled_for_post_type( $post_type ) ) {
        return false;
    }
    return $open;
}
add_filter( 'comments_open', 'dcp_disable_frontend_comments', 10, 2 );
add_filter( 'pings_open', 'dcp_disable_frontend_comments', 10, 2 );

function dcp_disable_backend_comments() {
    global $pagenow;

    if ( 'edit-comments.php' === $pagenow || 'comment.php' === $pagenow ) {
        wp_die( __( 'Comments are disabled site-wide.', 'disable-comments-pro' ), '', array( 'response' => 403 ) );
    }

    add_action( 'admin_menu', 'dcp_remove_comment_menu' );
    add_action( 'add_meta_boxes', 'dcp_remove_comment_meta_boxes' );
    add_action( 'admin_init', 'dcp_remove_comment_support' );
}
add_action( 'admin_init', 'dcp_disable_backend_comments' );


function dcp_remove_comment_menu() {
    $options = get_option( 'dcp_settings' );
    if ( isset( $options['enabled'] ) && $options['enabled'] ) {
        remove_menu_page( 'edit-comments.php' );
    }
}

function dcp_remove_comment_meta_boxes() {
    $options = get_option( 'dcp_settings' );
    if ( isset( $options['enabled'] ) && $options['enabled'] ) {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $post_type ) {
            if ( dcp_is_comments_disabled_for_post_type( $post_type ) ) {
                remove_meta_box( 'commentdiv', $post_type, 'normal' );
                remove_meta_box( 'commentsdiv', $post_type, 'normal' );
            }
        }
    }
}

function dcp_remove_comment_support() {
    $options = get_option( 'dcp_settings' );
    if ( isset( $options['enabled'] ) && $options['enabled'] ) {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $post_type ) {
             if ( dcp_is_comments_disabled_for_post_type( $post_type ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    }
}
function dcp_add_settings_page() {
    add_options_page(
        'Disable Comments Pro Settings',
        'Disable Comments Pro',
        'manage_options',
        'disable-comments-pro',
        'dcp_settings_page_content' 
    );
}
add_action( 'admin_menu', 'dcp_add_settings_page' );

function dcp_settings_init() {
    register_setting(
        'dcp_settings_group',
        'dcp_settings',
        'dcp_sanitize_settings'
    );

    add_settings_section(
        'dcp_settings_section',
        'Plugin Settings',
        'dcp_settings_section_callback',
        'disable-comments-pro'
    );

    add_settings_field(
        'dcp_enabled',
        'Enable Plugin',
        'dcp_enabled_field_callback',
        'disable-comments-pro',
        'dcp_settings_section'
    );

    add_settings_field(
        'dcp_post_types',
        'Disable Comments For',
        'dcp_post_types_field_callback',
        'disable-comments-pro',
        'dcp_settings_section'
    );
}
add_action( 'admin_init', 'dcp_settings_init' );

function dcp_settings_section_callback() {
    echo '&lt;p&gt;Configure the settings for the Disable Comments Pro plugin.&lt;/p&gt;';
}

function dcp_enabled_field_callback() {
    $options = get_option( 'dcp_settings' );
    $enabled = isset( $options['enabled'] ) ? (bool) $options['enabled'] : false;
    ?>
    &lt;label for="dcp_enabled"&gt;
        &lt;input type="checkbox" name="dcp_settings[enabled]" id="dcp_enabled" value="1" &lt;?php checked( $enabled, true ); ?&gt;&gt;
        Check this box to enable the plugin.
    &lt;/label&gt;
    <?php
}

function dcp_post_types_field_callback() {
    $options = get_option( 'dcp_settings' );
    $selected_post_types = isset( $options['post_types'] ) ? (array) $options['post_types'] : array();
    $post_types = get_post_types( array( 'public' => true ), 'objects' );

    echo '&lt;p&gt;Select the post types for which comments should be disabled:&lt;/p&gt;';
    echo '&lt;ul&gt;';
    foreach ( $post_types as $post_type_object ) {
        $post_type = $post_type_object->name;
        $label = $post_type_object->labels->singular_name;
        $checked = in_array( $post_type, $selected_post_types ) ? 'checked' : '';
        echo "&lt;li&gt;&lt;label&gt;&lt;input type='checkbox' name='dcp_settings[post_types][]' value='{$post_type}' {$checked}&gt; {$label}&lt;/label&gt;&lt;/li&gt;";
    }
    echo '&lt;/ul&gt;';
}

function dcp_sanitize_settings( $input ) {
    $output = array();

    $output['enabled'] = isset( $input['enabled'] ) ? (bool) $input['enabled'] : false;

    if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
        $output['post_types'] = array_map( 'sanitize_text_field', $input['post_types'] );
    } else {
        $output['post_types'] = array();
    }

    return $output;
}

function dcp_settings_page_content() {
    ?>
    &lt;div class="wrap"&gt;
        &lt;h1&gt;Disable Comments Pro Settings&lt;/h1&gt;
        &lt;form method="post" action="options.php"&gt;
            &lt;?php
            settings_fields( 'dcp_settings_group' );
            do_settings_sections( 'disable-comments-pro' );
            submit_button();
            ?&gt;
        &lt;/form&gt;
    &lt;/div&gt;
    <?php
}
