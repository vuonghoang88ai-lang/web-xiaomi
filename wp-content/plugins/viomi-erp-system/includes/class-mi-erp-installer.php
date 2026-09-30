<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Installer {
    public static function install() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mi_serials';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            serial_number varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'in_stock',
            order_id bigint(20) unsigned DEFAULT NULL,
            warehouse_id bigint(20) unsigned DEFAULT NULL,
            purchase_order_id bigint(20) unsigned DEFAULT NULL,
            sold_date datetime DEFAULT NULL,
            activated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY serial_number (serial_number),
            KEY product_id (product_id),
            KEY order_id (order_id),
            KEY warehouse_id (warehouse_id)
        ) $charset_collate;
        ";

        $table_logs = $wpdb->prefix . 'mi_inventory_logs';
        $sql .= "CREATE TABLE $table_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            serial_number varchar(255) NOT NULL,
            action_type varchar(50) NOT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            actor_id bigint(20) unsigned NOT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY serial_number (serial_number),
            KEY action_type (action_type)
        ) $charset_collate;";

        $table_warehouses = $wpdb->prefix . 'mi_warehouses';
        $sql .= "CREATE TABLE $table_warehouses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            location varchar(255) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_transactions = $wpdb->prefix . 'mi_inventory_transactions';
        $sql .= "CREATE TABLE $table_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            ref_type varchar(50) DEFAULT NULL,
            ref_id bigint(20) unsigned DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY ref_type (ref_type),
            KEY ref_id (ref_id)
        ) $charset_collate;";

        $table_tickets = $wpdb->prefix . 'mi_tickets';
        $sql .= "CREATE TABLE $table_tickets (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned DEFAULT NULL,
            customer_phone varchar(20) DEFAULT NULL,
            serial_number varchar(255) DEFAULT NULL,
            issue_type varchar(50) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'open',
            assigned_to bigint(20) unsigned DEFAULT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY customer_phone (customer_phone),
            KEY serial_number (serial_number),
            KEY status (status)
        ) $charset_collate;";

        $table_finance_transactions = $wpdb->prefix . 'mi_transactions';
        $sql .= "CREATE TABLE $table_finance_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            amount decimal(15,2) NOT NULL DEFAULT '0.00',
            ref_type varchar(50) DEFAULT NULL,
            ref_id bigint(20) unsigned DEFAULT NULL,
            description text,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY ref_type (ref_type),
            KEY ref_id (ref_id)
        ) $charset_collate;";

        $table_suppliers = $wpdb->prefix . 'mi_suppliers';
        $sql .= "CREATE TABLE $table_suppliers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            tax_code varchar(50) DEFAULT NULL,
            address text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_pos = $wpdb->prefix . 'mi_purchase_orders';
        $sql .= "CREATE TABLE $table_pos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            supplier_id bigint(20) unsigned NOT NULL,
            total_amount decimal(15,2) NOT NULL DEFAULT '0.00',
            status varchar(50) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY supplier_id (supplier_id),
            KEY status (status)
        ) $charset_collate;";

        $table_po_items = $wpdb->prefix . 'mi_po_items';
        $sql .= "CREATE TABLE $table_po_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            po_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            qty int(11) NOT NULL DEFAULT 1,
            unit_price decimal(15,2) NOT NULL DEFAULT '0.00',
            PRIMARY KEY  (id),
            KEY po_id (po_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        $table_hrm_metrics = $wpdb->prefix . 'mi_employee_metrics';
        $sql .= "CREATE TABLE $table_hrm_metrics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            period varchar(10) NOT NULL,
            total_sales_amount decimal(15,2) NOT NULL DEFAULT '0.00',
            total_commissions decimal(15,2) NOT NULL DEFAULT '0.00',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_period (user_id, period)
        ) $charset_collate;";

        $table_production_orders = $wpdb->prefix . 'mi_production_orders';
        $sql .= "CREATE TABLE $table_production_orders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Create Warranty Check Page if it doesn't exist
        $page_title = 'Tra cứu bảo hành';
        $page_content = '[mi_warranty_check]';
        
        $page_check = get_page_by_title( $page_title );
        if ( ! isset( $page_check->ID ) ) {
            $page = array(
                'post_title'   => $page_title,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'page'
            );
            wp_insert_post( $page );
        }
    }
}
