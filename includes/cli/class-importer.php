<?php
namespace WeDevs\Dokan\DevTools\Cli;

use \WP_CLI;

/**
 * CSV Importer
 */
class Importer {

    /**
     * The current delimiter for the file being read.
     *
     * @var string
     */
    protected $delimiter = ',';

    function __construct() {
        # code...
    }

    /**
     * Sanitize special column name regex.
     *
     * @param  string $value Raw special column name.
     * @return string
     */
    protected function sanitize_special_column_name_regex( $value ) {
        return '/' . str_replace( array( '%d', '%s' ), '(.*)', quotemeta( $value ) ) . '/';
    }

    /**
     * Get special columns.
     *
     * @param  array $columns Raw special columns.
     * @return array
     */
    protected function get_special_columns( $columns ) {
        $formatted = array();

        foreach ( $columns as $key => $value ) {
            $regex = $this->sanitize_special_column_name_regex( $key );

            $formatted[ $regex ] = $value;
        }

        return $formatted;
    }

    /**
     * Columns to normalize.
     *
     * @param  array $columns List of columns names and keys.
     * @return array
     */
    protected function normalize_columns_names( $columns ) {
        $normalized = array();

        foreach ( $columns as $key => $value ) {
            $normalized[ strtolower( $key ) ] = $value;
        }

        return $normalized;
    }

    /**
     * Auto map column names.
     *
     * @param  array $raw_headers Raw header columns.
     * @param  bool  $num_indexes If should use numbers or raw header columns as indexes.
     * @return array
     */
    protected function auto_map_columns( $raw_headers, $num_indexes = true ) {
        $weight_unit     = get_option( 'woocommerce_weight_unit' );
        $dimension_unit  = get_option( 'woocommerce_dimension_unit' );

        include WC_ABSPATH . 'includes/admin/importers/mappings/mappings.php';

        /**
         * @hooked wc_importer_generic_mappings - 10
         * @hooked wc_importer_wordpress_mappings - 10
         * @hooked wc_importer_default_english_mappings - 100
         */
        $default_columns = $this->normalize_columns_names( apply_filters( 'woocommerce_csv_product_import_mapping_default_columns', array(
            __( 'ID', 'woocommerce' )                                      => 'id',
            __( 'Type', 'woocommerce' )                                    => 'type',
            __( 'SKU', 'woocommerce' )                                     => 'sku',
            __( 'Name', 'woocommerce' )                                    => 'name',
            __( 'Published', 'woocommerce' )                               => 'published',
            __( 'Is featured?', 'woocommerce' )                            => 'featured',
            __( 'Visibility in catalog', 'woocommerce' )                   => 'catalog_visibility',
            __( 'Short description', 'woocommerce' )                       => 'short_description',
            __( 'Description', 'woocommerce' )                             => 'description',
            __( 'Date sale price starts', 'woocommerce' )                  => 'date_on_sale_from',
            __( 'Date sale price ends', 'woocommerce' )                    => 'date_on_sale_to',
            __( 'Tax status', 'woocommerce' )                              => 'tax_status',
            __( 'Tax class', 'woocommerce' )                               => 'tax_class',
            __( 'In stock?', 'woocommerce' )                               => 'stock_status',
            __( 'Stock', 'woocommerce' )                                   => 'stock_quantity',
            __( 'Backorders allowed?', 'woocommerce' )                     => 'backorders',
            __( 'Sold individually?', 'woocommerce' )                      => 'sold_individually',
            sprintf( __( 'Weight (%s)', 'woocommerce' ), $weight_unit )    => 'weight',
            sprintf( __( 'Length (%s)', 'woocommerce' ), $dimension_unit ) => 'length',
            sprintf( __( 'Width (%s)', 'woocommerce' ), $dimension_unit )  => 'width',
            sprintf( __( 'Height (%s)', 'woocommerce' ), $dimension_unit ) => 'height',
            __( 'Allow customer reviews?', 'woocommerce' )                 => 'reviews_allowed',
            __( 'Purchase note', 'woocommerce' )                           => 'purchase_note',
            __( 'Sale price', 'woocommerce' )                              => 'sale_price',
            __( 'Regular price', 'woocommerce' )                           => 'regular_price',
            __( 'Categories', 'woocommerce' )                              => 'category_ids',
            __( 'Tags', 'woocommerce' )                                    => 'tag_ids',
            __( 'Shipping class', 'woocommerce' )                          => 'shipping_class_id',
            __( 'Images', 'woocommerce' )                                  => 'images',
            __( 'Download limit', 'woocommerce' )                          => 'download_limit',
            __( 'Download expiry days', 'woocommerce' )                    => 'download_expiry',
            __( 'Parent', 'woocommerce' )                                  => 'parent_id',
            __( 'Upsells', 'woocommerce' )                                 => 'upsell_ids',
            __( 'Cross-sells', 'woocommerce' )                             => 'cross_sell_ids',
            __( 'Grouped products', 'woocommerce' )                        => 'grouped_products',
            __( 'External URL', 'woocommerce' )                            => 'product_url',
            __( 'Button text', 'woocommerce' )                             => 'button_text',
            __( 'Position', 'woocommerce' )                                => 'menu_order',
        ) ) );

        $special_columns = $this->get_special_columns( $this->normalize_columns_names( apply_filters( 'woocommerce_csv_product_import_mapping_special_columns',
            array(
                __( 'Attribute %d name', 'woocommerce' )     => 'attributes:name',
                __( 'Attribute %d value(s)', 'woocommerce' ) => 'attributes:value',
                __( 'Attribute %d visible', 'woocommerce' )  => 'attributes:visible',
                __( 'Attribute %d global', 'woocommerce' )   => 'attributes:taxonomy',
                __( 'Attribute %d default', 'woocommerce' )  => 'attributes:default',
                __( 'Download %d name', 'woocommerce' )      => 'downloads:name',
                __( 'Download %d URL', 'woocommerce' )       => 'downloads:url',
                __( 'Meta: %s', 'woocommerce' )              => 'meta:',
            )
        ) ) );

        $headers = array();
        foreach ( $raw_headers as $key => $field ) {
            $field             = strtolower( $field );
            $index             = $num_indexes ? $key : $field;
            $headers[ $index ] = $field;

            if ( isset( $default_columns[ $field ] ) ) {
                $headers[ $index ] = $default_columns[ $field ];
            } else {
                foreach ( $special_columns as $regex => $special_key ) {
                    if ( preg_match( $regex, $field, $matches ) ) {
                        $headers[ $index ] = $special_key . $matches[1];
                        break;
                    }
                }
            }
        }

        return apply_filters( 'woocommerce_csv_product_import_mapped_columns', $headers, $raw_headers );
    }

    public function import() {
        $file = WC_ABSPATH . 'sample-data/sample_products.csv';
        WP_CLI::log( 'Starting Import:' . $file . '...' );

        if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
            require WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
        }

        // print_r( $this->auto_map_columns() ); exit;

        $args = array(
            'update_existing'  => true,
            'prevent_timeouts' => false,
            'parse'           => true,
        );

        $tmp = new \WC_Product_CSV_Importer( $file );

        $headers  = $tmp->get_raw_keys();
        $columns = $this->auto_map_columns( $headers );
        // $sample   = current( $importer->get_raw_data() );
        $args['mapping'] = $columns;
        $importer = new \WC_Product_CSV_Importer( $file, $args );
        $results  = $importer->import();
        $percent_complete = $importer->get_percent_complete();
        // print_r( $importer->get_raw_data() );
        // print_r( $headers );
        print_r( $results );
        print_r( $percent_complete );
    }
}

WP_CLI::add_command( 'dokan import', array( __NAMESPACE__ . '\Importer', 'import' ) );
