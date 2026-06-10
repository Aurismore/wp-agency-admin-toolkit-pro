(function($){
  if ($.fn.wpColorPicker) { $('.aat-color-field').wpColorPicker(); }
  function openModal(){ $('#aat-support-modal').addClass('is-open').attr('aria-hidden','false'); }
  function closeModal(){ $('#aat-support-modal').removeClass('is-open').attr('aria-hidden','true'); }
  $(document).on('click','.aat-open-support,#wp-admin-bar-aat-support-request a',function(e){ e.preventDefault(); openModal(); });
  $(document).on('click','.aat-modal-close',function(e){ e.preventDefault(); closeModal(); });
  $(document).on('click','#aat-support-modal',function(e){ if(e.target === this) closeModal(); });
  $(document).on('submit','#aat-support-form',function(e){
    e.preventDefault();
    var $form=$(this), $status=$form.find('.aat-support-status');
    $status.text('Sending...');
    $.post((window.ajaxurl || (window.aatSupport && window.aatSupport.ajaxUrl)), $form.serialize()).done(function(resp){
      $status.text(resp && resp.data && resp.data.message ? resp.data.message : 'Sent.');
      $form[0].reset();
      setTimeout(closeModal, 1000);
    }).fail(function(xhr){
      var msg='Could not send request.';
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
      if(!$preview.length){ $preview=$('<div class="aat-media-preview"><img alt="Login background preview"></div>').insertAfter($field); }
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
    var i=$('#aat-shortcuts .aat-shortcut-row').length+100;
    $('#aat-shortcuts').append('<div class="aat-shortcut-row"><input placeholder="Label" name="aat_settings[shortcuts]['+i+'][label]"><input placeholder="URL" name="aat_settings[shortcuts]['+i+'][url]"><input placeholder="Capability" name="aat_settings[shortcuts]['+i+'][cap]" value="read"></div>');
  });
})(jQuery);
