<?php
namespace WeDevs\Dokan\DevTools;

/**
 * Order Info Class
 */
class Order_Info {

    function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
    }

    public function register_metabox() {
        add_meta_box( 'dokan-vendors', 'Dokan - Vendor Split Info', [ $this, 'render_metabox' ], 'shop_order' );
    }

    public function render_metabox( $post ) {
        if ( $post->post_parent != '0' ) {
            echo '<div style="background-color:#dc3232;color:#fff;padding:10px;">This Order needs to be a parent order.</div>';
            return;
        }

        $o_quntity_total = $o_subtotal = $o_subtotal_tax = $o_total_tax = $o_total =  0;
        $seller_total = array();

        $parent_order = wc_get_order( $post->ID );
        $vendors      = dokan_get_sellers_by( $post->ID );

        printf( '<strong>Got %d Vendors</strong>', count( $vendors ) );

        $wc_shipping      = \WC_Shipping::instance();
        $shipping_methods = $parent_order->get_shipping_methods();
        $methods          = $wc_shipping->get_shipping_method_class_names();

        $coupon_items  = $parent_order->get_items( 'coupon' );
        $order_coupons = $parent_order->get_used_coupons();

        // var_dump( $parent_order->get_items('tax' ) );

        // var_dump( $shipping_methods );

        foreach ($vendors as $seller_id => $seller_products ) {
            $order_total    = 0;
            $shipping_total = 0;
            $tax_total      = 0;
            $discount_total = 0;

            $seller_total[ $seller_id ] = array(
                'shipping'       => 0,
                'shipping_tax'   => 0,
                'discount'       => 0,
                'discount_tax'   => 0,
                'total_tax'      => 0,
                'subtotal'       => 0,
                'subtotal_tax'   => 0,
                'cart_total_tax' => 0,
                'total'          => 0,
            );

            $p_quntity_total = $p_subtotal = $p_subtotal_tax = $p_total_tax = $p_total =  0;

            echo '<h3>Vendor: ' . $seller_id . ' - ' . get_user_by( 'id', $seller_id )->display_name . '</h3>';

            $applied_shipping_method = '';

            if ( $shipping_methods ) {
                foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
                    $shipping_seller_id = wc_get_order_item_meta( $method_item_id, 'seller_id', true );

                    if ( $seller_id == $shipping_seller_id ) {
                        $applied_shipping_method = $shipping_object;
                    }
                }

                if ( $applied_shipping_method ) {
                    $seller_total[ $seller_id ]['shipping']     = $applied_shipping_method->get_total();
                    $seller_total[ $seller_id ]['shipping_tax'] = $applied_shipping_method->get_total_tax();
                }
            }

            $product_ids = array();
            foreach ( $seller_products as $product_item ) {
                $product_ids[] = $product_item->get_product_id();
                $seller_total[ $seller_id ]['subtotal']       += $product_item->get_subtotal();
                $seller_total[ $seller_id ]['subtotal_tax']   += $product_item->get_subtotal_tax();
            }

            if ( $order_coupons ) {
                foreach ($order_coupons as $coupon_code) {
                    $coupon = new \WC_Coupon( $coupon_code );

                    if ( $coupon && !is_wp_error( $coupon ) && array_intersect( $product_ids, $coupon->get_product_ids() ) ) {
                        foreach ( $coupon_items as $coupon_item ) {
                            if ( $coupon_item->get_code() == $coupon_code ) {
                                $seller_total[ $seller_id ]['discount'] += $coupon_item->get_discount();
                                $seller_total[ $seller_id ]['discount_tax'] += $coupon_item->get_discount_tax();
                            }
                        }
                    }
                }
            }


            ?>

            <table class="wp-list-table widefat striped">
                <thead>
                    <th>Product ID</th>
                    <th>Variation ID</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Sub Total Tax</th>
                    <th>Total Tax</th>
                    <th>Total</th>
                </thead>

                <tbody>

                    <?php
                    $shipping_products = array();
                    $packages          = array();

                    foreach ( $seller_products as $item ) {
                        $product = wc_get_product( $item->get_product_id() );

                        $p_quntity_total += $item->get_quantity();
                        $p_subtotal      += $item->get_subtotal();
                        $p_subtotal_tax  += $item->get_subtotal_tax();
                        $p_total_tax     += $item->get_total_tax();
                        $p_total         += $item->get_total();

                        if ( $product->needs_shipping() ) {
                            $shipping_products[] = array(
                                'product_id'        => $item->get_product_id(),
                                'variation_id'      => $item->get_variation_id(),
                                'variation'         => '',
                                'quantity'          => $item->get_quantity(),
                                'data'              => $product,
                                'line_total'        => $item->get_total(),
                                'line_tax'          => $item->get_total_tax(),
                                'line_subtotal'     => $item->get_subtotal(),
                                'line_subtotal_tax' => $item->get_subtotal_tax(),
                            );
                        }
                        ?>
                        <tr>
                            <td><?php echo $item->get_product_id(); ?></td>
                            <td><?php echo $item->get_variation_id(); ?></td>
                            <td><?php echo $item->get_quantity(); ?></td>
                            <td><?php echo $item->get_subtotal(); ?></td>
                            <td><?php echo $item->get_subtotal_tax(); ?></td>
                            <td><?php echo $item->get_total_tax(); ?></td>
                            <td><?php echo $item->get_total(); ?></td>
                        </tr>
                        <?php
                    }

                    $o_quntity_total += $p_quntity_total;
                    $o_subtotal      += $p_subtotal;
                    $o_subtotal_tax  += $p_subtotal_tax;
                    $o_total_tax     += $p_total_tax;
                    $o_total         += $p_total;
                ?>
                </tbody>

                <tfoot>
                    <th colspan="2">&nbsp;</th>
                    <th><?php echo $p_quntity_total; ?></th>
                    <th><?php echo $p_subtotal; ?></th>
                    <th><?php echo $p_subtotal_tax; ?></th>
                    <th><?php echo $p_total_tax; ?></th>
                    <th><?php echo $p_total; ?></th>
                </tfoot>
            </table>
            <?php

            $seller_total[ $seller_id ]['total_tax'] = $seller_total[ $seller_id ]['subtotal_tax'] + $seller_total[ $seller_id ]['shipping_tax'] - $seller_total[ $seller_id ]['discount_tax'];
            $seller_total[ $seller_id ]['total'] = ( $seller_total[ $seller_id ]['subtotal'] + $seller_total[ $seller_id ]['total_tax'] + $seller_total[ $seller_id ]['shipping'] ) - $seller_total[ $seller_id ]['discount'];

            // echo '</table>';
            echo '<hr>';
        }

        // var_dump( $parent_order->get_subtotal() );

        ?>

        <h3>Totals</h3>

        <table class="wp-list-table widefat striped">
            <thead>
                <th>&nbsp;</th>
                <th>Discount</th>
                <th>Discount Tax</th>
                <th>Shipping</th>
                <th>Shipping Tax</th>
                <th>Tax</th>
                <th>Sub Total</th>
                <th>Total</th>
            </thead>
            <tbody>
                <tr>
                    <th>Parent Order</th>
                    <td><?php echo $parent_order->get_discount_total(); ?></td>
                    <td><?php echo $parent_order->get_discount_tax(); ?></td>
                    <td><?php echo $parent_order->get_shipping_total(); ?></td>
                    <td><?php echo $parent_order->get_shipping_tax(); ?></td>
                    <td><?php echo $parent_order->get_total_tax(); ?></td>
                    <td><?php echo $parent_order->get_subtotal(); ?></td>
                    <td><?php echo $parent_order->get_total(); ?></td>
                </tr>

                <?php foreach ( $seller_total as $seller_id => $totals ) { ?>

                    <tr>
                        <th><?php echo get_user_by( 'id', $seller_id )->display_name; ?></th>
                        <td><?php echo $totals['discount']; ?></td>
                        <td><?php echo $totals['discount_tax']; ?></td>
                        <td><?php echo $totals['shipping']; ?></td>
                        <td><?php echo $totals['shipping_tax']; ?></td>
                        <td><?php echo $totals['total_tax']; ?></td>
                        <td><?php echo $totals['subtotal']; ?></td>
                        <td><?php echo $totals['total']; ?></td>
                    </tr>

                <?php } ?>
            </tbody>
            <tfoot class="df_foot">
                <th>&nbsp;</th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'discount' ) );
                    $diff  = $parent_order->get_discount_total() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'discount_tax' ) );
                    $diff  = $parent_order->get_discount_tax() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'shipping' ) );
                    $diff  = $parent_order->get_shipping_total() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'shipping_tax' ) );
                    $diff  = $parent_order->get_shipping_tax() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'total_tax' ) );
                    // $shipping_tax = array_sum( wp_list_pluck( $seller_total, 'shipping_tax' ) );
                    $diff  = $parent_order->get_total_tax() - ( $total );
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'subtotal' ) );
                    $diff  = $parent_order->get_subtotal() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
                <th><?php
                    $total = array_sum( wp_list_pluck( $seller_total, 'total' ) );
                    $diff  = $parent_order->get_total() - $total;
                    $color = $diff == 0 ? 'green' : 'red';

                    printf( '<span style="color: %s">%s (%s)</span>', $color, $total, $diff );
                ?></th>
            </tfoot>
        </table>

        <style>
            .widefat tfoot.df_foot th {
                padding-right: 0 !important;
                padding-left: 0;
            }
        </style>
        <?php
    }
}
