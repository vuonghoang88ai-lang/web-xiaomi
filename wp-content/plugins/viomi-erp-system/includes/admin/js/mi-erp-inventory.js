jQuery(document).ready(function($) {
    const $form = $('#mi-inventory-action-form');
    const $msgBox = $('#inventory-action-message');
    const $tbody = $('#inventory-table-body');

    // Chặn hành vi submit mặc định (BUG-02)
    $form.on('submit', function(e) {
        e.preventDefault();

        const serialNumber = $('#inventory_serial_number').val().trim();
        const actionType = $('#inventory_action_type').val();

        if (!serialNumber) return;

        $msgBox.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700')
               .addClass('bg-blue-100 text-blue-700')
               .html('Đang xử lý...');

        // NOTE: wp_ajax action requires backend setup in class-mi-erp-inventory.php
        $.ajax({
            url: mi_erp_inventory_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mi_erp_inventory_action', // We will need to implement this in PHP later
                nonce: mi_erp_inventory_data.nonce,
                serial_number: serialNumber,
                action_type: actionType
            },
            success: function(response) {
                if (response.success) {
                    $msgBox.removeClass('bg-blue-100 text-blue-700')
                           .addClass('bg-green-100 text-green-700')
                           .html('Thành công: ' + response.data);
                    $form[0].reset();
                    // Load lại bảng log
                    loadInventoryLogs();
                } else {
                    $msgBox.removeClass('bg-blue-100 text-blue-700')
                           .addClass('bg-red-100 text-red-700')
                           .html('Lỗi: ' + (response.data || 'Không thể thực hiện giao dịch.'));
                }
            },
            error: function() {
                $msgBox.removeClass('bg-blue-100 text-blue-700')
                       .addClass('bg-red-100 text-red-700')
                       .html('Lỗi hệ thống. Vui lòng thử lại sau.');
            }
        });
    });

    // Hàm load dữ liệu log giả định (Cần backend API trả về)
    function loadInventoryLogs() {
        $tbody.html('<tr><td colspan="4" class="px-6 py-4 text-center">Tải dữ liệu thành công (Demo).</td></tr>');
    }
});
