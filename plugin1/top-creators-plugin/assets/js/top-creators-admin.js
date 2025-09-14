(function($){
  $(document).on('click', '.cp-select-image', function(){
    var root = $(this).closest('.cp-creator-item');
    var field = root.find('.cp-image-id');
    var frame = wp.media({ title: 'Выберите изображение', multiple: false, library: { type: ['image'] } });
    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      field.val(att.id);
      var img = $('<img/>', { src: (att.sizes && (att.sizes.medium_large || att.sizes.medium || att.sizes.thumbnail)) ? (att.sizes.medium_large ? att.sizes.medium_large.url : (att.sizes.medium ? att.sizes.medium.url : att.sizes.thumbnail.url)) : att.url, class: 'cp-preview' });
      root.find('.cp-creator-thumb').empty().append(img);
    });
    frame.open();
  });

  $(document).on('click', '.cp-remove-image', function(){
    var root = $(this).closest('.cp-creator-item');
    root.find('.cp-image-id').val('0');
    var idx = $('.cp-creator-item').index(root) + 1;
    root.find('.cp-creator-thumb').html('<div class="cp-placeholder">Персона '+ idx +'</div>');
  });
})(jQuery);

