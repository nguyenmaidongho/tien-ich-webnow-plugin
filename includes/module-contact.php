<?php
/**
 * Nút liên hệ nổi (gọi, Zalo, Messenger, WhatsApp, bản đồ). Thay plugin Button Contact VR.
 */

function wn_contact_channels() {
  return [
    'phone' => ['label' => 'Gọi điện', 'placeholder' => '0835 541 118', 'help' => 'Số điện thoại', 'color' => '#e5392b'],
    'zalo' => ['label' => 'Zalo', 'placeholder' => '0835541118', 'help' => 'Số Zalo', 'color' => '#0068ff'],
    'messenger' => ['label' => 'Messenger', 'placeholder' => 'tenfanpage', 'help' => 'Username hoặc ID fanpage (m.me/…)', 'color' => '#a033ff'],
    'whatsapp' => ['label' => 'WhatsApp', 'placeholder' => '84835541118', 'help' => 'Số WhatsApp có mã quốc gia', 'color' => '#25d366'],
    'map' => ['label' => 'Chỉ đường', 'placeholder' => 'https://maps.app.goo.gl/…', 'help' => 'Link Google Maps', 'color' => '#ea4335'],
  ];
}

function wn_contact_defaults() {
  return ['phone' => '', 'zalo' => '', 'messenger' => '', 'whatsapp' => '', 'map' => '', 'position' => 'left', 'show_label' => '1', 'bottom_mobile' => 76];
}

function wn_contact_settings() {
  $saved = get_option('wn_contact');
  if (!is_array($saved)) {
    // Lần đầu: lấy từ Button Contact VR nếu có.
    $saved = [
      'phone' => (string) get_option('pzf_phone', ''),
      'zalo' => (string) get_option('pzf_zalo', ''),
      'messenger' => (string) get_option('pzf_id_fanpage', ''),
      'whatsapp' => (string) get_option('pzf_whatsapp', ''),
      'map' => (string) get_option('pzf_linkggmap', ''),
    ];
  }
  return wp_parse_args($saved, wn_contact_defaults());
}

function wn_contact_save_settings($in) {
  $s = wn_contact_defaults();
  foreach (array_keys(wn_contact_channels()) as $k) {
    $v = trim(wp_unslash($in['wn_contact_' . $k] ?? ''));
    $s[$k] = $k === 'map' ? esc_url_raw($v) : sanitize_text_field($v);
  }
  $s['position'] = ($in['wn_contact_position'] ?? '') === 'right' ? 'right' : 'left';
  $s['show_label'] = empty($in['wn_contact_show_label']) ? '0' : '1';
  $s['bottom_mobile'] = max(0, min(300, (int) ($in['wn_contact_bottom_mobile'] ?? 76)));
  update_option('wn_contact', $s);
}

function wn_contact_link($k, $v) {
  $digits = preg_replace('/[^0-9+]/', '', $v);
  switch ($k) {
    case 'phone':
      return 'tel:' . $digits;
    case 'zalo':
      return 'https://zalo.me/' . ltrim($digits, '+');
    case 'messenger':
      return 'https://m.me/' . rawurlencode(preg_replace('#^https?://(www\.)?(facebook\.com|m\.me)/#', '', $v));
    case 'whatsapp':
      return 'https://wa.me/' . ltrim($digits, '+');
    default:
      return $v;
  }
}

function wn_contact_icon($k) {
  $icons = [
    'phone' => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z" fill="currentColor"/>',
    'zalo' => '<text x="12" y="15.5" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-weight="700" font-size="8.2" fill="currentColor">Zalo</text>',
    'messenger' => '<path d="M12 2C6.4 2 2 6.1 2 11.3c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.6 0 10-4.1 10-9.3S17.6 2 12 2zm1 12.5-2.6-2.7-5 2.7 5.5-5.8 2.6 2.7 4.9-2.7z" fill="currentColor"/>',
    'whatsapp' => '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2s-1 .2-3.3-.7a11.6 11.6 0 0 1-4.5-4c-.3-.5-1-1.6-1-2.9s.7-2 1-2.3a1 1 0 0 1 .7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6a8 8 0 0 0 1.5 1.8 7 7 0 0 0 2.1 1.3c.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3a2 2 0 0 1 0 1.4z" fill="currentColor"/>',
    'map' => '<path d="M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/>',
  ];
  return '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">' . $icons[$k] . '</svg>';
}

add_action('wp_footer', function () {
  if (!wn_module_on('contact') || is_admin()) {
    return;
  }
  $s = wn_contact_settings();
  $items = [];
  foreach (wn_contact_channels() as $k => $c) {
    if ($s[$k] !== '') {
      $items[$k] = $c;
    }
  }
  if (!$items) {
    return;
  }
  $side = $s['position'];
  ?>
  <div class="wn-contact wn-contact--<?php echo esc_attr($side); ?>" style="--wn-cb:<?php echo (int) $s['bottom_mobile']; ?>px">
    <?php foreach ($items as $k => $c) :
      $ext = in_array($k, ['zalo', 'messenger', 'whatsapp', 'map'], true); ?>
      <a class="wn-contact__btn wn-contact__btn--<?php echo esc_attr($k); ?>" href="<?php echo esc_url(wn_contact_link($k, $s[$k]), ['tel', 'https', 'http']); ?>"<?php echo $ext ? ' target="_blank" rel="noopener nofollow"' : ''; ?> style="--wn-c:<?php echo esc_attr($c['color']); ?>" aria-label="<?php echo esc_attr($c['label']); ?>">
        <span class="wn-contact__ico"><?php echo wn_contact_icon($k); ?></span>
        <?php if ($s['show_label'] === '1') : ?><span class="wn-contact__label"><?php echo esc_html($k === 'phone' ? $s['phone'] : $c['label']); ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <style>
    .wn-contact{position:fixed;bottom:22px;z-index:998;display:flex;flex-direction:column;gap:12px}
    .wn-contact--left{left:18px}.wn-contact--right{right:18px;align-items:flex-end}
    .wn-contact__btn{display:flex;align-items:center;gap:10px;text-decoration:none!important;color:#fff!important}
    .wn-contact--right .wn-contact__btn{flex-direction:row-reverse}
    .wn-contact__ico{position:relative;width:50px;height:50px;border-radius:50%;background:var(--wn-c);display:grid;place-items:center;box-shadow:0 10px 24px -8px var(--wn-c);transition:transform .2s}
    .wn-contact__ico::before{content:"";position:absolute;inset:0;border-radius:50%;background:var(--wn-c);opacity:.35;animation:wnPulse 2s ease-out infinite;z-index:-1}
    .wn-contact__btn:hover .wn-contact__ico{transform:scale(1.08)}
    .wn-contact__btn--phone .wn-contact__ico svg{animation:wnRing 1.6s ease-in-out infinite}
    .wn-contact__label{background:#fff;color:#111827;font:600 13px/1 system-ui,sans-serif;padding:9px 12px;border-radius:99px;box-shadow:0 8px 20px -10px rgba(0,0,0,.35);white-space:nowrap;opacity:0;transform:translateX(-6px);transition:.2s;pointer-events:none}
    .wn-contact--right .wn-contact__label{transform:translateX(6px)}
    .wn-contact__btn:hover .wn-contact__label,.wn-contact__btn--phone .wn-contact__label{opacity:1;transform:none}
    @keyframes wnPulse{0%{transform:scale(1);opacity:.4}100%{transform:scale(1.7);opacity:0}}
    @keyframes wnRing{0%,50%,100%{transform:rotate(0)}10%,30%{transform:rotate(-14deg)}20%,40%{transform:rotate(14deg)}}
    @media (max-width:849px){.wn-contact{bottom:var(--wn-cb);gap:10px}.wn-contact__ico{width:44px;height:44px}.wn-contact__label{display:none}}
    @media (prefers-reduced-motion:reduce){.wn-contact__ico::before,.wn-contact__btn--phone .wn-contact__ico svg{animation:none}}
  </style>
  <?php
});

function wn_contact_render_settings() {
  $s = wn_contact_settings();
  foreach (wn_contact_channels() as $k => $c) : ?>
    <div class="wn-field">
      <label class="wn-field-label" for="wn-c-<?php echo esc_attr($k); ?>"><?php echo esc_html($c['label']); ?><small><?php echo esc_html($c['help']); ?> — để trống để ẩn.</small></label>
      <input id="wn-c-<?php echo esc_attr($k); ?>" type="text" name="wn_contact_<?php echo esc_attr($k); ?>" value="<?php echo esc_attr($s[$k]); ?>" placeholder="<?php echo esc_attr($c['placeholder']); ?>" class="wn-input">
    </div>
  <?php endforeach; ?>
  <div class="wn-field">
    <div class="wn-field-label">Vị trí</div>
    <div class="wn-chips">
      <?php foreach (['left' => 'Góc trái', 'right' => 'Góc phải'] as $v => $l) : ?>
        <label class="wn-chip"><input type="radio" name="wn_contact_position" value="<?php echo esc_attr($v); ?>" <?php checked($s['position'], $v); ?>><span><?php echo esc_html($l); ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="wn-field">
    <div class="wn-field-label">Hiện nhãn<small>Hiện số điện thoại cạnh nút gọi trên máy tính.</small></div>
    <label class="wn-chip"><input type="checkbox" name="wn_contact_show_label" value="1" <?php checked($s['show_label'], '1'); ?>><span>Bật</span></label>
  </div>
  <div class="wn-field">
    <label class="wn-field-label" for="wn-c-bottom">Cách đáy trên điện thoại (px)<small>Tăng lên nếu nút bị che bởi thanh menu đáy.</small></label>
    <input id="wn-c-bottom" type="number" min="0" max="300" name="wn_contact_bottom_mobile" value="<?php echo (int) $s['bottom_mobile']; ?>" class="wn-input wn-input-sm">
  </div>
  <?php
}
