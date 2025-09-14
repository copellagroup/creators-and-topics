/**
 * Copella Topics JS
 * Progressive enhancement: smooth-scroll to block top on pagination click.
 * Server-side pagination remains the source of truth.
 */
(function(){
  document.addEventListener('click', function(e){
    var a = e.target.closest('.copella-ht .ht-pagination a');
    if(!a) return;
    // Allow default navigation, but scroll to top of block after load
    try {
      var url = new URL(a.href);
      url.hash = 'hot-topics';
      a.href = url.toString();
    } catch(_) {}
  });
  // If navigated with hash, nudge scroll into view
  if (location.hash === '#hot-topics') {
    var el = document.querySelector('.copella-ht');
    if (el) {
      setTimeout(function(){ el.scrollIntoView({behavior:'smooth', block:'start'}); }, 0);
    }
  }
  
  // Инициализация горизонтальной прокрутки для hot topics
  var hotTopics = document.querySelectorAll('.copella-ht');
  hotTopics.forEach(function(container) {
    var track = container.querySelector('.ht-track');
    if (track && track.scrollWidth > track.clientWidth) {
      container.classList.add('has-scroll');
    }
  });
})();
