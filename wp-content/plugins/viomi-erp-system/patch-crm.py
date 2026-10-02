import sys

content = open('includes/modules/crm/views/customer-dashboard.php').read()

if 'crm-tickets-table' not in content:
    ticket_html = '''
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
'''
    content = content.replace('    </div>\n</div>', ticket_html + '\n</div>')
    
    js_logic = '''
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
'''
    content = content.replace("                    $('#crm-results').removeClass('hidden');", js_logic + "\n                    $('#crm-results').removeClass('hidden');")
    
    js_events = '''
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
'''
    content = content.replace('});\n</script>', js_events + '\n});\n</script>')

    open('includes/modules/crm/views/customer-dashboard.php', 'w').write(content)
    print('Updated customer-dashboard.php')
else:
    print('Already updated customer-dashboard.php')
