<div class="wrap">
    <h1><?php esc_html_e( 'Bán hàng nhanh (POS)', 'mi-erp-system' ); ?></h1>
    <p class="description">Tạo đơn hàng trực tiếp mà không cần qua nhiều bước trung gian. Đơn hàng sẽ được tự động đồng bộ vào hệ thống WooCommerce và xuất Serial.</p>
    <hr style="margin: 20px 0;">

    <?php
    $users = get_users( array( 'role__in' => array( 'administrator', 'editor', 'shop_manager', 'author' ) ) );
    $current_user_id = get_current_user_id();

    // Kết nối thẳng vào DB không qua hàm WooCommerce
    global $wpdb;
    $products = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_price'
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
        ORDER BY p.post_title ASC
    ");

    // Lấy danh sách kho từ bảng mi_warehouses
    $warehouses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mi_warehouses WHERE status = 'active' ORDER BY name ASC" );
    ?>

    <form id="mi-erp-pos-form" class="flex flex-col lg:flex-row gap-6 mt-6">
        <!-- Cột 1: Khách hàng & Nhân viên -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex-1 min-w-[320px]">
            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Thông tin Khách hàng & Sale</h2>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Tên khách hàng <span class="text-red-500">*</span></label>
                <input type="text" id="pos_customer_name" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required placeholder="Nhập tên khách hàng">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                <input type="text" id="pos_customer_phone" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required placeholder="Nhập số điện thoại">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Kho xuất hàng</label>
                <select id="pos_origin_warehouse" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <?php foreach ( $warehouses as $wh ) : ?>
                        <option value="<?php echo esc_attr( $wh->location ); ?>" data-id="<?php echo esc_attr( $wh->id ); ?>"><?php echo esc_html( $wh->name . ' - ' . $wh->location ); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">-- Nhập địa chỉ khác --</option>
                </select>
                <input type="text" id="pos_custom_origin" class="mt-2 shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 hidden" placeholder="Nhập địa chỉ kho...">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Địa chỉ giao hàng (Điểm đến)</label>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-2 mb-2 w-full">
                    <select id="pos_customer_province" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 w-full min-w-0 text-ellipsis overflow-hidden"><option value="">-- Tỉnh/Thành --</option></select>
                    <select id="pos_customer_district" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 w-full min-w-0 text-ellipsis overflow-hidden"><option value="">-- Quận/Huyện --</option></select>
                    <select id="pos_customer_ward" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 w-full min-w-0 text-ellipsis overflow-hidden"><option value="">-- Phường/Xã --</option></select>
                </div>
                <input type="text" id="pos_customer_street" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 mb-2" placeholder="Số nhà, Tên đường (VD: 65 Hồ 3 Mẫu)">
                <input type="hidden" id="pos_customer_address">
                <button type="button" id="pos_calc_distance_btn" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 border border-gray-300 rounded shadow-sm transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg> Bản đồ & Đo khoảng cách
                </button>
            </div>
            
            <div id="pos-map" class="w-full h-64 bg-gray-200 mb-4 hidden rounded-md border border-gray-300"></div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Khoảng cách giao hàng (km)</label>
                <input type="number" id="pos_delivery_distance" class="bg-gray-100 shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 cursor-not-allowed" placeholder="0" min="0" step="0.1" readonly>
                <span class="text-xs text-gray-500 block mt-1">Hệ thống tự tính (Miễn phí 20km đầu, trên 20km tính 10.000đ/km).</span>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nhân viên bán hàng</label>
                <select id="pos_sales_employee" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700">
                    <option value="">-- Mặc định (Tài khoản hiện tại) --</option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $current_user_id, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Ghi chú đơn hàng</label>
                <textarea id="pos_order_note" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" rows="3" placeholder="Ghi chú thêm (nếu có)"></textarea>
            </div>
        </div>

        <!-- Cột 2: Sản phẩm & Giỏ hàng -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex-[1.5] min-w-[400px] flex flex-col">
            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Giỏ hàng</h2>
            
            <!-- Tìm kiếm sản phẩm -->
            <div class="mb-6 flex flex-col sm:flex-row gap-3 items-start">
                <div class="flex-1 w-full">
                    <select id="pos_product_search" class="w-full">
                        <option value=""><?php esc_html_e( '-- Gõ tên hoặc mã sản phẩm --', 'mi-erp-system' ); ?></option>
                        <?php foreach ( $products as $p ) : 
                            $price = $p->price ? $p->price : 0;
                            $formatted_price = number_format( $price, 0, ',', '.' ) . ' ₫';
                        ?>
                            <option value="<?php echo esc_attr( $p->ID ); ?>" data-price="<?php echo esc_attr( $price ); ?>" data-formatted-price="<?php echo esc_attr( $formatted_price ); ?>">
                                <?php echo esc_html( $p->post_title . ' (#' . $p->ID . ') - ' . $formatted_price ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="pos_add_product_btn" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-2 px-4 border border-indigo-200 rounded shadow-sm transition-all whitespace-nowrap">
                    Thêm vào giỏ
                </button>
            </div>

            <!-- Bảng giỏ hàng -->
            <div class="overflow-x-auto flex-1 border border-gray-200 rounded-md">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="p-3 font-semibold text-gray-700 w-1/2">Sản phẩm</th>
                            <th class="p-3 font-semibold text-gray-700 text-center w-1/5">Số lượng</th>
                            <th class="p-3 font-semibold text-gray-700 text-right w-1/5">Đơn giá</th>
                            <th class="p-3 font-semibold text-gray-700 text-center w-1/10">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="pos_cart_body" class="divide-y divide-gray-100">
                        <tr id="pos_empty_cart_row">
                            <td colspan="4" class="p-8 text-center text-gray-400 italic">Chưa có sản phẩm nào.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <th colspan="2" class="p-3 text-right text-gray-600 font-medium">Phí vận chuyển (Tuỳ chỉnh):</th>
                            <th colspan="2" class="p-3 text-right">
                                <div class="flex items-center justify-end">
                                    <input type="number" id="pos_custom_shipping_fee" class="shadow-sm border border-gray-300 rounded py-1 px-2 text-right w-32 focus:outline-none focus:ring-1 focus:ring-blue-500" value="0" min="0" step="1000">
                                    <span class="ml-2 text-gray-600">₫</span>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <th colspan="2" class="p-3 text-right text-lg text-gray-800 font-bold">Tổng cộng:</th>
                            <th colspan="2" class="p-3 text-right text-xl text-red-600 font-bold" id="pos_cart_total">0 ₫</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Nút tạo đơn -->
            <div class="mt-6 flex justify-end">
                <button type="submit" id="pos_submit_btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    TẠO ĐƠN HÀNG NGAY
                </button>
            </div>
        </div>
    </form>

    <?php
    // Bỏ qua WP enqueue để load trực tiếp, chống mọi lỗi cache plugin
    $admin_dir = dirname( dirname( __FILE__ ) );
    $select2_css = plugins_url( 'css/select2.min.css', $admin_dir . '/fake.php' );
    $select2_js = plugins_url( 'js/vendor/select2.min.js', $admin_dir . '/fake.php' );
    ?>
    <link rel="stylesheet" href="<?php echo esc_url($select2_css); ?>">
    <script src="<?php echo esc_url($select2_js); ?>"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Khởi tạo Javascript trực tiếp để đảm bảo luôn chạy kể cả khi cache JS bên ngoài bị lỗi -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var cart = []; // Array of { id, text, price, formatted_price, qty }
        
        var $posForm = $('#mi-erp-pos-form');
        var $posProductSearch = $('#pos_product_search');
        var $posCartBody = $('#pos_cart_body');
        var $posCartTotal = $('#pos_cart_total');
        
        if ( $posProductSearch.length && typeof $.fn.select2 === 'function' ) {
            $posProductSearch.select2({
                placeholder: 'Nhập tên hoặc mã sản phẩm...',
                allowClear: true,
                width: '100%'
            });
        }

        function formatCurrency(number) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
        }

        function renderCart() {
            if (cart.length === 0) {
                $posCartBody.html('<tr id="pos_empty_cart_row"><td colspan="4" class="p-8 text-center text-gray-400 italic">Chưa có sản phẩm nào.</td></tr>');
                $posCartTotal.text('0 ₫');
                return;
            }

            var html = '';
            var total = 0;
            
            $.each(cart, function(index, item) {
                var itemTotal = item.price * item.qty;
                total += itemTotal;
                
                html += '<tr class="group hover:bg-gray-50 transition-colors">';
                html += '<td class="p-3"><strong class="text-gray-800">' + item.text + '</strong></td>';
                html += '<td class="p-3 text-center"><input type="number" class="pos-qty-input shadow-sm border border-gray-300 rounded py-1 px-2 w-16 text-center focus:outline-none focus:ring-1 focus:ring-blue-500" data-index="' + index + '" value="' + item.qty + '" min="1"></td>';
                html += '<td class="p-3 text-right text-gray-700 font-medium">' + item.formatted_price + '</td>';
                html += '<td class="p-3 text-center"><button type="button" class="pos-remove-btn text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded transition-colors" data-index="' + index + '"><svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></td>';
                html += '</tr>';
            });
            
            $posCartBody.html(html);
            
            // Cập nhật tổng cộng với phí vận chuyển tùy chỉnh
            var shippingFee = parseFloat($('#pos_custom_shipping_fee').val()) || 0;
            var grandTotal = total + shippingFee;
            $posCartTotal.text(formatCurrency(grandTotal));
        }

        // Thay đổi phí vận chuyển tuỳ chỉnh sẽ update lại tổng
        $('#pos_custom_shipping_fee').on('input', function() {
            renderCart();
        });

        // Xử lý ẩn/hiện ô nhập kho custom
        $('#pos_origin_warehouse').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#pos_custom_origin').show();
            } else {
                $('#pos_custom_origin').hide();
            }
        });

        // Logic Bản đồ Leaflet + OSRM
        var map, routeLayer;
        var provincesData = [];
        var $prov = $('#pos_customer_province');
        var $dist = $('#pos_customer_district');
        var $ward = $('#pos_customer_ward');

        // Fetch Provinces with esgoo
        function fetchEsgoo(url, callback) {
            $.getJSON(url, function(res) {
                if (res.error === 0) {
                    callback(res.data);
                } else {
                    callback([]);
                }
            });
        }

        fetchEsgoo('https://esgoo.net/api-tinhthanh/1/0.htm', function(data) {
            populateSelect($prov, data, '-- Tỉnh/Thành --');
        });

        function populateSelect($select, items, defaultText) {
            $select.empty().append('<option value="">' + defaultText + '</option>');
            $.each(items, function(i, item) {
                // Use full_name for better Geocoding compatibility
                var itemName = item.full_name ? item.full_name : item.name;
                $select.append('<option value="' + itemName + '" data-code="' + item.id + '">' + itemName + '</option>');
            });
        }

        $prov.on('change', function() {
            var provCode = $(this).find('option:selected').data('code');
            $ward.empty().append('<option value="">-- Phường/Xã --</option>');
            
            if (provCode) {
                fetchEsgoo('https://esgoo.net/api-tinhthanh/2/' + provCode + '.htm', function(data) {
                    populateSelect($dist, data, '-- Quận/Huyện --');
                });
            } else {
                $dist.empty().append('<option value="">-- Quận/Huyện --</option>');
            }
            updateHiddenAddress();
        });

        $dist.on('change', function() {
            var distCode = $(this).find('option:selected').data('code');
            
            if (distCode) {
                fetchEsgoo('https://esgoo.net/api-tinhthanh/3/' + distCode + '.htm', function(data) {
                    populateSelect($ward, data, '-- Phường/Xã --');
                });
            } else {
                $ward.empty().append('<option value="">-- Phường/Xã --</option>');
            }
            updateHiddenAddress();
        });

        $ward.on('change', updateHiddenAddress);
        $('#pos_customer_street').on('input', updateHiddenAddress);

        function updateHiddenAddress() {
            var street = $('#pos_customer_street').val().trim();
            var prov = $prov.val();
            var dist = $dist.val();
            var ward = $ward.val();
            
            var parts = [];
            if (street) parts.push(street);
            if (ward) parts.push(ward);
            if (dist) parts.push(dist);
            if (prov) parts.push(prov);
            
            $('#pos_customer_address').val(parts.join(', '));
        }
        
        async function geocodeAddress(address) {
            var parts = address.split(',').map(s => s.trim()).filter(s => s);
            
            for (var i = 0; i < parts.length; i++) {
                // Thử từ chi tiết đến chung chung (bỏ dần phần đầu)
                var currentParts = parts.slice(i);
                var query1 = currentParts.join(', '); // Có tiền tố
                
                // Bỏ tiền tố hành chính để phòng trường hợp map không nhận diện được
                var query2 = currentParts.join(', ')
                    .replace(/Thành phố /gi, '')
                    .replace(/Tỉnh /gi, '')
                    .replace(/Quận /gi, '')
                    .replace(/Huyện /gi, '')
                    .replace(/Phường /gi, '')
                    .replace(/Xã /gi, '')
                    .replace(/Thị trấn /gi, '');

                var queriesToTry = [query1, query2];

                for (var q of queriesToTry) {
                    try {
                        // Thử Nominatim
                        var url = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=vn&limit=1&q=' + encodeURIComponent(q);
                        var response = await fetch(url);
                        if (response.ok) {
                            var data = await response.json();
                            if (data && data.length > 0) {
                                return { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon) };
                            }
                        }
                    } catch (e) {
                        console.error("Nominatim failed for " + q);
                    }

                    try {
                        // Fallback sang Photon Komoot nếu Nominatim thất bại
                        var url2 = 'https://photon.komoot.io/api/?limit=1&q=' + encodeURIComponent(q);
                        var response2 = await fetch(url2);
                        if (response2.ok) {
                            var data2 = await response2.json();
                            if (data2 && data2.features && data2.features.length > 0) {
                                var coords = data2.features[0].geometry.coordinates; // [lon, lat]
                                return { lat: parseFloat(coords[1]), lon: parseFloat(coords[0]) };
                            }
                        }
                    } catch (e) {
                        console.error("Photon failed for " + q);
                    }
                }
                
                // Đợi 500ms để tránh Rate Limit nếu phải lặp lại
                await new Promise(r => setTimeout(r, 500));
            }
            return null;
        }

        $('#pos_calc_distance_btn').on('click', async function() {
            var $btn = $(this);
            var origin = $('#pos_origin_warehouse').val();
            if (origin === 'custom') {
                origin = $('#pos_custom_origin').val();
            }
            var destination = $('#pos_customer_address').val();

            if (!origin || !destination) {
                alert('Vui lòng cung cấp cả địa chỉ Kho đi và Địa chỉ nhận.');
                return;
            }

            $btn.prop('disabled', true).text('Đang đo...');

            var originCoords = await geocodeAddress(origin);
            if (!originCoords) {
                alert('Không thể tìm thấy tọa độ cho Kho xuất hàng: ' + origin + '\n\nHãy vào tab "Cài đặt" để lưu lại Kho xuất hàng với cấu trúc chuẩn (có Tỉnh/Thành, Quận/Huyện).');
                $('#pos_distance_result').val('');
                $btn.prop('disabled', false).text('📍 Bản đồ & Đo khoảng cách');
                return;
            }

            var destCoords = await geocodeAddress(destination);
            if (!destCoords) {
                alert('Không thể tìm thấy tọa độ cho Điểm đến: ' + destination + '\n\nBản đồ không nhận diện được ngay cả khi đã thử tìm từng phần của địa chỉ. Hãy thử bỏ số nhà/tên tòa nhà đi.');
                $('#pos_distance_result').val('');
                $btn.prop('disabled', false).text('📍 Bản đồ & Đo khoảng cách');
                return;
            }

            $('#pos-map').show();
            
            if (!map) {
                map = L.map('pos-map').setView([originCoords.lat, originCoords.lon], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
            }

            if (routeLayer) {
                map.removeLayer(routeLayer);
            }

            // Gọi OSRM (Open Source Routing Machine) API miễn phí
            var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' 
                        + originCoords.lon + ',' + originCoords.lat + ';' 
                        + destCoords.lon + ',' + destCoords.lat 
                        + '?overview=full&geometries=geojson';

            try {
                var res = await fetch(osrmUrl);
                var routingData = await res.json();
                if (routingData.routes && routingData.routes.length > 0) {
                    var route = routingData.routes[0];
                    var distanceKm = (route.distance / 1000).toFixed(1);
                    
                    $('#pos_delivery_distance').val(distanceKm);
                    
                    // Tính phí ship (20km free, 10k/km)
                    var km = parseFloat(distanceKm);
                    var fee = 0;
                    if (km > 20) {
                        fee = Math.ceil(km - 20) * 10000;
                    }
                    $('#pos_custom_shipping_fee').val(fee).trigger('input'); // trigger input to update total
                    
                    // Vẽ tuyến đường
                    routeLayer = L.geoJSON(route.geometry, {
                        style: { color: 'blue', weight: 4 }
                    }).addTo(map);
                    
                    // Add markers
                    L.marker([originCoords.lat, originCoords.lon]).addTo(routeLayer).bindPopup("Kho: " + origin);
                    L.marker([destCoords.lat, destCoords.lon]).addTo(routeLayer).bindPopup("Giao: " + destination);
                    
                    map.fitBounds(routeLayer.getBounds());
                } else {
                    alert('Không tìm được đường đi.');
                }
            } catch (e) {
                console.error(e);
                alert('Lỗi tính đường đi.');
            }

            $btn.prop('disabled', false).text('📍 Bản đồ & Đo khoảng cách');
        });

        // Add Product to Cart
        $('#pos_add_product_btn').on('click', function() {
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
        $posCartBody.on('change', '.pos-qty-input', function() {
            var index = $(this).data('index');
            var newQty = parseInt($(this).val());
            if (newQty < 1) newQty = 1;
            cart[index].qty = newQty;
            renderCart();
        });

        // Remove item
        $posCartBody.on('click', '.pos-remove-btn', function() {
            var index = $(this).data('index');
            cart.splice(index, 1);
            renderCart();
        });

        // Submit form
        $posForm.on('submit', function(e) {
            e.preventDefault();

            if (cart.length === 0) {
                alert('Vui lòng thêm ít nhất một sản phẩm vào giỏ hàng!');
                return;
            }

            var $submitBtn = $('#pos_submit_btn');
            $submitBtn.prop('disabled', true).text('Đang xử lý...');

            var distance = parseFloat($('#pos_delivery_distance').val()) || 0;
            var shippingFee = parseFloat($('#pos_custom_shipping_fee').val()) || 0;

            var data = {
                action: 'mi_erp_create_pos_order',
                nonce: '<?php echo wp_create_nonce("mi_erp_admin_nonce"); ?>',
                customer_name: $('#pos_customer_name').val(),
                customer_phone: $('#pos_customer_phone').val(),
                customer_address: $('#pos_customer_address').val(),
                sales_employee: $('#pos_sales_employee').val(),
                order_note: $('#pos_order_note').val(),
                delivery_distance: distance,
                shipping_fee: shippingFee,
                cart: cart
            };

            $.post('<?php echo admin_url("admin-ajax.php"); ?>', data, function(response) {
                if (response.success) {
                    var createAnother = confirm(response.data.message + "\n\nBạn có muốn tạo thêm đơn hàng mới không?\n- Bấm OK để tiếp tục tạo đơn mới.\n- Bấm Cancel (Hủy) để chuyển sang trang xuất Serial.");
                    if (createAnother) {
                        window.location.reload(); // Reload trang để xóa form
                    } else {
                        window.location.href = '<?php echo admin_url("admin.php?page=mi-erp-orders"); ?>';
                    }
                } else {
                    alert('Lỗi: ' + response.data);
                    $submitBtn.prop('disabled', false).text('TẠO ĐƠN HÀNG NGAY');
                }
            }).fail(function() {
                alert('Lỗi kết nối máy chủ.');
                $submitBtn.prop('disabled', false).text('TẠO ĐƠN HÀNG NGAY');
            });
        });
    });
    </script>
</div>
