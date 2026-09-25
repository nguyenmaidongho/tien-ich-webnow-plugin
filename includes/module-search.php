<?php
/**
 * Module Tìm kiếm nhanh — thay plugin "Search Popup ThunderBolt" (popup-tb).
 * Cùng ý tưởng gốc (nhanh vì không có ý tưởng nào nhanh hơn: build sẵn 1 file JSON
 * nhỏ chứa toàn bộ bài cần tìm, gửi 1 lần cho trình duyệt, lọc bằng JS ngay tại chỗ
 * thay vì gọi server mỗi lần gõ phím), nhưng vá 2 điểm yếu của bản gốc:
 * 1. Bản gốc chỉ thêm bài MỚI vào JSON khi publish, không tự tạo lại nếu file bị
 *    mất — từng khiến search "chậm" (thực ra là không có dữ liệu, bị Rank Math
 *    redirect 404 về trang chủ). Module này tự kiểm tra + tạo lại khi thiếu.
 * 2. Bản gốc không cập nhật khi SỬA bài đã xuất bản (chỉ bắt lúc publish lần đầu).
 *    Module này cập nhật lại mỗi khi lưu bài, kể cả sửa tiêu đề/ảnh sau này.
 */

define('WN_SEARCH_INDEX_FILE', 'wn-search-index.json');

function wn_search_enabled() {
  return wn_module_on('search');
}

function wn_search_post_types() {
  $types = get_option('wn_search_post_types', ['post']);
  return is_array($types) && $types ? $types : ['post'];
}

function wn_search_index_path() {
  $u = wp_upload_dir();
  return $u['basedir'] . '/' . WN_SEARCH_INDEX_FILE;
}

function wn_search_index_url() {
  $u = wp_upload_dir();
  return $u['baseurl'] . '/' . WN_SEARCH_INDEX_FILE;
}

/** Dữ liệu rút gọn của 1 bài để đưa vào chỉ mục tìm kiếm. */
function wn_search_item($post) {
  $item = [
    'id' => $post->ID,
    'type' => $post->post_type,
    'title' => get_the_title($post),
    'url' => get_permalink($post),
    'thumb' => get_the_post_thumbnail_url($post, 'thumbnail') ?: '',
  ];
  if ($post->post_type === 'product' && function_exists('wc_get_product')) {
    $product = wc_get_product($post->ID);
    $item['price'] = $product ? wc_price($product->get_price()) : '';
  }
  $taxonomies = get_object_taxonomies($post->post_type);
  foreach ($taxonomies as $tax) {
    $terms = get_the_terms($post, $tax);
    if ($terms && !is_wp_error($terms)) {
      $item['taxonomy'] = reset($terms)->name;
      break;
    }
  }
  return $item;
}

/** Dựng lại toàn bộ chỉ mục từ đầu. */
function wn_search_build_index() {
  $posts = get_posts([
    'post_type' => wn_search_post_types(),
    'post_status' => 'publish',
    'numberposts' => -1,
  ]);
  $data = [];
  foreach ($posts as $post) {
    $data[$post->ID] = wn_search_item($post);
  }
  file_put_contents(wn_search_index_path(), wp_json_encode(array_values($data)));
  return count($data);
}

/** Thêm/cập nhật 1 bài vào chỉ mục hiện có (không phải dựng lại toàn bộ). */
function wn_search_upsert($post_id, $post) {
  if (!in_array($post->post_type, wn_search_post_types(), true) || $post->post_status !== 'publish') {
    return;
  }
  $path = wn_search_index_path();
  $data = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
  $data = array_filter($data, fn($x) => $x['id'] != $post_id);
  $data[] = wn_search_item($post);
  file_put_contents($path, wp_json_encode(array_values($data)));
}

function wn_search_remove($post_id) {
  $path = wn_search_index_path();
  if (!file_exists($path)) {
    return;
  }
  $data = json_decode(file_get_contents($path), true) ?: [];
  $data = array_filter($data, fn($x) => $x['id'] != $post_id);
  file_put_contents($path, wp_json_encode(array_values($data)));
}

add_action('save_post', function ($post_id, $post) {
  if (!wn_search_enabled() || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }
  wn_search_upsert($post_id, $post);
}, 20, 2);

add_action('trashed_post', 'wn_search_remove');
add_action('before_delete_post', 'wn_search_remove');

/** Tự vá nếu file chỉ mục bị mất (xóa nhầm, restore backup thiếu uploads...). */
add_action('init', function () {
  if (!wn_search_enabled() || file_exists(wn_search_index_path())) {
    return;
  }
  if (get_transient('wn_search_rebuilding')) {
    return;
  }
  set_transient('wn_search_rebuilding', 1, 5 * MINUTE_IN_SECONDS);
  wn_search_build_index();
}, 999);

/* ---------- Frontend: popup kết quả, gắn vào ô tìm kiếm có sẵn của theme (input[name="s"]) ---------- */

add_action('wp_enqueue_scripts', function () {
  if (!wn_search_enabled() || is_admin()) {
    return;
  }
  wp_enqueue_style('wn-search', wn_plugin_url('/css/search.css'), [], '1.0');
});

add_action('wp_footer', function () {
  if (!wn_search_enabled()) {
    return;
  }
  $type_labels = [];
  foreach (wn_search_post_types() as $t) {
    $obj = get_post_type_object($t);
    $type_labels[$t] = $obj ? $obj->labels->name : $t;
  }
  $limit = (int) get_option('wn_search_limit', 10) ?: 10;
  ?>
  <div class="ft-search" id="ft-search" style="display:none">
    <div class="ft-sbox">
      <span id="ft-sclose" onclick="document.getElementById('ft-search').style.display='none'">&#215;</span>
      <form class="ft-sform" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="text" id="ft-sinput" placeholder="<?php esc_attr_e('Nhập từ khóa để tìm', 'wn'); ?>" name="s" value="" maxlength="50" required>
        <button id="ft-ssumit" type="submit"><?php _e('TÌM', 'wn'); ?></button>
      </form>
      <ul id="ft-show"></ul>
    </div>
  </div>
  <script>
  (function ($) {
    var WN_SEARCH_DATA = null;
    var WN_SEARCH_URL = <?php echo wp_json_encode(wn_search_index_url()); ?>;
    var WN_TYPE_LABELS = <?php echo wp_json_encode($type_labels); ?>;
    var WN_LIMIT = <?php echo (int) $limit; ?>;

    function removeDiacritics(str) {
      return str.normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function highlight(text, q) {
      var re = new RegExp(q.trim().split(/\s+/).join('|'), 'gi');
      return text.replace(re, function (m) { return '<span class="ft-sselec">' + m + '</span>'; });
    }
    function ensureData(cb) {
      if (WN_SEARCH_DATA) { cb(WN_SEARCH_DATA); return; }
      fetch(WN_SEARCH_URL).then(function (r) { return r.json(); }).then(function (data) {
        WN_SEARCH_DATA = data;
        cb(data);
      }).catch(function (e) { console.error('WN search index load error:', e); });
    }
    function render(data, q) {
      var $show = $('#ft-show').empty();
      var byType = {};
      var hasResults = false;
      var normQ = removeDiacritics(q.toLowerCase());
      var re = new RegExp(normQ.replace(/\s+/g, '.*'), 'i');
      data.forEach(function (item) {
        if (!re.test(removeDiacritics(item.title))) return;
        byType[item.type] = byType[item.type] || [];
        if (byType[item.type].length >= WN_LIMIT) return;
        hasResults = true;
        var title = highlight(item.title, q);
        var extra = (item.taxonomy ? '<span class="ft-ssap-cm">' + item.taxonomy + '</span>' : '') +
          (item.price ? '<span class="ft-ssap-pri">' + item.price + '</span>' : '');
        byType[item.type].push(
          item.thumb
            ? '<li class="ft-ssp"><a href="' + item.url + '"><img src="' + item.thumb + '"></a><a href="' + item.url + '"><span class="ft-ssap-tit">' + title + '</span>' + extra + '</a></li>'
            : '<li class="ft-sspno"><a href="' + item.url + '"><span class="ft-ssap-tit">' + title + '</span>' + extra + '</a></li>'
        );
      });
      Object.keys(byType).forEach(function (type) {
        $show.addClass('ft-showbg').append('<li class="ft-stit">' + (WN_TYPE_LABELS[type] || type) + '</li>' + byType[type].join(''));
      });
      if (!hasResults) {
        $show.append('<li><?php echo esc_js(__('Không tìm thấy kết quả', 'wn')); ?></li>');
      }
    }

    $(function () {
      var debounce;
      $(document).on('input', 'input[name="s"]', function () {
        var q = $(this).val();
        $('#ft-search').css('display', 'block');
        $('#ft-sinput').val(q);
        if ($('.mfp-close').length) { $('.mfp-close').click(); }
        $('#ft-sinput').focus();
        clearTimeout(debounce);
        if (q.length < 1) { $('#ft-show').empty().removeClass('ft-showbg'); return; }
        debounce = setTimeout(function () { ensureData(function (data) { render(data, q); }); }, 100);
      });
    });
  })(jQuery);
  </script>
  <?php
}, 20);
