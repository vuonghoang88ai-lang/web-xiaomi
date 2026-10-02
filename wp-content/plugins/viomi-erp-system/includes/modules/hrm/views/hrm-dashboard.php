<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Nhân sự & Lương thưởng (HRM)</h1>
    <hr class="wp-header-end">
    
    <div class="mt-4 flex border-b border-gray-200">
        <button class="tab-link px-6 py-3 font-semibold text-blue-700 bg-white border-l border-t border-r rounded-t" data-tab="tab-kpi">Hiệu suất & Lương (KPIs)</button>
        <button class="tab-link px-6 py-3 font-semibold text-gray-500 bg-gray-50 hover:bg-gray-100" data-tab="tab-settings">Cài đặt Cơ chế Lương</button>
        <button class="tab-link px-6 py-3 font-semibold text-gray-500 bg-gray-50 hover:bg-gray-100" data-tab="tab-add-emp">Quản lý Nhân sự</button>
        <button class="tab-link px-6 py-3 font-semibold text-gray-500 bg-gray-50 hover:bg-gray-100" data-tab="tab-departments">Phòng ban & Cơ cấu</button>
    </div>

    <!-- TAB 1: KPI & LƯƠNG -->
    <div id="tab-kpi" class="tab-content bg-white p-6 rounded-b-lg shadow-sm border-l border-r border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-center border-b pb-4 mb-4 gap-4">
            <h2 class="text-lg font-bold text-gray-800">Hiệu suất nhân viên (Tháng này)</h2>
            <div class="flex gap-2 items-center flex-wrap justify-end">
                <select id="kpi-filter-dept" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 min-w-[150px] dynamic-dept-select">
                    <option value="all">Tất cả phòng ban</option>
                </select>
                <select id="kpi-filter-status" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" selected>Đang làm việc</option>
                    <option value="inactive">Đã đình chỉ/Nghỉ</option>
                </select>
                <input type="month" id="hrm-period" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" value="<?php echo date('Y-m'); ?>">
                <button type="button" id="btn-load-hrm" class="px-4 py-2 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700 font-bold text-sm transition-colors">Đồng bộ & Xem</button>
                <button type="button" id="btn-save-deductions" class="px-4 py-2 bg-red-600 text-white rounded shadow-sm hover:bg-red-700 font-bold text-sm transition-colors">Lưu Khấu Trừ</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="hrm-table">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-3 font-semibold text-sm text-gray-700">ID</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Nhân viên</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 text-right">Doanh thu bán</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 text-right">Giá vốn</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 text-right">Phí Ship</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 text-right">Lương cứng</th>
                        <th class="p-3 font-semibold text-sm text-green-700 text-right">Hoa hồng</th>
                        <th class="p-3 font-semibold text-sm text-orange-600 text-right">Khấu trừ/Tạm ứng (₫)</th>
                        <th class="p-3 font-semibold text-sm text-red-600 text-right">Thực nhận</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td colspan="9" class="p-8 text-center text-gray-400 italic">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: CÀI ĐẶT CƠ CHẾ LƯƠNG -->
    <div id="tab-settings" class="tab-content hidden bg-white p-6 rounded-b-lg shadow-sm border-l border-r border-b border-gray-200">
        <div class="flex justify-between items-center border-b pb-4 mb-4">
            <h2 class="text-lg font-bold text-gray-800">Cấu hình Lương, Thưởng & Phòng ban</h2>
            <div class="flex gap-2 items-center flex-wrap justify-end">
                <select id="settings-filter-dept" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 min-w-[150px] dynamic-dept-select">
                    <option value="all">Tất cả phòng ban</option>
                </select>
                <select id="settings-filter-status" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" selected>Đang làm việc</option>
                    <option value="inactive">Đã đình chỉ/Nghỉ</option>
                </select>
                <button type="button" id="btn-save-settings" class="px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700 font-bold text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Lưu cấu hình
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="settings-table">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-3 font-semibold text-sm text-gray-700 w-1/12">ID</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-2/12">Nhân viên</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-2/12">Phòng ban</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-2/12">Mô hình lương</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-2/12">Lương cơ bản (₫)</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-1/12">Hệ số (%)</th>
                        <th class="p-3 font-semibold text-sm text-gray-700 w-2/12">Thưởng / Đơn (₫)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td colspan="6" class="p-8 text-center text-gray-400 italic">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: QUẢN LÝ NHÂN SỰ -->
    <div id="tab-add-emp" class="tab-content hidden bg-white p-6 rounded-b-lg shadow-sm border-l border-r border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-center border-b pb-4 mb-4 gap-4">
            <h2 class="text-lg font-bold text-gray-800">Danh sách & Phân quyền Nhân sự</h2>
            <div class="flex gap-2 items-center flex-wrap justify-end">
                <select id="emp-filter-dept" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 min-w-[150px] dynamic-dept-select">
                    <option value="all">Tất cả phòng ban</option>
                </select>
                <select id="emp-filter-status" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" selected>Đang làm việc</option>
                    <option value="inactive">Đã đình chỉ/Nghỉ</option>
                </select>
                <button type="button" id="btn-toggle-add-emp" class="px-4 py-2 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700 font-bold text-sm transition-colors flex items-center gap-2">
                    + Thêm nhân sự mới
                </button>
            </div>
        </div>

        <!-- Bảng danh sách nhân sự -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-left border-collapse" id="employee-list-table">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-3 font-semibold text-sm text-gray-700">ID</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Tên đăng nhập / Email</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Họ & Tên</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Vai trò (Role)</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Phòng ban</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Trạng thái</th>
                        <th class="p-3 font-semibold text-sm text-gray-700">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Đang tải dữ liệu nhân sự...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Form Thêm mới (Modal style) -->
        <div id="add-employee-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex justify-center items-center">
            <div class="relative p-8 bg-white w-full max-w-4xl m-auto rounded-md shadow-lg">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-bold text-blue-700">Tạo Tài Khoản Mới</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 btn-close-add-modal">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="form-add-employee">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Cột 1 -->
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">1. Thông tin Tài khoản (WordPress)</h3>
                            <div class="mb-3">
                                <label class="block text-sm font-bold mb-1">Tên đăng nhập (Username) *</label>
                                <input type="text" name="emp_username" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-bold mb-1">Email *</label>
                                <input type="email" name="emp_email" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-bold mb-1">Mật khẩu *</label>
                                <input type="password" name="emp_password" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                            <div class="mb-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Họ & Tên</label>
                                    <input type="text" name="emp_fullname" class="shadow border rounded w-full py-2 px-3">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Số điện thoại</label>
                                    <input type="text" name="emp_phone" class="shadow border rounded w-full py-2 px-3">
                                </div>
                            </div>
                        </div>

                        <!-- Cột 2 -->
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">2. Vai trò & Cơ chế Lương (ERP)</h3>
                            <div class="mb-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Quyền hạn (Role) *</label>
                                    <select name="emp_role" class="shadow border rounded w-full py-2 px-3" required>
                                        <option value="shop_manager">Quản lý Cửa hàng</option>
                                        <option value="editor">Nhân viên Sales (Editor)</option>
                                        <option value="subscriber">Khác (Subscriber)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Phòng ban</label>
                                    <select name="emp_department" class="shadow border rounded w-full py-2 px-3 dynamic-dept-select-no-all">
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-bold mb-1">Cơ chế hoa hồng</label>
                                <select name="emp_commission_type" class="shadow border rounded w-full py-2 px-3">
                                    <option value="profit">Tính trên Lợi nhuận (Doanh thu - Vốn - Ship)</option>
                                    <option value="sales">Tính trên Tổng Doanh thu</option>
                                </select>
                            </div>
                            <div class="mb-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Lương cơ bản (VNĐ)</label>
                                    <input type="number" name="emp_base_salary" class="shadow border rounded w-full py-2 px-3" value="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Tỷ lệ Hoa hồng (%)</label>
                                    <input type="number" step="0.1" name="emp_commission_rate" class="shadow border rounded w-full py-2 px-3" value="0">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="emp_exclude_payroll" value="1" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                    <span class="ml-2 text-sm font-bold text-gray-700">Tài khoản này không tính lương (Admin)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end items-center border-t border-gray-300 pt-4 gap-2">
                        <button type="button" class="btn-close-add-modal bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded hover:bg-gray-400">Hủy</button>
                        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700">Khởi tạo & Lưu</button>
                        <span class="ml-3 hidden text-gray-500" id="emp-loading-indicator">Đang xử lý...</span>
                    </div>
                    <div id="emp-message" class="mt-3 hidden p-3 rounded font-bold"></div>
                </form>
            </div>
        </div>

        <!-- Form Sửa (Modal style) -->
        <div id="edit-employee-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex justify-center items-center">
            <div class="relative p-8 bg-white w-full max-w-2xl m-auto rounded-md shadow-lg">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-bold text-blue-700">Chỉnh sửa thông tin: <span id="edit-emp-name" class="text-gray-800"></span></h3>
                    <button type="button" id="btn-close-edit-modal-x" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form id="form-edit-employee">
                    <input type="hidden" name="user_id" id="edit-emp-id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-bold mb-1">Quyền hạn (Role) *</label>
                                <select name="emp_role" id="edit-emp-role" class="shadow border rounded w-full py-2 px-3">
                                    <option value="administrator">Quản trị viên (Admin)</option>
                                    <option value="shop_manager">Quản lý Cửa hàng</option>
                                    <option value="editor">Nhân viên Sales (Editor)</option>
                                    <option value="subscriber">Khác (Subscriber)</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-bold mb-1">Phòng ban</label>
                                <select name="emp_department" id="edit-emp-department" class="shadow border rounded w-full py-2 px-3 dynamic-dept-select-no-all">
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-bold mb-1">Cơ chế hoa hồng</label>
                                <select name="emp_commission_type" id="edit-emp-comm-type" class="shadow border rounded w-full py-2 px-3">
                                    <option value="profit">Tính trên Lợi nhuận (Doanh thu - Vốn - Ship)</option>
                                    <option value="sales">Tính trên Tổng Doanh thu</option>
                                </select>
                            </div>
                            <div class="mb-4 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Lương cơ bản</label>
                                    <input type="number" name="emp_base_salary" id="edit-emp-base" class="shadow border rounded w-full py-2 px-3">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Hoa hồng (%)</label>
                                    <input type="number" step="0.1" name="emp_commission_rate" id="edit-emp-rate" class="shadow border rounded w-full py-2 px-3">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="emp_exclude_payroll" id="edit-emp-exclude-payroll" value="1" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                    <span class="ml-2 text-sm font-bold text-gray-700">Tài khoản này không tính lương (Admin)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end items-center border-t border-gray-300 pt-4 gap-2">
                        <button type="button" id="btn-close-edit-modal" class="px-6 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 font-bold">Hủy</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 4: PHÒNG BAN & CƠ CẤU -->
    <div id="tab-departments" class="tab-content hidden bg-white p-6 rounded-b-lg shadow-sm border-l border-r border-b border-gray-200">
        <div class="border-b pb-4 mb-4">
            <h2 class="text-lg font-bold text-gray-800">Quản lý Phòng ban</h2>
        </div>
        <div class="max-w-3xl">
            <p class="text-sm text-gray-600 mb-4">Thêm mới hoặc quản lý danh sách phòng ban. Các thay đổi sẽ được lưu tự động.</p>
            
            <div class="flex gap-2 mb-6">
                <input type="text" id="new-dept-name" class="shadow-sm border border-gray-300 rounded py-2 px-3 flex-1 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Nhập tên phòng ban mới...">
                <button type="button" id="btn-add-department" class="px-6 py-2 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700 font-bold text-sm transition-colors whitespace-nowrap">
                    + Thêm phòng ban
                </button>
            </div>

            <div class="bg-gray-50 border rounded p-4">
                <h3 class="font-bold text-gray-700 mb-3 border-b pb-2">Danh sách hiện tại</h3>
                <div id="departments-list" class="space-y-2">
                    <!-- Sẽ được load bằng JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Xử lý Tabs
    $('.tab-link').click(function() {
        $('.tab-link').removeClass('border-l border-t border-r rounded-t text-blue-700 bg-white').addClass('text-gray-500 bg-gray-50 hover:bg-gray-100 border-transparent');
        $(this).removeClass('text-gray-500 bg-gray-50 hover:bg-gray-100 border-transparent').addClass('border-l border-t border-r rounded-t text-blue-700 bg-white border-gray-200');
        
        $('.tab-content').addClass('hidden');
        let target = $(this).data('tab');
        $('#' + target).removeClass('hidden');

        if(target === 'tab-kpi') {
            loadHRMData();
        } else if (target === 'tab-settings') {
            loadSalarySettings();
        } else if (target === 'tab-departments') {
            renderDepartmentsUI();
        } else if (target === 'tab-add-emp') {
            loadEmployeeList();
        }
    });

    // Hàm format tiền (nếu cần ở JS)
    function formatCurrency(number) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
    }

    // Load KPI
    function loadHRMData() {
        let period = $('#hrm-period').val();
        if (!period) return;
        
        $('#hrm-table tbody').html('<tr><td colspan="9" class="p-8 text-center text-gray-400 italic">Đang tải và đồng bộ dữ liệu...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_hrm',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                period: period,
                department: $('#kpi-filter-dept').val(),
                status: $('#kpi-filter-status').val()
            },
            success: function(res) {
                if (res.success) {
                    let users = res.data.users;
                    let html = '';
                    if (users.length === 0) {
                        html = '<tr><td colspan="9" class="p-8 text-center text-gray-400 italic">Chưa có nhân viên nào.</td></tr>';
                    } else {
                        users.forEach(function(u) {
                            html += `<tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-bold text-sm text-gray-600">#${u.id}</td>
                                <td class="p-3">
                                    <div class="text-sm font-semibold text-gray-800">${u.name}</div>
                                    <div class="text-xs text-gray-500">${u.email}</div>
                                </td>
                                <td class="p-3 text-sm font-bold text-blue-600 text-right">${u.sales}</td>
                                <td class="p-3 text-sm font-medium text-gray-500 text-right">${u.cost}</td>
                                <td class="p-3 text-sm font-medium text-gray-500 text-right">${u.shipping}</td>
                                <td class="p-3 text-sm font-medium text-gray-700 text-right">${u.base_salary}</td>
                                <td class="p-3 text-sm font-bold text-green-600 text-right">${u.commissions}</td>
                                <td class="p-3 text-right">
                                    <input type="number" class="hrm-deduction shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-32 text-right focus:ring-1 focus:ring-red-500" data-uid="${u.id}" value="${u.deductions}" min="0" step="10000">
                                </td>
                                <td class="p-3 text-base font-black text-red-600 text-right">${u.total_income}</td>
                            </tr>`;
                        });
                    }
                    $('#hrm-table tbody').html(html);
                } else {
                    $('#hrm-table tbody').html('<tr><td colspan="9" class="p-3 text-center text-red-500">Có lỗi xảy ra.</td></tr>');
                }
            }
        });
    }

    $('#btn-load-hrm').click(function() {
        $(this).text('Đang đồng bộ...').prop('disabled', true);
        let btn = $(this);
        setTimeout(function(){ btn.text('Đồng bộ & Xem').prop('disabled', false); }, 1500); // UI feel
        loadHRMData();
    });

    // Save Deductions
    $('#btn-save-deductions').click(function() {
        let period = $('#hrm-period').val();
        let btn = $(this);
        let originalText = btn.text();
        btn.text('Đang lưu...').prop('disabled', true);
        
        let deductionsData = {};
        $('.hrm-deduction').each(function() {
            deductionsData[$(this).data('uid')] = $(this).val();
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_save_hrm_deductions',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                period: period,
                deductions: deductionsData
            },
            success: function(res) {
                if (res.success) {
                    alert(res.data);
                    loadHRMData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            complete: function() {
                btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // Load Cài đặt Lương
    function loadSalarySettings() {
        $('#settings-table tbody').html('<tr><td colspan="6" class="p-8 text-center text-gray-400 italic">Đang tải dữ liệu...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_salary_settings',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                department: $('#settings-filter-dept').val(),
                status: $('#settings-filter-status').val()
            },
            success: function(res) {
                if (res.success) {
                    let users = res.data;
                    let html = '';
                    if (users.length === 0) {
                        html = '<tr><td colspan="6" class="p-8 text-center text-gray-400 italic">Chưa có nhân viên nào.</td></tr>';
                    } else {
                        users.forEach(function(u) {
                            html += `<tr class="hover:bg-gray-50 transition-colors settings-row" data-user-id="${u.id}">
                                <td class="p-3 font-bold text-sm text-gray-600">#${u.id}</td>
                                <td class="p-3 text-sm font-semibold text-gray-800">${u.name}</td>
                                <td class="p-3">
                                    <select class="setting-dept shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-full focus:ring-1 focus:ring-blue-500">
                                        ${getDeptOptionsForUser(u.department)}
                                    </select>
                                </td>
                                <td class="p-3">
                                    <select class="setting-type shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-full focus:ring-1 focus:ring-blue-500">
                                        <option value="sales" ${u.commission_type === 'sales' ? 'selected' : ''}>% Doanh thu</option>
                                        <option value="profit" ${u.commission_type === 'profit' ? 'selected' : ''}>% Lợi nhuận (Bán - Nhập)</option>
                                        <option value="margin" ${u.commission_type === 'margin' ? 'selected' : ''}>% Lợi nhuận ròng (Bán - Nhập - Ship)</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <input type="number" class="setting-base shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-full focus:ring-1 focus:ring-blue-500" value="${u.base_salary}" min="0" step="100000">
                                </td>
                                <td class="p-3">
                                    <input type="number" class="setting-comm shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-full focus:ring-1 focus:ring-blue-500" value="${u.commission_rate}" min="0" max="100" step="0.1">
                                </td>
                                <td class="p-3">
                                    <input type="number" class="setting-bonus shadow-sm border border-gray-300 rounded py-1 px-2 text-sm w-full focus:ring-1 focus:ring-blue-500" value="${u.ticket_bonus}" min="0" step="10000">
                                </td>
                            </tr>`;
                        });
                    }
                    $('#settings-table tbody').html(html);
                }
            }
        });
    }

    // Save Cài đặt Lương
    $('#btn-save-settings').click(function() {
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Đang lưu...').prop('disabled', true);
        
        let settingsData = [];
        $('.settings-row').each(function() {
            settingsData.push({
                user_id: $(this).data('user-id'),
                department: $(this).find('.setting-dept').val(),
                commission_type: $(this).find('.setting-type').val(),
                base_salary: $(this).find('.setting-base').val(),
                commission_rate: $(this).find('.setting-comm').val(),
                ticket_bonus: $(this).find('.setting-bonus').val()
            });
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_save_salary_settings',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                settings: settingsData
            },
            success: function(res) {
                if (res.success) {
                    alert(res.data);
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Handle Add Employee form
    $('#form-add-employee').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $form.find('button[type="submit"]');
        let $loading = $('#emp-loading-indicator');
        let $msg = $('#emp-message');

        $btn.prop('disabled', true);
        $loading.removeClass('hidden');
        $msg.addClass('hidden').removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800');

        let formData = $form.serializeArray();
        formData.push({name: 'action', value: 'mi_erp_add_employee'});
        formData.push({name: 'nonce', value: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'});

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(res) {
                $loading.addClass('hidden');
                $btn.prop('disabled', false);
                $msg.removeClass('hidden');
                
                if(res.success) {
                    $msg.addClass('bg-green-100 text-green-800').text('✅ ' + res.data);
                    $form[0].reset();
                    
                    // Delay slightly to show success message before closing modal
                    setTimeout(() => {
                        $('#add-employee-modal').addClass('hidden');
                        $msg.addClass('hidden');
                        loadEmployeeList(); // Reload table
                    }, 1000);
                } else {
                    $msg.addClass('bg-red-100 text-red-800').text('❌ Lỗi: ' + res.data);
                }
            },
            error: function() {
                $loading.addClass('hidden');
                $btn.prop('disabled', false);
                $msg.removeClass('hidden').addClass('bg-red-100 text-red-800').text('❌ Lỗi kết nối hệ thống!');
            }
        });
    });

    // Handle toggle Add Employee Form
    $('#btn-toggle-add-emp').on('click', function() {
        $('#add-employee-modal').removeClass('hidden');
    });

    $('.btn-close-add-modal').on('click', function() {
        $('#add-employee-modal').addClass('hidden');
        $('#form-add-employee')[0].reset();
        $('#emp-message').addClass('hidden');
    });

    // Load Employee List
    function loadEmployeeList() {
        $('#employee-list-table tbody').html('<tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Đang tải dữ liệu nhân sự...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_employees_list',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                department: $('#emp-filter-dept').val(),
                status: $('#emp-filter-status').val()
            },
            success: function(res) {
                if (res.success) {
                    let users = res.data.users;
                    let html = '';
                    if (users.length === 0) {
                        html = '<tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Chưa có nhân viên nào.</td></tr>';
                    } else {
                        users.forEach(function(u) {
                            let statusClass = u.status_code === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            let iconPath = u.status_code === 'active' ? 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' : 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z';
                            let titleText = u.status_code === 'active' ? 'Đình chỉ' : 'Mở khóa';

                            html += `<tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-bold text-sm text-gray-600">#${u.id}</td>
                                <td class="p-3">
                                    <div class="text-sm font-semibold text-gray-800">${u.login}</div>
                                    <div class="text-xs text-gray-500">${u.email}</div>
                                </td>
                                <td class="p-3 text-sm text-gray-800">${u.name}</td>
                                <td class="p-3 text-sm text-blue-600 font-medium">${u.roles}</td>
                                <td class="p-3 text-sm text-gray-700">${u.department}</td>
                                <td class="p-3 text-sm"><span class="px-2 py-1 rounded text-xs ${statusClass}">${u.status}</span></td>
                                <td class="p-3 text-sm">
                                    <button class="btn-edit-employee text-blue-600 hover:text-blue-800 mr-2" 
                                        data-uid="${u.id}" 
                                        data-name="${u.login}" 
                                        data-role="${u.role_key}" 
                                        data-dept="${u.department}" 
                                        data-comm-type="${u.commission_type}" 
                                        data-base="${u.base_salary}" 
                                        data-rate="${u.commission_rate}" 
                                        data-exclude-payroll="${u.exclude_payroll}"
                                        title="Sửa"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                    <button class="btn-toggle-status text-red-600 hover:text-red-800" data-uid="${u.id}" title="${titleText}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path></svg></button>
                                </td>
                            </tr>`;
                        });
                    }
                    $('#employee-list-table tbody').html(html);
                } else {
                    $('#employee-list-table tbody').html(`<tr><td colspan="7" class="p-8 text-center text-red-500 italic">Lỗi: ${res.data}</td></tr>`);
                }
            },
            error: function() {
                $('#employee-list-table tbody').html('<tr><td colspan="7" class="p-8 text-center text-red-500 italic">Lỗi kết nối máy chủ!</td></tr>');
            }
        });
    }

    // Toggle Status
    $(document).on('click', '.btn-toggle-status', function() {
        let uid = $(this).data('uid');
        if(confirm('Bạn có chắc chắn muốn thay đổi trạng thái hoạt động của nhân viên này?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mi_erp_toggle_employee_status',
                    nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                    user_id: uid
                },
                success: function(res) {
                    if (res.success) {
                        loadEmployeeList(); // Reload table
                    } else {
                        alert('Lỗi: ' + res.data);
                    }
                }
            });
        }
    });

    // Edit Employee Modal
    $(document).on('click', '.btn-edit-employee', function() {
        let btn = $(this);
        $('#edit-emp-id').val(btn.data('uid'));
        $('#edit-emp-name').text(btn.data('name'));
        $('#edit-emp-role').val(btn.data('role'));
        
        let dept = btn.data('dept');
        if(!dept) dept = 'Chưa phân bổ';
        $('#edit-emp-department').val(dept);
        
        let type = btn.data('comm-type');
        if(!type) type = 'sales';
        $('#edit-emp-comm-type').val(type);
        
        $('#edit-emp-base').val(btn.data('base'));
        $('#edit-emp-rate').val(btn.data('rate'));
        
        let excludePayroll = btn.data('exclude-payroll');
        $('#edit-emp-exclude-payroll').prop('checked', excludePayroll == '1');
        
        $('#edit-employee-modal').removeClass('hidden');
    });

    $('#btn-close-edit-modal, #btn-close-edit-modal-x').on('click', function() {
        $('#edit-employee-modal').addClass('hidden');
    });

    $('#form-edit-employee').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'mi_erp_edit_employee'});
        formData.push({name: 'nonce', value: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'});
        
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Đang lưu...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(res) {
                if (res.success) {
                    $('#edit-employee-modal').addClass('hidden');
                    loadEmployeeList();
                    // Reload the KPI table data as well since base salary might have changed
                    loadHRMData();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            complete: function() {
                btn.prop('disabled', false).text('Lưu thay đổi');
            }
        });
    });

    $('.tab-link').on('click', function() {
        let target = $(this).data('tab');
        if(target === 'tab-add-emp') {
            loadEmployeeList();
        }
    });

    // ==========================================
    // QUẢN LÝ PHÒNG BAN (DEPARTMENTS)
    // ==========================================
    let cachedDepartments = [];

    function loadDepartmentsFromServer(callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_load_departments',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
            },
            success: function(res) {
                if (res.success) {
                    cachedDepartments = res.data;
                    updateDepartmentSelects();
                    if(callback) callback();
                }
            }
        });
    }

    function updateDepartmentSelects() {
        let optionsWithAll = '<option value="all">Tất cả phòng ban</option>';
        let optionsNoAll = '<option value="">Chọn phòng ban...</option>';
        
        cachedDepartments.forEach(dept => {
            let escapedDept = $('<div>').text(dept).html(); // Escape HTML
            optionsWithAll += `<option value="${escapedDept}">${escapedDept}</option>`;
            optionsNoAll += `<option value="${escapedDept}">${escapedDept}</option>`;
        });
        optionsNoAll += '<option value="Chưa phân bổ">Chưa phân bổ</option>';
        optionsWithAll += '<option value="Chưa phân bổ">Chưa phân bổ</option>';

        $('.dynamic-dept-select').html(optionsWithAll);
        $('.dynamic-dept-select-no-all').html(optionsNoAll);
    }

    function saveDepartmentsToServer(depts, btn, originalText, callback) {
        if(btn) btn.prop('disabled', true).text('Đang xử lý...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mi_erp_save_departments',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                departments: depts
            },
            success: function(res) {
                if(res.success) {
                    cachedDepartments = depts;
                    updateDepartmentSelects();
                    renderDepartmentsUI();
                    if(callback) callback();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            complete: function() {
                if(btn) btn.prop('disabled', false).html(originalText);
            }
        });
    }

    function renderDepartmentsUI() {
        let html = '';
        if(cachedDepartments.length === 0) {
            html = '<div class="text-gray-500 italic text-sm p-2">Chưa có phòng ban nào.</div>';
        } else {
            cachedDepartments.forEach((dept, index) => {
                let escapedDept = $('<div>').text(dept).html();
                html += `
                <div class="flex items-center justify-between p-3 bg-white border rounded shadow-sm">
                    <div class="font-medium text-gray-800">${escapedDept}</div>
                    <div class="flex gap-4">
                        <button type="button" class="btn-edit-dept text-blue-600 hover:text-blue-800 text-sm font-semibold transition-colors" data-index="${index}">Sửa</button>
                        <button type="button" class="btn-remove-dept text-red-600 hover:text-red-800 text-sm font-semibold transition-colors" data-index="${index}">Xóa</button>
                    </div>
                </div>`;
            });
        }
        $('#departments-list').html(html);
    }
    
    // Attach function to global scope to be called by string template
    window.getDeptOptionsForUser = function(currentDept) {
        let html = '';
        cachedDepartments.forEach(dept => {
            let escapedDept = $('<div>').text(dept).html();
            html += `<option value="${escapedDept}" ${escapedDept === currentDept ? 'selected' : ''}>${escapedDept}</option>`;
        });
        html += `<option value="Chưa phân bổ" ${currentDept === 'Chưa phân bổ' || !currentDept ? 'selected' : ''}>Chưa phân bổ</option>`;
        return html;
    };

    $('#btn-add-department').click(function() {
        let name = $('#new-dept-name').val().trim();
        if(!name) {
            alert('Vui lòng nhập tên phòng ban!');
            return;
        }
        if(cachedDepartments.includes(name)) {
            alert('Phòng ban này đã tồn tại!');
            return;
        }
        let newDepts = [...cachedDepartments, name];
        saveDepartmentsToServer(newDepts, $(this), '+ Thêm phòng ban', function() {
            $('#new-dept-name').val('');
        });
    });

    $(document).on('click', '.btn-remove-dept', function() {
        if(!confirm('Bạn có chắc chắn muốn xóa phòng ban này?')) return;
        let index = $(this).data('index');
        let newDepts = [...cachedDepartments];
        newDepts.splice(index, 1);
        saveDepartmentsToServer(newDepts, null, null);
    });

    $(document).on('click', '.btn-edit-dept', function() {
        let index = $(this).data('index');
        let currentName = cachedDepartments[index];
        let newName = prompt('Nhập tên mới cho phòng ban:', currentName);
        if(newName !== null) {
            newName = newName.trim();
            if(newName === '') {
                alert('Tên phòng ban không được để trống!');
                return;
            }
            if(newName === currentName) return;
            if(cachedDepartments.includes(newName)) {
                alert('Tên phòng ban này đã tồn tại!');
                return;
            }
            let newDepts = [...cachedDepartments];
            newDepts[index] = newName;
            saveDepartmentsToServer(newDepts, null, null);
        }
    });

    // Event listeners for filters
    $('#kpi-filter-dept, #kpi-filter-status').on('change', function() {
        loadHRMData();
    });
    $('#settings-filter-dept, #settings-filter-status').on('change', function() {
        loadSalarySettings();
    });
    $('#emp-filter-dept, #emp-filter-status').on('change', function() {
        loadEmployeeList();
    });

    // Init
    loadDepartmentsFromServer(function() {
        loadHRMData();
    });
});
</script>
