<?php
/**
 * Plugin Name:       Leon Popup Gate (AFF Pairs)
 * Plugin URI:        https://example.com/
 * Description:       Gated popup flow with affiliate pairs and chapter detection logic.
 * Version:           1.2.2
 * Author:            Your Name
 * Text Domain:       leon-popup-gate-affpairs
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leon_Popup_Gate_AFFPairs {
    const VERSION = '1.2.2';
    const OPTION_SETTINGS = 'lpgc_settings_v120';
    const OPTION_BYPASS_ROLES = 'lpgc_bypass_roles_v120';

    /**
     * Initialize hooks.
     */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_popup_markup' ] );
        register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );
    }

    /**
     * Activation hook to ensure defaults are stored.
     */
    public static function activate() : void {
        $settings = get_option( self::OPTION_SETTINGS, [] );
        if ( empty( $settings ) || ! is_array( $settings ) ) {
            update_option( self::OPTION_SETTINGS, self::get_default_settings() );
        }
        $roles = get_option( self::OPTION_BYPASS_ROLES, [] );
        if ( ! is_array( $roles ) ) {
            update_option( self::OPTION_BYPASS_ROLES, [] );
        }
    }

    /**
     * Get default settings array.
     */
    public static function get_default_settings() : array {
        return [
            'enable'           => 1,
            'delay_ms'         => 1000,
            'ttl_ms'           => 21600 * 1000,
            'cookie_version'   => 1,
            'notice_html'      => '<p class="ndt-notice">Chương này đã bị khóa. Vui lòng bấm vào nút mở ứng dụng trên Popup để mở khóa nội dung.</p>',
            'note_text'        => 'Lưu ý: Quảng cáo trên chỉ xuất hiện 1 lần trong ngày, mong Quý độc giả ủng hộ.',
            'thanks_text'      => 'Xin chân thành cảm ơn!',
            'popup_intro_line1'=> 'Mời bạn CLICK vào liên kết bên dưới và',
            'popup_intro_line2'=> 'Mở Ứng Dụng %APP% để mở khóa toàn bộ chương truyện!',
            'pairs'            => [
                [
                    'type'    => 'shopee',
                    'url'     => 'https://s.shopee.vn/4AorzINDJl',
                    'image'   => '',
                    'enabled' => 1,
                ],
            ],
        ];
    }

    /**
     * Retrieve merged settings with defaults.
     */
    public static function get_settings() : array {
        $stored = get_option( self::OPTION_SETTINGS, [] );
        if ( ! is_array( $stored ) ) {
            $stored = [];
        }

        $defaults = self::get_default_settings();
        $settings = array_merge( $defaults, $stored );

        // Ensure pairs exist and sanitized structure.
        if ( empty( $settings['pairs'] ) || ! is_array( $settings['pairs'] ) ) {
            $settings['pairs'] = $defaults['pairs'];
        }

        return $settings;
    }

    /**
     * Register settings and sanitize callbacks.
     */
    public static function register_settings() : void {
        register_setting( 'lpgc_group_v120', self::OPTION_SETTINGS, [ __CLASS__, 'sanitize_settings' ] );
        register_setting( 'lpgc_group_v120', self::OPTION_BYPASS_ROLES, [ __CLASS__, 'sanitize_bypass_roles' ] );
    }

    /**
     * Sanitize plugin settings.
     *
     * @param array|string $input Raw input.
     *
     * @return array
     */
    public static function sanitize_settings( $input ) : array {
        $defaults = self::get_default_settings();
        $sanitized = $defaults;

        if ( ! is_array( $input ) ) {
            return $sanitized;
        }

        $sanitized['enable'] = empty( $input['enable'] ) ? 0 : 1;
        $sanitized['delay_ms'] = isset( $input['delay_ms'] ) ? max( 0, intval( $input['delay_ms'] ) ) : $defaults['delay_ms'];
        $sanitized['ttl_ms'] = isset( $input['ttl_ms'] ) ? max( 0, intval( $input['ttl_ms'] ) ) : $defaults['ttl_ms'];

        $cookie_version = isset( $input['cookie_version'] ) ? intval( $input['cookie_version'] ) : $defaults['cookie_version'];
        if ( $cookie_version < 1 ) {
            $cookie_version = 1;
        }
        $sanitized['cookie_version'] = $cookie_version;

        $sanitized['notice_html'] = isset( $input['notice_html'] ) ? wp_kses_post( $input['notice_html'] ) : $defaults['notice_html'];
        $sanitized['note_text'] = isset( $input['note_text'] ) ? sanitize_text_field( $input['note_text'] ) : $defaults['note_text'];
        $sanitized['thanks_text'] = isset( $input['thanks_text'] ) ? sanitize_text_field( $input['thanks_text'] ) : $defaults['thanks_text'];
        $sanitized['popup_intro_line1'] = isset( $input['popup_intro_line1'] ) ? sanitize_text_field( $input['popup_intro_line1'] ) : $defaults['popup_intro_line1'];
        $sanitized['popup_intro_line2'] = isset( $input['popup_intro_line2'] ) ? sanitize_text_field( $input['popup_intro_line2'] ) : $defaults['popup_intro_line2'];

        $pairs = [];
        if ( isset( $input['pairs'] ) && is_array( $input['pairs'] ) ) {
            foreach ( $input['pairs'] as $pair ) {
                if ( empty( $pair['url'] ) ) {
                    continue;
                }

                $type = isset( $pair['type'] ) ? sanitize_text_field( $pair['type'] ) : 'shopee';
                if ( ! in_array( $type, [ 'shopee', 'tiktok', 'lazada' ], true ) ) {
                    $type = 'shopee';
                }

                $pairs[] = [
                    'type'    => $type,
                    'url'     => esc_url_raw( $pair['url'] ),
                    'image'   => isset( $pair['image'] ) ? esc_url_raw( $pair['image'] ) : '',
                    'enabled' => empty( $pair['enabled'] ) ? 0 : 1,
                ];
            }
        }

        if ( empty( $pairs ) ) {
            $pairs = $defaults['pairs'];
        }

        $sanitized['pairs'] = $pairs;

        return $sanitized;
    }

    /**
     * Sanitize bypass roles option.
     *
     * @param array|string $input Raw input.
     *
     * @return array
     */
    public static function sanitize_bypass_roles( $input ) : array {
        if ( ! function_exists( 'get_role' ) ) {
            return [];
        }

        $roles = [];
        if ( is_array( $input ) ) {
            foreach ( $input as $role => $value ) {
                if ( empty( $value ) ) {
                    continue;
                }
                $role_key = sanitize_key( $role );
                if ( get_role( $role_key ) ) {
                    $roles[ $role_key ] = 1;
                }
            }
        }
        return $roles;
    }

    /**
     * Register settings page.
     */
    public static function register_menu() : void {
        add_options_page(
            __( 'Leon Popup Gate (AFF)', 'leon-popup-gate-affpairs' ),
            __( 'Leon Popup Gate (AFF)', 'leon-popup-gate-affpairs' ),
            'manage_options',
            'leon-popup-gate-affpairs',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue admin assets.
     */
    public static function enqueue_admin_assets( string $hook ) : void {
        if ( 'settings_page_leon-popup-gate-affpairs' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'lpgc-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', [], self::VERSION );
        wp_enqueue_script( 'lpgc-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', [], self::VERSION, true );
        wp_localize_script( 'lpgc-admin', 'LPGCAdmin', [
            'newRowHTML' => self::get_aff_pair_row_template(),
        ] );
    }

    /**
     * Render settings page.
     */
    public static function render_settings_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = self::get_settings();
        $bypass_roles = get_option( self::OPTION_BYPASS_ROLES, [] );
        if ( ! is_array( $bypass_roles ) ) {
            $bypass_roles = [];
        }

        $roles = self::get_all_roles();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Leon Popup Gate (AFF) Settings', 'leon-popup-gate-affpairs' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'lpgc_group_v120' ); ?>
                <h2><?php esc_html_e( 'General', 'leon-popup-gate-affpairs' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[enable]" value="1" <?php checked( ! empty( $settings['enable'] ) ); ?> />
                                    <?php esc_html_e( 'Activate popup gate', 'leon-popup-gate-affpairs' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Delay (ms)', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="number" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[delay_ms]" value="<?php echo esc_attr( $settings['delay_ms'] ); ?>" min="0" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'TTL (ms)', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="number" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[ttl_ms]" value="<?php echo esc_attr( $settings['ttl_ms'] ); ?>" min="0" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Cookie Version', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="number" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[cookie_version]" value="<?php echo esc_attr( $settings['cookie_version'] ); ?>" min="1" />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e( 'Custom Texts', 'leon-popup-gate-affpairs' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Notice HTML', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <textarea name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[notice_html]" rows="5" cols="50"><?php echo esc_textarea( $settings['notice_html'] ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Displayed when the popup is closed without unlocking.', 'leon-popup-gate-affpairs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Note text', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[note_text]" value="<?php echo esc_attr( $settings['note_text'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Thanks text', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[thanks_text]" value="<?php echo esc_attr( $settings['thanks_text'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Intro line 1', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[popup_intro_line1]" value="<?php echo esc_attr( $settings['popup_intro_line1'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Intro line 2', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[popup_intro_line2]" value="<?php echo esc_attr( $settings['popup_intro_line2'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Use %APP% placeholder for the affiliate platform name.', 'leon-popup-gate-affpairs' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e( 'Affiliate Pairs', 'leon-popup-gate-affpairs' ); ?></h2>
                <table class="widefat striped" id="lpgc-aff-pairs-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Enabled', 'leon-popup-gate-affpairs' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'leon-popup-gate-affpairs' ); ?></th>
                            <th><?php esc_html_e( 'URL', 'leon-popup-gate-affpairs' ); ?></th>
                            <th><?php esc_html_e( 'Image URL', 'leon-popup-gate-affpairs' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'leon-popup-gate-affpairs' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $settings['pairs'] as $index => $pair ) : ?>
                            <?php echo self::get_aff_pair_row_html( $index, $pair ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" class="button" id="lpgc-add-row">+ <?php esc_html_e( 'Thêm dòng', 'leon-popup-gate-affpairs' ); ?></button>
                </p>

                <h2><?php esc_html_e( 'Bypass Roles', 'leon-popup-gate-affpairs' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'User Roles', 'leon-popup-gate-affpairs' ); ?></th>
                            <td>
                                <?php foreach ( $roles as $role_key => $role ) : ?>
                                    <label class="lpgc-role-checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_BYPASS_ROLES ); ?>[<?php echo esc_attr( $role_key ); ?>]" value="1" <?php checked( isset( $bypass_roles[ $role_key ] ) ); ?> />
                                        <?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
                                    </label><br />
                                <?php endforeach; ?>
                                <p class="description"><?php esc_html_e( 'Selected roles will bypass the popup and notice entirely.', 'leon-popup-gate-affpairs' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Generate HTML for an affiliate pair row.
     */
    private static function get_aff_pair_row_html( int $index, array $pair ) : string {
        $types = [
            'shopee' => __( 'Shopee', 'leon-popup-gate-affpairs' ),
            'tiktok' => __( 'TikTok', 'leon-popup-gate-affpairs' ),
            'lazada' => __( 'Lazada', 'leon-popup-gate-affpairs' ),
        ];

        $current_type = isset( $pair['type'] ) ? $pair['type'] : 'shopee';
        if ( ! isset( $types[ $current_type ] ) ) {
            $current_type = 'shopee';
        }

        ob_start();
        ?>
        <tr class="lpgc-aff-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
            <td>
                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[pairs][<?php echo esc_attr( (string) $index ); ?>][enabled]" value="1" <?php checked( ! empty( $pair['enabled'] ) ); ?> />
            </td>
            <td>
                <select name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[pairs][<?php echo esc_attr( (string) $index ); ?>][type]">
                    <?php foreach ( $types as $type_key => $label ) : ?>
                        <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $current_type, $type_key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[pairs][<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $pair['url'] ?? '' ); ?>" />
            </td>
            <td>
                <input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[pairs][<?php echo esc_attr( (string) $index ); ?>][image]" value="<?php echo esc_attr( $pair['image'] ?? '' ); ?>" />
            </td>
            <td>
                <button type="button" class="button-link-delete lpgc-remove-row">&times;</button>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Provide template row for JS.
     */
    private static function get_aff_pair_row_template() : string {
        $placeholder = self::get_aff_pair_row_html( '__INDEX__', [
            'type'    => 'shopee',
            'url'     => '',
            'image'   => '',
            'enabled' => 1,
        ] );
        return $placeholder;
    }

    /**
     * Retrieve all registered roles in a defensive way.
     */
    private static function get_all_roles() : array {
        if ( function_exists( 'wp_roles' ) ) {
            $wp_roles = wp_roles();
            if ( is_object( $wp_roles ) && isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
                return $wp_roles->roles;
            }
        }

        global $wp_roles;
        if ( is_object( $wp_roles ) && isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
            return $wp_roles->roles;
        }

        return [];
    }

    /**
     * Check whether current user bypasses popup.
     */
    private static function current_user_bypasses() : bool {
        $bypass_roles = get_option( self::OPTION_BYPASS_ROLES, [] );
        if ( empty( $bypass_roles ) || ! is_array( $bypass_roles ) ) {
            return false;
        }

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        if ( empty( $user->roles ) ) {
            return false;
        }

        foreach ( $user->roles as $role ) {
            if ( isset( $bypass_roles[ $role ] ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enqueue frontend assets when needed.
     */
    public static function enqueue_frontend_assets() : void {
        if ( is_admin() ) {
            return;
        }

        if ( ! is_singular() ) {
            return;
        }

        if ( self::current_user_bypasses() ) {
            return;
        }

        $settings = self::get_settings();
        if ( empty( $settings['enable'] ) ) {
            return;
        }

        $pair = self::get_random_pair( $settings['pairs'] );
        if ( ! $pair ) {
            return;
        }

        wp_enqueue_style( 'lpgc-frontend', plugin_dir_url( __FILE__ ) . 'assets/lpgc.css', [], self::VERSION );
        wp_enqueue_script( 'lpgc-frontend', plugin_dir_url( __FILE__ ) . 'assets/lpgc.js', [], self::VERSION, true );

        $localized = [
            'delay_ms'       => (int) $settings['delay_ms'],
            'ttl_ms'         => (int) $settings['ttl_ms'],
            'cookie_version' => (int) $settings['cookie_version'],
            'notice_html'    => wp_kses_post( $settings['notice_html'] ),
            'note_text'      => $settings['note_text'],
            'thanks_text'    => $settings['thanks_text'],
            'intro1'         => $settings['popup_intro_line1'],
            'intro2'         => $settings['popup_intro_line2'],
        ];

        wp_localize_script( 'lpgc-frontend', 'LPGC', $localized );

        // Store selected pair for later rendering.
        $GLOBALS['lpgc_selected_pair'] = $pair;
        $GLOBALS['lpgc_settings']      = $settings;
    }

    /**
     * Render popup markup in footer.
     */
    public static function render_popup_markup() : void {
        if ( is_admin() ) {
            return;
        }

        if ( ! is_singular() ) {
            return;
        }

        if ( self::current_user_bypasses() ) {
            return;
        }

        $settings = $GLOBALS['lpgc_settings'] ?? self::get_settings();
        if ( empty( $settings['enable'] ) ) {
            return;
        }

        $pair = $GLOBALS['lpgc_selected_pair'] ?? self::get_random_pair( $settings['pairs'] );
        if ( ! $pair ) {
            return;
        }

        $button_label = self::get_button_label( $pair['type'] );
        $intro2 = str_replace( '%APP%', self::get_platform_name( $pair['type'] ), $settings['popup_intro_line2'] );
        ?>
        <div id="lpgc-popup" class="popup" style="display:none" aria-hidden="true">
            <div class="popup-content" role="dialog" aria-modal="true">
                <button type="button" class="close-btn" aria-label="<?php esc_attr_e( 'Close popup', 'leon-popup-gate-affpairs' ); ?>">&times;</button>
                <div class="popup_content--body">
                    <p><?php echo esc_html( $settings['popup_intro_line1'] ); ?></p>
                    <p><strong><span style="color:#ff0000;"><?php echo esc_html( $intro2 ); ?></span></strong></p>
                    <div class="popup_redirect text-center">
                        <a class="popup_redirect-point btn-open-app <?php echo esc_attr( self::get_button_class( $pair['type'] ) ); ?>" href="<?php echo esc_url( $pair['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $button_label ); ?>
                        </a>
                        <?php if ( ! empty( $pair['image'] ) ) : ?>
                            <a class="lpgc-image-link" href="<?php echo esc_url( $pair['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?php echo esc_url( $pair['image'] ); ?>" alt="" class="img-fluid aff-image" />
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="aff-note"><?php echo esc_html( $settings['note_text'] ); ?></p>
                    <h3 class="aff-thanks"><?php echo esc_html( $settings['thanks_text'] ); ?></h3>
                </div>
            </div>
        </div>
        <div id="lpgc-notice-overlay" class="lpgc-notice-overlay" style="display:none" aria-hidden="true">
            <div class="lpgc-notice-inner">
                <?php echo wp_kses_post( $settings['notice_html'] ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Select a random enabled pair.
     */
    private static function get_random_pair( array $pairs ) : ?array {
        $enabled = [];
        foreach ( $pairs as $pair ) {
            if ( empty( $pair['url'] ) ) {
                continue;
            }
            if ( empty( $pair['enabled'] ) ) {
                continue;
            }
            $enabled[] = $pair;
        }

        if ( empty( $enabled ) ) {
            foreach ( $pairs as $pair ) {
                if ( ! empty( $pair['url'] ) ) {
                    return $pair;
                }
            }
            return null;
        }

        return $enabled[ array_rand( $enabled ) ];
    }

    /**
     * Get button label based on type.
     */
    private static function get_button_label( string $type ) : string {
        switch ( $type ) {
            case 'tiktok':
                return __( 'MỞ ỨNG DỤNG TIKTOK NGAY', 'leon-popup-gate-affpairs' );
            case 'lazada':
                return __( 'MỞ ỨNG DỤNG LAZADA NGAY', 'leon-popup-gate-affpairs' );
            case 'shopee':
            default:
                return __( 'MỞ ỨNG DỤNG SHOPEE NGAY', 'leon-popup-gate-affpairs' );
        }
    }

    /**
     * Get button class based on type.
     */
    private static function get_button_class( string $type ) : string {
        switch ( $type ) {
            case 'tiktok':
                return 'btn-tiktok';
            case 'lazada':
                return 'btn-lazada';
            case 'shopee':
            default:
                return 'btn-shopee';
        }
    }

    /**
     * Get platform name substitution.
     */
    private static function get_platform_name( string $type ) : string {
        switch ( $type ) {
            case 'tiktok':
                return 'TikTok';
            case 'lazada':
                return 'Lazada';
            case 'shopee':
            default:
                return 'Shopee';
        }
    }
}

Leon_Popup_Gate_AFFPairs::init();
