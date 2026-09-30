<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template part: Pre-Footer Trust Badges
 * Hiển thị 4 biểu tượng cam kết chất lượng Xiaomi Store.
 * Nguồn dữ liệu: ACF Options `mi_trust_badges` (repeater: icon, title).
 * Fallback: 4 badge tĩnh chuẩn theo AGENTS.md khi chưa cấu hình ACF.
 */
$trust_badges = function_exists( 'get_field' ) ? get_field( 'mi_trust_badges', 'option' ) : [];

// Fallback chuẩn 4 badge theo quy định AGENTS.md §6.6
if ( empty( $trust_badges ) ) {
    $trust_badges = [
        [ 'icon' => 'verified',        'title' => '100% Chính hãng'     ],
        [ 'icon' => 'local_shipping',  'title' => 'Giao hàng siêu tốc' ],
        [ 'icon' => 'support_agent',   'title' => 'Bảo hành tận tâm'   ],
        [ 'icon' => 'assignment_return','title' => 'Đổi trả 7 ngày'    ],
    ];
}
?>
<!-- 6. Trust Badges Section -->
<section class="py-12 bg-surface-container-low border-t border-outline-variant">
    <div class="max-w-[1440px] mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ( $trust_badges as $badge ) : ?>
            <div class="flex flex-col items-center justify-center text-center p-6 bg-white rounded-xl shadow-sm border border-outline-variant hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <?php if ( ! empty( $badge['icon'] ) && filter_var( $badge['icon'], FILTER_VALIDATE_URL ) ) : ?>
                    <img loading="lazy" src="<?php echo esc_url( $badge['icon'] ); ?>" alt="<?php echo esc_attr( $badge['title'] ); ?>" class="w-16 h-16 object-contain mb-4" />
                <?php else : ?>
                    <div class="size-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;"><?php echo esc_html( $badge['icon'] ?: 'verified' ); ?></span>
                    </div>
                <?php endif; ?>
                <h4 class="font-bold text-label-md text-on-surface"><?php echo esc_html( $badge['title'] ); ?></h4>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
