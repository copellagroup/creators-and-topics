/**
 * Copella Playlist JS
 * Intentional no-op to avoid double-binding interactions.
 * Inline scripts within the rendered shortcodes manage all behaviors.
 */
(function(){
  // Guard: if inline init is present, do nothing
  var anyInit = document.querySelector('.copella-playlist[data-pl-init]');
  if (anyInit) { return; }
  // If needed in the future, fallback behaviors can be added here
})();