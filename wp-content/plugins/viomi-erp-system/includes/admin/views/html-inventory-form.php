<?php
global $wpdb;
$products = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as price
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ORDER BY p.post_title ASC
");

$admin_dir = dirname( dirname( __FILE__ ) );
$select2_css = plugins_url( 'css/select2.min.css', $admin_dir . '/fake.php' );
$select2_js = plugins_url( 'js/vendor/select2.min.js', $admin_dir . '/fake.php' );
?>
<link rel="stylesheet" href="<?php echo esc_url($select2_css); ?>">
<script src="<?php echo esc_url($select2_js); ?>"></script>

<div class="wrap">
    <h1><?php esc_html_e( 'Quản lý kho Serials Mi ERP', 'mi-erp-system' ); ?></h1>
    
    <hr style="margin: 20px 0;">

    <h2><?php esc_html_e( 'Nhập kho sản phẩm (Serials)', 'mi-erp-system' ); ?></h2>
    <form id="mi-erp-inventory-form" method="post" action="">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="product_id"><?php esc_html_e( 'Sản phẩm', 'mi-erp-system' ); ?></label></th>
                    <td>
                        <select id="product_id" name="product_id" style="width:100%; max-width: 400px;" required>
                            <option value=""><?php esc_html_e( '-- Chọn sản phẩm --', 'mi-erp-system' ); ?></option>
                            <?php foreach ( $products as $p ) : 
                                $price = $p->price ? $p->price : 0;
                                $formatted_price = number_format( $price, 0, ',', '.' ) . ' ₫';
                            ?>
                                <option value="<?php echo esc_attr( $p->ID ); ?>">
                                    <?php echo esc_html( $p->post_title . ' (#' . $p->ID . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Tìm kiếm và chọn sản phẩm cần nhập kho.', 'mi-erp-system' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="serial_numbers"><?php esc_html_e( 'Mã Serial', 'mi-erp-system' ); ?></label></th>
                    <td>
                        <textarea id="serial_numbers" name="serial_numbers" rows="8" class="large-text" required placeholder="<?php esc_html_e( 'Mỗi dòng một mã serial...', 'mi-erp-system' ); ?>"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( __( 'Cập nhật kho', 'mi-erp-system' ), 'primary', 'submit_inventory' ); ?>
    </form>

    <div id="mi-erp-import-results" style="display:none; margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff;">
        <h4><?php esc_html_e( 'Kết quả nhập:', 'mi-erp-system' ); ?></h4>
        <ul id="mi-erp-import-log" style="list-style-type: none; margin: 0; padding: 0;"></ul>
    </div>

    <hr style="margin: 40px 0 20px 0;">

    <h2><?php esc_html_e( 'Bảng tra cứu tồn kho (Serials)', 'mi-erp-system' ); ?></h2>
    <table id="mi-erp-inventory-table" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Mã Serial</th>
                <th>Trạng thái</th>
                <th>Mã Đơn hàng</th>
                <th>Ngày bán (Kích hoạt)</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 1. Khởi tạo Select2 trực tiếp
    var $productSelect = $('#product_id');
    if ( $productSelect.length && typeof $.fn.select2 === 'function' ) {
        $productSelect.select2({
            placeholder: 'Nhập tên hoặc mã sản phẩm...',
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Xử lý form submit Cập nhật kho
    var $inventoryForm = $('#mi-erp-inventory-form');
    if ( $inventoryForm.length ) {
        $inventoryForm.on('submit', function(e) {
            e.preventDefault();

            var $submitButton = $inventoryForm.find('input[type="submit"]');
            $submitButton.prop('disabled', true).val('Đang xử lý...');

            var data = {
                action: 'mi_erp_submit_inventory',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                product_id: $('#product_id').val(),
                serial_numbers: $('#serial_numbers').val()
            };

            $.post('<?php echo admin_url("admin-ajax.php"); ?>', data, function( response ) {
                var $resultsDiv = $('#mi-erp-import-results');
                var $logUl = $('#mi-erp-import-log');
                $logUl.empty();

                if ( response.success ) {
                    if ( response.data && response.data.log ) {
                        $.each(response.data.log, function(index, item) {
                            var color = item.status === 'success' ? 'green' : 'red';
                            var icon = item.status === 'success' ? '✓' : '✗';
                            $logUl.append('<li style="color: ' + color + ';"><span style="font-weight:bold;">' + icon + ' ' + item.serial + '</span>: ' + item.message + '</li>');
                        });
                        $resultsDiv.show();
                        $('#serial_numbers').val(''); // Clear textarea
                        
                        // Reload DataTables if it exists
                        if (typeof inventoryDataTable !== 'undefined') {
                            inventoryDataTable.ajax.reload(null, false);
                        }
                    } else {
                        alert( response.data );
                        $('#serial_numbers').val(''); // Clear textarea
                    }
                } else {
                    alert( 'Lỗi: ' + response.data );
                }
            })
            .fail(function() {
                alert('Có lỗi xảy ra khi kết nối với máy chủ.');
            })
            .always(function() {
                $submitButton.prop('disabled', false).val('Cập nhật kho');
            });
        });
    }

    // 3. Khởi tạo DataTables trực tiếp (đảm bảo nó chạy)
    var $inventoryTable = $('#mi-erp-inventory-table');
    if ( $inventoryTable.length && typeof $.fn.DataTable === 'function' ) {
        window.inventoryDataTable = $inventoryTable.DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": '<?php echo admin_url("admin-ajax.php"); ?>',
                "type": "POST",
                "data": function ( d ) {
                    d.action = 'mi_erp_get_inventory';
                    d.nonce = '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>';
                }
            },
            "columns": [
                { "data": 0 },
                { "data": 1 },
                { "data": 2 },
                { "data": 3 },
                { "data": 4 },
                { "data": 5 }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
            }
        });
    }
});
</script>
