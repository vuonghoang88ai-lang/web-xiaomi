<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Tài chính & Kế toán (Finance)</h1>
    <hr class="wp-header-end">
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 mb-6">
        <!-- Doanh thu -->
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Tổng Doanh Thu</h3>
            <p class="text-3xl font-bold text-green-600 mt-2" id="fin-total-income">0 ₫</p>
        </div>
        <!-- Chi phí -->
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Tổng Chi Phí</h3>
            <p class="text-3xl font-bold text-red-600 mt-2" id="fin-total-expense">0 ₫</p>
        </div>
        <!-- Lợi nhuận -->
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Lợi Nhuận Ròng</h3>
            <p class="text-3xl font-bold text-blue-600 mt-2" id="fin-net-profit">0 ₫</p>
        </div>
    </div>

    <!-- Sổ quỹ (Transactions) -->
    <div class="bg-white p-6 rounded-lg shadow mt-6">
        <div class="flex justify-between items-center border-b pb-2 mb-4">
            <h2 class="text-lg font-bold text-gray-800">Sổ Quỹ Tiền Mặt & Ngân Hàng</h2>
            <div class="flex gap-2">
                <button type="button" id="btn-sync-finance" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 font-bold text-sm">Đồng bộ Đơn hàng cũ</button>
                <button type="button" id="btn-open-transaction-modal" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold text-sm">Ghi chép thu chi</button>
            </div>
        </div>
        <table class="w-full text-left border-collapse" id="finance-transactions-table">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-3 font-semibold">ID</th>
                    <th class="p-3 font-semibold">Ngày</th>
                    <th class="p-3 font-semibold">Loại GD</th>
                    <th class="p-3 font-semibold">Số tiền</th>
                    <th class="p-3 font-semibold">Nguồn/Tham chiếu</th>
                    <th class="p-3 font-semibold">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="p-3 text-center text-gray-500">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Giao Dịch -->
<div id="transaction-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-4">Ghi chép Thu / Chi mới</h2>
        <form id="transaction-form">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Loại giao dịch</label>
                <select id="trans-type" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <option value="income">Phiếu Thu (Income)</option>
                    <option value="expense">Phiếu Chi (Expense)</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Số tiền (VNĐ)</label>
                <input type="number" id="trans-amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required min="1000">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nguồn / Tham chiếu (VD: Đơn hàng #123)</label>
                <input type="text" id="trans-ref" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Lý do / Ghi chú</label>
                <textarea id="trans-desc" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-close-transaction-modal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 font-bold">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadFinanceData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_finance_load',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if (res.success) {
                    let data = res.data;
                    $('#fin-total-income').html(data.total_income);
                    $('#fin-total-expense').html(data.total_expense);
                    $('#fin-net-profit').html(data.net_profit);
                    
                    let html = '';
                    if (!data.transactions || data.transactions.length === 0) {
                        html = '<tr><td colspan="6" class="p-3 text-center text-gray-500">Chưa có giao dịch nào.</td></tr>';
                    } else {
                        data.transactions.forEach(function(t) {
                            let typeHtml = t.type === 'income' ? '<span class="text-green-600 font-bold">Thu</span>' : '<span class="text-red-600 font-bold">Chi</span>';
                            let amountHtml = t.type === 'income' ? '<span class="text-green-600">+' + t.amount_formatted + '</span>' : '<span class="text-red-600">-' + t.amount_formatted + '</span>';
                            
                            html += `<tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-bold">#${t.id}</td>
                                <td class="p-3">${t.date}</td>
                                <td class="p-3">${typeHtml}</td>
                                <td class="p-3 font-mono font-bold">${amountHtml}</td>
                                <td class="p-3">${t.ref_type || '-'} ${t.ref_id ? '#' + t.ref_id : ''}</td>
                                <td class="p-3">${t.description}</td>
                            </tr>`;
                        });
                    }
                    $('#finance-transactions-table tbody').html(html);
                }
            }
        });
    }

    loadFinanceData();

    $('#btn-open-transaction-modal').click(function() {
        $('#transaction-modal').removeClass('hidden');
    });

    $('#btn-close-transaction-modal').click(function() {
        $('#transaction-modal').addClass('hidden');
    });

    $('#btn-sync-finance').click(function() {
        if (!confirm('Hệ thống sẽ quét và ghi nhận doanh thu từ các đơn hàng WooCommerce (trạng thái Hoàn thành/Đang xử lý) cũ chưa có trong sổ quỹ. Tiếp tục?')) {
            return;
        }
        var $btn = $(this);
        $btn.text('Đang đồng bộ...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_finance_sync',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if (res.success) {
                    alert('Đồng bộ thành công! ' + res.data);
                    loadFinanceData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            complete: function() {
                $btn.text('Đồng bộ Đơn hàng cũ').prop('disabled', false);
            }
        });
    });

    $('#transaction-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_finance_add_transaction',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                type: $('#trans-type').val(),
                amount: $('#trans-amount').val(),
                ref: $('#trans-ref').val(),
                desc: $('#trans-desc').val()
            },
            success: function(res) {
                if (res.success) {
                    $('#transaction-form')[0].reset();
                    $('#transaction-modal').addClass('hidden');
                    loadFinanceData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });
});
</script>
