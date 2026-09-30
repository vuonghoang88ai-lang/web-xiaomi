<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Mua hàng & Quản lý Nhà cung cấp (Procurement)</h1>
    <hr class="wp-header-end">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        
        <!-- Khối Nhà Cung Cấp -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-lg font-bold text-gray-800">Danh sách Nhà Cung Cấp</h2>
                <button type="button" id="btn-add-supplier" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 font-bold text-sm">Thêm Mới</button>
            </div>
            
            <form id="form-add-supplier" class="hidden mb-4 p-4 border border-gray-200 rounded-xl bg-gray-50 shadow-sm transition-all">
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <input type="text" id="sup-name" placeholder="Tên nhà cung cấp *" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <input type="text" id="sup-phone" placeholder="Số điện thoại" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <input type="email" id="sup-email" placeholder="Email" class="col-span-2 shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white rounded-lg py-2 font-bold hover:bg-green-700 transition-colors shadow-sm">Lưu Nhà Cung Cấp</button>
            </form>

            <table class="display cell-border stripe hover text-sm" id="suppliers-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Tên NCC</th>
                        <th>Điện thoại</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="3" class="text-center text-gray-500">Đang tải...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Khối Đơn Đặt Hàng -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-lg font-bold text-gray-800">Đơn Đặt Hàng (PO)</h2>
                <button type="button" id="btn-add-po" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold text-sm shadow-sm transition-colors">Tạo PO Mới</button>
            </div>

            <form id="form-add-po" class="hidden mb-4 p-4 border border-gray-200 rounded-xl bg-gray-50 shadow-sm transition-all">
                <div class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nhà cung cấp</label>
                    <select id="po-supplier" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Chọn nhà cung cấp...</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tổng tiền ước tính (VNĐ)</label>
                    <input type="number" id="po-amount" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required min="1000">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white rounded-lg py-2 font-bold hover:bg-blue-700 transition-colors shadow-sm">Tạo Phiếu Đặt Hàng</button>
            </form>

            <table class="display cell-border stripe hover text-sm" id="pos-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nhà cung cấp</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-center text-gray-500">Đang tải...</td></tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadProcurementData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_procurement',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if (res.success) {
                    let data = res.data;
                    
                    // Fill suppliers
                    let supHtml = '';
                    let supOptions = '<option value="">Chọn nhà cung cấp...</option>';
                    if (data.suppliers.length === 0) {
                        supHtml = '<tr><td colspan="3" class="p-3 text-center text-gray-500">Chưa có NCC nào.</td></tr>';
                    } else {
                        data.suppliers.forEach(function(s) {
                            supHtml += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-2 font-bold text-sm">${s.name}</td>
                                <td class="p-2 text-sm">${s.phone || '-'}</td>
                                <td class="p-2 text-sm">${s.email || '-'}</td>
                            </tr>`;
                            supOptions += `<option value="${s.id}">${s.name}</option>`;
                        });
                    }
                    $('#suppliers-table tbody').html(supHtml);
                    $('#po-supplier').html(supOptions);

                    // Fill POs
                    let poHtml = '';
                    if (data.pos.length === 0) {
                        poHtml = '<tr><td colspan="5" class="text-center text-gray-500">Chưa có PO nào.</td></tr>';
                    } else {
                        data.pos.forEach(function(p) {
                            let statusBadge = p.status === 'draft' ? '<span class="px-2 py-1 bg-gray-200 rounded text-xs font-semibold">Nháp</span>' : '<span class="px-2 py-1 bg-green-200 rounded text-xs font-semibold text-green-800">Hoàn thành</span>';
                            poHtml += `<tr class="border-b hover:bg-gray-50">
                                <td class="font-bold text-sm">#${p.id}</td>
                                <td class="text-sm">${p.supplier_name}</td>
                                <td class="text-sm font-bold text-red-600">${p.total_amount}</td>
                                <td class="text-sm">${statusBadge}</td>
                                <td class="text-sm text-gray-500">${p.created_at}</td>
                            </tr>`;
                        });
                    }
                    $('#pos-table tbody').html(poHtml);

                    // Initialize DataTables
                    if ($.fn.DataTable.isDataTable('#suppliers-table')) {
                        $('#suppliers-table').DataTable().destroy();
                    }
                    $('#suppliers-table').DataTable({
                        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json" }
                    });

                    if ($.fn.DataTable.isDataTable('#pos-table')) {
                        $('#pos-table').DataTable().destroy();
                    }
                    $('#pos-table').DataTable({
                        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json" }
                    });
                }
            }
        });
    }

    loadProcurementData();

    // Toggle Forms
    $('#btn-add-supplier').click(function() {
        $('#form-add-supplier').toggleClass('hidden');
    });
    $('#btn-add-po').click(function() {
        $('#form-add-po').toggleClass('hidden');
    });

    // Add Supplier
    $('#form-add-supplier').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_add_supplier',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                name: $('#sup-name').val(),
                phone: $('#sup-phone').val(),
                email: $('#sup-email').val()
            },
            success: function(res) {
                if (res.success) {
                    $('#form-add-supplier')[0].reset();
                    $('#form-add-supplier').addClass('hidden');
                    loadProcurementData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });

    // Add PO
    $('#form-add-po').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_add_po',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                supplier_id: $('#po-supplier').val(),
                amount: $('#po-amount').val()
            },
            success: function(res) {
                if (res.success) {
                    $('#form-add-po')[0].reset();
                    $('#form-add-po').addClass('hidden');
                    loadProcurementData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });
});
</script>
