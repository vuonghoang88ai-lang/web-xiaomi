jQuery(document).ready(function($) {
    const $form = $('#mi-warranty-check-form');
    const $resultBox = $('#mi-warranty-result');

    $form.on('submit', function(e) {
        // Tránh BUG-02: Chặn hành vi submit mặc định gây chớp màn hình tải lại
        e.preventDefault();

        const serialNumber = $('#mi_serial_number').val().trim();
        if (!serialNumber) return;

        // Hiển thị trạng thái đang tải
        $resultBox.removeClass('hidden').html('<p class="text-slate-500 animate-pulse">Đang tra cứu dữ liệu, vui lòng đợi...</p>');

        // Call AJAX
        $.ajax({
            url: mi_erp_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'mi_erp_frontend_check_warranty',
                nonce: mi_erp_frontend.nonce,
                serial_number: serialNumber
            },
            success: function(response) {
                $resultBox.empty(); // Xóa trạng thái loading

                if (response.success && response.data.length > 0) {
                    // Trả về thành công
                    let html = '<h4 class="font-bold text-slate-800 text-lg border-b border-slate-200 pb-3 mb-4">Kết quả tra cứu (' + response.data.length + ' sản phẩm)</h4>';
                    html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

                    response.data.forEach(function(item) {
                        const statusClass = item.is_expired ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700';
                        const statusIcon = item.is_expired ? 'Hết hạn bảo hành' : 'Đang bảo hành';

                        html += `
                            <div class="p-4 rounded-lg border ${statusClass}">
                                <h5 class="font-bold text-slate-800 mb-2">${item.product_name}</h5>
                                <ul class="text-sm space-y-1 text-slate-600 list-none p-0 m-0 mb-3">
                                    <li><strong>S/N:</strong> ${item.serial_number}</li>
                                    <li><strong>Kích hoạt:</strong> ${item.sold_date}</li>
                                    <li><strong>Hết hạn:</strong> ${item.expire_date}</li>
                                </ul>
                                <span class="inline-block px-2 py-1 rounded-md bg-white border ${statusClass} text-xs font-bold uppercase">${statusIcon}</span>
                            </div>
                        `;
                    });

                    html += '</div>';
                    $resultBox.html(html);

                } else {
                    // Lỗi nghiệp vụ (Không tìm thấy, v.v...)
                    $resultBox.html(`<div class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-200"><strong>Lỗi:</strong> ${response.data || 'Không tìm thấy thông tin.'}</div>`);
                }
            },
            error: function() {
                // Lỗi HTTP / Server
                $resultBox.html('<div class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-200"><strong>Lỗi kết nối:</strong> Không thể tra cứu lúc này.</div>');
            }
        });
    });
});
