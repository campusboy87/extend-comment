<?php
/*
Plugin Name: Расширенные комментарии
Version: 1.1
Plugin URI: https://wp-kama.ru/?p=8342
Description: Плагин, добавляющий произвольные поля в форму комментариев.
Author: Campusboy
Author URI: https://wp-plus.ru/
*/


// Добавляем поля для незарегистрированных пользователей
add_filter( 'comment_form_default_fields', 'extend_comment_default_fields' );
function extend_comment_default_fields( $fields ) {

	$fields['phone'] = '<p class="comment-form-phone">' .
	                   '<label for="phone">' . __( 'Phone' ) . '</label>' .
	                   '<input id="phone" name="phone" type="text" size="30"/></p>';

	return $fields;
}

// Добавляем поля для всех пользователей
add_action( 'comment_form_logged_in_after', 'extend_comment_custom_fields' );
add_action( 'comment_form_after_fields', 'extend_comment_custom_fields' );
function extend_comment_custom_fields() {

	echo '<p class="comment-form-title">' .
	     '<label for="title">' . __( 'Comment Title' ) . '</label>' .
	     '<input id="title" name="title" type="text" size="30"/></p>';

	echo '<p class="comment-form-rating">' .
	     '<label for="rating">' . __( 'Rating' ) . '<span class="required">*</span></label>
			  <span class="commentratingbox">';

	for ( $i = 1; $i <= 5; $i ++ ) {
		echo '
		<label class="commentrating">
			<input type="radio" name="rating" id="rating" value="' . $i . '"/> ' . $i . '&nbsp;&nbsp;&nbsp;
		</label>';
	}

	echo '</span></p>';
}

// Сохраняем поля
add_action( 'comment_post', 'save_extend_comment_meta_data' );
function save_extend_comment_meta_data( $comment_id ) {

	if ( ! empty( $_POST['phone'] ) ) {
		$phone = sanitize_text_field( $_POST['phone'] );
		add_comment_meta( $comment_id, 'phone', $phone );
	}

	if ( ! empty( $_POST['title'] ) ) {
		$title = sanitize_text_field( $_POST['title'] );
		add_comment_meta( $comment_id, 'title', $title );
	}

	if ( ! empty( $_POST['rating'] ) ) {
		$rating = intval( $_POST['rating'] );
		add_comment_meta( $comment_id, 'rating', $rating );
	}

}


// Проверяем, заполнено ли поле "Рейтинг"
add_filter( 'preprocess_comment', 'verify_extend_comment_meta_data' );
function verify_extend_comment_meta_data( $commentdata ) {

	if ( empty( $_POST['rating'] ) || ! (int) $_POST['rating'] ) {
		wp_die( __( 'Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.' ) );
	}

	return $commentdata;
}


// Отображение содержимого метаполей во фронт-энде
add_filter( 'comment_text', 'modify_extend_comment' );
function modify_extend_comment( $text ) {
	if ( $commenttitle = get_comment_meta( get_comment_ID(), 'title', true ) ) {
		$commenttitle = '<strong>' . esc_attr( $commenttitle ) . '</strong><br/>';
		$text         = $commenttitle . $text;
	}

	if ( $commentrating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {

		if ( ! function_exists( 'wp_star_rating' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		$commentrating = wp_star_rating( array(
			'rating' => $commentrating,
			'echo'   => false
		) );

		$text = $text . $commentrating;
	}

	return $text;
}


// Добавляем шаблон рейтинга и его стили
add_action( 'wp_enqueue_scripts', 'check_count_extend_comments' );
function check_count_extend_comments() {
	if ( is_singular() && comments_open() ) {

		wp_enqueue_style( 'dashicons' );

		$stars_css = '
		.comment-form label.commentrating { display: inline-block }
		.star-rating .star-full:before { content: "\f155"; }
		.star-rating .star-half:before { content: "\f459"; }
		.star-rating .star-empty:before { content: "\f154"; }
		.star-rating .star {
			color: #0074A2;
			display: inline-block;
			font-family: dashicons;
			font-size: 20px;
			font-style: normal;
			font-weight: 400;
			height: 20px;
			line-height: 1;
			text-align: center;
			text-decoration: inherit;
			vertical-align: top;
			width: 20px;
		}
		';

		wp_add_inline_style( 'dashicons', $stars_css );
	}
}

// Добавляем новый метабокс на страницу редактирования комментария
add_action( 'add_meta_boxes_comment', 'extend_comment_add_meta_box' );
function extend_comment_add_meta_box() {
	add_meta_box( 'title', __( 'Comment Metadata - Extend Comment' ), 'extend_comment_meta_box', 'comment', 'normal', 'high' );
}

// Отображаем наши поля
function extend_comment_meta_box( $comment ) {
	$phone  = get_comment_meta( $comment->comment_ID, 'phone', true );
	$title  = get_comment_meta( $comment->comment_ID, 'title', true );
	$rating = get_comment_meta( $comment->comment_ID, 'rating', true );

	wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false );
	?>
    <p>
        <label for="phone"><?php _e( 'Phone' ); ?></label>
        <input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat"/>
    </p>
    <p>
        <label for="title"><?php _e( 'Comment Title' ); ?></label>
        <input type="text" name="title" value="<?php echo esc_attr( $title ); ?>" class="widefat"/>
    </p>
    <p>
        <label for="rating"><?php _e( 'Rating: ' ); ?></label>
        <span class="commentratingbox">
		<?php
		for ( $i = 1; $i <= 5; $i ++ ) {
			echo '
		  <span class="commentrating">
				<input type="radio" name="rating" id="rating" value="' . $i . '" ' . checked( $i, $rating, 0 ) . '/>
		  </span>';
		}
		?>
		</span>
    </p>
	<?php
}

// Сохраняем данные метаполей, заполенных в админке на странице редактирования комментария
add_action( 'edit_comment', 'extend_comment_edit_meta_data' );
function extend_comment_edit_meta_data( $comment_id ) {
	if ( ! isset( $_POST['extend_comment_update'] ) || ! wp_verify_nonce( $_POST['extend_comment_update'], 'extend_comment_update' ) ) {
		return;
	}

	if ( ! empty( $_POST['phone'] ) ) {
		$phone = sanitize_text_field( $_POST['phone'] );
		update_comment_meta( $comment_id, 'phone', $phone );
	} else {
		delete_comment_meta( $comment_id, 'phone' );
	}

	if ( ! empty( $_POST['title'] ) ) {
		$title = sanitize_text_field( $_POST['title'] );
		update_comment_meta( $comment_id, 'title', $title );
	} else {
		delete_comment_meta( $comment_id, 'title' );
	}

	if ( ! empty( $_POST['rating'] ) ) {
		$rating = intval( $_POST['rating'] );
		update_comment_meta( $comment_id, 'rating', $rating );
	} else {
		delete_comment_meta( $comment_id, 'rating' );
	}
}

/**
 * Возвращает HTML блок со средним рейтингом в виде звездочек для текущего поста.
 *
 * @see wp_star_rating()
 *
 * @return string
 */
function get_the_extend_comment_post_star_rating() {
	if ( ! function_exists( 'wp_star_rating' ) ) {
		require_once ABSPATH . 'wp-admin/includes/template.php';
	}

	$star_rating = wp_star_rating( array(
		'rating' => get_the_extend_comment_post_rating(),
		'echo'   => false
	) );

	return $star_rating;
}

/**
 * Возвращает средний рейтинг поста.
 *
 * @return string
 */
function get_the_extend_comment_post_rating() {
	global $wpdb;
	$rating = 0;

	if ( is_singular() ) {
		$post_id = (int) get_the_ID();
		$rating  = wp_cache_get( $post_id, 'post_rating' );

		if ( false === $rating ) {

			$rating = $wpdb->get_var( "
                SELECT AVG(cm.meta_value) as avg
                FROM $wpdb->commentmeta as cm
                JOIN $wpdb->comments as c
                ON cm.comment_id = c.comment_ID
                WHERE c.comment_post_ID IN ($post_id) AND cm.meta_key = 'rating'
	        " );
			$rating = $rating ?: 0;

			wp_cache_add( $post_id, $rating, 'post_rating' );
		}


	}

	return $rating;
}