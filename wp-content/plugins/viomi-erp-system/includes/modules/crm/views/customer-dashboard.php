<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Khách hàng (CRM)</h1>
    <hr class="wp-header-end">
    
    <div class="bg-white p-6 rounded-lg shadow mt-4 mb-6">
        <h2 class="text-xl font-semibold mb-4 text-blue-700">Tra cứu thông tin khách hàng</h2>
        <form id="crm-search-form" class="flex gap-4">
            <input type="text" id="crm-phone" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline w-64" placeholder="Nhập số điện thoại..." required>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Tìm kiếm</button>
            <span id="crm-loading" class="ml-3 hidden text-gray-500 flex items-center">Đang tìm...</span>
        </form>
    </div>

    <!-- Kết quả -->
    <div id="crm-results" class="hidden">
        <!-- Thông tin cơ bản -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Thông tin khách hàng</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>Họ tên:</strong> <span id="res-name"></span></div>
                <div><strong>Số điện thoại:</strong> <span id="res-phone"></span></div>
                <div class="col-span-2"><strong>Địa chỉ:</strong> <span id="res-address"></span></div>
                <div class="col-span-2"><strong>Tổng chi tiêu:</strong> <span id="res-total" class="text-green-600 font-bold"></span></div>
            </div>
        </div>

        <!-- Lịch sử mua hàng / Serials -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Lịch sử thiết bị & Bảo hành</h2>
            <table class="w-full text-left border-collapse" id="crm-serials-table">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-3 font-semibold">Đơn hàng</th>
                        <th class="p-3 font-semibold">Sản phẩm</th>
                        <th class="p-3 font-semibold">Mã Serial</th>
                        <th class="p-3 font-semibold">Ngày mua</th>
                        <th class="p-3 font-semibold">Hạn bảo hành</th>
                        <th class="p-3 font-semibold">Trạng thái BH</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <!-- Danh sách Khiếu nại (Tickets) -->
        <div class="bg-white p-6 rounded-lg shadow mt-6">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-lg font-bold text-gray-800">Lịch sử Ticket / Bảo hành</h2>
                <button type="button" id="btn-open-ticket-modal" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold text-sm">Tạo Ticket Mới</button>
            </div>
            <table class="w-full text-left border-collapse" id="crm-tickets-table">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-3 font-semibold">ID</th>
                        <th class="p-3 font-semibold">Ngày tạo</th>
                        <th class="p-3 font-semibold">Mã Serial</th>
                        <th class="p-3 font-semibold">Vấn đề</th>
                        <th class="p-3 font-semibold">Trạng thái</th>
                        <th class="p-3 font-semibold">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal Tạo Ticket -->
    <div id="ticket-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-xl font-bold mb-4">Tạo Ticket Bảo Hành</h2>
            <form id="ticket-form">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mã Serial (tùy chọn)</label>
                    <input type="text" id="ticket-serial" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Loại vấn đề</label>
                    <select id="ticket-issue" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <option value="Bảo hành sửa chữa">Bảo hành sửa chữa</option>
                        <option value="Đổi trả (RMA)">Đổi trả (RMA)</option>
                        <option value="Khiếu nại dịch vụ">Khiếu nại dịch vụ</option>
                        <option value="Tư vấn kỹ thuật">Tư vấn kỹ thuật</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Ghi chú</label>
                    <textarea id="ticket-note" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="btn-close-modal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 font-bold">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Tạo</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    $('#crm-search-form').submit(function(e) {
        e.preventDefault(); // BUG-02: Prevent page reload
        
        let phone = $('#crm-phone').val().trim();
        if(!phone) return;

        $('#crm-loading').removeClass('hidden');
        $('#crm-results').addClass('hidden');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_crm_search',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                phone: phone
            },
            success: function(res) {
                $('#crm-loading').addClass('hidden');
                
                if(res.success) {
                    let data = res.data;
                    
                    // Fill profile
                    $('#res-name').text(data.customer.name || 'N/A');
                    $('#res-phone').text(data.customer.phone || 'N/A');
                    $('#res-address').text(data.customer.address || 'N/A');
                    $('#res-total').html(data.customer.total_spent || '0 ₫');
                    
                    // Fill table
                    let html = '';
                    if(data.serials.length === 0) {
                        html = '<tr><td colspan="6" class="p-3 text-center">Khách hàng chưa mua sản phẩm nào có mã Serial.</td></tr>';
                    } else {
                        data.serials.forEach(function(item) {
                            let wStatus = item.warranty_valid ? '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Còn hạn</span>' : '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Hết hạn</span>';
                            
                            html += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-3"><a href="${item.order_url}" target="_blank" class="text-blue-600 font-bold">#${item.order_id}</a></td>
                                <td class="p-3 font-semibold">${item.product_name}</td>
                                <td class="p-3 font-mono">${item.serial_number}</td>
                                <td class="p-3">${item.sold_date}</td>
                                <td class="p-3 font-bold">${item.warranty_end}</td>
                                <td class="p-3">${wStatus}</td>
                            </tr>`;
                        });
                    }
                    $('#crm-serials-table tbody').html(html);
                    

                    // Fill tickets
                    let tHtml = '';
                    if(!data.tickets || data.tickets.length === 0) {
                        tHtml = '<tr><td colspan="6" class="p-3 text-center">Chưa có ticket nào.</td></tr>';
                    } else {
                        data.tickets.forEach(function(t) {
                            let statusHtml = t.status === 'open' ? '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Đang xử lý</span>' : '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Đã đóng</span>';
                            let actionHtml = t.status === 'open' ? `<button class="text-red-600 font-bold btn-close-ticket" data-id="${t.id}">Đóng</button>` : '';
                            
                            tHtml += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-bold">#${t.id}</td>
                                <td class="p-3">${t.created_at}</td>
                                <td class="p-3 font-mono">${t.serial_number || '-'}</td>
                                <td class="p-3">${t.issue_type}</td>
                                <td class="p-3">${statusHtml}</td>
                                <td class="p-3">${actionHtml}</td>
                            </tr>`;
                        });
                    }
                    $('#crm-tickets-table tbody').html(tHtml);

                    $('#crm-results').removeClass('hidden');
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });

    // Modal events
    $('#btn-open-ticket-modal').click(function() {
        $('#ticket-modal').removeClass('hidden');
    });
    $('#btn-close-modal').click(function() {
        $('#ticket-modal').addClass('hidden');
    });

    // Create ticket
    $('#ticket-form').submit(function(e) {
        e.preventDefault();
        let phone = $('#crm-phone').val().trim();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_crm_create_ticket',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                phone: phone,
                serial: $('#ticket-serial').val(),
                issue: $('#ticket-issue').val(),
                note: $('#ticket-note').val()
            },
            success: function(res) {
                if(res.success) {
                    alert(res.data);
                    $('#ticket-modal').addClass('hidden');
                    $('#crm-search-form').submit(); // Reload data
                    $('#ticket-form')[0].reset();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });

    // Close ticket
    $(document).on('click', '.btn-close-ticket', function() {
        if(!confirm('Bạn có chắc muốn đóng ticket này?')) return;
        let id = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_crm_update_ticket',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                id: id,
                status: 'closed'
            },
            success: function(res) {
                if(res.success) {
                    $('#crm-search-form').submit(); // Reload data
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });

});
</script>
