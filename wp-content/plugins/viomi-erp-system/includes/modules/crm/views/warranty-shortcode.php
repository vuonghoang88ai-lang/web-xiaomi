<div class="mi-warranty-check-wrapper bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto my-8 border border-gray-100">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Tra Cứu Bảo Hành</h3>
    <p class="text-gray-600 mb-6">Quý khách có thể tra cứu hạn bảo hành bằng Số điện thoại mua hàng hoặc Mã Serial của sản phẩm.</p>
    
    <form id="mi-warranty-form" class="flex flex-col sm:flex-row gap-4 mb-6">
        <input type="text" id="mi-warranty-keyword" class="flex-1 border border-gray-300 rounded px-4 py-3 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Nhập Số điện thoại hoặc Mã Serial..." required>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded shadow transition-colors whitespace-nowrap">
            Tra Cứu
        </button>
    </form>
    
    <div id="mi-warranty-loading" class="hidden text-center py-8">
        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-500 font-medium">Đang kiểm tra hệ thống...</p>
    </div>
    
    <div id="mi-warranty-results" class="space-y-4"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('mi-warranty-form');
    const resultDiv = document.getElementById('mi-warranty-results');
    const loadingDiv = document.getElementById('mi-warranty-loading');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // BUG-02: Prevent form reload
            const keyword = document.getElementById('mi-warranty-keyword').value.trim();
            if (!keyword) return;

            loadingDiv.classList.remove('hidden');
            resultDiv.innerHTML = '';

            const formData = new FormData();
            formData.append('action', 'mi_erp_check_warranty');
            formData.append('keyword', keyword);

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                loadingDiv.classList.add('hidden');
                if (res.success) {
                    let html = '';
                    res.data.forEach(item => {
                        const statusClass = item.warranty_valid ? 'bg-green-50 border-green-200 text-green-900' : 'bg-red-50 border-red-200 text-red-900';
                        const badgeClass = item.warranty_valid ? 'bg-green-600 text-white' : 'bg-red-600 text-white';
                        const statusText = item.warranty_valid ? 'Còn Bảo Hành' : 'Hết Bảo Hành';
                        
                        // BUG-04: Using flex layout instead of absolute positioning to prevent height collapse
                        html += `
                        <div class="border rounded-lg p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 ${statusClass}">
                            <div class="flex-1">
                                <h4 class="font-bold text-lg mb-2">${item.product_name}</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-1 text-sm">
                                    <p><span class="text-gray-500">Mã Serial:</span> <strong>${item.serial_number}</strong></p>
                                    <p><span class="text-gray-500">Kích hoạt:</span> <strong>${item.sold_date}</strong></p>
                                    <p><span class="text-gray-500">Thời hạn:</span> <strong>${item.months} tháng</strong></p>
                                    <p><span class="text-gray-500">Hết hạn:</span> <strong>${item.warranty_end}</strong></p>
                                </div>
                            </div>
                            <div class="px-5 py-2 font-bold rounded shadow-sm text-center whitespace-nowrap ${badgeClass}">
                                ${statusText}
                            </div>
                        </div>`;
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-200 text-center font-medium">${res.data}</div>`;
                }
            })
            .catch(err => {
                loadingDiv.classList.add('hidden');
                resultDiv.innerHTML = `<div class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-200 text-center font-medium">Lỗi kết nối máy chủ. Vui lòng thử lại sau.</div>`;
            });
        });
    }
});
</script>
