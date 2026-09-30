jQuery(document).ready(function ($) {
    if (typeof mi_erp_admin === 'undefined') {
        return;
    }

    var $productSelect = $('#product_id');
    var $inventoryForm = $('#mi-erp-inventory-form');

    // Initialize Select2 for AJAX product search
    if ($productSelect.length) {
        $productSelect.select2({
            ajax: {
                url: mi_erp_admin.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term,
                        action: 'mi_erp_search_product',
                        nonce: mi_erp_admin.nonce
                    };
                },
                processResults: function (data) {
                    var terms = [];
                    if (data.success && data.data) {
                        $.each(data.data, function (id, text) {
                            terms.push({ id: text.id, text: text.text });
                        });
                    }
                    return {
                        results: terms
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: 'Nhập tên hoặc mã sản phẩm...',
            allowClear: true
        });
    }

    // Handle form submission via AJAX
    if ($inventoryForm.length) {
        $inventoryForm.on('submit', function (e) {
            e.preventDefault();

            var $submitButton = $inventoryForm.find('input[type="submit"]');
            $submitButton.prop('disabled', true).val('Đang xử lý...');

            var data = {
                action: 'mi_erp_submit_inventory',
                nonce: mi_erp_admin.nonce,
                product_id: $('#product_id').val(),
                serial_numbers: $('#serial_numbers').val()
            };

            $.post(mi_erp_admin.ajax_url, data, function (response) {
                var $resultsDiv = $('#mi-erp-import-results');
                var $logUl = $('#mi-erp-import-log');
                $logUl.empty();

                if (response.success) {
                    if (response.data && response.data.log) {
                        $.each(response.data.log, function (index, item) {
                            var color = item.status === 'success' ? 'green' : 'red';
                            var icon = item.status === 'success' ? '✓' : '✗';
                            $logUl.append('<li style="color: ' + color + ';"><span style="font-weight:bold;">' + icon + ' ' + item.serial + '</span>: ' + item.message + '</li>');
                        });
                        $resultsDiv.show();
                        $('#serial_numbers').val(''); // Clear textarea
                        alert(response.data.message);
                    } else {
                        alert(response.data);
                        $('#serial_numbers').val(''); // Clear textarea
                    }
                } else {
                    alert('Lỗi: ' + response.data);
                }
            })
                .fail(function () {
                    alert('Có lỗi xảy ra khi kết nối với máy chủ.');
                })
                .always(function () {
                    $submitButton.prop('disabled', false).val('Cập nhật kho');
                });
        });
    }

    // Handle manual activate form submission
    var $manualActivateForm = $('#mi-erp-manual-activate-form');
    if ($manualActivateForm.length) {
        $manualActivateForm.on('submit', function (e) {
            e.preventDefault();

            var $submitButton = $manualActivateForm.find('input[type="submit"]');
            $submitButton.prop('disabled', true).val('Đang xử lý...');

            var data = {
                action: 'mi_erp_manual_activate',
                nonce: mi_erp_admin.nonce,
                serial: $('#manual_serial').val()
            };

            $.post(mi_erp_admin.ajax_url, data, function (response) {
                if (response.success) {
                    alert(response.data);
                    $('#manual_serial').val(''); // Clear input
                } else {
                    alert('Lỗi: ' + response.data);
                }
            })
                .fail(function () {
                    alert('Có lỗi xảy ra khi kết nối với máy chủ.');
                })
                .always(function () {
                    $submitButton.prop('disabled', false).val('Kích hoạt ngay');
                });
        });
    }

    // Initialize DataTables
    var $inventoryTable = $('#mi-erp-inventory-table');
    var inventoryDataTable;
    if ($inventoryTable.length) {
        inventoryDataTable = $inventoryTable.DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": mi_erp_admin.ajax_url,
                "type": "POST",
                "data": function (d) {
                    d.action = 'mi_erp_get_inventory';
                    d.nonce = mi_erp_admin.nonce;
                }
            },
            "columns": [
                { "data": 0 },
                { "data": 1 },
                { "data": 2 },
                { "data": 3 },
                { "data": 4 },
                { "data": 5 }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
            }
        });
    }

    // Refresh DataTables on inventory update
    if ($inventoryForm.length && typeof inventoryDataTable !== 'undefined') {
        $inventoryForm.on('submit', function (e) {
            // We hook into the ajaxComplete or just do it inside the existing submit handler
            // But since it's already defined above, we can just attach an ajaxSuccess event,
            // or simply modify the above submit handler to reload the table.
        });

        // Listen to document ajaxComplete to refresh table if inventory was added
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (settings.data && settings.data.indexOf('action=mi_erp_submit_inventory') !== -1) {
                if (xhr.responseJSON && xhr.responseJSON.success) {
                    inventoryDataTable.ajax.reload(null, false);
                }
            }
        });
    }

    // Initialize Orders DataTables
    var $ordersTable = $('#mi-erp-orders-table');
    if ($ordersTable.length) {
        $ordersTable.DataTable({
            "destroy": true,
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": mi_erp_admin.ajax_url,
                "type": "POST",
                "data": function (d) {
                    d.action = 'mi_erp_get_orders';
                    d.nonce = mi_erp_admin.nonce;
                }
            },
            "columns": [
                { "data": 0 },
                { "data": 1 },
                { "data": 2 },
                { "data": 3 },
                { "data": 4 },
                { "data": 5 },
                { "data": 6 },
                { "data": 7, "orderable": false }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
            },
            "order": [[3, 'desc']] // Order by Date descending by default
        });
    }

    // POS Screen Logic
    var $posForm = $('#mi-erp-pos-form');
    if ($posForm.length) {
        var cart = []; // Array of { id, text, price, formatted_price, qty }

        var $posProductSearch = $('#pos_product_search');
        var $posCartBody = $('#pos_cart_body');
        var $posCartTotal = $('#pos_cart_total');
        var $posEmptyCartRow = $('#pos_empty_cart_row');

        // Initialize Select2 for POS product search (No AJAX needed, options are pre-rendered directly from DB)
        $posProductSearch.select2({
            placeholder: 'Nhập tên hoặc mã sản phẩm...',
            allowClear: true,
            width: '100%'
        });

        function formatCurrency(number) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
        }

        function renderCart() {
            if (cart.length === 0) {
                $posCartBody.html('<tr id="pos_empty_cart_row"><td colspan="4" style="text-align:center; padding:20px; color:#999;">Chưa có sản phẩm nào.</td></tr>');
                $posCartTotal.text('0 ₫');
                return;
            }

            var html = '';
            var total = 0;

            $.each(cart, function (index, item) {
                var itemTotal = item.price * item.qty;
                total += itemTotal;

                html += '<tr>';
                html += '<td><strong>' + item.text + '</strong></td>';
                html += '<td style="text-align:center;"><input type="number" class="pos-qty-input" data-index="' + index + '" value="' + item.qty + '" min="1" style="width:60px;"></td>';
                html += '<td style="text-align:right;">' + item.formatted_price + '</td>';
                html += '<td style="text-align:center;"><button type="button" class="button pos-remove-btn" data-index="' + index + '" style="color:red; border-color:red;">X</button></td>';
                html += '</tr>';
            });

            $posCartBody.html(html);
            $posCartTotal.text(formatCurrency(total));
        }

        // Add Product to Cart
        $('#pos_add_product_btn').on('click', function () {
            var $selected = $posProductSearch.find(':selected');
            var id = $selected.val();

            if (!id) {
                alert('Vui lòng chọn một sản phẩm.');
                return;
            }

            var text = $selected.text();
            var price = parseFloat($selected.data('price')) || 0;
            var formatted_price = $selected.data('formatted-price') || formatCurrency(price);

            // Check if already in cart
            var existingIndex = -1;
            for (var i = 0; i < cart.length; i++) {
                if (cart[i].id == id) {
                    existingIndex = i;
                    break;
                }
            }

            if (existingIndex > -1) {
                cart[existingIndex].qty += 1;
            } else {
                cart.push({
                    id: id,
                    text: text.trim(),
                    price: price,
                    formatted_price: formatted_price,
                    qty: 1
                });
            }

            $posProductSearch.val(null).trigger('change');
            renderCart();
        });

        // Change Qty
        $posCartBody.on('change', '.pos-qty-input', function () {
            var index = $(this).data('index');
            var newQty = parseInt($(this).val());
            if (newQty < 1) newQty = 1;
            cart[index].qty = newQty;
            renderCart();
        });

        // Remove item
        $posCartBody.on('click', '.pos-remove-btn', function () {
            var index = $(this).data('index');
            cart.splice(index, 1);
            renderCart();
        });

        // Submit form
        $posForm.on('submit', function (e) {
            e.preventDefault();

            if (cart.length === 0) {
                alert('Vui lòng thêm ít nhất một sản phẩm vào giỏ hàng!');
                return;
            }

            var $submitBtn = $('#pos_submit_btn');
            $submitBtn.prop('disabled', true).text('Đang xử lý...');

            var data = {
                action: 'mi_erp_create_pos_order',
                nonce: mi_erp_admin.nonce,
                customer_name: $('#pos_customer_name').val(),
                customer_phone: $('#pos_customer_phone').val(),
                customer_address: $('#pos_customer_address').val(),
                sales_employee: $('#pos_sales_employee').val(),
                order_note: $('#pos_order_note').val(),
                cart: cart
            };

            $.post(mi_erp_admin.ajax_url, data, function (response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.url; // Redirect to WC Edit Order screen
                } else {
                    alert('Lỗi: ' + response.data);
                    $submitBtn.prop('disabled', false).text('TẠO ĐƠN HÀNG NGAY');
                }
            }).fail(function () {
                alert('Lỗi kết nối máy chủ.');
                $submitBtn.prop('disabled', false).text('TẠO ĐƠN HÀNG NGAY');
            });
        });
    }
});
