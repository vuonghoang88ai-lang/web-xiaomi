<div class="wrap">
    <h1><?php esc_html_e( 'Đơn hàng & Xuất kho ERP', 'mi-erp-system' ); ?></h1>
    
    <p class="description">
        <?php esc_html_e( 'Bảng điều khiển tập trung giúp theo dõi trạng thái xuất Serial cho các đơn hàng WooCommerce.', 'mi-erp-system' ); ?>
    </p>

    <hr style="margin: 20px 0;">

    <style>
        /* Fix width for DataTables length select dropdown in WP Admin */
        .dataTables_wrapper .dataTables_length select {
            width: 60px !important;
            padding-right: 24px !important;
            margin: 0 5px;
        }
    </style>

    <table id="mi-erp-orders-table" class="display cell-border stripe hover" style="width:100%">
        <thead>
            <tr>
                <th>Mã Đơn</th>
                <th>Khách hàng</th>
                <th>Nhân viên</th>
                <th>Ngày mua</th>
                <th>Tổng tiền</th>
                <th>Trạng thái WC</th>
                <th>Trạng thái Serial</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script type="text/javascript">
// Initialization is handled globally in mi-erp-admin.js
</script>

<!-- Modal Cập nhật Serial -->
<div id="mi-erp-assign-modal" class="mi-erp-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="mi-erp-modal-content" style="background-color:#fefefe; margin:5% auto; padding:20px; border:1px solid #888; width:80%; max-width:800px; border-radius:5px;">
        <span class="mi-erp-close-modal" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
        <h2>Gán Serial cho Đơn hàng #<span id="assign-modal-order-id"></span></h2>
        <p>Vui lòng chọn hoặc quét mã Serial tương ứng cho từng sản phẩm.</p>
        
        <form id="mi-erp-assign-form">
            <input type="hidden" id="assign-order-id-input" name="order_id" value="">
            <div id="assign-modal-items-container">
                <!-- Nội dung sản phẩm sẽ được load qua AJAX -->
            </div>
            
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="button mi-erp-close-modal-btn">Hủy bỏ</button>
                <button type="submit" class="button button-primary" id="mi-erp-save-assign-btn">Lưu Serial</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Đóng Modal
    $('.mi-erp-close-modal, .mi-erp-close-modal-btn').on('click', function() {
        $('#mi-erp-assign-modal').hide();
    });

    // Mở Modal và Load Items
    $(document).on('click', '.mi-erp-assign-serial-btn', function() {
        var orderId = $(this).data('order-id');
        $('#assign-modal-order-id').text(orderId);
        $('#assign-order-id-input').val(orderId);
        
        var $container = $('#assign-modal-items-container');
        $container.html('<p>Đang tải dữ liệu sản phẩm...</p>');
        $('#mi-erp-assign-modal').show();

        $.ajax({
            url: mi_erp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'mi_erp_get_order_items',
                nonce: mi_erp_admin.nonce,
                order_id: orderId
            },
            success: function(res) {
                if (res.success && res.data.items) {
                    var html = '<table class="wp-list-table widefat striped" style="margin-top:10px; table-layout:fixed; width:100%;">';
                    html += '<thead><tr><th style="width:50%;">Sản phẩm</th><th style="width:15%;">Số lượng</th><th style="width:35%;">Mã Serial (Quét / Chọn)</th></tr></thead><tbody>';
                    
                    if (res.data.items.length === 0) {
                        html += '<tr><td colspan="3">Đơn hàng không có sản phẩm nào cần gán serial.</td></tr>';
                    } else {
                        $.each(res.data.items, function(i, item) {
                            html += '<tr>';
                            html += '<td><strong>' + item.name + '</strong></td>';
                            html += '<td>' + item.qty + '</td>';
                            html += '<td>';
                            // Tạo input Select2 multi cho số lượng serial cần nhập
                            html += '<select class="mi-erp-serial-select" name="item_serials[' + item.item_id + '][' + item.product_id + '][]" multiple="multiple" style="width:100%;" data-product-id="' + item.product_id + '" data-max-qty="' + item.qty + '"></select>';
                            html += '</td>';
                            html += '</tr>';
                        });
                    }
                    html += '</tbody></table>';
                    $container.html(html);

                    // Init Select2 cho từng dòng
                    $('.mi-erp-serial-select').each(function() {
                        var $select = $(this);
                        var pId = $select.data('product-id');
                        var maxQty = parseInt($select.data('max-qty'), 10);

                        try {
                            var selectFn = $.fn.selectWoo ? $select.selectWoo.bind($select) : $select.select2.bind($select);
                            selectFn({
                                dropdownParent: $('#mi-erp-assign-modal'), // Fix z-index issue in modal
                                placeholder: "Nhập / Quét Serial...",
                                maximumSelectionLength: maxQty,
                                width: '100%',
                                ajax: {
                                    url: mi_erp_admin.ajax_url,
                                    type: 'POST',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function (params) {
                                        return {
                                            action: 'mi_erp_search_serials',
                                            nonce: mi_erp_admin.nonce,
                                            product_id: pId,
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
                            });
                        } catch (e) {
                            console.error("Select2 Init Error:", e);
                            $select.css({ 'height': '100px' }); // Fallback UI for native select multiple
                        }
                    });

                } else {
                    $container.html('<p style="color:red;">Lỗi tải dữ liệu: ' + (res.data || 'Unknown error') + '</p>');
                }
            },
            error: function() {
                $container.html('<p style="color:red;">Đã xảy ra lỗi hệ thống khi tải đơn hàng.</p>');
            }
        });
    });

    // Lưu Serial
    $('#mi-erp-assign-form').on('submit', function(e) {
        e.preventDefault();
        
        var orderId = $('#assign-order-id-input').val();
        var assignments = [];
        var hasError = false;

        $('.mi-erp-serial-select').each(function() {
            var $select = $(this);
            var nameAttr = $select.attr('name'); // item_serials[item_id][product_id][]
            var match = nameAttr.match(/item_serials\[(\d+)\]\[(\d+)\]/);
            if (match) {
                var itemId = match[1];
                var productId = match[2];
                var selectedVals = $select.val() || [];
                var maxQty = $select.data('max-qty');

                if (selectedVals.length > 0 && selectedVals.length !== maxQty) {
                    alert('Sản phẩm ID ' + productId + ' yêu cầu gán ' + maxQty + ' mã Serial, nhưng bạn chỉ mới gán ' + selectedVals.length + '.');
                    hasError = true;
                    return false; // break each
                }

                if (selectedVals.length > 0) {
                    assignments.push({
                        item_id: itemId,
                        product_id: productId,
                        serials: selectedVals
                    });
                }
            }
        });

        if (hasError) return;
        
        if (assignments.length === 0) {
            alert('Vui lòng chọn ít nhất một mã Serial trước khi lưu.');
            return;
        }

        var $btn = $('#mi-erp-save-assign-btn');
        $btn.prop('disabled', true).text('Đang lưu...');

        $.ajax({
            url: mi_erp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'mi_erp_assign_serials',
                nonce: mi_erp_admin.nonce,
                order_id: orderId,
                assignments: assignments
            },
            success: function(res) {
                if (res.success) {
                    alert('Thành công: ' + res.data);
                    $('#mi-erp-assign-modal').hide();
                    $('#mi-erp-orders-table').DataTable().ajax.reload();
                } else {
                    alert('Lỗi: ' + res.data);
                }
            },
            error: function() {
                alert('Đã xảy ra lỗi kết nối khi lưu.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Lưu Serial');
            }
        });
    });
});
</script>
