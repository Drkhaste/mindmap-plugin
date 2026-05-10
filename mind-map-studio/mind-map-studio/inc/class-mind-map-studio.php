<?php
/**
 * Mind Map Studio Main Class.
 */

defined( 'ABSPATH' ) || exit;

class Mind_Map_Studio {

	const CPT_SLUG      = 'mind_map';
	const SETTINGS_SLUG = 'mind-map-settings';

	public static function init() {
		add_action( 'init',                                          array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_menu',                                    array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init',                                    array( __CLASS__, 'register_settings' ) );
		add_action( 'add_meta_boxes',                                array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT_SLUG,                   array( __CLASS__, 'save_mindmap_data' ) );
		add_action( 'admin_enqueue_scripts',                         array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts',                            array( __CLASS__, 'frontend_assets' ) );
		add_shortcode( 'mindmap',                                    array( __CLASS__, 'render_shortcode' ) );
		add_action( 'admin_init',                                    array( __CLASS__, 'tinymce_setup' ) );
		add_action( 'wp_ajax_mind_map_get_list',                     array( __CLASS__, 'ajax_get_mindmap_list' ) );
		add_filter( 'manage_' . self::CPT_SLUG . '_posts_columns',   array( __CLASS__, 'add_shortcode_column' ) );
		add_action( 'manage_' . self::CPT_SLUG . '_posts_custom_column', array( __CLASS__, 'render_shortcode_column' ), 10, 2 );
	}

	/* ──────────────────────────────────────────────
	   CPT
	─────────────────────────────────────────────── */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'نقشه‌های ذهنی', 'post type general name', 'mind-map-studio' ),
			'singular_name'      => _x( 'نقشه ذهنی', 'post type singular name', 'mind-map-studio' ),
			'menu_name'          => _x( 'نقشه‌ساز ذهنی', 'admin menu', 'mind-map-studio' ),
			'add_new'            => _x( 'افزودن جدید', 'mindmap', 'mind-map-studio' ),
			'add_new_item'       => __( 'افزودن نقشه ذهنی جدید', 'mind-map-studio' ),
			'edit_item'          => __( 'ویرایش نقشه ذهنی', 'mind-map-studio' ),
			'all_items'          => __( 'همه نقشه‌ها', 'mind-map-studio' ),
			'not_found'          => __( 'نقشه‌ای یافت نشد.', 'mind-map-studio' ),
			'not_found_in_trash' => __( 'نقشه‌ای در زباله‌دان یافت نشد.', 'mind-map-studio' ),
		);
		register_post_type( self::CPT_SLUG, array(
			'labels'          => $labels,
			'public'          => true,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'query_var'       => true,
			'rewrite'         => array( 'slug' => 'mindmap' ),
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
			'menu_position'   => 20,
			'menu_icon'       => 'dashicons-chart-pie',
			'supports'        => array( 'title' ),
		) );
	}

	/* ──────────────────────────────────────────────
	   ADMIN MENU & SETTINGS
	─────────────────────────────────────────────── */
	public static function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . self::CPT_SLUG,
			__( 'تنظیمات نقشه‌ساز', 'mind-map-studio' ),
			__( 'تنظیمات', 'mind-map-studio' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_text',    array( 'default' => '' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_size',    array( 'default' => 14 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_spacing_desktop', array( 'default' => 220 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_spacing_mobile',  array( 'default' => 110 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_color',   array( 'default' => '#94a3b8' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_watermark_opacity', array( 'default' => 0.18 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_theme_light',       array( 'default' => 'primary' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_theme_dark',        array( 'default' => 'dark' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_line_color',         array( 'default' => '#cbd5e1' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_line_style',         array( 'default' => 'bezier' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_line_width',         array( 'default' => 2 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_node_border_radius', array( 'default' => 5 ) );
		register_setting( 'mind_map_settings_group', 'mind_map_custom_node_bg',     array( 'default' => '#ffffff' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_custom_node_text',   array( 'default' => '#333333' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_custom_root_bg',     array( 'default' => '#334155' ) );
		register_setting( 'mind_map_settings_group', 'mind_map_custom_root_text',   array( 'default' => '#ffffff' ) );
	}

	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'تنظیمات نقشه‌ساز ذهنی', 'mind-map-studio' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'mind_map_settings_group' ); ?>
				<table class="form-table">
					<tr>
						<th><?php _e( 'متن واترمارک', 'mind-map-studio' ); ?></th>
						<td><input type="text" name="mind_map_watermark_text" value="<?php echo esc_attr( get_option( 'mind_map_watermark_text', '' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><?php _e( 'سایز واترمارک (px)', 'mind-map-studio' ); ?></th>
						<td><input type="number" name="mind_map_watermark_size" value="<?php echo esc_attr( get_option( 'mind_map_watermark_size', 14 ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php _e( 'فاصله واترمارک دسکتاپ (px)', 'mind-map-studio' ); ?></th>
						<td><input type="number" name="mind_map_watermark_spacing_desktop" value="<?php echo esc_attr( get_option( 'mind_map_watermark_spacing_desktop', 220 ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php _e( 'فاصله واترمارک موبایل (px)', 'mind-map-studio' ); ?></th>
						<td><input type="number" name="mind_map_watermark_spacing_mobile" value="<?php echo esc_attr( get_option( 'mind_map_watermark_spacing_mobile', 110 ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php _e( 'رنگ واترمارک', 'mind-map-studio' ); ?></th>
						<td><input type="color" name="mind_map_watermark_color" value="<?php echo esc_attr( get_option( 'mind_map_watermark_color', '#94a3b8' ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php _e( 'شفافیت واترمارک (0 تا 1)', 'mind-map-studio' ); ?></th>
						<td><input type="number" step="0.01" min="0" max="1" name="mind_map_watermark_opacity" value="<?php echo esc_attr( get_option( 'mind_map_watermark_opacity', 0.18 ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php _e( 'تم حالت روشن', 'mind-map-studio' ); ?></th>
						<td>
							<select name="mind_map_theme_light">
								<?php self::render_theme_options( get_option( 'mind_map_theme_light', 'primary' ) ); ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'تم حالت تاریک', 'mind-map-studio' ); ?></th>
						<td>
							<select name="mind_map_theme_dark">
								<?php self::render_theme_options( get_option( 'mind_map_theme_dark', 'dark' ) ); ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'رنگ خطوط بین نودها', 'mind-map-studio' ); ?></th>
						<td>
							<input type="color" name="mind_map_line_color" value="<?php echo esc_attr( get_option( 'mind_map_line_color', '#cbd5e1' ) ); ?>" />
							<p class="description"><?php _e( 'رنگ خطوط اتصال بین نودها در نمودار', 'mind-map-studio' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'استایل خطوط', 'mind-map-studio' ); ?></th>
						<td>
							<select name="mind_map_line_style">
								<option value="bezier" <?php selected( get_option( 'mind_map_line_style', 'bezier' ), 'bezier' ); ?>><?php _e( 'منحنی (Bezier)', 'mind-map-studio' ); ?></option>
								<option value="straight" <?php selected( get_option( 'mind_map_line_style', 'straight' ), 'straight' ); ?>><?php _e( 'مستقیم (Straight)', 'mind-map-studio' ); ?></option>
								<option value="rounded" <?php selected( get_option( 'mind_map_line_style', 'rounded' ), 'rounded' ); ?>><?php _e( 'گوشه گرد (Rounded)', 'mind-map-studio' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'ضخامت خطوط (px)', 'mind-map-studio' ); ?></th>
						<td><input type="number" name="mind_map_line_width" value="<?php echo esc_attr( get_option( 'mind_map_line_width', 2 ) ); ?>" min="1" max="10" /></td>
					</tr>
					<tr>
						<th><?php _e( 'گردی لبه نودها (px)', 'mind-map-studio' ); ?></th>
						<td><input type="number" name="mind_map_node_border_radius" value="<?php echo esc_attr( get_option( 'mind_map_node_border_radius', 5 ) ); ?>" min="0" max="50" /></td>
					</tr>
					<tr class="custom-theme-only">
						<th><?php _e( 'رنگ پس‌زمینه نودها (تم کاستوم)', 'mind-map-studio' ); ?></th>
						<td><input type="color" name="mind_map_custom_node_bg" value="<?php echo esc_attr( get_option( 'mind_map_custom_node_bg', '#ffffff' ) ); ?>" /></td>
					</tr>
					<tr class="custom-theme-only">
						<th><?php _e( 'رنگ متن نودها (تم کاستوم)', 'mind-map-studio' ); ?></th>
						<td><input type="color" name="mind_map_custom_node_text" value="<?php echo esc_attr( get_option( 'mind_map_custom_node_text', '#333333' ) ); ?>" /></td>
					</tr>
					<tr class="custom-theme-only">
						<th><?php _e( 'رنگ پس‌زمینه ریشه (تم کاستوم)', 'mind-map-studio' ); ?></th>
						<td><input type="color" name="mind_map_custom_root_bg" value="<?php echo esc_attr( get_option( 'mind_map_custom_root_bg', '#334155' ) ); ?>" /></td>
					</tr>
					<tr class="custom-theme-only">
						<th><?php _e( 'رنگ متن ریشه (تم کاستوم)', 'mind-map-studio' ); ?></th>
						<td><input type="color" name="mind_map_custom_root_text" value="<?php echo esc_attr( get_option( 'mind_map_custom_root_text', '#ffffff' ) ); ?>" /></td>
					</tr>
				</table>
				<script>
				jQuery(document).ready(function($) {
					function toggleCustomColors() {
						var theme = $('select[name="mind_map_theme_light"]').val();
						if (theme === 'custom') {
							$('.custom-theme-only').show();
						} else {
							$('.custom-theme-only').hide();
						}
					}
					$('select[name="mind_map_theme_light"]').on('change', toggleCustomColors);
					toggleCustomColors();
				});
				</script>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
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
		add_meta_box(
			'mind_map_editor',
			__( 'ادیتور نقشه ذهنی', 'mind-map-studio' ),
			array( __CLASS__, 'render_editor_meta_box' ),
			self::CPT_SLUG,
			'normal',
			'high'
		);
	}

	public static function render_editor_meta_box( $post ) {
		$data   = get_post_meta( $post->ID, '_mind_map_data', true );
		$layout = get_post_meta( $post->ID, '_mind_map_layout', true ) ?: 'both';
		wp_nonce_field( 'mind_map_save', 'mind_map_nonce' );

		$custom_css = '';
		if ( get_option( 'mind_map_theme_light' ) === 'custom' ) {
			$custom_css = sprintf(
				'--mms-node-bg: %s; --mms-node-text: %s; --mms-root-bg: %s; --mms-root-text: %s;',
				get_option( 'mind_map_custom_node_bg', '#ffffff' ),
				get_option( 'mind_map_custom_node_text', '#333333' ),
				get_option( 'mind_map_custom_root_bg', '#334155' ),
				get_option( 'mind_map_custom_root_text', '#ffffff' )
			);
		}
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
		</style>
		<?php
	}

	/* ──────────────────────────────────────────────
	   SAVE
	─────────────────────────────────────────────── */
	public static function save_mindmap_data( $post_id ) {
		if ( ! isset( $_POST['mind_map_nonce'] ) || ! wp_verify_nonce( $_POST['mind_map_nonce'], 'mind_map_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( isset( $_POST['mind_map_data'] ) ) {
			update_post_meta( $post_id, '_mind_map_data', wp_unslash( $_POST['mind_map_data'] ) );
		}
		if ( isset( $_POST['mind_map_layout'] ) ) {
			update_post_meta( $post_id, '_mind_map_layout', sanitize_text_field( $_POST['mind_map_layout'] ) );
		}
	}

	/* ──────────────────────────────────────────────
	   ASSETS - ADMIN
	─────────────────────────────────────────────── */
	public static function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) return;
		if ( $screen->post_type !== self::CPT_SLUG ) return;

		wp_enqueue_style(  'jsmind',                MIND_MAP_STUDIO_URL . 'assets/css/jsmind.css', array(), '0.5.2' );
		wp_enqueue_script( 'jsmind',                MIND_MAP_STUDIO_URL . 'assets/vendor/jsmind.js',    array(), '0.5.2', true );
		wp_enqueue_script( 'mindmap-studio-admin',  MIND_MAP_STUDIO_URL . 'assets/js/mindmap-admin.js',           array( 'jquery', 'jsmind' ), MIND_MAP_STUDIO_VERSION, true );

		wp_localize_script( 'mindmap-studio-admin', 'mindMapStudioSettings', array(
			'watermark' => array(
				'text'    => get_option( 'mind_map_watermark_text', '' ),
				'size'    => get_option( 'mind_map_watermark_size', 14 ),
				'spacing_desktop' => get_option( 'mind_map_watermark_spacing_desktop', 220 ),
				'spacing_mobile'  => get_option( 'mind_map_watermark_spacing_mobile', 110 ),
				'color'   => get_option( 'mind_map_watermark_color', '#94a3b8' ),
				'opacity' => get_option( 'mind_map_watermark_opacity', 0.18 ),
			),
			'theme'      => get_option( 'mind_map_theme_light', 'primary' ),
			'line_style' => get_option( 'mind_map_line_style', 'bezier' ),
			'line_width' => get_option( 'mind_map_line_width', 2 ),
			'border_radius' => get_option( 'mind_map_node_border_radius', 5 ),
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
		// enqueue واقعی داخل render_shortcode انجام می‌شه
		wp_register_style(  'jsmind',                 MIND_MAP_STUDIO_URL . 'assets/css/jsmind.css', array(), '0.5.2' );
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
					'text'    => get_option( 'mind_map_watermark_text', '' ),
					'size'    => (int) get_option( 'mind_map_watermark_size', 14 ),
					'spacing_desktop' => (int) get_option( 'mind_map_watermark_spacing_desktop', 220 ),
					'spacing_mobile'  => (int) get_option( 'mind_map_watermark_spacing_mobile', 110 ),
					'color'   => get_option( 'mind_map_watermark_color', '#94a3b8' ),
					'opacity' => (float) get_option( 'mind_map_watermark_opacity', 0.18 ),
				),
				'theme_light' => get_option( 'mind_map_theme_light', 'primary' ),
				'theme_dark'  => get_option( 'mind_map_theme_dark', 'dark' ),
				'line_color'  => get_option( 'mind_map_line_color', '#cbd5e1' ),
				'line_style'  => get_option( 'mind_map_line_style', 'bezier' ),
				'line_width'  => get_option( 'mind_map_line_width', 2 ),
				'border_radius' => get_option( 'mind_map_node_border_radius', 5 ),
			);
			wp_add_inline_script(
				'mindmap-studio-frontend',
				'window.mindMapStudioSettings = ' . wp_json_encode( $inline_settings ) . ';',
				'before'
			);
			$settings_printed = true;
		}

		$unique_id = 'mms_' . $post_id . '_' . wp_unique_id();

		// Custom theme CSS variables
		$custom_css = '';
		if ( get_option( 'mind_map_theme_light' ) === 'custom' || get_option( 'mind_map_theme_dark' ) === 'custom' ) {
			$custom_css = sprintf(
				'--mms-node-bg: %s; --mms-node-text: %s; --mms-root-bg: %s; --mms-root-text: %s;',
				get_option( 'mind_map_custom_node_bg', '#ffffff' ),
				get_option( 'mind_map_custom_node_text', '#333333' ),
				get_option( 'mind_map_custom_root_bg', '#334155' ),
				get_option( 'mind_map_custom_root_text', '#ffffff' )
			);
		}

		ob_start();
		?>
		<div class="mindmap-studio-wrapper" style="width:100%;margin:20px 0; <?php echo esc_attr( $custom_css ); ?>">
			<div class="mindmap-studio-capture" id="capture_<?php echo esc_attr( $unique_id ); ?>" style="width:100%;background:transparent;position:relative;">
				<div
					id="<?php echo esc_attr( $unique_id ); ?>"
					class="mindmap-studio-container"
					data-mindmap-data="<?php echo esc_attr( $data ); ?>"
					data-mindmap-layout="<?php echo esc_attr( $layout ); ?>"
					data-line-color="<?php echo esc_attr( get_option( 'mind_map_line_color', '#cbd5e1' ) ); ?>">
				</div>
			</div>
		</div>
		<style>
			#<?php echo esc_attr( $unique_id ); ?> jmnode {
				font-family: inherit !important;
				border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
			}
			#<?php echo esc_attr( $unique_id ); ?> { direction: ltr !important; overflow: hidden !important; }
			#<?php echo esc_attr( $unique_id ); ?> jmexpander { display: none !important; }
			.mindmap-studio-capture { background-repeat: repeat !important; }
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
}
