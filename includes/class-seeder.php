<?php
namespace WeDevs\Dokan\DevTools;

/**
 * Seeder Class
 */
class Seeder {

    public function __construct() {
        add_action( 'dokan_devtools_adminbar', [ $this, 'admin_bar' ] );

        add_action( 'wp_ajax_dokan_create_vendors', [ $this, 'create_vendors' ] );
    }

    public function admin_bar( $admin_bar ) {
        $admin_bar->add_menu( [
            'parent' => 'dokan_tools',
            'id'     => 'dokan_tools_create_user',
            'title'  => 'Create Vendors',
            'href'   => admin_url( 'admin-ajax.php?action=dokan_create_vendors' ),
            'meta' => [
                'onclick' => 'return confirm("Are you sure?");'
            ]
        ] );

    }

    public function create_vendors() {

        if ( current_user_can( 'manage_options' ) ) {
            $this->run();
            $this->redirect();
        }
    }

    public function mapping() {
        $maps = [
            [
                'user' => [
                    'username'   => 'apple',
                    'email'      => 'contact@fakeapple.com',
                    'password'   => 'admin',
                    'nicename'   => 'apple',
                    'first_name' => 'Apple',
                    'last_name'  => 'Store',
                    'store'      => [
                        'store_name' => 'Apple Store',
                        'social'     => [],
                        'payment'    => [ 'paypal' => [], 'bank' => [] ],
                        'banner'     => 0,
                        'icon'       => 0,
                        'phone'      => '+18002752273',
                        'show_email' => 'no',
                        'location'   => '',
                        'address'    => [
                            'street_1' => '1 Infinite Loop',
                            'street_2' => '',
                            'city'     => 'Cupertino',
                            'zip'      => '95014',
                            'country'  => 'US',
                            'state'    => 'CA'
                        ]
                    ]
                ],
                'products' => [
                    [
                        'type'              => ['simple'],
                        'name'              => '',
                        'regular_price'     => '',
                        'sale_price'        => '',
                        'sku'               => '',
                        'visibility'        => 'visible',
                        'description'       => '',
                        'short_description' => '',
                        'tax_status'        => 'taxable',
                        'in_stock'          => 1,
                        'stock'             => '',
                        'categories'        => '',
                        'attributes'        => []
                    ]
                ]
            ],
            [
                'user' => [
                    'username'   => 'google',
                    'email'      => 'contact@fakegoogle.com',
                    'password'   => 'admin',
                    'nicename'   => 'google',
                    'first_name' => 'Google',
                    'last_name'  => 'Store',
                    'store'      => [
                        'store_name' => 'Google Store',
                        'social'     => [],
                        'payment'    => [ 'paypal' => [], 'bank' => [] ],
                        'banner'     => 0,
                        'icon'       => 0,
                        'phone'      => '+18002752273',
                        'show_email' => 'no',
                        'location'   => '',
                        'address'    => [
                            'street_1' => '1600 Amphitheatre Parkway',
                            'street_2' => '',
                            'city'     => 'Mountain View',
                            'zip'      => '94043',
                            'country'  => 'US',
                            'state'    => 'CA'
                        ]
                    ]
                ],
                'products' => [
                    [
                        'type'              => ['simple'],
                        'name'              => '',
                        'regular_price'     => '',
                        'sale_price'        => '',
                        'sku'               => '',
                        'visibility'        => 'visible',
                        'description'       => '',
                        'short_description' => '',
                        'tax_status'        => 'taxable',
                        'in_stock'          => 1,
                        'stock'             => '',
                        'categories'        => '',
                        'attributes'        => []
                    ]
                ]
            ],
        ];

        return $maps;
    }

    public function run() {
        $vendors = $this->mapping();

        foreach ( $vendors as $vendor ) {
            $user = $vendor['user'];

            $vendor_id = wp_insert_user( [
                'user_login'   => $user['username'],
                'user_pass'    => $user['password'],
                'user_email'   => $user['email'],
                'role'         => 'seller',
                'first_name'   => $user['first_name'],
                'last_name'    => $user['last_name'],
                'display_name' => $user['store']['store_name']
            ] );

            update_user_meta( $vendor_id, 'dokan_profile_settings', $user['store'] );
        }
    }

    public function redirect() {
        wp_redirect( $_SERVER["HTTP_REFERER"] );
        exit;
    }

}
