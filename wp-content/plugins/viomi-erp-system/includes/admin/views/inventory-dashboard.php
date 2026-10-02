<div class="wrap mi-erp-inventory-wrap">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mt-4 block relative">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Quản lý Kho (Inventory Management)</h1>

        <!-- Form nhập liệu/Tìm kiếm -->
        <div class="mb-6 p-5 bg-slate-50 border border-slate-200 rounded-lg">
            <h2 class="text-lg font-semibold text-slate-700 mb-4">Tra cứu / Cập nhật trạng thái</h2>
            <form id="mi-inventory-action-form" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" id="inventory_serial_number" name="serial_number" required placeholder="Nhập Serial Number..." class="w-full px-4 py-2 border border-slate-300 rounded-md focus:ring focus:ring-blue-200">
                </div>
                <div class="w-full md:w-48">
                    <select id="inventory_action_type" name="action_type" class="w-full px-4 py-2 border border-slate-300 rounded-md">
                        <option value="import">Nhập kho (Import)</option>
                        <option value="export">Xuất kho (Export)</option>
                        <option value="return">Hoàn trả (Return)</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition-colors whitespace-nowrap">
                    Thực hiện
                </button>
            </form>
            <div id="inventory-action-message" class="hidden mt-4 p-3 rounded-md"></div>
        </div>

        <!-- Bảng danh sách - Giải quyết BUG-04 bằng block & max-h thay vì absolute inset-0 -->
        <div class="relative w-full h-auto max-h-[600px] overflow-y-auto border border-slate-200 rounded-lg bg-white">
            <table class="w-full text-sm text-left text-slate-500" id="mi-inventory-table">
                <thead class="text-xs text-slate-700 uppercase bg-slate-100 sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-3">Serial Number</th>
                        <th scope="col" class="px-6 py-3">Loại hành động</th>
                        <th scope="col" class="px-6 py-3">Người thực hiện</th>
                        <th scope="col" class="px-6 py-3">Ngày giờ</th>
                    </tr>
                </thead>
                <tbody id="inventory-table-body">
                    <!-- Dữ liệu sẽ được load qua JS -->
                    <tr class="bg-white border-b hover:bg-slate-50">
                        <td colspan="4" class="px-6 py-4 text-center">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
