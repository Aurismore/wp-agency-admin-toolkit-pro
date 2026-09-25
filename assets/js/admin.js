(function($){
  if ($.fn.wpColorPicker) { $('.aat-color-field').wpColorPicker(); }

  var optionName = (window.aatSupport && window.aatSupport.optionName) ? window.aatSupport.optionName : 'aat_settings';
  var i18n = (window.aatSupport && window.aatSupport.strings) ? window.aatSupport.strings : {};

  function openModal(){ $('#aat-support-modal').addClass('is-open').attr('aria-hidden','false'); }
  function closeModal(){ $('#aat-support-modal').removeClass('is-open').attr('aria-hidden','true'); }
  $(document).on('click','.aat-open-support,#wp-admin-bar-aat-support-request a',function(e){ e.preventDefault(); openModal(); });
  $(document).on('click','.aat-modal-close',function(e){ e.preventDefault(); closeModal(); });
  $(document).on('click','#aat-support-modal',function(e){ if(e.target === this) closeModal(); });
  $(document).on('submit','#aat-support-form',function(e){
    e.preventDefault();
    var $form=$(this), $status=$form.find('.aat-support-status');
    $status.text(i18n.sending || 'Sending...');
    $.post((window.ajaxurl || (window.aatSupport && window.aatSupport.ajaxUrl)), $form.serialize()).done(function(resp){
      $status.text(resp && resp.data && resp.data.message ? resp.data.message : (i18n.sent || 'Sent.'));
      $form[0].reset();
      setTimeout(closeModal, 1000);
    }).fail(function(xhr){
      var msg=i18n.sendError || 'Could not send request.';
      if(xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) msg=xhr.responseJSON.data.message;
      $status.text(msg);
    });
  });

  $(document).on('click','.aat-media-upload',function(e){
    e.preventDefault();
    var $button=$(this), $field=$button.closest('.aat-media-field');
    if (typeof wp === 'undefined' || !wp.media) return;
    var frame=wp.media({
      title:$button.data('title') || 'Choose image',
      button:{ text:$button.data('button') || 'Use image' },
      multiple:false,
      library:{ type:'image' }
    });
    frame.on('select',function(){
      var attachment=frame.state().get('selection').first().toJSON();
      $field.find('.aat-media-id').val(attachment.id || 0);
      $field.find('.aat-media-url').val(attachment.url || '');
      var $preview=$field.next('.aat-media-preview');
      if(!$preview.length){ $preview=$('<div class="aat-media-preview"><img alt=""></div>').insertAfter($field); }
      $preview.find('img').attr('src', attachment.url || '');
    });
    frame.open();
  });
  $(document).on('click','.aat-media-clear',function(e){
    e.preventDefault();
    var $field=$(this).closest('.aat-media-field');
    $field.find('.aat-media-id').val('0');
    $field.find('.aat-media-url').val('');
    $field.next('.aat-media-preview').remove();
  });

  $('#aat-add-shortcut').on('click',function(){
    var i = $('#aat-shortcuts .aat-shortcut-row').length + 100;
    var nameBase = optionName + '[shortcuts][' + i + ']';
    var html = '<div class="aat-shortcut-row">'
      + '<input placeholder="' + (i18n.label || 'Label') + '" name="' + nameBase + '[label]">'
      + '<input placeholder="' + (i18n.url || 'URL') + '" name="' + nameBase + '[url]">'
      + '<input placeholder="' + (i18n.capability || 'Capability') + '" name="' + nameBase + '[cap]" value="read">'
      + '<button type="button" class="button-link-delete aat-remove-shortcut" aria-label="' + (i18n.removeShortcut || 'Remove shortcut') + '">&times;</button>'
      + '</div>';
    $('#aat-shortcuts').append(html);
  });

  $(document).on('click','.aat-remove-shortcut',function(e){
    e.preventDefault();
    $(this).closest('.aat-shortcut-row').remove();
  });

  $(document).on('change','#aat-tickets-select-all',function(){
    $('.aat-ticket-cb').prop('checked', this.checked);
  });

  $(document).on('click','.aat-switch-licence',function(e){
    e.preventDefault();
    var $row=$('.aat-licence-switch-row');
    $row.addClass('is-open');
    $row.find('.aat-licence-key-input').prop('disabled', false).trigger('focus');
  });
  $(document).on('click','.aat-licence-switch-cancel',function(e){
    e.preventDefault();
    var $row=$('.aat-licence-switch-row');
    $row.removeClass('is-open');
    $row.find('.aat-licence-key-input').prop('disabled', true).val('');
  });

  // Sales chart hover tooltip (client dashboard, WooCommerce-focused layout).
  $(function(){
    document.querySelectorAll('.aat-sales-chart').forEach(function(wrap){
      var raw = wrap.getAttribute('data-series');
      if(!raw) return;
      var data;
      try { data = JSON.parse(raw); } catch(e){ return; }
      var svg = wrap.querySelector('.aat-sales-svg');
      var hair = wrap.querySelector('.aat-sales-hairline');
      var mCur = wrap.querySelector('.aat-sales-marker-cur');
      var mCmp = wrap.querySelector('.aat-sales-marker-cmp');
      var tip = wrap.querySelector('.aat-sales-tooltip');
      if(!svg || !tip) return;
      var n = data.n || 1;
      function esc(v){ return String(v == null ? '' : v).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
      function idxFromEvent(e){
        var rect = svg.getBoundingClientRect();
        if(rect.width <= 0) return 0;
        var ratio = (e.clientX - rect.left) / rect.width;
        ratio = Math.max(0, Math.min(1, ratio));
        return Math.round(ratio * (n - 1));
      }
      function show(e){
        var i = idxFromEvent(e);
        var x = data.x[i];
        if(hair){ hair.setAttribute('x1', x); hair.setAttribute('x2', x); hair.style.display=''; }
        if(mCur && typeof data.yCur[i] !== 'undefined'){ mCur.setAttribute('cx', x); mCur.setAttribute('cy', data.yCur[i]); mCur.style.display=''; }
        else if(mCur){ mCur.style.display='none'; }
        if(mCmp && typeof data.yCmp[i] !== 'undefined'){ mCmp.setAttribute('cx', x); mCmp.setAttribute('cy', data.yCmp[i]); mCmp.style.display=''; }
        else if(mCmp){ mCmp.style.display='none'; }
        var html = '<span class="aat-tip-day">' + esc(data.labels[i]) + '</span>';
        if(typeof data.cur[i] !== 'undefined'){
          html += '<span class="aat-tip-row"><em class="aat-tip-dot" style="background:' + esc(data.cCur) + '"></em>' + esc(data.curLabel) + ': <strong>' + esc(data.cur[i]) + '</strong></span>';
        }
        if(typeof data.cmp[i] !== 'undefined'){
          html += '<span class="aat-tip-row"><em class="aat-tip-dot" style="background:' + esc(data.cCmp) + '"></em>' + esc(data.cmpLabel) + ': <strong>' + esc(data.cmp[i]) + '</strong></span>';
        }
        tip.innerHTML = html;
        tip.style.display='';
        var wrapRect = wrap.getBoundingClientRect();
        var left = (n <= 1 ? 0 : (i / (n - 1))) * wrapRect.width;
        left = Math.max(0, Math.min(wrapRect.width - tip.offsetWidth, left - tip.offsetWidth / 2));
        tip.style.left = left + 'px';
      }
      function hide(){ tip.style.display='none'; if(hair) hair.style.display='none'; if(mCur) mCur.style.display='none'; if(mCmp) mCmp.style.display='none'; }
      svg.addEventListener('mousemove', show);
      svg.addEventListener('mouseleave', hide);
      svg.addEventListener('touchstart', function(e){ if(e.touches[0]) show(e.touches[0]); }, {passive:true});
      svg.addEventListener('touchmove', function(e){ if(e.touches[0]) show(e.touches[0]); }, {passive:true});
    });
  });
})(jQuery);
