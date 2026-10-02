<div class="wrap">
    <h1><?php esc_html_e( 'Tổng quan Mi ERP System', 'mi-erp-system' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Bảng điều khiển trung tâm giúp bạn truy cập nhanh tới các chức năng của hệ thống ERP.', 'mi-erp-system' ); ?></p>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 30px;">
        
        <!-- Kho & Kích hoạt -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>📦 Quản lý Kho Serials</h3>
            <p>Nhập hàng loạt mã Serial cho sản phẩm vào hệ thống để tự động xuất kho khi có đơn hàng WooCommerce.</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-inventory' ); ?>" class="button button-primary">Tới trang Nhập kho</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>⚡ Kích hoạt Thủ công</h3>
            <p>Xuất kho và kích hoạt bảo hành cho khách mua trực tiếp (không qua WooCommerce).</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-manual-activate' ); ?>" class="button">Tới trang Kích hoạt</a>
        </div>

        <!-- Các chức năng tự động -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🛒 Tự động hóa Đơn hàng</h3>
            <p>Hệ thống đang tự động bốc Serial gán vào đơn hàng mỗi khi đơn hàng chuyển sang trạng thái <strong>Đang xử lý</strong> hoặc <strong>Hoàn thành</strong>.</p>
            <a href="<?php echo admin_url( 'edit.php?post_type=shop_order' ); ?>" class="button">Xem Đơn hàng</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🚚 Vận chuyển Mi Logistics</h3>
            <p>Phương thức vận chuyển riêng của Mi ERP với tính năng tự động miễn phí vận chuyển cho đơn hàng lớn.</p>
            <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping' ); ?>" class="button">Cấu hình Vận chuyển</a>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🛡️ Cổng Tra cứu Bảo hành</h3>
            <p>Khách hàng có thể lên website nhập mã Serial để kiểm tra thời hạn bảo hành. Bạn cần đặt shortcode <code>[mi_warranty_check]</code> vào một trang bất kỳ.</p>
            <a href="<?php echo admin_url( 'edit.php?post_type=page' ); ?>" class="button">Quản lý Trang</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>👥 Khách hàng & Khiếu nại (CRM)</h3>
            <p>Tra cứu lịch sử mua hàng, mã Serial khách đang sở hữu và quản lý các yêu cầu bảo hành, đổi trả (RMA).</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-crm' ); ?>" class="button button-primary">Mở Quản lý CRM</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>💰 Tài chính & Kế toán</h3>
            <p>Sổ quỹ tiền mặt và ngân hàng. Theo dõi doanh thu, chi phí và lợi nhuận ròng của toàn bộ hệ thống.</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-finance' ); ?>" class="button button-primary">Mở Sổ Quỹ</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🛒 Mua hàng (Procurement)</h3>
            <p>Quản lý danh sách nhà cung cấp và tạo các Đơn đặt hàng (Purchase Orders) để nhập hàng hóa vào kho.</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-procurement' ); ?>" class="button button-primary">Quản lý Mua hàng</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🧑‍💼 Nhân sự & KPI (HRM)</h3>
            <p>Theo dõi hiệu suất bán hàng của từng nhân sự, tính toán hoa hồng (commissions) theo chu kỳ.</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-hrm' ); ?>" class="button button-primary">Quản lý Nhân sự</a>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1; min-width: 300px;">
            <h3>🏭 Sản xuất & Lắp ráp (Mfg)</h3>
            <p>Tạo các Lệnh Sản Xuất (Work Orders) để theo dõi quá trình lắp ráp thành phẩm từ linh kiện.</p>
            <a href="<?php echo admin_url( 'admin.php?page=mi-erp-manufacturing' ); ?>" class="button button-primary">Mở Xưởng Sản Xuất</a>
        </div>

    </div>
</div>
