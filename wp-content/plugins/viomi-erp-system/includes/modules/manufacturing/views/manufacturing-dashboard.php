<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Lệnh Sản Xuất (Manufacturing)</h1>
    <hr class="wp-header-end">
    
    <div class="bg-white p-6 rounded-lg shadow mt-6">
        <div class="flex justify-between items-center border-b pb-2 mb-4">
            <h2 class="text-lg font-bold text-gray-800">Danh sách Lệnh Sản Xuất (Work Orders)</h2>
            <button type="button" id="btn-add-order" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold text-sm">Tạo Lệnh Mới</button>
        </div>

        <form id="form-add-order" class="hidden mb-4 p-4 border rounded bg-gray-50 flex gap-2 items-end">
            <div class="flex-1">
                <label class="block text-sm font-bold mb-1">ID Sản phẩm (WooCommerce Product ID)</label>
                <input type="number" id="mfg-product-id" class="shadow border rounded w-full py-1 px-2" required>
            </div>
            <div class="w-32">
                <label class="block text-sm font-bold mb-1">Số lượng</label>
                <input type="number" id="mfg-qty" class="shadow border rounded w-full py-1 px-2" required min="1" value="1">
            </div>
            <button type="submit" class="bg-blue-600 text-white rounded px-4 py-1 font-bold hover:bg-blue-700 h-[34px]">Tạo Lệnh</button>
        </form>

        <table class="w-full text-left border-collapse" id="mfg-table">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-3 font-semibold text-sm">Mã Lệnh (WO)</th>
                    <th class="p-3 font-semibold text-sm">Sản phẩm Thành phẩm</th>
                    <th class="p-3 font-semibold text-sm">Số lượng</th>
                    <th class="p-3 font-semibold text-sm">Trạng thái</th>
                    <th class="p-3 font-semibold text-sm">Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="p-3 text-center text-gray-500">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadMfgData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_manufacturing',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if (res.success) {
                    let orders = res.data.orders;
                    let html = '';
                    if (orders.length === 0) {
                        html = '<tr><td colspan="5" class="p-3 text-center text-gray-500">Chưa có lệnh sản xuất nào.</td></tr>';
                    } else {
                        orders.forEach(function(o) {
                            let badge = o.status === 'pending' ? '<span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs">Đang chờ</span>' : '<span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs">Hoàn thành</span>';
                            html += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-bold text-sm">WO-#${o.id}</td>
                                <td class="p-3 text-sm">${o.product_name}</td>
                                <td class="p-3 text-sm font-bold">${o.quantity}</td>
                                <td class="p-3 text-sm">${badge}</td>
                                <td class="p-3 text-sm">${o.created_at}</td>
                            </tr>`;
                        });
                    }
                    $('#mfg-table tbody').html(html);
                } else {
                    alert('Lỗi tải dữ liệu');
                }
            }
        });
    }

    loadMfgData();

    $('#btn-add-order').click(function() {
        $('#form-add-order').toggleClass('hidden');
    });

    $('#form-add-order').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_add_production_order',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                product_id: $('#mfg-product-id').val(),
                qty: $('#mfg-qty').val()
            },
            success: function(res) {
                if (res.success) {
                    $('#form-add-order')[0].reset();
                    $('#form-add-order').addClass('hidden');
                    loadMfgData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });
});
</script>
