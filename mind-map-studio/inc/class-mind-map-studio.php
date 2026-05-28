<?php
/**
 * Mind Map Studio Main Class.
 */

defined( 'ABSPATH' ) || exit;

class Mind_Map_Studio {

	const CPT_COURSE    = 'mms_course';
	const CPT_LESSON    = 'mms_lesson';
	const CPT_TOPIC     = 'mms_topic';
	const CPT_SLUG      = 'mind_map'; // Keeping for backward compatibility if needed, but primary will be the new ones
	const SETTINGS_SLUG = 'mind-map-settings';

	public static function init() {
		add_action( 'init',                                          array( __CLASS__, 'register_post_types' ) );
		add_action( 'admin_menu',                                    array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'add_meta_boxes',                                array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post',                                     array( __CLASS__, 'save_meta_box_data' ) );
		add_action( 'admin_enqueue_scripts',                         array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts',                            array( __CLASS__, 'frontend_assets' ) );
		add_shortcode( 'mindmap',                                    array( __CLASS__, 'render_shortcode' ) );
		add_action( 'admin_init',                                    array( __CLASS__, 'tinymce_setup' ) );
		add_action( 'wp_ajax_mind_map_get_list',                     array( __CLASS__, 'ajax_get_mindmap_list' ) );
		add_action( 'wp_ajax_mms_get_lessons',                       array( __CLASS__, 'ajax_get_lessons' ) );
		add_action( 'wp_ajax_mms_update_order',                      array( __CLASS__, 'ajax_update_order' ) );
		add_action( 'init',                                          array( __CLASS__, 'custom_rewrite_rules' ) );
		add_filter( 'query_vars',                                    array( __CLASS__, 'register_query_vars' ) );
		add_filter( 'manage_' . self::CPT_SLUG . '_posts_columns',   array( __CLASS__, 'add_shortcode_column' ) );
		add_action( 'manage_' . self::CPT_SLUG . '_posts_custom_column', array( __CLASS__, 'render_shortcode_column' ), 10, 2 );
		add_filter( 'template_include',                              array( __CLASS__, 'load_custom_templates' ) );
		add_filter( 'post_type_link',                                array( __CLASS__, 'filter_mms_links' ), 10, 2 );

		// Flush rules if needed (Temporary for update)
		if ( get_option( 'mms_flush_rules_needed' ) ) {
			add_action( 'init', function() {
				self::custom_rewrite_rules();
				flush_rewrite_rules();
				delete_option( 'mms_flush_rules_needed' );
			}, 99 );
		}
	}

	/* ──────────────────────────────────────────────
	   CPT
	─────────────────────────────────────────────── */
	public static function register_post_types() {
		// Courses
		register_post_type( self::CPT_COURSE, array(
			'labels' => array(
				'name'          => __( 'کورس‌های نقشه ذهنی', 'mind-map-studio' ),
				'singular_name' => __( 'کورس', 'mind-map-studio' ),
				'menu_name'     => __( 'نقشه‌ساز ذهنی', 'mind-map-studio' ),
				'all_items'     => __( 'همه کورس‌ها', 'mind-map-studio' ),
				'add_new'       => __( 'افزودن کورس جدید', 'mind-map-studio' ),
				'add_new_item'  => __( 'افزودن کورس جدید', 'mind-map-studio' ),
			),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => 'mms_builder_page',
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-welcome-learn-more',
		) );

		// Lessons
		register_post_type( self::CPT_LESSON, array(
			'labels' => array(
				'name'          => __( 'درس‌ها', 'mind-map-studio' ),
				'singular_name' => __( 'درس', 'mind-map-studio' ),
				'all_items'     => __( 'همه درس‌ها', 'mind-map-studio' ),
				'add_new'       => __( 'افزودن درس جدید', 'mind-map-studio' ),
				'add_new_item'  => __( 'افزودن درس جدید', 'mind-map-studio' ),
			),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => 'mms_builder_page',
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		) );

		// Topics (The ones containing Mind Maps)
		register_post_type( self::CPT_TOPIC, array(
			'labels' => array(
				'name'          => __( 'مباحث', 'mind-map-studio' ),
				'singular_name' => __( 'مبحث', 'mind-map-studio' ),
				'all_items'     => __( 'همه مباحث', 'mind-map-studio' ),
				'add_new'       => __( 'افزودن مبحث جدید', 'mind-map-studio' ),
				'add_new_item'  => __( 'افزودن مبحث جدید', 'mind-map-studio' ),
			),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => 'mms_builder_page',
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		) );

		// Original Mind Map CPT (Optional, maybe keep for existing data)
		register_post_type( self::CPT_SLUG, array(
			'labels' => array(
				'name'          => __( 'نقشه‌های قدیمی', 'mind-map-studio' ),
				'singular_name' => __( 'نقشه ذهنی', 'mind-map-studio' ),
			),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => 'mms_builder_page',
			'supports'     => array( 'title' ),
		) );
	}

	/* ──────────────────────────────────────────────
	   ADMIN MENU & SETTINGS
	─────────────────────────────────────────────── */
	public static function add_admin_menu() {
		add_menu_page(
			__( 'نقشه‌ساز ذهنی', 'mind-map-studio' ),
			__( 'نقشه‌ساز ذهنی', 'mind-map-studio' ),
			'manage_options',
			'mms_builder_page',
			null,
			'dashicons-chart-pie',
			20
		);
	}

	private static function render_theme_options( $selected ) {
		$themes = array(
			'primary'   => 'Primary',
			'modern'    => 'Modern (جدید)',
			'custom'    => 'Custom (سفارشی)',
			'orange'    => 'Orange',
			'blue'      => 'Blue',
			'greyscale' => 'Greyscale',
			'dark'      => 'Dark',
		);
		foreach ( $themes as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $label )
			);
		}
	}

	/* ──────────────────────────────────────────────
	   META BOX
	─────────────────────────────────────────────── */
	public static function add_meta_boxes() {
		// Old Mind Map Editor (Keep for now)
		add_meta_box(
			'mind_map_editor',
			__( 'ادیتور نقشه ذهنی', 'mind-map-studio' ),
			array( __CLASS__, 'render_editor_meta_box' ),
			self::CPT_SLUG,
			'normal',
			'high'
		);

		// English Slug for all
		$slug_post_types = array( self::CPT_COURSE, self::CPT_LESSON, self::CPT_TOPIC );
		foreach ( $slug_post_types as $post_type ) {
			add_meta_box(
				'mms_english_slug_meta_box',
				__( 'نامک انگلیسی (Slug)', 'mind-map-studio' ),
				array( __CLASS__, 'render_english_slug_meta_box' ),
				$post_type,
				'side'
			);
		}

		// Lesson -> Course relationship
		add_meta_box(
			'mms_lesson_parent_meta_box',
			__( 'انتخاب کورس', 'mind-map-studio' ),
			array( __CLASS__, 'render_lesson_parent_meta_box' ),
			self::CPT_LESSON,
			'side'
		);

		// Topic -> Course & Lesson relationship
		add_meta_box(
			'mms_topic_course_meta_box',
			__( 'انتخاب کورس', 'mind-map-studio' ),
			array( __CLASS__, 'render_topic_course_meta_box' ),
			self::CPT_TOPIC,
			'side'
		);
		add_meta_box(
			'mms_topic_lesson_meta_box',
			__( 'انتخاب درس', 'mind-map-studio' ),
			array( __CLASS__, 'render_topic_lesson_meta_box' ),
			self::CPT_TOPIC,
			'side'
		);

		// Ordering
		add_meta_box(
			'mms_course_lessons_order_meta_box',
			__( 'ترتیب درس‌ها', 'mind-map-studio' ),
			array( __CLASS__, 'render_course_lessons_order_meta_box' ),
			self::CPT_COURSE,
			'normal'
		);
		add_meta_box(
			'mms_lesson_topics_order_meta_box',
			__( 'ترتیب مباحث', 'mind-map-studio' ),
			array( __CLASS__, 'render_lesson_topics_order_meta_box' ),
			self::CPT_LESSON,
			'normal'
		);

		// Mind Map Repeater for Topics
		add_meta_box(
			'mms_topic_mindmaps_meta_box',
			__( 'طراحی نقشه‌های ذهنی', 'mind-map-studio' ),
			array( __CLASS__, 'render_topic_mindmaps_meta_box' ),
			self::CPT_TOPIC,
			'normal',
			'high'
		);
	}

	public static function render_english_slug_meta_box( $post ) {
		$slug = get_post_meta( $post->ID, '_mms_english_slug', true );
		wp_nonce_field( 'mms_save_english_slug', 'mms_english_slug_nonce' );
		?>
		<input type="text" name="mms_english_slug" value="<?php echo esc_attr( $slug ); ?>" class="widefat">
		<p class="description"><?php _e( 'فقط حروف انگلیسی، اعداد و خط تیره.', 'mind-map-studio' ); ?></p>
		<?php
	}

	public static function render_lesson_parent_meta_box( $post ) {
		$course_id = get_post_meta( $post->ID, '_mms_course_id', true );
		$courses = get_posts( array( 'post_type' => self::CPT_COURSE, 'numberposts' => -1 ) );
		wp_nonce_field( 'mms_save_lesson_parent', 'mms_lesson_parent_nonce' );
		?>
		<select name="mms_course_id" class="widefat">
			<option value=""><?php _e( '— انتخاب کنید —', 'mind-map-studio' ); ?></option>
			<?php foreach ( $courses as $course ) : ?>
				<option value="<?php echo $course->ID; ?>" <?php selected( $course_id, $course->ID ); ?>><?php echo esc_html( $course->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public static function render_topic_course_meta_box( $post ) {
		$course_id = get_post_meta( $post->ID, '_mms_course_id', true );
		$courses = get_posts( array( 'post_type' => self::CPT_COURSE, 'numberposts' => -1 ) );
		wp_nonce_field( 'mms_save_topic_course', 'mms_topic_course_nonce' );
		?>
		<select name="mms_course_id" id="mms_course_id" class="widefat">
			<option value=""><?php _e( '— انتخاب کنید —', 'mind-map-studio' ); ?></option>
			<?php foreach ( $courses as $course ) : ?>
				<option value="<?php echo $course->ID; ?>" <?php selected( $course_id, $course->ID ); ?>><?php echo esc_html( $course->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public static function render_topic_lesson_meta_box( $post ) {
		$course_id = get_post_meta( $post->ID, '_mms_course_id', true );
		$lesson_id = get_post_meta( $post->ID, '_mms_lesson_id', true );
		$lessons = array();
		if ( $course_id ) {
			$lessons = get_posts( array(
				'post_type'  => self::CPT_LESSON,
				'meta_key'   => '_mms_course_id',
				'meta_value' => $course_id,
				'numberposts' => -1
			) );
		}
		wp_nonce_field( 'mms_save_topic_lesson', 'mms_topic_lesson_nonce' );
		?>
		<select name="mms_lesson_id" id="mms_lesson_id" class="widefat">
			<option value=""><?php _e( '— انتخاب کنید —', 'mind-map-studio' ); ?></option>
			<?php foreach ( $lessons as $lesson ) : ?>
				<option value="<?php echo $lesson->ID; ?>" <?php selected( $lesson_id, $lesson->ID ); ?>><?php echo esc_html( $lesson->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<script>
		jQuery(document).ready(function($) {
			$('#mms_course_id').on('change', function() {
				var course_id = $(this).val();
				var $lesson_select = $('#mms_lesson_id');
				$lesson_select.empty().append('<option value=""><?php _e( 'در حال بارگذاری...', 'mind-map-studio' ); ?></option>');

				$.post(ajaxurl, {
					action: 'mms_get_lessons',
					course_id: course_id
				}, function(response) {
					$lesson_select.empty().append('<option value=""><?php _e( '— انتخاب کنید —', 'mind-map-studio' ); ?></option>');
					if (response.success) {
						$.each(response.data, function(i, lesson) {
							$lesson_select.append('<option value="' + lesson.id + '">' + lesson.title + '</option>');
						});
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function render_course_lessons_order_meta_box( $post ) {
		$lessons = get_posts( array(
			'post_type'  => self::CPT_LESSON,
			'meta_key'   => '_mms_course_id',
			'meta_value' => $post->ID,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'numberposts' => -1
		) );
		echo '<ul class="mms-sortable-items" data-post-type="mms_lesson">';
		foreach ( $lessons as $lesson ) {
			echo '<li data-id="' . $lesson->ID . '" style="padding: 10px; background: #fff; border: 1px solid #ccd0d4; margin-bottom: 5px; cursor: move;"><span class="dashicons dashicons-menu"></span> ' . esc_html( $lesson->post_title ) . '</li>';
		}
		echo '</ul>';
		wp_nonce_field( 'mms_update_order', 'mms_order_nonce' );
	}

	public static function render_topic_mindmaps_meta_box( $post ) {
		$accordions = get_post_meta( $post->ID, '_mms_accordions', true ) ?: array();
		wp_nonce_field( 'mms_save_accordions', 'mms_accordions_nonce' );
		?>
		<div id="mms-accordions-repeater">
			<div class="mms-accordions-container">
				<?php foreach ( $accordions as $a_index => $accordion ) : ?>
					<div class="mms-accordion-item" style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; background: #f9f9f9; border-radius: 8px;">
						<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
							<input type="text" name="mms_accordions[<?php echo $a_index; ?>][title]" value="<?php echo esc_attr( $accordion['title'] ); ?>" placeholder="<?php _e( 'عنوان آکاردئون (مثلاً: نقشه کلی)', 'mind-map-studio' ); ?>" style="width: 80%; font-weight: bold;">
							<button type="button" class="button button-link-delete mms-remove-accordion" style="color: #d63638;"><?php _e( 'حذف آکاردئون', 'mind-map-studio' ); ?></button>
						</div>

						<div class="mms-mindmaps-container" style="padding-right: 20px; border-right: 2px solid #ddd;">
							<?php
							$mindmaps = isset( $accordion['mindmaps'] ) ? $accordion['mindmaps'] : array();
							foreach ( $mindmaps as $m_index => $mindmap ) :
								$unique_id = "mms_editor_{$a_index}_{$m_index}";
							?>
								<div class="mms-mindmap-item" style="margin-bottom: 30px; border: 1px solid #eee; padding: 10px; background: #fff;">
									<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
										<input type="text" name="mms_accordions[<?php echo $a_index; ?>][mindmaps][<?php echo $m_index; ?>][title]" value="<?php echo esc_attr( $mindmap['title'] ); ?>" placeholder="<?php _e( 'عنوان نقشه', 'mind-map-studio' ); ?>" style="width: 70%;">
										<button type="button" class="button button-link-delete mms-remove-mindmap" style="color: #d63638;"><?php _e( 'حذف نقشه', 'mind-map-studio' ); ?></button>
									</div>
									<div class="mms-editor-wrapper" data-id="<?php echo $unique_id; ?>">
										<div class="mindmap-toolbar" style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
											<button type="button" class="button mms-btn-insert-template"><?php _e( 'درج قالب', 'mind-map-studio' ); ?></button>
											<button type="button" class="button mms-btn-clear-text"><?php _e( 'پاکسازی', 'mind-map-studio' ); ?></button>
											<button type="button" class="button <?php echo ($mindmap['layout'] === 'both' ? 'button-primary' : ''); ?> mms-btn-toggle-layout">
												<?php echo $mindmap['layout'] === 'both' ? __( '📏 چیدمان دو طرفه', 'mind-map-studio' ) : __( '🌲 درختی یک طرفه', 'mind-map-studio' ); ?>
											</button>
											<input type="hidden" name="mms_accordions[<?php echo $a_index; ?>][mindmaps][<?php echo $m_index; ?>][layout]" class="mms-layout-input" value="<?php echo esc_attr( $mindmap['layout'] ); ?>">

											<div class="visual-edit-group" style="margin-right:20px; display:flex; gap:5px; border-right:1px solid #ccc; padding-right:15px;">
												<button type="button" class="button button-secondary mms-btn-add-child"><span class="dashicons dashicons-plus-alt" style="margin-top:4px;"></span></button>
												<button type="button" class="button button-secondary mms-btn-add-sibling"><span class="dashicons dashicons-plus" style="margin-top:4px;"></span></button>
												<button type="button" class="button button-link-delete mms-btn-delete-node" style="color:#d63638;"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
											</div>
										</div>
										<div style="display:flex;gap:10px;">
											<textarea name="mms_accordions[<?php echo $a_index; ?>][mindmaps][<?php echo $m_index; ?>][data]" class="mms-mindmap-data" style="width:30%; height:300px; font-family: monospace; direction: ltr;"><?php echo esc_textarea( $mindmap['data'] ); ?></textarea>
											<div class="mms-jsmind-container" id="<?php echo $unique_id; ?>" style="flex:1; height:300px; border:1px solid #ccc; background:#fff;"></div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button button-secondary mms-add-mindmap"><?php _e( '+ افزودن نقشه ذهنی جدید به این آکاردئون', 'mind-map-studio' ); ?></button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-primary" id="mms-add-accordion"><?php _e( '++ افزودن آکاردئون جدید', 'mind-map-studio' ); ?></button>
		</div>

		<style>
			.mms-jsmind-container jmnode {
				font-family: inherit !important;
				border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
			}
			.mms-jsmind-container jmexpander { display: none !important; }
		</style>
		<?php
	}

	public static function render_lesson_topics_order_meta_box( $post ) {
		$topics = get_posts( array(
			'post_type'  => self::CPT_TOPIC,
			'meta_key'   => '_mms_lesson_id',
			'meta_value' => $post->ID,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'numberposts' => -1
		) );
		echo '<ul class="mms-sortable-items" data-post-type="mms_topic">';
		foreach ( $topics as $topic ) {
			echo '<li data-id="' . $topic->ID . '" style="padding: 10px; background: #fff; border: 1px solid #ccd0d4; margin-bottom: 5px; cursor: move;"><span class="dashicons dashicons-menu"></span> ' . esc_html( $topic->post_title ) . '</li>';
		}
		echo '</ul>';
		wp_nonce_field( 'mms_update_order', 'mms_order_nonce' );
	}

	public static function render_editor_meta_box( $post ) {
		$data   = get_post_meta( $post->ID, '_mind_map_data', true );
		$layout = get_post_meta( $post->ID, '_mind_map_layout', true ) ?: 'both';
		wp_nonce_field( 'mind_map_save', 'mind_map_nonce' );

		$custom_css = '';
		?>
		<div id="mind-map-admin-editor" style="<?php echo esc_attr( $custom_css ); ?>">
			<div class="mindmap-toolbar" style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
				<button type="button" class="button" id="btn-insert-template"><?php _e( 'درج قالب', 'mind-map-studio' ); ?></button>
				<button type="button" class="button" id="btn-clear-text"><?php _e( 'پاکسازی', 'mind-map-studio' ); ?></button>
				<button type="button" class="button <?php echo $layout === 'both' ? 'button-primary' : ''; ?>" id="btn-toggle-layout">
					<?php echo $layout === 'both' ? __( '📏 چیدمان دو طرفه', 'mind-map-studio' ) : __( '🌲 درختی یک طرفه', 'mind-map-studio' ); ?>
				</button>
				<input type="hidden" name="mind_map_layout" id="mind_map_layout" value="<?php echo esc_attr( $layout ); ?>">

				<div class="visual-edit-group" style="margin-right:20px; display:flex; gap:5px; border-right:1px solid #ccc; padding-right:15px;">
					<button type="button" class="button button-secondary" id="btn-add-child" title="<?php _e( 'افزودن نود فرزند', 'mind-map-studio' ); ?>"><span class="dashicons dashicons-plus-alt" style="margin-top:4px;"></span> <?php _e( 'فرزند', 'mind-map-studio' ); ?></button>
					<button type="button" class="button button-secondary" id="btn-add-sibling" title="<?php _e( 'افزودن نود هم‌سطح', 'mind-map-studio' ); ?>"><span class="dashicons dashicons-plus" style="margin-top:4px;"></span> <?php _e( 'هم‌سطح', 'mind-map-studio' ); ?></button>
					<button type="button" class="button button-link-delete" id="btn-delete-node" title="<?php _e( 'حذف نود', 'mind-map-studio' ); ?>" style="color:#d63638;"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
				</div>

				<span style="margin-right:auto;font-size:12px;color:#666;"><?php _e( 'Tab = فاصله‌گذاری، Shift+Tab = برگشت', 'mind-map-studio' ); ?></span>
			</div>

			<div style="display:flex;gap:20px;margin-top:10px;">
				<div style="flex:0 0 300px;">
					<textarea id="mind_map_data" name="mind_map_data"
						style="width:100%;height:500px;font-family:'Vazirmatn',monospace;direction:rtl;text-align:right;font-size:13px;line-height:1.8;"
					><?php echo esc_textarea( $data ); ?></textarea>
				</div>
				<div style="flex:1;border:1px solid #ccd0d4;position:relative;background:#fff;min-height:500px;overflow:visible;">
					<div id="capture_area" style="width:100%;height:500px;position:relative;">
						<div id="jsmind_container" style="width:100%;height:100%;"></div>
					</div>
					<div style="position:absolute;bottom:10px;left:10px;display:flex;gap:4px;">
						<button type="button" id="zoom-in"  class="button" style="font-size:16px;line-height:1;padding:2px 8px;">+</button>
						<button type="button" id="zoom-out" class="button" style="font-size:16px;line-height:1;padding:2px 8px;">−</button>
					</div>
				</div>
			</div>
		</div>
		<style>
			#jsmind_container jmnode {
				font-family: inherit !important;
				border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
			}
			jmexpander { display: none !important; }
			.mms-modal jmnode {
				border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
			}
		</style>
		<?php
	}

	/* ──────────────────────────────────────────────
	   SAVE
	─────────────────────────────────────────────── */
	public static function save_meta_box_data( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		// Old Mind Map data
		if ( isset( $_POST['mind_map_nonce'] ) && wp_verify_nonce( $_POST['mind_map_nonce'], 'mind_map_save' ) ) {
			if ( isset( $_POST['mind_map_data'] ) ) {
				update_post_meta( $post_id, '_mind_map_data', wp_unslash( $_POST['mind_map_data'] ) );
			}
			if ( isset( $_POST['mind_map_layout'] ) ) {
				update_post_meta( $post_id, '_mind_map_layout', sanitize_text_field( $_POST['mind_map_layout'] ) );
			}
		}

		// English Slug
		if ( isset( $_POST['mms_english_slug_nonce'] ) && wp_verify_nonce( $_POST['mms_english_slug_nonce'], 'mms_save_english_slug' ) ) {
			if ( isset( $_POST['mms_english_slug'] ) ) {
				$slug = sanitize_title( $_POST['mms_english_slug'] );
				update_post_meta( $post_id, '_mms_english_slug', $slug );

				// Optional: Sync with post_name
				remove_action( 'save_post', array( __CLASS__, 'save_meta_box_data' ) );
				wp_update_post( array( 'ID' => $post_id, 'post_name' => $slug ) );
				add_action( 'save_post', array( __CLASS__, 'save_meta_box_data' ) );
			}
		}

		// Lesson Parent
		if ( isset( $_POST['mms_lesson_parent_nonce'] ) && wp_verify_nonce( $_POST['mms_lesson_parent_nonce'], 'mms_save_lesson_parent' ) ) {
			if ( isset( $_POST['mms_course_id'] ) ) {
				update_post_meta( $post_id, '_mms_course_id', absint( $_POST['mms_course_id'] ) );
			}
		}

		// Topic Parent
		if ( isset( $_POST['mms_topic_course_nonce'] ) && wp_verify_nonce( $_POST['mms_topic_course_nonce'], 'mms_save_topic_course' ) ) {
			if ( isset( $_POST['mms_course_id'] ) ) {
				update_post_meta( $post_id, '_mms_course_id', absint( $_POST['mms_course_id'] ) );
			}
		}
		if ( isset( $_POST['mms_topic_lesson_nonce'] ) && wp_verify_nonce( $_POST['mms_topic_lesson_nonce'], 'mms_save_topic_lesson' ) ) {
			if ( isset( $_POST['mms_lesson_id'] ) ) {
				update_post_meta( $post_id, '_mms_lesson_id', absint( $_POST['mms_lesson_id'] ) );
			}
		}

		// Accordions & Mind Maps
		if ( isset( $_POST['mms_accordions_nonce'] ) && wp_verify_nonce( $_POST['mms_accordions_nonce'], 'mms_save_accordions' ) ) {
			if ( isset( $_POST['mms_accordions'] ) ) {
				$accordions = $_POST['mms_accordions'];
				// Data sanitization could be improved here, but we need to keep the structure
				foreach ( $accordions as &$accordion ) {
					$accordion['title'] = sanitize_text_field( $accordion['title'] );
					if ( isset( $accordion['mindmaps'] ) ) {
						foreach ( $accordion['mindmaps'] as &$mindmap ) {
							$mindmap['title'] = sanitize_text_field( $mindmap['title'] );
							$mindmap['data'] = wp_unslash( $mindmap['data'] ); // Keep the spacing/indents
							$mindmap['layout'] = sanitize_text_field( $mindmap['layout'] );
						}
					} else {
						$accordion['mindmaps'] = array();
					}
				}
				update_post_meta( $post_id, '_mms_accordions', $accordions );
			} else {
				delete_post_meta( $post_id, '_mms_accordions' );
			}
		}
	}

	/* ──────────────────────────────────────────────
	   ASSETS - ADMIN
	─────────────────────────────────────────────── */
	public static function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) return;
		$allowed_post_types = array( self::CPT_SLUG, self::CPT_COURSE, self::CPT_LESSON, self::CPT_TOPIC );
		if ( ! in_array( $screen->post_type, $allowed_post_types ) ) return;

		wp_enqueue_style(  'jsmind',                MIND_MAP_STUDIO_URL . 'assets/css/jsmind.css', array(), '0.5.2' );
		wp_enqueue_script( 'jsmind',                MIND_MAP_STUDIO_URL . 'assets/vendor/jsmind.js',    array(), '0.5.2', true );
		wp_enqueue_script( 'mindmap-studio-admin',  MIND_MAP_STUDIO_URL . 'assets/js/mindmap-admin.js',           array( 'jquery', 'jsmind' ), MIND_MAP_STUDIO_VERSION, true );

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'mms-admin-scripts',     MIND_MAP_STUDIO_URL . 'assets/js/mms-admin-scripts.js', array('jquery', 'jquery-ui-sortable'), MIND_MAP_STUDIO_VERSION, true );

		if ( $screen->post_type === self::CPT_TOPIC ) {
			wp_enqueue_script( 'mms-topic-editor',  MIND_MAP_STUDIO_URL . 'assets/js/mms-topic-editor.js', array( 'jquery', 'jsmind' ), MIND_MAP_STUDIO_VERSION, true );
		}

		wp_localize_script( 'mindmap-studio-admin', 'mindMapStudioSettings', array(
			'watermark' => array(
				'text'    => '',
				'size'    => 14,
				'spacing_desktop' => 220,
				'spacing_mobile'  => 110,
				'color'   => '#94a3b8',
				'opacity' => 0.18,
			),
			'theme'      => 'primary',
			'line_style' => 'bezier',
			'line_width' => 2,
			'border_radius' => 5,
		) );
	}

	/* ──────────────────────────────────────────────
	   ASSETS - FRONTEND
	   *** اینجا بود مشکل اصلی ***
	   wp_enqueue_scripts خیلی زود اجرا می‌شه و $post
	   هنوز آماده نیست. راه‌حل: shortcode خودش assets
	   رو enqueue می‌کنه (late enqueue).
	─────────────────────────────────────────────── */
	public static function frontend_assets() {
		// این تابع فقط اسکریپت‌ها رو register می‌کنه، enqueue نمی‌کنه
		// enqueue واقعی داخل render_shortcode یا فایل‌های تمپلیت انجام می‌شه
		wp_register_style(  'jsmind',                 MIND_MAP_STUDIO_URL . 'assets/css/jsmind.css', array(), '0.5.2' );
		wp_register_style(  'mms-frontend',           MIND_MAP_STUDIO_URL . 'assets/css/mms-frontend.css', array(), MIND_MAP_STUDIO_VERSION );
		wp_register_script( 'jsmind',                 MIND_MAP_STUDIO_URL . 'assets/vendor/jsmind.js',    array(), '0.5.2', true );
		wp_register_script( 'mindmap-studio-frontend', MIND_MAP_STUDIO_URL . 'assets/js/mindmap-frontend.js',        array( 'jsmind' ), MIND_MAP_STUDIO_VERSION, true );
	}

	/* ──────────────────────────────────────────────
	   SHORTCODE
	─────────────────────────────────────────────── */
	public static function render_shortcode( $atts ) {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts );
		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) return '';

		$data   = get_post_meta( $post_id, '_mind_map_data', true );
		$layout = get_post_meta( $post_id, '_mind_map_layout', true ) ?: 'both';
		if ( ! $data ) return '';

		// ── Enqueue اسکریپت‌ها درست اینجا ──
		wp_enqueue_style(  'jsmind' );
		wp_enqueue_script( 'jsmind' );
		wp_enqueue_script( 'mindmap-studio-frontend' );

		// settings رو inline به صفحه اضافه کن - مطمئن‌ترین روش
		static $settings_printed = false;
		if ( ! $settings_printed ) {
			$inline_settings = array(
				'watermark'   => array(
					'text'    => '',
					'size'    => 14,
					'spacing_desktop' => 220,
					'spacing_mobile'  => 110,
					'color'   => '#94a3b8',
					'opacity' => 0.18,
				),
				'theme_light' => 'primary',
				'theme_dark'  => 'dark',
				'line_color'  => '#cbd5e1',
				'line_style'  => 'bezier',
				'line_width'  => 2,
				'border_radius' => 5,
			);
			wp_add_inline_script(
				'mindmap-studio-frontend',
				'window.mindMapStudioSettings = ' . wp_json_encode( $inline_settings ) . ';',
				'before'
			);
			$settings_printed = true;
		}

		$unique_id = 'mms_' . $post_id . '_' . wp_unique_id();

		ob_start();
		?>
		<div class="mindmap-studio-wrapper" style="width:100%;margin:20px 0;">
			<div class="mindmap-studio-capture" id="capture_<?php echo esc_attr( $unique_id ); ?>" style="width:100%;background:transparent;position:relative;">
				<div
					id="<?php echo esc_attr( $unique_id ); ?>"
					class="mindmap-studio-container"
					data-mindmap-data="<?php echo esc_attr( $data ); ?>"
					data-mindmap-layout="<?php echo esc_attr( $layout ); ?>"
					data-line-color="#cbd5e1"
					data-line-style="bezier"
					data-line-width="2">
				</div>
			</div>
		</div>
		<style>
			#<?php echo esc_attr( $unique_id ); ?> jmnode {
				font-family: inherit !important;
				border-radius: 5px !important;
			}
			#<?php echo esc_attr( $unique_id ); ?> { direction: ltr !important; overflow: hidden !important; }
			#<?php echo esc_attr( $unique_id ); ?> jmexpander { display: none !important; }
			.mindmap-studio-capture { background-repeat: repeat !important; }
			.mms-modal jmnode {
				border-radius: 5px !important;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/* ──────────────────────────────────────────────
	   TINYMCE
	─────────────────────────────────────────────── */
	public static function tinymce_setup() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) return;
		$rich = get_user_option( 'rich_editing' );
		if ( $rich === '0' || $rich === false ) return;

		add_filter( 'mce_external_plugins', array( __CLASS__, 'add_tinymce_plugin' ) );
		add_filter( 'mce_buttons',          array( __CLASS__, 'register_tinymce_button' ) );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'tinymce_vars' ), 1 );
		add_action( 'admin_enqueue_scripts', function() {
			wp_add_inline_style( 'wp-admin', 'i.mce-i-mind_map:before{content:"\f185"!important;font-family:dashicons!important;color:#333!important;}' );
		});
	}

	public static function tinymce_vars() {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base ) return;
		echo '<script>window.mind_map_vars={nonce:"' . esc_js( wp_create_nonce( 'mind_map_tinymce' ) ) . '"};</script>';
	}

	public static function add_tinymce_plugin( $plugin_array ) {
		$plugin_array['mind_map'] = MIND_MAP_STUDIO_URL . 'assets/js/mindmap-tinymce.js';
		return $plugin_array;
	}

	public static function register_tinymce_button( $buttons ) {
		$buttons[] = 'mind_map';
		return $buttons;
	}

	/* ──────────────────────────────────────────────
	   TEMPLATES
	─────────────────────────────────────────────── */
	public static function load_custom_templates( $template ) {
		if ( is_singular( self::CPT_COURSE ) ) {
			$plugin_template = MIND_MAP_STUDIO_PATH . 'templates/single-mms_course.php';
			if ( file_exists( $plugin_template ) ) return $plugin_template;
		} elseif ( is_singular( self::CPT_LESSON ) ) {
			$plugin_template = MIND_MAP_STUDIO_PATH . 'templates/single-mms_lesson.php';
			if ( file_exists( $plugin_template ) ) return $plugin_template;
		} elseif ( is_singular( self::CPT_TOPIC ) ) {
			$plugin_template = MIND_MAP_STUDIO_PATH . 'templates/single-mms_topic.php';
			if ( file_exists( $plugin_template ) ) return $plugin_template;
		}
		return $template;
	}

	/* ──────────────────────────────────────────────
	   COLUMNS & AJAX
	─────────────────────────────────────────────── */
	public static function add_shortcode_column( $columns ) {
		$columns['shortcode'] = __( 'کد کوتاه', 'mind-map-studio' );
		return $columns;
	}

	public static function render_shortcode_column( $column, $post_id ) {
		if ( 'shortcode' === $column ) {
			echo '<code>[mindmap id="' . $post_id . '"]</code>';
		}
	}

	public static function ajax_get_mindmap_list() {
		check_ajax_referer( 'mind_map_tinymce', 'security' );
		$query = new WP_Query( array(
			'post_type'      => self::CPT_SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		$list = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$list[] = array( 'id' => get_the_ID(), 'title' => get_the_title() );
			}
			wp_reset_postdata();
		}
		wp_send_json_success( $list );
	}

	public static function ajax_get_lessons() {
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		$lessons = get_posts( array(
			'post_type'  => self::CPT_LESSON,
			'meta_key'   => '_mms_course_id',
			'meta_value' => $course_id,
			'numberposts' => -1,
			'orderby'    => 'title',
			'order'      => 'ASC'
		) );
		$data = array();
		foreach ( $lessons as $lesson ) {
			$data[] = array( 'id' => $lesson->ID, 'title' => $lesson->post_title );
		}
		wp_send_json_success( $data );
	}

	public static function ajax_update_order() {
		check_ajax_referer( 'mms_update_order', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();

		$order = isset( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : array();
		foreach ( $order as $index => $post_id ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'menu_order' => $index,
			) );
		}
		wp_send_json_success();
	}

	public static function custom_rewrite_rules() {
		add_rewrite_rule(
			'^mindmap/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?post_type=mms_topic&name=$matches[3]&mms_topic_slug=$matches[3]&mms_lesson_slug=$matches[2]&mms_course_slug=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^mindmap/([^/]+)/([^/]+)/?$',
			'index.php?post_type=mms_lesson&name=$matches[2]&mms_lesson_slug=$matches[2]&mms_course_slug=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^mindmap/([^/]+)/?$',
			'index.php?post_type=mms_course&name=$matches[1]&mms_course_slug=$matches[1]',
			'top'
		);
	}

	public static function register_query_vars( $vars ) {
		$vars[] = 'mms_course_slug';
		$vars[] = 'mms_lesson_slug';
		$vars[] = 'mms_topic_slug';
		return $vars;
	}

	public static function get_mms_permalink( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) return '';

		$post_type = $post->post_type;
		$slugs = array();

		switch ( $post_type ) {
			case self::CPT_TOPIC:
				$topic_slug = $post->post_name;
				$lesson_id = get_post_meta( $post_id, '_mms_lesson_id', true );
				if ( $lesson_id ) {
					$lesson = get_post( $lesson_id );
					$lesson_slug = $lesson ? $lesson->post_name : '';
					$course_id = get_post_meta( $post_id, '_mms_course_id', true );
					if ( $course_id ) {
						$course = get_post( $course_id );
						$course_slug = $course ? $course->post_name : '';
						if ( $topic_slug && $lesson_slug && $course_slug ) {
							$slugs = array( $course_slug, $lesson_slug, $topic_slug );
						}
					}
				}
				break;
			case self::CPT_LESSON:
				$lesson_slug = $post->post_name;
				$course_id = get_post_meta( $post_id, '_mms_course_id', true );
				if ( $course_id ) {
					$course = get_post( $course_id );
					$course_slug = $course ? $course->post_name : '';
					if ( $lesson_slug && $course_slug ) {
						$slugs = array( $course_slug, $lesson_slug );
					}
				}
				break;
			case self::CPT_COURSE:
				$slugs = array( $post->post_name );
				break;
		}

		if ( ! empty( $slugs ) ) {
			return home_url( '/mindmap/' . implode( '/', $slugs ) . '/' );
		}

		// If it's one of our CPTs but we don't have enough slugs (e.g. no parent set), return a basic link
		$mms_post_types = array( self::CPT_COURSE, self::CPT_LESSON, self::CPT_TOPIC );
		if ( in_array( $post_type, $mms_post_types ) ) {
			return home_url( '/mindmap/' . $post->post_name . '/' );
		}

		return ''; // Fallback for filter
	}

	public static function filter_mms_links( $post_link, $post ) {
		$mms_post_types = array( self::CPT_COURSE, self::CPT_LESSON, self::CPT_TOPIC );
		if ( in_array( $post->post_type, $mms_post_types ) ) {
			$new_link = self::get_mms_permalink( $post->ID );
			if ( $new_link ) return $new_link;
		}
		return $post_link;
	}
}
