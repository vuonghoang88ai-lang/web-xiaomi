<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_shipping_init', 'mi_erp_shipping_method_init' );

function mi_erp_shipping_method_init() {
    if ( ! class_exists( 'WC_Shipping_Method' ) ) {
        return;
    }

    class Mi_ERP_Shipping extends WC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'mi_logistics';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Mi Logistics', 'mi-erp-system' );
            $this->method_description = __( 'Phương thức vận chuyển chuyên dụng cho hệ thống Mi ERP.', 'mi-erp-system' );
            $this->supports           = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );

            $this->init();
        }

        public function init() {
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option( 'title' );
            $this->base_cost = $this->get_option( 'base_cost', 0 );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function init_form_fields() {
            $this->instance_form_fields = array(
                'title' => array(
                    'title'       => __( 'Tiêu đề', 'mi-erp-system' ),
                    'type'        => 'text',
                    'description' => __( 'Tiêu đề hiển thị cho khách hàng.', 'mi-erp-system' ),
                    'default'     => __( 'Mi Logistics', 'mi-erp-system' ),
                    'desc_tip'    => true,
                ),
                'base_cost' => array(
                    'title'       => __( 'Phí vận chuyển cơ bản', 'mi-erp-system' ),
                    'type'        => 'number',
                    'description' => __( 'Phí vận chuyển mặc định (VND).', 'mi-erp-system' ),
                    'default'     => 30000,
                    'desc_tip'    => true,
                ),
            );
        }

        public function calculate_shipping( $package = array() ) {
            $cost = floatval( $this->base_cost );
            $has_bulky_item = false;
            $bulky_multiplier = 2; // Nhân đôi phí cho hàng cồng kềnh (Tivi 85 inch)

            // 1. Phân tích giỏ hàng (Routing Logic theo thuộc tính hàng hoá)
            foreach ( $package['contents'] as $item_id => $values ) {
                $product = $values['data'];
                if ( $product ) {
                    $shipping_class_id = $product->get_shipping_class_id();
                    $shipping_class = get_term( $shipping_class_id, 'product_shipping_class' );
                    
                    if ( ! is_wp_error( $shipping_class ) && $shipping_class ) {
                        // Ví dụ: kiểm tra slug shipping class xem có phải loại cồng kềnh/lớn không
                        if ( strpos( $shipping_class->slug, 'cong-kenh' ) !== false || strpos( $shipping_class->slug, '85-inch' ) !== false ) {
                            $has_bulky_item = true;
                        }
                    }
                }
            }

            if ( $has_bulky_item ) {
                $cost = $cost * $bulky_multiplier;
            }
            
            // 2. Logic tính tĩnh dựa theo Vùng (City/State) - Có thể mở rộng API Google Maps sau
            $destination_state = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
            $destination_city = isset( $package['destination']['city'] ) ? $package['destination']['city'] : '';
            
            // Phụ phí ngoại thành hoặc tỉnh xa
            if ( stripos( $destination_city, 'ngoại thành' ) !== false || stripos( $destination_city, 'huyện' ) !== false ) {
                $cost += 50000;
            }

            $rate = array(
                'id'      => $this->id . ( $this->instance_id ? ':' . $this->instance_id : '' ),
                'label'   => $this->title . ( $has_bulky_item ? ' (Hàng cồng kềnh)' : '' ),
                'cost'    => $cost,
                'package' => $package,
            );
            $this->add_rate( $rate );
        }
    }
}

add_filter( 'woocommerce_shipping_methods', 'add_mi_erp_shipping_method' );
function add_mi_erp_shipping_method( $methods ) {
    $methods['mi_logistics'] = 'Mi_ERP_Shipping';
    return $methods;
}
