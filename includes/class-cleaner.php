<?php
namespace WeDevs\Dokan\DevTools;

/**
 * Cleaner Class
 */
class Cleaner {

    public function __construct() {
        add_action( 'wp_ajax_dokan_clean_orders', [ $this, 'clean_orders' ] );
        add_action( 'wp_ajax_dokan_clean_products', [ $this, 'clean_products' ] );
        add_action( 'wp_ajax_dokan_clean_vendors', [ $this, 'clean_vendors' ] );

        add_action( 'dokan_devtools_adminbar', [ $this, 'admin_bar' ] );
    }

    public function clean_orders() {

        if ( current_user_can( 'manage_options' ) ) {
            $this->delete_orders();
            $this->delete_orphaned_meta();
        }

        $this->redirect();
    }

    public function clean_products() {

        if ( current_user_can( 'manage_options' ) ) {
            $this->delete_products();
            $this->delete_orphaned_meta();
        }

        $this->redirect();
    }

    public function clean_vendors() {

        if ( current_user_can( 'manage_options' ) ) {
            $vendors = get_users( [
                'role' => 'seller'
            ] );

            if ( $vendors ) {
                foreach ( $vendors as $vendor ) {
                    wp_delete_user( $vendor->ID );
                }
            }
        }

        $this->redirect();
    }

    public function delete_orders() {
        global $wpdb;

        $wpdb->query( "DELETE FROM $wpdb->posts where post_type = 'shop_order'");
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_items");
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta");
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dokan_orders");
    }

    public function delete_products() {
        global $wpdb;

        $wpdb->query( "DELETE FROM $wpdb->posts where post_type = 'product'");
        $wpdb->query( "DELETE FROM $wpdb->posts where post_type = 'product_variation'");
    }

    public function delete_orphaned_meta() {
        global $wpdb;

        $wpdb->query( "DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
    }

    public function redirect() {
        wp_redirect( $_SERVER["HTTP_REFERER"] );
        exit;
    }

    public function admin_bar( $admin_bar ) {
        $admin_bar->add_menu( [
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_tools',
            'title'  => 'Tools',
        ] );

        $admin_bar->add_menu( [
            'parent' => 'dokan_tools',
            'id'     => 'dokan_tools_order',
            'title'  => 'Delete All Orders',
            'href'   => admin_url( 'admin-ajax.php?action=dokan_clean_orders' ),
            'meta' => [
                'onclick' => 'return confirm("Are you sure?");'
            ]
        ] );

        $admin_bar->add_menu( [
            'parent' => 'dokan_tools',
            'id'     => 'dokan_tools_products',
            'title'  => 'Delete All Products',
            'href'   => admin_url( 'admin-ajax.php?action=dokan_clean_products' ),
            'meta' => [
                'onclick' => 'return confirm("Are you sure?");'
            ]
        ] );

        $admin_bar->add_menu( [
            'parent' => 'dokan_tools',
            'id'     => 'dokan_tools_vendors',
            'title'  => 'Delete All Vendors',
            'href'   => admin_url( 'admin-ajax.php?action=dokan_clean_vendors' ),
            'meta' => [
                'onclick' => 'return confirm("Are you sure?");'
            ]
        ] );
    }
}
