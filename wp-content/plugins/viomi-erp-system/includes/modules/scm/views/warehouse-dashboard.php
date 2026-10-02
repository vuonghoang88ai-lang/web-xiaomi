<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline text-2xl font-bold mb-4">Quản lý Kho hàng (SCM)</h1>
    <hr class="wp-header-end">
    
    <style>
        /* Fix width for DataTables length select dropdown in WP Admin */
        .dataTables_wrapper .dataTables_length select {
            width: 60px !important;
            padding-right: 24px !important;
            margin: 0 5px;
        }
    </style>
    <div class="bg-white p-6 rounded-lg shadow mt-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Danh sách kho hàng</h2>
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors" id="btn-add-warehouse">
                + Thêm Kho Mới
            </button>
        </div>
        
        <table class="display cell-border stripe hover" id="warehouse-table" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Kho</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm/Sửa Kho -->
<div id="warehouse-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex justify-center items-center">
    <div class="relative mx-auto p-6 border w-96 shadow-xl rounded-xl bg-white transition-all transform scale-100">
        <div class="text-center">
            <h3 class="text-xl leading-6 font-bold text-gray-900 mb-4" id="modal-title">Thêm Kho Mới</h3>
            <div class="text-left">
                <form id="warehouse-form">
                    <input type="hidden" id="warehouse-id" name="id" value="0">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tên Kho *</label>
                        <input type="text" id="warehouse-name" name="name" class="shadow-sm appearance-none border border-gray-300 rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Vị trí (Location)</label>
                        <input type="text" id="warehouse-location" name="location" class="shadow-sm appearance-none border border-gray-300 rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Trạng thái</label>
                        <select id="warehouse-status" name="status" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                            <option value="active">Hoạt động (Active)</option>
                            <option value="inactive">Tạm ngưng (Inactive)</option>
                        </select>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <button type="button" id="btn-close-modal" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">Hủy</button>
                        <button type="submit" id="btn-save-warehouse" class="px-5 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 shadow-sm transition-colors">Lưu lại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let dtTable = null;

    // 1. Tải danh sách kho bằng DataTables
    function loadWarehouses() {
        if (dtTable) {
            dtTable.ajax.reload();
            return;
        }
        
        dtTable = $('#warehouse-table').DataTable({
            "processing": true,
            "ajax": {
                "url": ajaxurl,
                "type": 'POST',
                "data": function(d) {
                    d.action = 'mi_erp_get_warehouses';
                    d.nonce = '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>';
                },
                "dataSrc": function(json) {
                    if(!json.success) return [];
                    return json.data;
                }
            },
            "columns": [
                { "data": "id" },
                { "data": "name", "render": function(data, type, row) { return '<strong class="text-gray-800">' + data + '</strong>'; } },
                { "data": "location", "render": function(data, type, row) { return data || '-'; } },
                { 
                    "data": "status", 
                    "render": function(data, type, row) {
                        return data === 'active' 
                            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Hoạt động</span>'
                            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Tạm ngưng</span>';
                    }
                },
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `<button class="text-blue-600 hover:text-blue-800 mr-3 font-medium btn-edit transition-colors" data-id="${row.id}" data-name="${row.name}" data-location="${row.location}" data-status="${row.status}">Sửa</button>
                                <button class="text-red-600 hover:text-red-800 font-medium btn-delete transition-colors" data-id="${row.id}">Xóa</button>`;
                    }
                }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
            }
        });
    }

    loadWarehouses();

    // 2. Mở / Đóng Modal
    const modal = $('#warehouse-modal');
    $('#btn-add-warehouse').click(function() {
        $('#warehouse-form')[0].reset();
        $('#warehouse-id').val(0);
        $('#modal-title').text('Thêm Kho Mới');
        modal.removeClass('hidden');
    });

    $('#btn-close-modal').click(function() {
        modal.addClass('hidden');
    });

    // 3. Sửa kho
    $(document).on('click', '.btn-edit', function() {
        $('#warehouse-id').val($(this).data('id'));
        $('#warehouse-name').val($(this).data('name'));
        $('#warehouse-location').val($(this).data('location'));
        $('#warehouse-status').val($(this).data('status'));
        $('#modal-title').text('Sửa thông tin Kho');
        modal.removeClass('hidden');
    });

    // 4. Lưu kho (Thêm / Sửa)
    $('#warehouse-form').submit(function(e) {
        e.preventDefault(); // BUG-02: Fix lỗi AJAX bị tải lại trang
        
        let formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'mi_erp_save_warehouse'});
        formData.push({name: 'nonce', value: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'});
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(res) {
                if(res.success) {
                    alert(res.data);
                    modal.addClass('hidden');
                    loadWarehouses();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            }
        });
    });

    // 5. Xóa kho
    $(document).on('click', '.btn-delete', function() {
        if(confirm('Bạn có chắc chắn muốn xóa kho này?')) {
            let id = $(this).data('id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mi_erp_delete_warehouse',
                    id: id,
                    nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>'
                },
                success: function(res) {
                    if(res.success) {
                        alert(res.data);
                        loadWarehouses();
                    } else {
                        alert('Lỗi: ' + res.data);
                    }
                }
            });
        }
    });
});
</script>
