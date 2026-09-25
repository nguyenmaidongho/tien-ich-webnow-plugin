<?php
/**
 * Trang trí Tết — thay plugin "DevVN - Trang trí Tết Việt Nam".
 * Dùng lại option `tet_options` của plugin gốc để giữ cấu hình cũ. Khác bản gốc:
 * hoa rơi chạy bằng requestAnimationFrame thay vì document.write + setTimeout 10ms;
 * pháo hoa dừng hẳn vòng lặp khi hết giờ; âm thanh phát ở lần tương tác đầu (trình duyệt
 * chặn autoplay); có lịch tự bật/tắt theo ngày; bỏ qua hiệu ứng nếu người xem bật
 * "giảm chuyển động".
 */

function wn_tet_url($file) {
  return wn_plugin_url('/assets/tet/' . $file);
}

function wn_tet_styles() {
  return [
    'style_1' => ['left' => 'left-1.png', 'right' => 'right-1.png', 'lw' => 191, 'rw' => 191, 'preview' => 'style-1.jpg', 'label' => 'Câu đối đỏ'],
    'style_3' => ['left' => 'left-3.png', 'right' => 'right-3.png', 'lw' => 266, 'rw' => 181, 'preview' => 'style-3.png', 'label' => 'Cành đào'],
    'style_7' => ['left' => 'left-ngo.png', 'right' => 'right-ngo.png', 'lw' => 148, 'rw' => 148, 'preview' => 'style-ngo.jpg', 'label' => 'Năm Ngọ'],
    'style_6' => ['left' => 'thin-1.png', 'right' => 'thin-2.png', 'lw' => 145, 'rw' => 145, 'preview' => 'giapthin2024.png', 'label' => 'Năm Thìn'],
    'style_5' => ['left' => 'left-5.png', 'right' => 'right-5.png', 'lw' => 148, 'rw' => 148, 'preview' => 'style-5.jpg', 'label' => 'Năm Mão'],
    'style_4' => ['left' => 'left-4.png', 'right' => 'right-4.png', 'lw' => 148, 'rw' => 148, 'preview' => 'style-4.jpg', 'label' => 'Năm Dần'],
    'style_2' => ['left' => 'left-2.png', 'right' => 'right-2.png', 'lw' => 148, 'rw' => 148, 'preview' => 'style-2.jpg', 'label' => 'Năm Tý'],
  ];
}

function wn_tet_bottoms() {
  return [
    'bottom_2' => ['file' => 'bottom-2.png', 'full' => true, 'label' => 'Dải ngang'],
    'bottom_1' => ['file' => 'bottom-1.png', 'full' => false, 'w' => 320, 'left' => 80, 'label' => 'Góc trái'],
  ];
}

function wn_tet_options() {
  $defaults = [
    'style' => 'style_7',
    'left_banner' => 0,
    'right_banner' => 0,
    'bottom_style' => 'bottom_2',
    'enable_firework' => 3,
    'firework_color' => '#ff0000',
    'firework_speed_pc' => 15,
    'firework_speed_mobile' => 50,
    'firework_timer' => 30,
    'enable_audio' => 0,
    'enable_hoamaidao' => 'dao',
    'hoadao_custom' => 0,
    'hoamai_custom' => 0,
    'number_tet_pc' => 20,
    'number_tet_mobile' => 10,
    'container_width' => 1140,
    'zindex' => 99,
    'show_mobile' => 1,
    'start_date' => '',
    'end_date' => '',
  ];
  $o = wp_parse_args((array) get_option('tet_options', []), $defaults);
  // Pháo hoa kiểu chữ (1) của bản gốc đã bỏ, quy về kiểu canvas.
  if ((int) $o['enable_firework'] === 1) {
    $o['enable_firework'] = 3;
  }
  return $o;
}

function wn_tet_in_schedule($o = null) {
  $o = $o ?: wn_tet_options();
  $today = wp_date('Y-m-d');
  if ($o['start_date'] && $today < $o['start_date']) {
    return false;
  }
  if ($o['end_date'] && $today > $o['end_date']) {
    return false;
  }
  return true;
}

function wn_tet_save_settings($post) {
  $in = (array) ($post['tet'] ?? []);
  $date = function ($v) {
    $v = sanitize_text_field($v ?? '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
  };
  $o = [
    'style' => in_array($in['style'] ?? '', array_merge(array_keys(wn_tet_styles()), ['hidden', 'custom']), true) ? $in['style'] : 'style_7',
    'left_banner' => absint($in['left_banner'] ?? 0),
    'right_banner' => absint($in['right_banner'] ?? 0),
    'bottom_style' => in_array($in['bottom_style'] ?? '', array_merge(array_keys(wn_tet_bottoms()), ['none']), true) ? $in['bottom_style'] : 'none',
    'enable_firework' => ($in['enable_firework'] ?? '') === '3' ? 3 : 2,
    'firework_color' => sanitize_hex_color($in['firework_color'] ?? '') ?: '#ff0000',
    'firework_speed_pc' => max(1, absint($in['firework_speed_pc'] ?? 15)),
    'firework_speed_mobile' => max(1, absint($in['firework_speed_mobile'] ?? 50)),
    'firework_timer' => absint($in['firework_timer'] ?? 30),
    'enable_audio' => empty($in['enable_audio']) ? 0 : 1,
    'enable_hoamaidao' => in_array($in['enable_hoamaidao'] ?? '', ['none', 'dao', 'mai', 'both'], true) ? $in['enable_hoamaidao'] : 'none',
    'hoadao_custom' => absint($in['hoadao_custom'] ?? 0),
    'hoamai_custom' => absint($in['hoamai_custom'] ?? 0),
    'number_tet_pc' => max(1, min(80, absint($in['number_tet_pc'] ?? 20))),
    'number_tet_mobile' => max(1, min(40, absint($in['number_tet_mobile'] ?? 10))),
    'container_width' => absint($in['container_width'] ?? 1140),
    'zindex' => absint($in['zindex'] ?? 99),
    'show_mobile' => empty($in['show_mobile']) ? 0 : 1,
    'start_date' => $date($in['start_date'] ?? ''),
    'end_date' => $date($in['end_date'] ?? ''),
  ];
  update_option('tet_options', $o);
  wn_tet_schedule_cache_clear($o);
}

/**
 * Trang có cache tĩnh (WP Fastest Cache) nên trang trí sẽ không tự hiện/ẩn đúng ngày —
 * đặt lịch xóa cache vào đầu ngày bắt đầu và ngày sau ngày kết thúc.
 */
function wn_tet_schedule_cache_clear($o) {
  wp_clear_scheduled_hook('wn_tet_clear_cache');
  $tz = wp_timezone();
  foreach ([$o['start_date'], $o['end_date'] ? wp_date('Y-m-d', strtotime($o['end_date'] . ' +1 day')) : ''] as $day) {
    if (!$day) {
      continue;
    }
    $ts = (new DateTimeImmutable($day . ' 00:01', $tz))->getTimestamp();
    if ($ts > time()) {
      wp_schedule_single_event($ts, 'wn_tet_clear_cache', [$day]);
    }
  }
}
add_action('wn_tet_clear_cache', 'wn_clear_page_cache');

add_action('wp_footer', function () {
  if (!wn_module_on('tet') || is_admin()) {
    return;
  }
  $o = wn_tet_options();
  if (!wn_tet_in_schedule($o)) {
    return;
  }
  $z = (int) $o['zindex'];
  $styles = wn_tet_styles();

  $left = $right = '';
  $lw = $rw = 0;
  if ($o['style'] === 'custom') {
    foreach (['left', 'right'] as $side) {
      $img = $o[$side . '_banner'] ? wp_get_attachment_image_src($o[$side . '_banner'], 'full') : null;
      if ($img) {
        ${$side} = $img[0];
        ${$side === 'left' ? 'lw' : 'rw'} = (int) $img[1];
      }
    }
  } elseif (isset($styles[$o['style']])) {
    $s = $styles[$o['style']];
    $left = wn_tet_url($s['left']);
    $right = wn_tet_url($s['right']);
    $lw = $s['lw'];
    $rw = $s['rw'];
  }

  $bottom = wn_tet_bottoms()[$o['bottom_style']] ?? null;

  $petals = [];
  if ($o['enable_hoamaidao'] !== 'none') {
    $flowers = [
      'dao' => [wn_tet_url('hoadao.png'), 15, $o['hoadao_custom']],
      'mai' => [wn_tet_url('hoamai.png'), 30, $o['hoamai_custom']],
    ];
    foreach ($flowers as $key => $f) {
      if ($o['enable_hoamaidao'] !== 'both' && $o['enable_hoamaidao'] !== $key) {
        continue;
      }
      if ($f[2] && ($img = wp_get_attachment_image_src($f[2], 'full'))) {
        $f = [$img[0], (int) $img[1]];
      }
      $petals[] = [$f[0], $f[1]];
    }
  }

  $hide_below = (int) $o['container_width'] + max($lw, $rw);
  $cfg = [
    'z' => $z,
    'mobile' => (bool) $o['show_mobile'],
    'petals' => $petals,
    'petalsPc' => (int) $o['number_tet_pc'],
    'petalsMobile' => (int) $o['number_tet_mobile'],
    'firework' => (int) $o['enable_firework'] === 3,
    'fwColor' => $o['firework_color'],
    'fwPc' => (int) $o['firework_speed_pc'],
    'fwMobile' => (int) $o['firework_speed_mobile'],
    'fwTimer' => (int) $o['firework_timer'],
    'audio' => $o['enable_audio'] ? wn_tet_url('phao-hoa.mp3') : '',
  ];
  ?>
  <style>
    .wn-tet-side,.wn-tet-bottom{position:fixed;z-index:<?php echo $z; ?>;pointer-events:none}
    .wn-tet-side{top:0}
    .wn-tet-side img,.wn-tet-bottom img{display:block;width:100%;height:auto}
    .wn-tet-left{left:0;width:<?php echo $lw; ?>px}
    .wn-tet-right{right:0;width:<?php echo $rw; ?>px}
    <?php if ($bottom && $bottom['full']) : ?>
    .wn-tet-bottom{left:0;bottom:0;width:100%;height:120px;background:url("<?php echo esc_url(wn_tet_url($bottom['file'])); ?>") repeat-x;background-size:auto 100%}
    <?php elseif ($bottom) : ?>
    .wn-tet-bottom{bottom:0;left:<?php echo (int) $bottom['left']; ?>px;width:<?php echo (int) $bottom['w']; ?>px}
    <?php endif; ?>
    .wn-tet-petals{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:<?php echo $z; ?>}
    .wn-tet-petals img{position:absolute;top:0;left:0;height:auto;will-change:transform}
    .wn-tet-firework{position:fixed;inset:0;pointer-events:none;z-index:<?php echo $z; ?>;transition:opacity 1s}
    @media (max-width:<?php echo $hide_below; ?>px){.wn-tet-side,.wn-tet-bottom{display:none}}
  </style>
  <?php if ($left) : ?><div class="wn-tet-side wn-tet-left" aria-hidden="true"><img src="<?php echo esc_url($left); ?>" alt="" width="<?php echo $lw; ?>" loading="lazy"></div><?php endif; ?>
  <?php if ($right) : ?><div class="wn-tet-side wn-tet-right" aria-hidden="true"><img src="<?php echo esc_url($right); ?>" alt="" width="<?php echo $rw; ?>" loading="lazy"></div><?php endif; ?>
  <?php if ($bottom) : ?><div class="wn-tet-bottom" aria-hidden="true"><?php if (!$bottom['full']) : ?><img src="<?php echo esc_url(wn_tet_url($bottom['file'])); ?>" alt="" loading="lazy"><?php endif; ?></div><?php endif; ?>
  <script>
  (function (cfg) {
    var small = matchMedia('(max-width: 767px)').matches;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || (small && !cfg.mobile)) return;
    function rand(a, b) { return Math.random() * (b - a) + a; }

    // Hoa đào/mai rơi
    if (cfg.petals.length) {
      var W = innerWidth, H = innerHeight, items = [];
      var layer = document.createElement('div');
      layer.className = 'wn-tet-petals';
      layer.setAttribute('aria-hidden', 'true');
      for (var i = 0, n = small ? cfg.petalsMobile : cfg.petalsPc; i < n; i++) {
        var p = cfg.petals[i % cfg.petals.length], img = new Image();
        img.src = p[0]; img.width = p[1]; img.alt = '';
        layer.appendChild(img);
        items.push({ el: img, x: rand(0, W - 50), y: rand(-H, H), amp: rand(0, 20), phase: 0, step: rand(.02, .12), fall: rand(1.1, 2.7) });
      }
      document.body.appendChild(layer);
      addEventListener('resize', function () { W = innerWidth; H = innerHeight; });
      (function fall() {
        for (var j = 0; j < items.length; j++) {
          var it = items[j];
          it.y += it.fall;
          it.phase += it.step;
          if (it.y > H) { it.y = -40; it.x = rand(0, W - it.amp - 30); }
          it.el.style.transform = 'translate(' + (it.x + it.amp * Math.sin(it.phase)) + 'px,' + it.y + 'px)';
        }
        requestAnimationFrame(fall);
      })();
    }

    // Pháo hoa
    if (cfg.firework) {
      addEventListener('load', function () {
        var c = document.createElement('canvas'), ctx = c.getContext('2d'), W, H;
        c.className = 'wn-tet-firework';
        c.setAttribute('aria-hidden', 'true');
        document.body.appendChild(c);
        function size() { W = c.width = innerWidth; H = c.height = innerHeight; }
        size();
        addEventListener('resize', size);

        var hex = cfg.fwColor.replace('#', ''), num = parseInt(hex, 16);
        var r = (num >> 16 & 255) / 255, g = (num >> 8 & 255) / 255, b = (num & 255) / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min, hue = 0;
        if (d) hue = max === r ? (g - b) / d % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
        hue = Math.round(hue * 60 + 360) % 360;

        var rockets = [], sparks = [], tick = 0, every = innerWidth <= 550 ? cfg.fwMobile : cfg.fwPc, running = true;
        function dist(x1, y1, x2, y2) { return Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2)); }
        function Rocket(tx, ty) {
          this.x = this.sx = W / 2; this.y = this.sy = H; this.tx = tx; this.ty = ty;
          this.total = dist(this.sx, this.sy, tx, ty); this.trail = [[this.x, this.y], [this.x, this.y], [this.x, this.y]];
          this.angle = Math.atan2(ty - this.sy, tx - this.sx); this.speed = 1.5; this.light = rand(60, 70);
        }
        function Spark(x, y) {
          this.x = x; this.y = y; this.trail = []; for (var k = 0; k < 5; k++) this.trail.push([x, y]);
          this.angle = rand(0, Math.PI * 2); this.speed = rand(1, 10); this.hue = rand(hue - 20, hue + 20);
          this.light = rand(40, 80); this.alpha = 1; this.decay = rand(.015, .03);
        }
        function line(t, x, y, color) { ctx.beginPath(); ctx.moveTo(t[0], t[1]); ctx.lineTo(x, y); ctx.strokeStyle = color; ctx.stroke(); }
        (function loop() {
          if (!running && !rockets.length && !sparks.length) { c.remove(); return; }
          requestAnimationFrame(loop);
          ctx.globalCompositeOperation = 'destination-out';
          ctx.fillStyle = 'rgba(0,0,0,.5)';
          ctx.fillRect(0, 0, W, H);
          ctx.globalCompositeOperation = 'lighter';
          for (var i = rockets.length; i--;) {
            var ro = rockets[i];
            ro.trail.pop(); ro.trail.unshift([ro.x, ro.y]); ro.speed *= 1.03;
            var vx = Math.cos(ro.angle) * ro.speed, vy = Math.sin(ro.angle) * ro.speed;
            if (dist(ro.sx, ro.sy, ro.x + vx, ro.y + vy) >= ro.total) {
              for (var k = 0; k < 80; k++) sparks.push(new Spark(ro.tx, ro.ty));
              rockets.splice(i, 1);
            } else { ro.x += vx; ro.y += vy; }
            line(ro.trail[ro.trail.length - 1], ro.x, ro.y, 'hsl(' + hue + ',100%,' + ro.light + '%)');
          }
          for (var j = sparks.length; j--;) {
            var s = sparks[j];
            s.trail.pop(); s.trail.unshift([s.x, s.y]); s.speed *= .95;
            s.x += Math.cos(s.angle) * s.speed; s.y += Math.sin(s.angle) * s.speed + 1; s.alpha -= s.decay;
            if (s.alpha <= s.decay) { sparks.splice(j, 1); continue; }
            line(s.trail[s.trail.length - 1], s.x, s.y, 'hsla(' + s.hue + ',100%,' + s.light + '%,' + s.alpha + ')');
          }
          if (running && ++tick >= every) { rockets.push(new Rocket(rand(0, W), rand(0, H / 2))); tick = 0; }
        })();
        if (cfg.fwTimer > 0) setTimeout(function () { running = false; }, cfg.fwTimer * 1000);
      });
    }

    // Âm thanh: trình duyệt chặn tự phát, nên phát ở lần chạm/cuộn/bấm phím đầu tiên.
    if (cfg.audio) {
      var play = function () {
        ['pointerdown', 'keydown', 'scroll'].forEach(function (e) { removeEventListener(e, play); });
        var a = new Audio(cfg.audio);
        a.volume = .6;
        a.play().catch(function () {});
        if (cfg.fwTimer > 0) setTimeout(function () { a.pause(); }, cfg.fwTimer * 1000);
      };
      ['pointerdown', 'keydown', 'scroll'].forEach(function (e) { addEventListener(e, play, { once: true, passive: true }); });
    }
  })(<?php echo wp_json_encode($cfg); ?>);
  </script>
  <?php
}, 999);

/* ---------- Giao diện cài đặt (tab Trang trí Tết) ---------- */

function wn_tet_media_field($name, $id, $label) {
  $url = $id ? wp_get_attachment_image_url($id, 'medium') : '';
  ?>
  <div class="wn-media wn-media-sm">
    <div class="wn-media-preview is-light">
      <img src="<?php echo esc_url($url); ?>" alt="" <?php echo $url ? '' : 'hidden'; ?>>
      <span <?php echo $url ? 'hidden' : ''; ?>><?php echo esc_html($label); ?></span>
    </div>
    <input type="hidden" name="tet[<?php echo esc_attr($name); ?>]" value="<?php echo $id ?: ''; ?>">
    <button type="button" class="wn-btn" data-media-pick>Chọn ảnh</button>
    <button type="button" class="wn-btn wn-btn-ghost" data-media-clear <?php echo $url ? '' : 'hidden'; ?>>Bỏ</button>
  </div>
  <?php
}

function wn_tet_render_settings() {
  $o = wn_tet_options();
  $in = wn_tet_in_schedule($o);
  ?>
  <div class="wn-field">
    <div class="wn-field-label">Thời gian hiển thị<small>Để trống = luôn hiện khi tính năng đang bật. Đặt ngày để Tết tới tự hiện rồi tự tắt, không cần nhớ.</small></div>
    <div>
      <div class="wn-inline">
        <label>Từ <input type="date" name="tet[start_date]" value="<?php echo esc_attr($o['start_date']); ?>" class="wn-input"></label>
        <label>đến <input type="date" name="tet[end_date]" value="<?php echo esc_attr($o['end_date']); ?>" class="wn-input"></label>
      </div>
      <p class="wn-hint <?php echo $in ? 'is-ok' : 'is-off'; ?>">
        <span class="dashicons <?php echo $in ? 'dashicons-yes' : 'dashicons-clock'; ?>"></span>
        <?php echo $in ? 'Hôm nay nằm trong thời gian hiển thị.' : 'Hôm nay ngoài thời gian hiển thị — trang trí đang ẩn.'; ?>
      </p>
    </div>
  </div>

  <div class="wn-field">
    <div class="wn-field-label">Câu đối hai bên<small>Chỉ hiện trên màn hình đủ rộng để không che nội dung.</small></div>
    <div class="wn-pick-grid" data-group="style">
      <label class="wn-pick"><input type="radio" name="tet[style]" value="hidden" <?php checked($o['style'], 'hidden'); ?>><span class="wn-pick-box wn-pick-text"><i class="dashicons dashicons-hidden"></i>Không dùng</span></label>
      <?php foreach (wn_tet_styles() as $key => $s) : ?>
        <label class="wn-pick"><input type="radio" name="tet[style]" value="<?php echo esc_attr($key); ?>" <?php checked($o['style'], $key); ?>><span class="wn-pick-box"><img src="<?php echo esc_url(wn_tet_url($s['preview'])); ?>" alt="" loading="lazy"><em><?php echo esc_html($s['label']); ?></em></span></label>
      <?php endforeach; ?>
      <label class="wn-pick"><input type="radio" name="tet[style]" value="custom" <?php checked($o['style'], 'custom'); ?>><span class="wn-pick-box wn-pick-text"><i class="dashicons dashicons-format-image"></i>Ảnh riêng</span></label>
    </div>
  </div>
  <div class="wn-field" data-show-if="tet[style]=custom">
    <div class="wn-field-label">Ảnh riêng<small>Ảnh PNG nền trong suốt, treo sát mép trái/phải.</small></div>
    <div class="wn-inline">
      <?php wn_tet_media_field('left_banner', $o['left_banner'], 'Bên trái'); ?>
      <?php wn_tet_media_field('right_banner', $o['right_banner'], 'Bên phải'); ?>
    </div>
  </div>

  <div class="wn-field">
    <div class="wn-field-label">Chân trang</div>
    <div class="wn-pick-grid">
      <label class="wn-pick"><input type="radio" name="tet[bottom_style]" value="none" <?php checked($o['bottom_style'], 'none'); ?>><span class="wn-pick-box wn-pick-text is-wide"><i class="dashicons dashicons-hidden"></i>Không dùng</span></label>
      <?php foreach (wn_tet_bottoms() as $key => $b) : ?>
        <label class="wn-pick"><input type="radio" name="tet[bottom_style]" value="<?php echo esc_attr($key); ?>" <?php checked($o['bottom_style'], $key); ?>><span class="wn-pick-box is-wide"><img src="<?php echo esc_url(wn_tet_url($b['file'])); ?>" alt="" loading="lazy"><em><?php echo esc_html($b['label']); ?></em></span></label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="wn-field">
    <div class="wn-field-label">Hoa rơi</div>
    <div class="wn-segment">
      <?php foreach (['none' => 'Tắt', 'dao' => 'Hoa đào', 'mai' => 'Hoa mai', 'both' => 'Cả hai'] as $v => $l) : ?>
        <label><input type="radio" name="tet[enable_hoamaidao]" value="<?php echo $v; ?>" <?php checked($o['enable_hoamaidao'], $v); ?>><span><?php if ($v === 'dao' || $v === 'both') : ?><img src="<?php echo esc_url(wn_tet_url('hoadao.png')); ?>" alt="" width="14"><?php endif; ?><?php if ($v === 'mai' || $v === 'both') : ?><img src="<?php echo esc_url(wn_tet_url('hoamai.png')); ?>" alt="" width="16"><?php endif; ?><?php echo $l; ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="wn-field" data-show-if="tet[enable_hoamaidao]!=none">
    <div class="wn-field-label">Số bông hoa<small>Nhiều quá sẽ rối mắt và nặng máy yếu.</small></div>
    <div class="wn-inline">
      <label><span class="dashicons dashicons-desktop"></span> Máy tính <input type="number" min="1" max="80" name="tet[number_tet_pc]" value="<?php echo (int) $o['number_tet_pc']; ?>" class="wn-input wn-input-xs"></label>
      <label><span class="dashicons dashicons-smartphone"></span> Điện thoại <input type="number" min="1" max="40" name="tet[number_tet_mobile]" value="<?php echo (int) $o['number_tet_mobile']; ?>" class="wn-input wn-input-xs"></label>
    </div>
  </div>
  <div class="wn-field" data-show-if="tet[enable_hoamaidao]!=none">
    <div class="wn-field-label">Ảnh hoa riêng<small>Không bắt buộc — để trống dùng ảnh mặc định.</small></div>
    <div class="wn-inline">
      <?php wn_tet_media_field('hoadao_custom', $o['hoadao_custom'], 'Hoa đào'); ?>
      <?php wn_tet_media_field('hoamai_custom', $o['hoamai_custom'], 'Hoa mai'); ?>
    </div>
  </div>

  <div class="wn-field">
    <div class="wn-field-label">Pháo hoa</div>
    <div class="wn-segment">
      <label><input type="radio" name="tet[enable_firework]" value="2" <?php checked((int) $o['enable_firework'], 2); ?>><span>Tắt</span></label>
      <label><input type="radio" name="tet[enable_firework]" value="3" <?php checked((int) $o['enable_firework'], 3); ?>><span>Bật</span></label>
    </div>
  </div>
  <div class="wn-field" data-show-if="tet[enable_firework]=3">
    <div class="wn-field-label">Tùy chỉnh pháo hoa<small>Tốc độ: số khung hình giữa 2 lần bắn — càng nhỏ bắn càng dày.</small></div>
    <div class="wn-inline">
      <label>Màu <input type="color" name="tet[firework_color]" value="<?php echo esc_attr($o['firework_color']); ?>" class="wn-color-native"></label>
      <label><span class="dashicons dashicons-desktop"></span> Tốc độ máy tính <input type="number" min="1" name="tet[firework_speed_pc]" value="<?php echo (int) $o['firework_speed_pc']; ?>" class="wn-input wn-input-xs"></label>
      <label><span class="dashicons dashicons-smartphone"></span> Điện thoại <input type="number" min="1" name="tet[firework_speed_mobile]" value="<?php echo (int) $o['firework_speed_mobile']; ?>" class="wn-input wn-input-xs"></label>
      <label>Bắn trong <input type="number" min="0" name="tet[firework_timer]" value="<?php echo (int) $o['firework_timer']; ?>" class="wn-input wn-input-xs"> giây</label>
    </div>
  </div>
  <div class="wn-field" data-show-if="tet[enable_firework]=3">
    <div class="wn-field-label">Âm thanh pháo<small>Trình duyệt chặn tự phát âm thanh, nên tiếng pháo sẽ phát khi khách chạm/cuộn trang lần đầu.</small></div>
    <label class="wn-check"><input type="checkbox" name="tet[enable_audio]" value="1" <?php checked($o['enable_audio'], 1); ?>> Bật tiếng pháo</label>
  </div>

  <details class="wn-advanced">
    <summary>Nâng cao</summary>
    <div class="wn-field">
      <div class="wn-field-label">Hiển thị trên điện thoại<small>Hoa rơi & pháo hoa (câu đối luôn ẩn trên màn hình nhỏ).</small></div>
      <label class="wn-check"><input type="checkbox" name="tet[show_mobile]" value="1" <?php checked($o['show_mobile'], 1); ?>> Hiện trên điện thoại</label>
    </div>
    <div class="wn-field">
      <label class="wn-field-label" for="wn-tet-cw">Chiều rộng nội dung<small>Màn hình hẹp hơn số này + bề rộng câu đối thì ẩn câu đối và chân trang.</small></label>
      <input id="wn-tet-cw" type="number" min="0" name="tet[container_width]" value="<?php echo (int) $o['container_width']; ?>" class="wn-input wn-input-sm"> px
    </div>
    <div class="wn-field">
      <label class="wn-field-label" for="wn-tet-z">Z-index<small>Tăng nếu trang trí bị header/popup che.</small></label>
      <input id="wn-tet-z" type="number" min="0" name="tet[zindex]" value="<?php echo (int) $o['zindex']; ?>" class="wn-input wn-input-sm">
    </div>
  </details>
  <?php
}
