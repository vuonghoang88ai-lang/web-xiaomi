<div class="wrap">
    <h1><?php esc_html_e( 'Kích hoạt bảo hành thủ công', 'mi-erp-system' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Dành cho các đơn hàng bán ngoài (qua điện thoại, tại cửa hàng) mà không tạo đơn trên WooCommerce.', 'mi-erp-system' ); ?></p>
    
    <hr style="margin: 20px 0;">

    <form id="mi-erp-manual-activate-form" method="post" action="">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="manual_serial"><?php esc_html_e( 'Nhập mã Serial', 'mi-erp-system' ); ?></label></th>
                    <td>
                        <input type="text" id="manual_serial" name="manual_serial" class="regular-text" required placeholder="Ví dụ: SN123456789">
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( __( 'Kích hoạt ngay', 'mi-erp-system' ), 'primary', 'submit_manual_activate' ); ?>
    </form>
</div>
