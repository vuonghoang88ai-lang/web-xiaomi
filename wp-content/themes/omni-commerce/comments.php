<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area mt-12 pt-8 border-t border-slate-200">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title text-2xl font-bold mb-6">
			<?php
			$viomi_theme_comment_count = get_comments_number();
			if ( '1' === $viomi_theme_comment_count ) {
				printf(
					/* translators: 1: title. */
					esc_html__( '1 bình luận', 'omni-commerce' )
				);
			} else {
				printf( 
					/* translators: 1: comment count number, 2: title. */
					esc_html( _nx( '%1$s bình luận', '%1$s bình luận', $viomi_theme_comment_count, 'comments title', 'omni-commerce' ) ),
					number_format_i18n( $viomi_theme_comment_count )
				);
			}
			?>
		</h2>
		<ol class="comment-list list-none p-0">
			<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => true,
			) );
			?>
		</ol>
		<?php the_comments_navigation(); ?>
		<?php if ( ! comments_open() ) : ?>
			<p class="no-comments text-slate-500 mt-4"><?php esc_html_e( 'Bình luận đã đóng.', 'omni-commerce' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	comment_form( array(
		'class_submit'  => 'bg-primary text-white px-6 py-2 rounded-md font-bold hover:bg-primary-container cursor-pointer transition-colors',
		'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title text-xl font-bold mb-4">',
		'title_reply_after'  => '</h2>',
	) );
	?>
</div>
