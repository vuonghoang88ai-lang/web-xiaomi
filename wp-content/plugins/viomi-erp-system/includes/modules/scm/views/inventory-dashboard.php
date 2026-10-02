<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $wpdb;
$warehouses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mi_warehouses WHERE status = 'active'" );
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Tồn kho (SCM)</h1>
    <hr class="wp-header-end">
    
    <div class="mt-4">
        <!-- Tabs Navigation -->
        <ul class="flex border-b" id="inventory-tabs">
            <li class="-mb-px mr-1">
                <a class="bg-white inline-block border-l border-t border-r rounded-t py-2 px-4 text-blue-700 font-semibold cursor-pointer tab-link" data-tab="tab-list">Danh Sách Tồn Kho</a>
            </li>
            <li class="mr-1">
                <a class="bg-white inline-block py-2 px-4 text-gray-500 hover:text-blue-800 font-semibold cursor-pointer tab-link" data-tab="tab-inbound">Nhập Kho</a>
            </li>
            <li class="mr-1">
                <a class="bg-white inline-block py-2 px-4 text-gray-500 hover:text-blue-800 font-semibold cursor-pointer tab-link" data-tab="tab-transfer">Chuyển Kho</a>
            </li>
            <li class="mr-1">
                <a class="bg-white inline-block py-2 px-4 text-gray-500 hover:text-blue-800 font-semibold cursor-pointer tab-link" data-tab="tab-outbound">Xuất Kho Khác</a>
            </li>
        </ul>

        <!-- Tab 0: Danh sách -->
        <div id="tab-list" class="tab-content bg-white p-6 border-l border-r border-b rounded-b-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Danh Sách Hàng Tồn Kho (Đang trong kho)</h2>
            <table class="w-full text-left border-collapse" id="inventory-list-table">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-3 font-semibold">ID</th>
                        <th class="p-3 font-semibold">Sản phẩm</th>
                        <th class="p-3 font-semibold">Mã Serial</th>
                        <th class="p-3 font-semibold">Vị trí (Kho)</th>
                        <th class="p-3 font-semibold">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <!-- Tab 1: Nhập Kho -->
        <div id="tab-inbound" class="tab-content hidden bg-white p-6 border-l border-r border-b rounded-b-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-green-700">Nhập Hàng Vào Kho (Inbound)</h2>
            <form id="inventory-in-form" class="max-w-2xl">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Chọn Kho hàng *</label>
                    <select id="inv-warehouse-id" name="warehouse_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">-- Chọn Kho --</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name . ' - ' . $wh->location); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Sản Phẩm (WooCommerce) *</label>
                    <select id="inv-product-id" name="product_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" style="width:100%;" required>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Giá nhập (Tùy chọn - Cập nhật cho lần bán sau)</label>
                    <input type="number" id="inv-import-price" name="import_price" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="VD: 8000000">
                    <p class="text-xs text-gray-500 mt-1">Sẽ được lưu thành Giá vốn (Cost of Goods) để tính lợi nhuận KPI.</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mã Serial * (Có thể nhập nhiều mã, mỗi mã 1 dòng)</label>
                    <textarea id="inv-serial-number" name="serial_number" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Nhập mã vạch (mỗi mã 1 dòng)..." required></textarea>
                    
                    <div class="mt-2 flex items-center">
                        <span class="text-sm text-gray-500 mr-2">Hoặc nhập từ file (.txt):</span>
                        <input type="file" id="inv-serial-file" accept=".txt" class="text-sm text-gray-700">
                    </div>
                </div>
                <div class="flex items-center">
                    <button type="submit" id="btn-save-inventory" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-bold">Nhập Kho</button>
                    <span class="inv-loading ml-3 hidden text-gray-500">Đang xử lý...</span>
                </div>
                <div class="inv-message mt-4 hidden p-3 rounded font-bold"></div>
            </form>
        </div>

        <!-- Tab 2: Chuyển Kho -->
        <div id="tab-transfer" class="tab-content hidden bg-white p-6 border-l border-r border-b rounded-b-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-blue-700">Chuyển Hàng Giữa Các Kho (Transfer)</h2>
            <form id="inventory-transfer-form" class="max-w-2xl">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Sản Phẩm (Không bắt buộc, dùng để kiểm tra chéo)</label>
                    <select id="trans-product-id" name="product_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" style="width:100%;">
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mã Serial cần chuyển * (Có thể nhập nhiều mã, mỗi mã 1 dòng)</label>
                    <textarea id="trans-serial-number" name="serial_number" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Nhập mã vạch (mỗi mã 1 dòng)..." required></textarea>
                    
                    <div class="mt-2 flex items-center">
                        <span class="text-sm text-gray-500 mr-2">Hoặc nhập từ file (.txt):</span>
                        <input type="file" id="trans-serial-file" accept=".txt" class="text-sm text-gray-700">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kho Đích (Destination) *</label>
                    <select id="trans-warehouse-id" name="warehouse_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">-- Chọn Kho Đích --</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name . ' - ' . $wh->location); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center">
                    <button type="submit" id="btn-transfer-inventory" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Chuyển Kho</button>
                    <span class="inv-loading ml-3 hidden text-gray-500">Đang xử lý...</span>
                </div>
                <div class="inv-message mt-4 hidden p-3 rounded font-bold"></div>
            </form>
        </div>

        <!-- Tab 3: Xuất Kho Khác -->
        <div id="tab-outbound" class="tab-content hidden bg-white p-6 border-l border-r border-b rounded-b-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-orange-700">Xuất Kho Khác (Outbound Manual)</h2>
            <p class="text-sm text-gray-500 mb-4">Tính năng này dùng để xuất kho thủ công (Hàng hỏng, mất mát, hoặc bán ngoài không qua WooCommerce).</p>
            <form id="inventory-outbound-form" class="max-w-2xl">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mã Serial cần xuất *</label>
                    <input type="text" id="out-serial-number" name="serial_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Quét mã vạch sản phẩm" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Lý do xuất *</label>
                    <select id="out-reason" name="reason" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">-- Chọn Lý do --</option>
                        <option value="sold_manual">Bán ngoài (POS/Trực tiếp)</option>
                        <option value="defective">Hàng lỗi / Hỏng</option>
                        <option value="lost">Thất thoát / Mất mát</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <button type="submit" id="btn-outbound-inventory" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 font-bold">Xuất Kho</button>
                    <span class="inv-loading ml-3 hidden text-gray-500">Đang xử lý...</span>
                </div>
                <div class="inv-message mt-4 hidden p-3 rounded font-bold"></div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
        // Xử lý Tabs
    $('.tab-link').click(function() {
        // Xóa class active ở tất cả tab
        $('.tab-link').removeClass('border-l border-t border-r rounded-t text-blue-700').addClass('text-gray-500');
        // Thêm class active cho tab được click
        $(this).removeClass('text-gray-500').addClass('border-l border-t border-r rounded-t text-blue-700');
        
        // Ẩn nội dung
        $('.tab-content').addClass('hidden');
        // Hiện nội dung
        let target = $(this).data('tab');
        $('#' + target).removeClass('hidden');

        if(target === 'tab-list') {
            loadInventoryList();
        }
    });

    // Load danh sách tồn kho
    function loadInventoryList() {
        $('#inventory-list-table tbody').html('<tr><td colspan="5" class="p-3 text-center">Đang tải dữ liệu...</td></tr>');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_get_inventory_list',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if(res.success) {
                    let html = '';
                    if(res.data.length === 0) {
                        html = '<tr><td colspan="5" class="p-3 text-center">Không có sản phẩm nào trong kho.</td></tr>';
                    } else {
                        res.data.forEach(function(item) {
                            html += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-3">${item.id}</td>
                                <td class="p-3 font-semibold">${item.product_name} (#${item.product_id})</td>
                                <td class="p-3 font-mono">${item.serial_number}</td>
                                <td class="p-3">${item.warehouse_name || 'Không xác định'}</td>
                                <td class="p-3"><span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">Trong kho</span></td>
                            </tr>`;
                        });
                    }
                    $('#inventory-list-table tbody').html(html);
                }
            }
        });
    }

    // Load mặc định tab đầu tiên
    loadInventoryList();

    // Hàm tiện ích xử lý AJAX chung
    function handleInventorySubmit(formId, actionName, inputSelectorToFocus) {
        $(formId).submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            let loading = form.find('.inv-loading');
            let msgDiv = form.find('.inv-message');

            loading.removeClass('hidden');
            btn.prop('disabled', true);
            msgDiv.addClass('hidden').removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800 text-orange-800 bg-orange-100');

            let formData = form.serializeArray();
            formData.push({name: 'action', value: actionName});
            formData.push({name: 'nonce', value: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'});

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(res) {
                    loading.addClass('hidden');
                    btn.prop('disabled', false);
                    msgDiv.removeClass('hidden');
                    
                    if(res.success) {
                        msgDiv.addClass('bg-green-100 text-green-800').html('✅ ' + res.data);
                        $(inputSelectorToFocus).val('').focus(); // Reset input serial và focus
                    } else {
                        msgDiv.addClass('bg-red-100 text-red-800').html('❌ Lỗi: ' + res.data);
                    }
                }
            });
        });
    }

    // Gắn sự kiện cho 3 form
    handleInventorySubmit('#inventory-in-form', 'mi_erp_add_inventory', '#inv-serial-number');
    handleInventorySubmit('#inventory-transfer-form', 'mi_erp_transfer_inventory', '#trans-serial-number');
    handleInventorySubmit('#inventory-outbound-form', 'mi_erp_outbound_inventory', '#out-serial-number');

    // Initialize Select2 cho việc tìm kiếm sản phẩm WooCommerce
    if ($.fn.selectWoo || $.fn.select2) {
        var selectFn = $.fn.selectWoo ? $.fn.selectWoo : $.fn.select2;
        var select2Options = {
            placeholder: "Tìm kiếm sản phẩm (Tên, Mã, SKU)...",
            allowClear: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'mi_erp_search_products',
                        nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.success ? data.data.results : []
                    };
                },
                cache: true
            }
        };
        selectFn.call($('#inv-product-id'), select2Options);
        selectFn.call($('#trans-product-id'), $.extend({}, select2Options, {placeholder: "Tìm sản phẩm (Không bắt buộc)..."}));
    }

    // Xử lý đọc file .txt cho Nhập kho
    $('#inv-serial-file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var contents = e.target.result;
            var current = $('#inv-serial-number').val();
            if (current && current.trim() !== '') {
                $('#inv-serial-number').val(current + '\n' + contents);
            } else {
                $('#inv-serial-number').val(contents);
            }
            $('#inv-serial-file').val(''); // Reset input
        };
        reader.readAsText(file);
    });

    // Xử lý đọc file .txt cho Chuyển kho
    $('#trans-serial-file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var contents = e.target.result;
            var current = $('#trans-serial-number').val();
            if (current && current.trim() !== '') {
                $('#trans-serial-number').val(current + '\n' + contents);
            } else {
                $('#trans-serial-number').val(contents);
            }
            $('#trans-serial-file').val(''); // Reset input
        };
        reader.readAsText(file);
    });
});
</script>
