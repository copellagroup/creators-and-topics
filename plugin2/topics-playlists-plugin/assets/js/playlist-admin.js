(function($){
  $(function(){ if(window.console){ console.log('[Copella] playlist admin script loaded'); }});
  function renderResults(items){
    var $box = $('#cp-pl-search-results').empty();
    if(!items || !items.length){
      $box.text(CopellaPlAdmin.i18n.nothingFound).show();
      return;
    }
    var $list = $('<div/>');
    items.forEach(function(it){
      var $row = $('<div class="cp-pl-row" style="display:flex;align-items:center;gap:8px;margin:6px 0;"/>');
      var $thumb = it.thumb ? $('<img/>',{src:it.thumb, width:40, height:40, style:'object-fit:cover;border-radius:6px;'}) : $('<span/>');
      var $title = $('<span/>').text(it.title + ' (#' + it.id + ')');
      var $date = it.date ? $('<span/>',{style:'opacity:.7;margin-left:auto;'}).text(it.date) : $('<span/>');
      $row.append($thumb,$title,$date);
      $list.append($row);
    });
    $box.append($list).show();
  }

  function doSearch(){
    if(window.console){ console.log('[Copella] doSearch click'); }
    var data = {
      action: 'copella_playlist_search',
      nonce: CopellaPlAdmin.nonce,
      s: $('#cp-pl-search-q').val() || '',
      post_type: $('#cp-pl-search-pt').val() || 'post',
      tax: $('#cp-pl-search-tax').val() || '',
      terms: $('#cp-pl-search-terms').val() || '',
      limit: 50
    };
    $('#cp-pl-search-results').text('…').show();
    $.post(CopellaPlAdmin.ajaxUrl, data, function(resp){
      if(window.console){ console.log('[Copella] search response', resp); }
      if(!resp || !resp.success){ renderResults([]); return; }
      var items = resp.data.items || [];
      renderResults(items);
      var links = items.map(function(it){ return it.link; }).filter(Boolean);
      $('#cp-pl-search-results').data('cpLinks', links);
    });
  }

  function resolveTextarea(){
    var el = document.getElementById('copella_playlist_items');
    if(!el){ el = document.querySelector('textarea[name="copella_playlist_items"]'); }
    return el ? $(el) : $();
  }

  function addSelected(){
    var ids = [];
    $('#cp-pl-search-results .cp-pl-pick:checked').each(function(){ ids.push($(this).val()); });
    if(window.console){ console.log('[Copella] addSelected: checked', ids.length, ids); }
    if(!ids.length){
      // Показать явную подсказку, если ничего не выбрано
      alert('Не выбрано ни одной записи. Поставьте галочки в списке выше.');
      return;
    }
    var $ta = resolveTextarea();
    if($ta.length === 0){ if(window.console){ console.log('[Copella] textarea not found'); } return; }
    var before = ($ta.val() || '').toString();
    var currentTrim = before.trim();
    var currentLines = currentTrim ? currentTrim.split(/\r?\n/) : [];
    var set = {};
    currentLines.forEach(function(l){ set[l.trim()] = true; });
    ids.forEach(function(id){ if(!set[id]){ currentLines.push(id); set[id] = true; } });
    var addition = currentLines.join('\n').replace(/\n{3,}/g,'\n\n');
    if(before && !/\n$/.test(before)) addition = '\n' + addition; // добавим перевод строки перед пачкой
    var after = before + addition;
    // Устанавливаем значение через jQuery и напрямую на DOM-элемент
    $ta.val(after);
    if($ta[0]){
      $ta[0].value = after;
      try{ $ta[0].dispatchEvent(new Event('input', { bubbles:true })); }catch(_){}}
    try{ $ta[0].dispatchEvent(new Event('change', { bubbles:true })); }catch(_){}
    // Scroll to textarea for feedback
    $ta.focus();
    // Визуальный отклик
    $ta.css('box-shadow','0 0 0 2px #72aee6').delay(300).queue(function(nextq){ $(this).css('box-shadow',''); nextq(); });
  }

  $(document).on('click', '#cp-pl-search-btn', function(e){ e.preventDefault(); doSearch(); });
  // New: copy all links button
  $(document).on('click', '#cp-pl-copy-all', function(e){ e.preventDefault();
    var items = $('#cp-pl-search-results').data('cpLinks') || [];
    if(!items.length){ alert('Нет данных для копирования. Сначала нажмите «Найти».'); return; }
    var text = items.join('\n');
    if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(text).then(function(){ alert('Ссылки скопированы в буфер обмена.'); }); }
    else {
      var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select();
      try{ document.execCommand('copy'); alert('Ссылки скопированы в буфер обмена.'); } finally { document.body.removeChild(ta); }
    }
  });
})(jQuery);

// Author picker script (vanilla to avoid double jQuery deps). Loaded on author edit screen inline.
(function(){
  if (typeof document === 'undefined') return;
  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }
  function init(){
    var root = document.querySelector('.cp-au-picker');
    if(!root) return;
    var input = $('#copella_author_playlists');
    var search = $('#cp-au-search');
    var rows = $all('.cp-au-row', root);
    var picks = $all('.cp-au-pick', root);
    var counter = $('#cp-au-picked-count');

    function updateStore(){
      var ids = picks.filter(function(ch){ return ch.checked; }).map(function(ch){ return ch.value; });
      if(input){ input.value = ids.join(','); }
      if(counter){ counter.textContent = 'Выбрано: ' + ids.length; }
    }
    picks.forEach(function(ch){ ch.addEventListener('change', updateStore); });

    function applyFilter(){
      var q = (search && search.value ? search.value : '').trim().toLowerCase();
      rows.forEach(function(row){
        var t = (row.getAttribute('data-title') || '').toLowerCase();
        row.style.display = (!q || t.indexOf(q) !== -1) ? 'flex' : 'none';
      });
    }
    if(search){ search.addEventListener('input', applyFilter); }
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// Author type toggling: hide/show stream vs video settings
(function(){
  if (typeof document === 'undefined') return;
  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }
  function init(){
    var typeRadios = $all('input[name="copella_author_type"]');
    if (!typeRadios.length) return;
    var videoBox = $('#cp-author-video-settings');
    var streamBox = $('#cp-author-stream-settings');
    function apply(){
      var checked = typeRadios.find(function(r){ return r.checked; });
      var val = checked ? checked.value : 'video';
      if(videoBox){ videoBox.style.display = (val === 'video') ? '' : 'none'; }
      if(streamBox){ streamBox.style.display = (val === 'stream') ? '' : 'none'; }
    }
    typeRadios.forEach(function(r){ r.addEventListener('change', apply); });
    // initial
    apply();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

