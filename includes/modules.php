<?php
/**
 * Danh sách tính năng bật/tắt được. Giá trị lưu ở option `wn_module_{key}` = '1' | '0';
 * chưa lưu lần nào thì dùng `default`.
 */
function wn_modules() {
  return [
    'search' => [
      'title' => 'Tìm kiếm nhanh',
      'desc' => 'Gợi ý kết quả ngay khi gõ, không tải lại trang. Thay plugin Search Popup ThunderBolt.',
      'icon' => 'dashicons-search',
      'default' => '1',
      'tab' => 'search',
    ],
    'tet' => [
      'title' => 'Trang trí Tết',
      'desc' => 'Câu đối, hoa đào/mai rơi, pháo hoa; hẹn ngày tự bật/tắt. Thay plugin DevVN Trang trí Tết.',
      'icon' => 'dashicons-star-filled',
      'default' => '0',
      'tab' => 'tet',
    ],
    'preload' => [
      'title' => 'Màn hình chờ',
      'desc' => 'Hiệu ứng vẽ logo SVG khi trang đang tải.',
      'icon' => 'dashicons-update',
      'default' => '0',
      'tab' => 'preload',
    ],
    'fontawesome' => [
      'title' => 'Font Awesome',
      'desc' => 'Nạp bộ icon Font Awesome 6 cho giao diện. Thay plugin Font Awesome.',
      'icon' => 'dashicons-flag',
      'default' => '1',
    ],
    'login_branding' => [
      'title' => 'Trang đăng nhập WebNow',
      'desc' => 'Logo, màu sắc và favicon thương hiệu ở trang đăng nhập & admin.',
      'icon' => 'dashicons-admin-network',
      'default' => '1',
    ],
    'hide_wp_logo' => [
      'title' => 'Ẩn logo WordPress',
      'desc' => 'Bỏ logo và menu WordPress ở góc trái thanh admin bar.',
      'icon' => 'dashicons-wordpress',
      'default' => '1',
    ],
    'hide_flatsome_notice' => [
      'title' => 'Ẩn thông báo Flatsome',
      'desc' => 'Ẩn banner nhắc đăng ký bản quyền của theme Flatsome trong admin.',
      'icon' => 'dashicons-hidden',
      'default' => '1',
    ],
    'plugin_warning' => [
      'title' => 'Nhắc plugin cần thiết',
      'desc' => 'Cảnh báo trong admin nếu Rank Math hoặc WP Fastest Cache bị tắt.',
      'icon' => 'dashicons-warning',
      'default' => '1',
    ],
    'hide_core_updates' => [
      'title' => 'Ẩn thông báo cập nhật',
      'desc' => 'Ẩn thông báo có bản mới của WordPress/plugin/theme.',
      'icon' => 'dashicons-update-alt',
      'default' => '0',
      'warn' => 'Không khuyến khích: dễ bỏ lỡ bản vá bảo mật.',
    ],
    'classic_editor' => [
      'title' => 'Classic Editor',
      'desc' => 'Dùng trình soạn thảo cổ điển thay cho Gutenberg.',
      'icon' => 'dashicons-edit',
      'default' => '0',
      'warn' => 'Foxtool đang làm việc này, chỉ bật khi tắt Foxtool.',
    ],
  ];
}

function wn_module_on($key) {
  $modules = wn_modules();
  $default = $modules[$key]['default'] ?? '0';
  return get_option('wn_module_' . $key, $default) === '1';
}
