<?php
namespace WeDevs\Dokan\DevTools;

/**
 * Admin bar
 */
class Admin_Bar {

    public function __construct() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 999 );
        add_action( 'wp_ajax_dokan_pro_toggle', [ $this, 'toggle_pro' ], 999 );

        add_action( 'admin_print_styles', [ $this, 'styles' ] );
        add_action( 'wp_print_styles', [ $this, 'styles' ] );
    }

    public function get_branch( $path ) {
        $git_info = @file( $path . '/.git/HEAD', FILE_USE_INCLUDE_PATH );

        if ( ! $git_info ) {
            return '';
        }

        $first_line    = $git_info[ 0 ];
        $branch_string = explode( '/', $first_line, 3 );
        $branch        = isset( $branch_string[2] ) ? $branch_string[2] : substr( $branch_string[0], 0, 7);

        return $branch;
    }

    public function admin_bar( $wp_admin_bar ) {
        $branch = $this->get_branch( DOKAN_DIR );

        $args = array(
            'id'    => 'dokan-dev-tools',
            'title' => sprintf( '<span class="ab-icon"></span>Dokan:<span class="ab-label">%s</span>', $branch ),
        );

        $wp_admin_bar->add_menu( $args );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_admin',
            'title'  => 'Admin Dashboard',
            'href'   => admin_url( 'admin.php?page=dokan' ),
        ) );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_settings',
            'title'  => 'Settings',
            'href'   => admin_url( 'admin.php?page=dokan-settings' ),
        ) );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_pro_status',
            'title'  => sprintf( 'Pro: %s', class_exists( 'Dokan_Pro' ) ? 'Active (' . $this->get_branch( DOKAN_PRO_DIR ) . ')' : 'Not Active' ),
            'href'   => admin_url( 'admin-ajax.php?action=dokan_pro_toggle&redirect_to=' ) . urlencode( $_SERVER['REQUEST_URI'] ),
            'meta'   => [
                'class' => class_exists( 'Dokan_Pro' ) ? 'active' : 'not-active'
            ]
        ) );

        if ( class_exists( 'Dokan_Pro' ) ) {
            $wp_admin_bar->add_menu( array(
                'parent' => 'dokan_pro_status',
                'id'     => 'dokan_modules',
                'title'  => 'Modules',
                'href'   => admin_url( 'admin.php?page=dokan-modules' ),
            ) );

            $wp_admin_bar->add_menu( array(
                'parent' => 'dokan_pro_status',
                'id'     => 'dokan_pro_tools',
                'title'  => 'Tools',
                'href'   => admin_url( 'admin.php?page=dokan-tools' ),
            ) );
        }

        do_action( 'dokan_devtools_adminbar', $wp_admin_bar );

        $wp_admin_bar->add_menu( [
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_repo',
            'title'  => 'Repository',
            'href'   => 'https://github.com/weDevsOfficial/dokan',
            'meta'   => array(
                'target' => '_blank',
            )
        ] );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan_repo',
            'id'     => 'dokan_github_repo',
            'title'  => 'GitHub Repo',
            'href'   => 'https://github.com/weDevsOfficial/dokan',
            'meta'   => array(
                'target' => '_blank',
            )
        ) );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan_repo',
            'id'     => 'dokan_github_issues',
            'title'  => 'GitHub Issues',
            'href'   => 'https://github.com/weDevsOfficial/dokan/issues',
            'meta'   => array(
                'target' => '_blank',
            )
        ) );

        $wp_admin_bar->add_menu( array(
            'parent' => 'dokan_repo',
            'id'     => 'dokan_bitbucket',
            'title'  => 'Bitbucket Repo',
            'href'   => 'https://bitbucket.org/wedevs/dokan-pro',
            'meta'   => array(
                'target' => '_blank',
            )
        ) );

        $wp_admin_bar->add_menu( [
            'parent' => 'dokan-dev-tools',
            'id'     => 'dokan_wporg',
            'title'  => 'WordPress.org',
            'href'   => 'https://wordpress.org/plugins/dokan-lite/',
            'meta'   => array(
                'target' => '_blank',
            )
        ] );

        $wp_admin_bar->add_menu( array(
            'target' => 'blank',
            'parent' => 'dokan_wporg',
            'id'     => 'dokan_wporg_index',
            'title'  => 'WordPress.org',
            'href'   => 'https://wordpress.org/plugins/dokan-lite/',
            'meta'   => array(
                'target' => '_blank',
            )
        ) );

        $wp_admin_bar->add_menu( array(
            'target' => 'blank',
            'parent' => 'dokan_wporg',
            'id'     => 'dokan_wporg_review',
            'title'  => 'Reviews',
            'href'   => 'https://wordpress.org/support/plugin/dokan-lite/reviews/',
            'meta'   => array(
                'target' => '_blank',
            )
        ) );

    }

    public function styles() {
        ?>
        <style type="text/css">
            #wp-admin-bar-dokan-dev-tools .ab-empty-item {
                background-color: #0073aa;
                color: #fff !important;
            }

            #wp-admin-bar-dokan-dev-tools .ab-icon::before {
                content: "\f503";
                color: #fff !important;
                top: 3px;
            }

            #wp-admin-bar-dokan_pro_status a {
                color: #fff;
            }

            #wp-admin-bar-dokan_pro_status.active {
                background-color: #4CAF50;
            }

            #wp-admin-bar-dokan_pro_status.not-active {
                background-color: #F44336;
            }
        </style>
        <?php
    }

    public function toggle_pro() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $dokan_pro = 'dokan-pro/dokan-pro.php';

        if ( ! is_plugin_active( $dokan_pro ) ) {
            activate_plugin( $dokan_pro );
        } else {
            deactivate_plugins( $dokan_pro );
        }

        wp_redirect( $_REQUEST['redirect_to'] );
        exit;
    }
}
