(function ($) {
  var toastTimer;
  function toast(msg, isError) {
    var $t = $('#wn-toast');
    $t.text(msg).toggleClass('is-error', !!isError).addClass('is-show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { $t.removeClass('is-show'); }, 2600);
  }

  // Bật/tắt tính năng: lưu ngay, cập nhật mọi công tắc/thẻ cùng tính năng trên trang.
  $(document).on('change', '.wn-switch input[data-module]', function () {
    var $input = $(this);
    var key = $input.data('module');
    var on = $input.is(':checked');
    var $switch = $input.closest('.wn-switch').addClass('is-busy');

    $.post(WN_ADMIN.ajax, { action: 'wn_toggle_module', _ajax_nonce: WN_ADMIN.nonce, module: key, on: on ? '1' : '0' })
      .done(function (res) {
        if (!res || !res.success) {
          $input.prop('checked', !on);
          toast((res && res.data) || 'Không lưu được', true);
          return;
        }
        $('[data-card="' + key + '"]').toggleClass('is-on', on);
        $('[data-dot="' + key + '"]').toggleClass('is-on', on);
        $('input[data-module="' + key + '"]').prop('checked', on);
        toast((on ? 'Đã bật: ' : 'Đã tắt: ') + res.data.title);
      })
      .fail(function () {
        $input.prop('checked', !on);
        toast('Lỗi kết nối, thử lại sau', true);
      })
      .always(function () { $switch.removeClass('is-busy'); });
  });

  $(function () {
    $('.wn-color').wpColorPicker({
      change: function (e, ui) { $('.wn-media-preview').css('background', ui.color.toString()); }
    });

    $(document).on('click', '[data-media-pick]', function (e) {
      e.preventDefault();
      var $box = $(this).closest('.wn-media');
      var frame = wp.media({ title: 'Chọn ảnh', multiple: false, library: { type: 'image' } });
      frame.on('select', function () {
        var file = frame.state().get('selection').first().toJSON();
        $box.find('input[type=hidden]').val(file.id);
        $box.find('img').attr('src', file.url).prop('hidden', false);
        $box.find('.wn-media-preview span').prop('hidden', true);
        $box.find('[data-media-clear]').prop('hidden', false);
      });
      frame.open();
    });

    $(document).on('click', '[data-media-clear]', function (e) {
      e.preventDefault();
      var $box = $(this).closest('.wn-media');
      $box.find('input[type=hidden]').val('');
      $box.find('img').attr('src', '').prop('hidden', true);
      $box.find('.wn-media-preview span').prop('hidden', false);
      $(this).prop('hidden', true);
    });

    // data-show-if="tên=giá trị" hoặc "tên!=giá trị": ẩn/hiện trường phụ theo lựa chọn.
    var $conds = $('[data-show-if]');
    function applyConds() {
      $conds.each(function () {
        var m = $(this).data('show-if').match(/^(.+?)(!?=)(.*)$/);
        var val = $('[name="' + m[1] + '"]:checked').val();
        $(this).prop('hidden', m[2] === '=' ? val !== m[3] : val === m[3]);
      });
    }
    if ($conds.length) {
      $(document).on('change', 'input[type=radio]', applyConds);
      applyConds();
    }
  });
})(jQuery);
