<?php
/*
Plugin Name: Progy Multistore Inventory Management
description: Multiple inventories / warehouses / stores management from a single Woocommerce online store. With this lightweight plugin, you can effectively control as many stock quantities as you want on your products.
Version: 0.51
Author: Progymedia Inc
Author URI: https://www.progymedia.com
License: GPLv2 or later
Text Domain: progy-multi-stores
*/

/**
 * Set constants
 */
if ( ! defined( 'PROGY_MS_FILE' ) ) {
	define( 'PROGY_MS_FILE', __FILE__ );
}

if ( ! defined( 'PROGY_MS_BASE' ) ) {
	define( 'PROGY_MS_BASE', plugin_basename( PROGY_MS_FILE ) );
}

if ( ! defined( 'PROGY_PO_DIR' ) ) {
	define( 'PROGY_PO_DIR', plugin_dir_path( PROGY_MS_FILE ) );
}

if ( ! defined( 'PROGY_MS_URI' ) ) {
	define( 'PROGY_MS_URI', plugins_url( '/', PROGY_MS_FILE ) );
}

class ProgyMultiStores {

    const _POST_TYPE_ = "pmstore";

    private $stores = [];

    static function install() {
        // do not generate any output here


    }

    /*static function deactivate(){

    }

    static function uninstall() {
        //delete custom post
    }*/

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        register_activation_hook( __FILE__, array( $this, 'install' ) );
        //register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        //register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );
        add_action( 'init', array( $this, 'createStorePostType' ) );
        add_action( 'add_meta_boxes_pmstore', array( $this, 'addMetaBoxes' ), 30 );
        add_action( 'save_post', array( $this, 'saveStoreInfo'), 10, 2 );
        add_action( 'woocommerce_product_options_stock_fields', array( $this, 'showStockFields') );
        add_action( 'woocommerce_process_product_meta', array( $this, 'saveStockFields') );
        //add_action( 'woocommerce_order_item_quantity', array($this, 'reduceOrderStock'), 1000, 3);
        add_action( 'woocommerce_can_restore_order_stock', array( $this, 'restoreOrderStock'), 1000, 2);
        add_action( 'woocommerce_can_reduce_order_stock', array( $this, 'reduceOrderStock'), 1000, 2);

        add_filter( 'woocommerce_product_get_stock_quantity', array($this, 'getStockQuantity'), 10, 2);

        add_action( 'woocommerce_checkout_order_processed', array($this, 'updateOrderInfo'), 15, 3 );
        //add_action( 'woocommerce_before_shop_loop', array($this, 'initDefaultStore'), 100); //TODO FIND BETTER hook (woocommerce_loaded doesnt work)
        add_action( 'wp_loaded', array($this, 'initDefaultStore'), 100);
        add_filter( 'woocommerce_product_is_in_stock', array($this, 'isInStock'), 100, 2);
        //add_filter( 'wc_product_post_class', array($this, 'getProductPostClass'), 100, 2);
        add_filter( 'woocommerce_product_get_stock_status', array($this, 'getStockStatus'), 100, 2);


        add_action( 'show_user_profile', array($this, 'show_user_profile_fields') );
        add_action( 'edit_user_profile', array($this, 'show_user_profile_fields') );
        add_action( "user_new_form", array($this, 'show_user_profile_fields'));
        add_action( 'user_register', array($this, 'save_user_profile_fields'));
        add_action( 'personal_options_update', array($this, 'save_user_profile_fields'));
        add_action( 'edit_user_profile_update', array($this, 'save_user_profile_fields'));

        add_action( 'init', array($this, 'load_plugin_language') );

        add_action( 'progymedia_pos_after_shop_loop_item', array($this, 'progymedia_pos_after_shop_loop_item'));

        add_action( 'restrict_manage_posts', array($this, 'restrict_manage_posts'), 100, 2);
        add_filter( 'request', array( $this, 'request' ) );
    }

    function load_plugin_language(){
        $domain = 'progy-multi-stores';
        $mo_file = WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . get_locale() . '.mo';

        load_textdomain( $domain, $mo_file );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function createStorePostType() {

        // Set UI labels for Custom Post Type
        $labels = array(
            'name'                => _x( 'Stores', 'Post Type General Name', 'progy-multi-stores' ),
            'singular_name'       => _x( 'Store', 'Post Type Singular Name', 'progy-multi-stores' ),
            'menu_name'           => __( 'Stores', 'progy-multi-stores' ),
            'parent_item_colon'   => __( 'Parent Store', 'progy-multi-stores' ),
            'all_items'           => __( 'All Stores', 'progy-multi-stores' ),
            'view_item'           => __( 'View Store', 'progy-multi-stores' ),
            'add_new_item'        => __( 'Add New Store', 'progy-multi-stores' ),
            'add_new'             => __( 'Add New', 'progy-multi-stores' ),
            'edit_item'           => __( 'Edit Store', 'progy-multi-stores' ),
            'update_item'         => __( 'Update Store', 'progy-multi-stores' ),
            'search_items'        => __( 'Search Store', 'progy-multi-stores' ),
            'not_found'           => __( 'Not Found', 'progy-multi-stores' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'progy-multi-stores' ),
        );

        // Set other options for Custom Post Type

        $args = array(
            'label'               => __( 'stores', 'progy-multi-stores' ),
            'description'         => __( 'Stores', 'progy-multi-stores' ),
            'labels'              => $labels,
            // Features this CPT supports in Post Editor
            'supports'            => array( 'title', /*'editor', 'excerpt', 'author',*/ 'thumbnail', /*'comments', 'revisions', 'custom-fields',*/ /*'page-attributes'*/ ),
            // You can associate this CPT with a taxonomy or custom taxonomy.
            //'taxonomies'          => array( 'genres' ),
            /* A hierarchical CPT is like Pages and can have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'page',
        );

        // Registering your Custom Post Type
        register_post_type( ProgyMultiStores::_POST_TYPE_, $args );
    }

    public function addMetaBoxes($post){
        remove_meta_box( 'postcustom', ProgyMultiStores::_POST_TYPE_, 'advanced' );
        //remove_meta_box( 'slugdiv', $this->postType, 'advanced' );
        //remove_meta_box( 'pageparentdiv', $this->postType, 'advanced' );

        add_meta_box(
            ProgyMultiStores::_POST_TYPE_.'_address',
            __( 'Address', 'woocommerce' ),
            array( $this, 'renderAddressMetaBox'),
            ProgyMultiStores::_POST_TYPE_, 'normal', 'default'
        );

        add_meta_box(
            ProgyMultiStores::_POST_TYPE_.'_contact',
            __( 'Contact Info' , 'woocommerce' ),
            array( $this, 'renderContactMetaBox'),
            ProgyMultiStores::_POST_TYPE_, 'normal', 'default'
        );

        add_meta_box(
            ProgyMultiStores::_POST_TYPE_.'_receipt',
            __( 'Receipt Options', 'woocommerce' ),
            array( $this, 'renderReceiptMetaBox'),
            ProgyMultiStores::_POST_TYPE_, 'normal', 'default'
        );
    }

    function renderAddressMetaBox($post, $metabox ){
        include 'templates/address.php';
    }

    function renderContactMetaBox($post, $metabox ){
        include 'templates/contact.php';
    }

    function renderReceiptMetaBox($post, $metabox ){
        include 'templates/receipt.php';
    }

    function showStockFields() {
        //error_log("showStockFields");
        $args = array(
            'numberposts' => -1,
            'post_type'=> 'pmstore',
            'order'    => 'ASC'
        );

        $stores = get_posts($args);
        foreach ($stores as $store) {
            $args = array(
                'id' => '_stock_' . $store->ID,
                'label' => __( 'Stock for ' . $store->post_title, 'progymedia' )
            );
            woocommerce_wp_text_input( $args );
        }

    }

    function saveStockFields( $post_id ) {
        //error_log("saveStockFields");

        $args = array(
            'numberposts' => -1,
            'post_type'=> 'pmstore',
            'order'    => 'ASC'
        );

        $product = wc_get_product( $post_id );

        $stores = get_posts($args);
        foreach ($stores as $store) {
            $title = isset( $_POST['_stock_' . $store->ID] ) ? sanitize_text_field($_POST['_stock_' . $store->ID]) : '';
            $product->update_meta_data( '_stock_' . $store->ID, $title );
        }

        $product->save();
    }

    public function reduceOrderStock($canChangeStock,  $order){
        if($canChangeStock){
            foreach ( $order->get_items() as $item ) {
                if ( ! $item->is_type( 'line_item' ) ) {
                    continue;
                }

                $product  = $item->get_product();

                $storeId  = $this->getUserStore();
                if($storeId){
                    $stock = get_post_meta( $product->get_id(), '_stock_' . $storeId, true );
                    update_post_meta($product->get_id(), '_stock_' . $storeId, $stock - $item->get_quantity());
                }
            }
        }

        return $canChangeStock;
    }
    public function restoreOrderStock($canChangeStock, $order){
        if($canChangeStock){
            foreach ( $order->get_items() as $item ) {
                if ( ! $item->is_type( 'line_item' ) ) {
                    continue;
                }

                $product  = $item->get_product();

                $storeId  = $this->getUserStore();
                if($storeId){
                    $stock = get_post_meta( $product->get_id(), '_stock_' . $storeId, true );
                    update_post_meta($product->get_id(), '_stock_' . $storeId, $stock + $item->get_quantity());
                }
            }
        }

        return $canChangeStock;
    }

    function getStockQuantity($value, $product){
        $storeId  = $this->getUserStore();
        //error_log("getStockQuantity : " . $storeId);

        if($storeId){
            $value = get_post_meta($product->get_id(), '_stock_' . $storeId, true);
        }

        return $value;
    }

    function getUserStore(){
        //Check user meta
        if(is_user_logged_in()){
            $storeId = get_user_meta(get_current_user_id(), '_store_id',true);
            if($storeId && $storeId != ""){
                return $storeId;
            }
        }
        //Check session
        if(WC()->session){
            $storeId  = WC()->session->get( 'current_pmstore' );
            if($storeId){
                return $storeId;
            }
        }

        return false;
    }

    function initDefaultStore(){
        $args = array(
            'numberposts' => -1,
            'post_type'=> 'pmstore',
            'orderby' => 'title',
            'order'    => 'ASC'
        );
        $this->stores = get_posts($args);

        if(WC()->session){
            $storeId  = WC()->session->get( 'current_pmstore' );
            //error_log("initDefaultStore : " . $storeId);
            if(!$storeId){
                //Load stock from first store
                $args = array(
                    'numberposts' => 1,
                    'post_type'=> 'pmstore',
                    'order'    => 'ASC'
                );
                $stores = get_posts($args);
                foreach ($stores as $store) {
                    WC()->session->set( 'current_pmstore' , $store->ID );
                }
            }
        }
    }

    function isInStock($stock_status, $product){
        //Override only if stock management is checked
        if($product->managing_stock()){
            $storeId  = $this->getUserStore();
            if($storeId){
                $value = get_post_meta($product->get_id(), '_stock_' . $storeId, true);
                $isInStock = $value > 0;
                if(!$isInStock && $product->backorders_allowed()){
                    return true;
                }
                return $isInStock;
            }
        }
        return $stock_status;
    }

    /*function getProductPostClass($classes, $class = '', $post_id = 0){
        error_log('getProductPostClass');
        if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'product', 'product_variation' ), true ) ) {
            return $classes;
        }

        $product = wc_get_product( $post_id );

        if ( $product  &&
            ($key = array_search($product->get_stock_status(), $classes)) !== false &&
            $product->managing_stock() &&
            WC()->session &&
            $storeId = WC()->session->get( 'current_pmstore' )
        ) {
            unset($classes[$key]);
            $value = get_post_meta($product->get_id(), '_stock_' . $storeId, true);
            if($value > 0){
                $classes[] = 'instock';
            }else{
                $classes[] = 'outofstock';
            }
        }

        return $classes;
    }*/

    function getStockStatus($status, $product){
        //error_log('getStockStatus');

        if ($product->managing_stock() &&
            $storeId = $this->getUserStore()
        ) {
            $value = get_post_meta($product->get_id(), '_stock_' . $storeId, true);
            if($value > 0){
                $status = 'instock';
            }else if($product->backorders_allowed()){
                $status = 'onbackorder';
            }else{
                $status = 'outofstock';
            }
        }

        return $status;
    }

    function updateOrderInfo( $order_id, $posted_data, $order ) {
        $storeId  = $this->getUserStore();
        if($storeId){
            $nextInvoiceId = get_post_meta($storeId, 'pos_next_invoice_id', true);

            add_post_meta($order_id,'_invoice_id', $nextInvoiceId );
            add_post_meta($order_id,'_store_id',$storeId);

            update_post_meta($storeId,'pos_next_invoice_id', $nextInvoiceId+1 );
        }
    }

    function saveStoreInfo($post_id, $post){
        if($post->post_type != ProgyMultiStores::_POST_TYPE_){
            return;
        }
        //templates/address.php
        update_post_meta( $post_id, 'pos_address_1', sanitize_text_field($_POST['pos_address_1']) );
        update_post_meta( $post_id, 'pos_address_2', sanitize_text_field($_POST['pos_address_2']) );
        update_post_meta( $post_id, 'pos_city', sanitize_text_field($_POST['pos_city']) );
        update_post_meta( $post_id, 'pos_state', sanitize_text_field($_POST['pos_state']) );
        update_post_meta( $post_id, 'pos_postcode', sanitize_text_field($_POST['pos_postcode']) );
        update_post_meta( $post_id, 'pos_country', sanitize_text_field($_POST['pos_country']) );
        //templates/contact.php
        update_post_meta( $post_id, 'pos_url', sanitize_text_field($_POST['pos_url']) );
        update_post_meta( $post_id, 'pos_phone', sanitize_text_field($_POST['pos_phone']) );
        update_post_meta( $post_id, 'pos_email', sanitize_email($_POST['pos_email']) );
        //templates/receipt.php
        update_post_meta( $post_id, 'pos_next_invoice_id', sanitize_text_field($_POST['pos_next_invoice_id']) );
        update_post_meta( $post_id, 'pos_personal_notes', sanitize_text_field($_POST['pos_personal_notes']) );
        update_post_meta( $post_id, 'pos_policies_conditions', sanitize_text_field($_POST['pos_policies_conditions']) );
        update_post_meta( $post_id, 'pos_footer_imprint', sanitize_text_field($_POST['pos_footer_imprint']) );
    }

    function show_user_profile_fields( $user ) {
        $args = array(
            'numberposts' => -1,
            'post_type'=> 'pmstore',
            'orderby' => 'title',
            'order'    => 'ASC'
        );
        $stores = get_posts($args);
        ?>
        <h3>Progymedia Multi-Store</h3>
        <table class="form-table">
            <tr>
                <th><label for="pmstore"><?php _e('Store','progy-multi-stores') ?></label></th>
                <td>
                    <select name="pmstore" id="pmstore" >
                        <option value=""><?php _e("Select a store...", "progy-multi-stores")?></option>
                        <?php foreach($stores as $store) { ?>
                            <option value="<?php echo $store->ID; ?>" <?php echo $store->ID == get_user_meta($user->ID, '_store_id', true)?'selected="selected"':''; ?>><?php echo $store->post_title?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    function save_user_profile_fields( $user_id ) {
        if ( !current_user_can( 'edit_user', $user_id ) )
            return false;
        update_user_meta($user_id, '_store_id', sanitize_text_field($_POST['pmstore']) );
    }

    function progymedia_pos_after_shop_loop_item(){

        $storeHeader = [];
        $storeRow = [];
        foreach($this->stores as $store){
            $storeHeader[] = '<th>' . $store->post_title .'</th>';
            $stock = get_post_meta(get_the_ID(),'_stock_'.$store->ID, true);
            if(!$stock){
                $stock = 0;
            }
            $storeRow[] = '<td>' . $stock .'</td>';
        }

        echo '<div id="all-stores-stock-container"><table id="all-stores-stock"><thead><tr>'.implode('',$storeHeader).'</tr></thead><tbody><tr>'.implode('',$storeRow).'</tr></tbody></table></div>';
    }

    function restrict_manage_posts($post_type, $area){
        //ADD store filter on order
        if($area == 'top' && $post_type == 'shop_order'){

            $defaultStore = get_user_meta(get_current_user_id(), "_store_id", true);
            if(!$defaultStore){
                $defaultStore = 0;
            }
            $store_id = isset( $_GET['_store_id'] ) ? (int) $_GET['_store_id'] : $defaultStore;
            $html = '<select name="_store_id" id="filter-by-store">';
            $html .= '<option '. selected( $store_id, 0 , false) .' value="0">' . __( 'All Stores' ) . '</option>';

            $args = array(
                'post_type'=> 'pmstore',
                'orderby' => 'title',
                'order'    => 'ASC',
                'numberposts' => -1
            );
            $stores = get_posts($args);

            foreach ( $stores as $store) {
                $html .= sprintf( "<option %s value='%s'>%s</option>",
                    selected( $store_id, $store->ID, false ),
                    $store->ID,
                    $store->post_title
                );
            }

            $html .= '</select>';

            echo $html;
        }
    }

    function request($query_vars){
        $defaultStore = get_user_meta(get_current_user_id(), "_store_id", true);
        if ( ! empty( $_GET['_store_id'] ) ) {
            $query_vars['meta_query'] = array(
                array(
                    'key'     => '_store_id',
                    'value'   => (int) $_GET['_store_id'],
                    'compare' => '=',
                ),
            );
        }else if(!isset($_GET['_store_id']) && $query_vars['post_type'] == "shop_order" && $defaultStore){
            $query_vars['meta_query'] = array(
                array(
                    'key'     => '_store_id',
                    'value'   => (int) $defaultStore,
                    'compare' => '=',
                ),
            );
        }

        return $query_vars;
    }
}
$progyMultiStores = new ProgyMultiStores();

function add_plugin_admin_options($links) {
	$custom_links = array(
		'<a href="edit.php?post_type=pmstore">Stores</a>');
		
	return array_merge($custom_links, $links);
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_plugin_admin_options');