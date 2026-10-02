<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_HRM_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_load_hrm', array( $this, 'load_hrm_data' ) );
        add_action( 'wp_ajax_mi_erp_save_hrm_deductions', array( $this, 'save_hrm_deductions' ) );
        add_action( 'wp_ajax_mi_erp_load_salary_settings', array( $this, 'load_salary_settings' ) );
        add_action( 'wp_ajax_mi_erp_save_salary_settings', array( $this, 'save_salary_settings' ) );
        add_action( 'wp_ajax_mi_erp_add_employee', array( $this, 'ajax_add_employee' ) );
        add_action( 'wp_ajax_mi_erp_load_employees_list', array( $this, 'load_employees_list' ) );
        add_action( 'wp_ajax_mi_erp_toggle_employee_status', array( $this, 'toggle_employee_status' ) );
        add_action( 'wp_ajax_mi_erp_edit_employee', array( $this, 'edit_employee' ) );
        add_action( 'wp_ajax_mi_erp_load_departments', array( $this, 'load_departments' ) );
        add_action( 'wp_ajax_mi_erp_save_departments', array( $this, 'save_departments' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function load_hrm_data() {
        $this->check_permission();
        global $wpdb;

        $period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : date('Y-m');
        
        // Tự động đồng bộ và tính toán lại KPI cho kỳ này trước khi load
        $this->sync_metrics( $period );
        
        $table_metrics = $wpdb->prefix . 'mi_employee_metrics';
        $metrics = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_metrics WHERE period = %s", $period ) );
        
        // Ensure all users who are authors/shop managers have a record
        $users = get_users( array( 'role__in' => array( 'administrator', 'shop_manager', 'author', 'editor' ) ) );
        
        $filter_dept = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : 'all';
        $filter_status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active';

        $formatted_users = array();
        foreach ( $users as $u ) {
            $user_id = $u->ID;
            
            $user_status = get_user_meta( $user_id, '_mi_erp_status', true );
            if ( empty($user_status) ) $user_status = 'active';
            
            $user_dept = get_user_meta( $user_id, '_mi_erp_department', true );
            if ( empty($user_dept) ) $user_dept = 'Chưa phân bổ';

            $exclude_payroll = get_user_meta( $user_id, '_mi_erp_exclude_payroll', true );
            if ( $exclude_payroll === '1' ) {
                continue;
            }

            if ( $filter_status !== 'all' && $user_status !== $filter_status ) {
                continue;
            }
            if ( $filter_dept !== 'all' && $user_dept !== $filter_dept ) {
                continue;
            }

            $m = null;
            foreach ( $metrics as $metric ) {
                if ( $metric->user_id == $user_id ) {
                    $m = $metric;
                    break;
                }
            }

            if ( ! $m ) {
                $m = (object) array(
                    'total_sales_amount'    => 0,
                    'total_cost_amount'     => 0,
                    'total_shipping_amount' => 0,
                    'total_commissions'     => 0,
                    'deductions'            => 0
                );
            }

            $base_salary = get_user_meta( $user_id, '_mi_erp_base_salary', true );
            $base_salary = $base_salary !== '' ? floatval( $base_salary ) : 0;
            
            $total_income = $base_salary + floatval($m->total_commissions) - floatval($m->deductions);

            $formatted_users[] = array(
                'id'           => $user_id,
                'name'         => $u->display_name,
                'email'        => $u->user_email,
                'sales'        => wc_price( $m->total_sales_amount ),
                'cost'         => wc_price( $m->total_cost_amount ?? 0 ),
                'shipping'     => wc_price( $m->total_shipping_amount ?? 0 ),
                'base_salary'  => wc_price( $base_salary ),
                'commissions'  => wc_price( $m->total_commissions ),
                'deductions'   => floatval($m->deductions ?? 0),
                'total_income' => wc_price( $total_income )
            );
        }

        wp_send_json_success( array(
            'period' => $period,
            'users' => $formatted_users
        ) );
    }

    private function sync_metrics( $period ) {
        global $wpdb;
        $table_metrics = $wpdb->prefix . 'mi_employee_metrics';
        $ticket_table = $wpdb->prefix . 'mi_tickets';

        $start_date = $period . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Auto upgrade schema if missing
        $columns = $wpdb->get_col( "DESC $table_metrics", 0 );
        if ( ! in_array( 'deductions', $columns ) ) {
            $wpdb->query("ALTER TABLE $table_metrics ADD COLUMN deductions decimal(15,2) NOT NULL DEFAULT '0.00', ADD COLUMN total_cost_amount decimal(15,2) NOT NULL DEFAULT '0.00', ADD COLUMN total_shipping_amount decimal(15,2) NOT NULL DEFAULT '0.00'");
        }

        // 1. Lấy Doanh số từ WooCommerce bằng wc_get_orders (Tương thích HPOS)
        $args = array(
            'status'       => array( 'completed', 'processing' ),
            'date_created' => $start_date . '...' . $end_date,
            'limit'        => -1, // Lấy tất cả
            'return'       => 'objects',
        );

        $orders = wc_get_orders( $args );

        $user_raw_data = array();
        foreach ( $orders as $order ) {
            $emp_id = $order->get_meta( '_mi_erp_sales_employee' );
            if ( ! empty( $emp_id ) ) {
                $uid = intval( $emp_id );
                if ( ! isset( $user_raw_data[$uid] ) ) {
                    $user_raw_data[$uid] = array('sales' => 0, 'shipping' => 0, 'cost' => 0);
                }
                
                $order_total = floatval( $order->get_total() );
                $shipping = floatval( $order->get_shipping_total() );
                
                $order_cost = 0;
                foreach ( $order->get_items() as $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        // Tính giá vốn (Ưu tiên _wc_cog_cost, dự phòng _mi_cost)
                        $cost = $product->get_meta( '_wc_cog_cost' );
                        if ( empty($cost) ) $cost = $product->get_meta( '_mi_cost' );
                        $order_cost += floatval($cost) * $item->get_quantity();
                    }
                }

                $user_raw_data[$uid]['sales'] += $order_total;
                $user_raw_data[$uid]['shipping'] += $shipping;
                $user_raw_data[$uid]['cost'] += $order_cost;
            }
        }

        // 2. Lấy số lượng Ticket đã giải quyết từ phân hệ CRM
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        
        $tickets = $wpdb->get_results( $wpdb->prepare( "
            SELECT assigned_to, COUNT(id) as resolved_count
            FROM $ticket_table
            WHERE status IN ('resolved', 'closed')
            AND created_at >= %s AND created_at <= %s
            GROUP BY assigned_to
        ", $start_datetime, $end_datetime ) );

        $user_tickets = array();
        foreach ( $tickets as $t ) {
            $user_tickets[intval($t->assigned_to)] = intval($t->resolved_count);
        }

        // 3. Tính lương/thưởng cho từng nhân sự
        $users = get_users( array( 'role__in' => array( 'administrator', 'shop_manager', 'author', 'editor' ) ) );
        
        foreach ( $users as $u ) {
            $uid = $u->ID;
            $sales = isset($user_raw_data[$uid]) ? $user_raw_data[$uid]['sales'] : 0;
            $shipping = isset($user_raw_data[$uid]) ? $user_raw_data[$uid]['shipping'] : 0;
            $cost = isset($user_raw_data[$uid]) ? $user_raw_data[$uid]['cost'] : 0;
            $ticket_count = isset($user_tickets[$uid]) ? $user_tickets[$uid] : 0;
            
            $raw_data = array(
                'total_sales'    => $sales,
                'total_shipping' => $shipping,
                'total_cost'     => $cost,
                'ticket_count'   => $ticket_count
            );
            
            // Lấy cấu hình lương/thưởng từ User Meta
            $commission_type = get_user_meta( $uid, '_mi_erp_commission_type', true );
            if ( empty($commission_type) ) $commission_type = 'sales';

            $base_salary = get_user_meta( $uid, '_mi_erp_base_salary', true );
            $base_salary = $base_salary !== '' ? floatval($base_salary) : 0;

            $commission_rate = get_user_meta( $uid, '_mi_erp_commission_rate', true );
            $commission_rate = $commission_rate !== '' ? (floatval($commission_rate) / 100) : 0;

            $ticket_bonus = get_user_meta( $uid, '_mi_erp_ticket_bonus', true );
            $ticket_bonus = $ticket_bonus !== '' ? floatval($ticket_bonus) : 0;
            
            $settings = array(
                'commission_type' => $commission_type,
                'base_salary'     => $base_salary,
                'commission_rate' => $commission_rate,
                'ticket_bonus'    => $ticket_bonus
            );
            
            // Tính hoa hồng mặc định theo Mô hình
            $commission = 0;
            if ( $commission_type === 'sales' ) {
                $commission = ($sales * $commission_rate);
            } elseif ( $commission_type === 'profit' ) {
                $profit = $sales - $cost;
                $commission = $profit > 0 ? ($profit * $commission_rate) : 0;
            } elseif ( $commission_type === 'margin' ) {
                $margin = $sales - $cost - $shipping;
                $commission = $margin > 0 ? ($margin * $commission_rate) : 0;
            }

            $commission += ($ticket_count * $ticket_bonus);

            // GỌI HOOK: Cho phép custom logic can thiệp
            $final_commission = apply_filters( 'mi_erp_calculate_employee_income', $commission, $uid, $raw_data, $settings );

            // Cập nhật vào DB
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_metrics WHERE user_id = %d AND period = %s", $uid, $period ) );
            if ( $exists ) {
                $wpdb->update( $table_metrics, array(
                    'total_sales_amount'    => $sales,
                    'total_cost_amount'     => $cost,
                    'total_shipping_amount' => $shipping,
                    'total_commissions'     => $final_commission
                ), array( 'id' => $exists ), array( '%f', '%f', '%f', '%f' ), array( '%d' ) );
            } else {
                $wpdb->insert( $table_metrics, array(
                    'user_id'               => $uid,
                    'period'                => $period,
                    'total_sales_amount'    => $sales,
                    'total_cost_amount'     => $cost,
                    'total_shipping_amount' => $shipping,
                    'total_commissions'     => $final_commission,
                    'deductions'            => 0
                ), array( '%d', '%s', '%f', '%f', '%f', '%f', '%f' ) );
            }
        }
    }

    public function save_hrm_deductions() {
        $this->check_permission();
        global $wpdb;
        $period = sanitize_text_field( $_POST['period'] );
        $deductions = $_POST['deductions'];
        $table_metrics = $wpdb->prefix . 'mi_employee_metrics';

        if ( is_array( $deductions ) ) {
            foreach ( $deductions as $uid => $val ) {
                $wpdb->update( 
                    $table_metrics, 
                    array( 'deductions' => floatval($val) ), 
                    array( 'user_id' => intval($uid), 'period' => $period ), 
                    array( '%f' ), 
                    array( '%d', '%s' ) 
                );
            }
        }
        wp_send_json_success( 'Đã lưu các khoản khấu trừ/tạm ứng thành công.' );
    }

    public function load_salary_settings() {
        $this->check_permission();
        
        $users = get_users( array( 'role__in' => array( 'administrator', 'shop_manager', 'author', 'editor' ) ) );
        $formatted_users = array();
        
        $filter_dept = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : 'all';
        $filter_status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active';

        foreach ( $users as $u ) {
            $status = get_user_meta( $u->ID, '_mi_erp_status', true );
            if ( empty($status) ) $status = 'active';
            
            $department = get_user_meta( $u->ID, '_mi_erp_department', true );
            if ( empty($department) ) $department = 'Chưa phân bổ';

            $exclude_payroll = get_user_meta( $u->ID, '_mi_erp_exclude_payroll', true );
            if ( $exclude_payroll === '1' ) {
                continue;
            }

            if ( $filter_status !== 'all' && $status !== $filter_status ) {
                continue;
            }
            if ( $filter_dept !== 'all' && $department !== $filter_dept ) {
                continue;
            }

            $base_salary = get_user_meta( $u->ID, '_mi_erp_base_salary', true );
            $commission_rate = get_user_meta( $u->ID, '_mi_erp_commission_rate', true );
            $ticket_bonus = get_user_meta( $u->ID, '_mi_erp_ticket_bonus', true );
            $commission_type = get_user_meta( $u->ID, '_mi_erp_commission_type', true );

            $formatted_users[] = array(
                'id'              => $u->ID,
                'name'            => $u->display_name,
                'department'      => $department !== '' ? $department : 'Chưa phân bổ',
                'commission_type' => $commission_type !== '' ? $commission_type : 'sales',
                'base_salary'     => $base_salary !== '' ? floatval($base_salary) : 0,
                'commission_rate' => $commission_rate !== '' ? floatval($commission_rate) : 0,
                'ticket_bonus'    => $ticket_bonus !== '' ? floatval($ticket_bonus) : 0
            );
        }
        
        wp_send_json_success( $formatted_users );
    }

    public function save_salary_settings() {
        $this->check_permission();
        
        if ( ! isset( $_POST['settings'] ) || ! is_array( $_POST['settings'] ) ) {
            wp_send_json_error( 'Dữ liệu không hợp lệ.' );
        }
        
        foreach ( $_POST['settings'] as $setting ) {
            $user_id = intval( $setting['user_id'] );
            if ( $user_id <= 0 ) continue;
            
            update_user_meta( $user_id, '_mi_erp_department', sanitize_text_field( $setting['department'] ) );
            update_user_meta( $user_id, '_mi_erp_commission_type', sanitize_text_field( $setting['commission_type'] ) );
            update_user_meta( $user_id, '_mi_erp_base_salary', floatval( $setting['base_salary'] ) );
            update_user_meta( $user_id, '_mi_erp_commission_rate', floatval( $setting['commission_rate'] ) );
            update_user_meta( $user_id, '_mi_erp_ticket_bonus', floatval( $setting['ticket_bonus'] ) );
        }
        
        wp_send_json_success( 'Đã lưu cấu hình lương thành công.' );
    }

    public function ajax_add_employee() {
        $this->check_permission();

        $username   = sanitize_user( $_POST['emp_username'] );
        $email      = sanitize_email( $_POST['emp_email'] );
        $password   = $_POST['emp_password']; 
        $fullname   = sanitize_text_field( $_POST['emp_fullname'] );
        $phone      = sanitize_text_field( $_POST['emp_phone'] );
        $role       = sanitize_text_field( $_POST['emp_role'] );
        $department = sanitize_text_field( $_POST['emp_department'] );
        $comm_type  = sanitize_text_field( $_POST['emp_commission_type'] );
        $base_salary= floatval( $_POST['emp_base_salary'] );
        $comm_rate  = floatval( $_POST['emp_commission_rate'] );
        $exclude_payroll = isset($_POST['emp_exclude_payroll']) && $_POST['emp_exclude_payroll'] === '1' ? '1' : '0';

        if ( username_exists( $username ) ) {
            wp_send_json_error( 'Tên đăng nhập này đã tồn tại!' );
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error( 'Email này đã được sử dụng!' );
        }

        $user_id = wp_insert_user( array(
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'first_name' => $fullname,
            'display_name' => $fullname,
            'role'       => $role
        ) );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( 'Lỗi tạo tài khoản: ' . $user_id->get_error_message() );
        }

        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, '_mi_erp_department', $department );
        update_user_meta( $user_id, '_mi_erp_commission_type', $comm_type );
        update_user_meta( $user_id, '_mi_erp_base_salary', $base_salary );
        update_user_meta( $user_id, '_mi_erp_commission_rate', $comm_rate );
        update_user_meta( $user_id, '_mi_erp_exclude_payroll', $exclude_payroll );

        wp_send_json_success( 'Tạo nhân sự thành công! Tài khoản đã được liên kết với hệ thống ERP.' );
    }

    public function load_employees_list() {
        $this->check_permission();
        
        $users = get_users( array( 'role__in' => array( 'administrator', 'shop_manager', 'author', 'editor', 'subscriber' ) ) );
        $formatted_users = array();
        
        global $wp_roles;
        $all_roles = $wp_roles->roles;

        $filter_dept = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : 'all';
        $filter_status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active';

        foreach ( $users as $u ) {
            $department = get_user_meta( $u->ID, '_mi_erp_department', true );
            if ( empty($department) ) $department = 'Chưa phân bổ';

            // Custom logic for status
            $status_code = get_user_meta( $u->ID, '_mi_erp_status', true );
            if ( empty($status_code) ) $status_code = 'active';
            $status = $status_code === 'active' ? 'Đang làm việc' : 'Đã đình chỉ';

            if ( $filter_status !== 'all' && $status_code !== $filter_status ) {
                continue;
            }
            if ( $filter_dept !== 'all' && $department !== $filter_dept ) {
                continue;
            }

            // Get role names
            $user_roles = array();
            foreach ( $u->roles as $role ) {
                if ( isset( $all_roles[$role] ) ) {
                    $user_roles[] = translate_user_role( $all_roles[$role]['name'] );
                }
            }
            $roles_str = !empty($user_roles) ? implode(', ', $user_roles) : 'Không có';
            $formatted_users[] = array(
                'id'              => $u->ID,
                'login'           => $u->user_login,
                'email'           => $u->user_email,
                'name'            => $u->display_name,
                'roles'           => $roles_str,
                'role_key'        => !empty($u->roles) ? $u->roles[0] : '', // Get the primary role key
                'department'      => $department,
                'status'          => $status,
                'status_code'     => $status_code,
                'exclude_payroll' => get_user_meta( $u->ID, '_mi_erp_exclude_payroll', true ) === '1' ? '1' : '0',
                'base_salary'     => get_user_meta( $u->ID, '_mi_erp_base_salary', true ),
                'commission_rate' => get_user_meta( $u->ID, '_mi_erp_commission_rate', true ),
                'commission_type' => get_user_meta( $u->ID, '_mi_erp_commission_type', true )
            );
        }
        
        wp_send_json_success( array( 'users' => $formatted_users ) );
    }

    public function toggle_employee_status() {
        $this->check_permission();
        
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        if ( ! $user_id ) wp_send_json_error( 'ID nhân viên không hợp lệ.' );

        // Không cho phép tự khóa chính mình
        if ( $user_id === get_current_user_id() ) {
            wp_send_json_error( 'Không thể tự khóa tài khoản của chính mình!' );
        }

        $current_status = get_user_meta( $user_id, '_mi_erp_status', true );
        $new_status = ( $current_status === 'inactive' ) ? 'active' : 'inactive';
        
        update_user_meta( $user_id, '_mi_erp_status', $new_status );

        if ( $new_status === 'inactive' ) {
            $sessions = WP_Session_Tokens::get_instance( $user_id );
            $sessions->destroy_all();
        }

        wp_send_json_success( 'Đã cập nhật trạng thái nhân viên thành công.' );
    }

    public function edit_employee() {
        $this->check_permission();
        
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        if ( ! $user_id ) wp_send_json_error( 'ID nhân viên không hợp lệ.' );

        $role       = sanitize_text_field( $_POST['emp_role'] );
        $department = sanitize_text_field( $_POST['emp_department'] );
        $comm_type  = sanitize_text_field( $_POST['emp_commission_type'] );
        $base_salary= floatval( $_POST['emp_base_salary'] );
        $comm_rate  = floatval( $_POST['emp_commission_rate'] );
        $exclude_payroll = isset($_POST['emp_exclude_payroll']) && $_POST['emp_exclude_payroll'] === '1' ? '1' : '0';

        // Update core WP Role
        $user = new WP_User( $user_id );
        $user->set_role( $role );

        // Update Meta
        update_user_meta( $user_id, '_mi_erp_department', $department );
        update_user_meta( $user_id, '_mi_erp_commission_type', $comm_type );
        update_user_meta( $user_id, '_mi_erp_base_salary', $base_salary );
        update_user_meta( $user_id, '_mi_erp_commission_rate', $comm_rate );
        update_user_meta( $user_id, '_mi_erp_exclude_payroll', $exclude_payroll );

        wp_send_json_success( 'Đã cập nhật thông tin nhân viên thành công.' );
    }

    public function load_departments() {
        $this->check_permission();
        $departments = get_option( 'mi_erp_departments', array() );
        wp_send_json_success( $departments );
    }

    public function save_departments() {
        $this->check_permission();
        $departments = isset( $_POST['departments'] ) ? array_map( 'sanitize_text_field', wp_unslash($_POST['departments']) ) : array();
        $departments = array_filter($departments, function($v) { return trim($v) !== ''; });
        $departments = array_values($departments);
        update_option( 'mi_erp_departments', $departments );
        wp_send_json_success( 'Đã lưu danh sách phòng ban thành công.' );
    }
}
new Mi_ERP_HRM_Ajax();
