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

  // Background image selection for creator pages
  $(document).on('click', '.cp-select-bg-image', function(){
    var field = $('#copella_creator_background_image');
    var preview = $('.cp-creator-bg-preview');
    var frame = wp.media({ title: 'Выберите фоновое изображение', multiple: false, library: { type: ['image'] } });
    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      field.val(att.id);
      var img = $('<img/>', { 
        src: (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url, 
        style: 'max-width: 200px; height: auto;' 
      });
      preview.empty().append(img);
    });
    frame.open();
  });

  $(document).on('click', '.cp-remove-bg-image', function(){
    $('#copella_creator_background_image').val('0');
    $('.cp-creator-bg-preview').html('<div style="width: 200px; height: 100px; background: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; color: #666;">Изображение не выбрано</div>');
  });

  // Playlist search functionality for creator pages
  $(document).on('input', '#cp-creator-playlist-search', function(){
    var query = $(this).val().toLowerCase();
    var rows = $('.cp-creator-playlist-row');
    var visibleCount = 0;
    
    rows.each(function(){
      var title = $(this).data('title') || '';
      if (title.indexOf(query) !== -1) {
        $(this).show();
        visibleCount++;
      } else {
        $(this).hide();
      }
    });
    
    // Update count
    $('#cp-creator-playlist-count').text('Видно: ' + visibleCount);
  });

  // Playlist selection functionality
  $(document).on('change', '.cp-creator-playlist-pick', function(){
    var selected = [];
    $('.cp-creator-playlist-pick:checked').each(function(){
      selected.push($(this).val());
    });
    $('#copella_creator_playlists').val(selected.join(','));
    $('#cp-creator-playlist-count').text('Выбрано: ' + selected.length);
  });

})(jQuery);

