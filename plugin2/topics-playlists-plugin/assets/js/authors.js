/**
 * Copella Authors JavaScript
 * Handles tabs navigation, card toggling, and playlist interactions
 */
(function() {
    'use strict';

    // Initialize author container functionality
    function initAuthorContainer(root) {
        if (!root) return;

        const isTabLayout = root.classList.contains('is-tabs');
        
        if (isTabLayout) {
            initTabsNavigation(root);
        }
        
        initCardToggling(root);
        initInnerPlaylistTabs(root);
    }

    // Initialize tabs navigation for tab layout
    function initTabsNavigation(root) {
        const tabs = Array.from(root.querySelectorAll('.au-tab'));
        const tabsTrack = root.querySelector('.au-tabs');
        const tabsPrev = root.querySelector('.au-tabsbar .au-prev');
        const tabsNext = root.querySelector('.au-tabsbar .au-next');

        function activateTab(index) {
            const next = root.querySelector(`.au-panel[data-au-index="${index}"]`);
            const current = root.querySelector('.au-panel.is-active');
            
            if (!next || next === current) return;

            tabs.forEach(tab => {
                const isActive = tab.getAttribute('data-au-index') === String(index);
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            if (current) current.classList.remove('is-active');
            next.classList.add('is-active');
        }

        // Tab click handlers
        root.addEventListener('click', function(e) {
            const tab = e.target?.closest('.au-tab');
            if (!tab || !root.contains(tab)) return;
            
            const index = tab.getAttribute('data-au-index');
            activateTab(index);
        });

        root.addEventListener('keydown', function(e) {
            const tab = e.target?.closest('.au-tab');
            if (!tab || !root.contains(tab)) return;
            
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                activateTab(tab.getAttribute('data-au-index'));
            }
        });

        // Tab scrolling
        function getTabsScrollAmount() {
            return Math.max(120, root.clientWidth / 3 + 20);
        }

        function updateTabsButtons() {
            if (!tabsTrack || !tabsPrev || !tabsNext) return;
            
            // Force a small delay to ensure layout is complete
            requestAnimationFrame(() => {
                const scrollWidth = tabsTrack.scrollWidth;
                const clientWidth = tabsTrack.clientWidth;
                const scrollLeft = tabsTrack.scrollLeft;
                
                // Check if there's scrollable content (with a 3px tolerance)
                const hasScroll = scrollWidth > clientWidth + 3;
                const maxScroll = Math.max(0, scrollWidth - clientWidth);
                
                tabsPrev.disabled = scrollLeft <= 3;
                tabsNext.disabled = scrollLeft >= maxScroll - 3;
                
                const tabsbar = root.querySelector('.au-tabsbar');
                if (tabsbar) {
                    tabsbar.classList.toggle('has-scroll', hasScroll);
                }
            });
        }

        function scrollTabsBy(direction) {
            if (tabsTrack) {
                tabsTrack.scrollBy({
                    left: direction * getTabsScrollAmount(),
                    behavior: 'smooth'
                });
            }
        }

        if (tabsPrev && tabsNext) {
            tabsPrev.addEventListener('click', () => scrollTabsBy(-1));
            tabsNext.addEventListener('click', () => scrollTabsBy(1));
        }

        if (tabsTrack) {
            tabsTrack.addEventListener('scroll', updateTabsButtons, { passive: true });
            
            // Add mouse wheel support for horizontal scrolling
            tabsTrack.addEventListener('wheel', function(e) {
                // Only handle horizontal scrolling when there's overflow
                const hasHorizontalScroll = tabsTrack.scrollWidth > tabsTrack.clientWidth;
                if (!hasHorizontalScroll) return;
                
                // Prevent vertical page scroll and enable horizontal scroll
                if (Math.abs(e.deltaX) > 0 || Math.abs(e.deltaY) > 0) {
                    e.preventDefault();
                    const delta = e.deltaX || e.deltaY;
                    tabsTrack.scrollBy({
                        left: delta,
                        behavior: 'auto'
                    });
                }
            }, { passive: false });
        }

        window.addEventListener('resize', updateTabsButtons);
        window.addEventListener('load', updateTabsButtons);
        
        if (document.fonts?.ready) {
            try {
                document.fonts.ready.then(() => {
                    setTimeout(updateTabsButtons, 100);
                });
            } catch (e) {
                // Fallback for older browsers
                setTimeout(updateTabsButtons, 100);
            }
        }
        
        // Multiple timing checks to ensure layout is ready
        setTimeout(updateTabsButtons, 0);
        setTimeout(updateTabsButtons, 100);
        setTimeout(updateTabsButtons, 300);
        updateTabsButtons();
    }

    // Initialize card toggling functionality
    function initCardToggling(root) {
        function toggleCard(card) {
            if (!card || card.__animating) return;

            const header = card.querySelector('.au-header');
            const body = card.querySelector('.au-body');
            
            if (!header || !body) return;

            const isCollapsed = card.classList.contains('is-collapsed');
            card.__animating = true;
            
            let finished = false;

            function doneClosing() {
                if (finished) return;
                finished = true;
                card.classList.add('is-collapsed');
                body.style.height = '';
                body.style.opacity = '';
                body.style.overflow = 'hidden';
                header.setAttribute('aria-expanded', 'false');
                card.__animating = false;
            }

            function doneOpening() {
                if (finished) return;
                finished = true;
                body.style.height = '';
                body.style.opacity = '';
                body.style.overflow = '';
                header.setAttribute('aria-expanded', 'true');
                card.__animating = false;
                
                // Trigger resize event for layout recalculation
                try {
                    setTimeout(() => window.dispatchEvent(new Event('resize')), 0);
                } catch (e) {
                    // Fallback for older browsers
                }
            }

            // Increased timeout to match new CSS transition duration (350ms + buffer)
            const fallback = setTimeout(() => {
                isCollapsed ? doneOpening() : doneClosing();
            }, 500);

            if (isCollapsed) {
                // Opening animation
                card.classList.remove('is-collapsed');
                body.style.overflow = 'hidden';
                body.style.height = '0px';
                body.style.opacity = '0';
                
                // Force a reflow before starting animation
                body.offsetHeight;
                
                requestAnimationFrame(() => {
                    body.style.height = body.scrollHeight + 'px';
                    body.style.opacity = '1';
                });

                const handleTransition = (ev) => {
                    if (ev.propertyName !== 'height') return;
                    clearTimeout(fallback);
                    doneOpening();
                    body.removeEventListener('transitionend', handleTransition);
                };

                body.addEventListener('transitionend', handleTransition);
            } else {
                // Closing animation
                body.style.overflow = 'hidden';
                body.style.height = body.scrollHeight + 'px';
                body.style.opacity = '1';
                
                // Force a reflow before starting animation
                body.offsetHeight;
                
                requestAnimationFrame(() => {
                    body.style.height = '0px';
                    body.style.opacity = '0';
                });

                const handleTransition = (ev) => {
                    if (ev.propertyName !== 'height') return;
                    clearTimeout(fallback);
                    doneClosing();
                    body.removeEventListener('transitionend', handleTransition);
                };

                body.addEventListener('transitionend', handleTransition);
            }
        }

        // Card toggle event handlers
        root.addEventListener('click', function(e) {
            const arrow = e.target?.closest('.au-arrow');
            if (arrow && root.contains(arrow)) {
                e.preventDefault();
                e.stopPropagation();
                toggleCard(arrow.closest('.au-card'));
                return;
            }

            const header = e.target?.closest('.au-header');
            if (!header || !root.contains(header)) return;
            
            // Only toggle if there's an arrow (not in individual tab view)
            if (!header.querySelector('.au-arrow')) return;
            
            e.preventDefault();
            e.stopPropagation();
            toggleCard(header.closest('.au-card'));
        });

        root.addEventListener('keydown', function(e) {
            const header = e.target?.closest('.au-header');
            if (!header || !root.contains(header)) return;
            
            // Only toggle if there's an arrow
            if (!header.querySelector('.au-arrow')) return;
            
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleCard(header.closest('.au-card'));
            }
        });
    }

    // Initialize inner playlist tabs for each author card
    function initInnerPlaylistTabs(root) {
        const cards = root.querySelectorAll('.au-card');
        
        cards.forEach(card => {
            initPlaylistTabsForCard(card);
        });

        // Re-initialize when cards are expanded
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const card = mutation.target.closest('.au-card');
                    if (card && !card.classList.contains('is-collapsed')) {
                        setTimeout(() => initPlaylistTabsForCard(card), 100);
                    }
                }
            });
        });

        cards.forEach(card => {
            observer.observe(card, { attributes: true, attributeFilter: ['class'] });
        });
    }

    function initPlaylistTabsForCard(card) {
        const tabsbar = card.querySelector('.au-pl-tabsbar');
        
        // Handle single playlist case (no tabs)
        const singlePlaylistContainer = card.querySelector('.au-playlists--single');
        if (singlePlaylistContainer && !tabsbar) {
            // Single playlist is already visible, no need for tab logic
            return;
        }
        
        if (!tabsbar) return;

        const scroller = tabsbar.querySelector('.au-pl-tabs');
        const prev = tabsbar.querySelector('.au-pl-prev');
        const next = tabsbar.querySelector('.au-pl-next');

        function getScrollAmount() {
            const base = scroller ? scroller.clientWidth : (tabsbar ? tabsbar.clientWidth : 300);
            return Math.max(120, base / 2 + 20);
        }

        function updateButtons() {
            if (!scroller || !prev || !next) return;
            
            // Force a small delay to ensure layout is complete
            requestAnimationFrame(() => {
                const scrollWidth = scroller.scrollWidth;
                const clientWidth = scroller.clientWidth;
                const scrollLeft = scroller.scrollLeft;
                
                // Check if there's scrollable content (with a 3px tolerance)
                const hasScroll = scrollWidth > clientWidth + 3;
                const maxScroll = Math.max(0, scrollWidth - clientWidth);
                
                prev.disabled = scrollLeft <= 3;
                next.disabled = scrollLeft >= maxScroll - 3;
                
                tabsbar.classList.toggle('has-scroll', hasScroll);
            });
        }

        function scrollByDirection(direction) {
            if (scroller) {
                scroller.scrollBy({
                    left: direction * getScrollAmount(),
                    behavior: 'smooth'
                });
            }
        }

        if (prev && next) {
            prev.addEventListener('click', () => scrollByDirection(-1));
            next.addEventListener('click', () => scrollByDirection(1));
        }

        if (scroller) {
            scroller.addEventListener('scroll', updateButtons, { passive: true });
            
            // Add mouse wheel support for horizontal scrolling
            scroller.addEventListener('wheel', function(e) {
                // Only handle horizontal scrolling when there's overflow
                const hasHorizontalScroll = scroller.scrollWidth > scroller.clientWidth;
                if (!hasHorizontalScroll) return;
                
                // Prevent vertical page scroll and enable horizontal scroll
                if (Math.abs(e.deltaX) > 0 || Math.abs(e.deltaY) > 0) {
                    e.preventDefault();
                    const delta = e.deltaX || e.deltaY;
                    scroller.scrollBy({
                        left: delta,
                        behavior: 'auto'
                    });
                }
            }, { passive: false });
        }

        window.addEventListener('resize', updateButtons);
        window.addEventListener('load', updateButtons);
        
        if (document.fonts?.ready) {
            try {
                document.fonts.ready.then(() => {
                    setTimeout(updateButtons, 100);
                });
            } catch (e) {
                // Fallback
                setTimeout(updateButtons, 100);
            }
        }

        // Multiple timing checks to ensure layout is ready
        setTimeout(updateButtons, 50);
        setTimeout(updateButtons, 200);
        setTimeout(updateButtons, 500);
        updateButtons();

        // Playlist tab clicking
        const tabs = tabsbar.querySelectorAll('.au-pl-tab');

        // Remove existing handler to prevent duplicates
        if (tabsbar._clickHandler) {
            tabsbar.removeEventListener('click', tabsbar._clickHandler);
        }

        tabsbar._clickHandler = function(e) {
            const tab = e.target?.closest('.au-pl-tab');
            if (!tab || !tabsbar.contains(tab)) return;

            e.preventDefault();
            e.stopPropagation();

            tabs.forEach(t => t.classList.toggle('is-active', t === tab));

            const playlistId = tab.getAttribute('data-pl');
            const titleOnlyContainer = card.querySelector('.au-playlists .au-pl-wrap--title-only');
            const fullWraps = card.querySelectorAll('.au-playlists .au-pl-wrap--full');
            
            if (playlistId === 'all') {
                // Show title-only grid for "All" tab
                if (titleOnlyContainer) {
                    titleOnlyContainer.style.display = 'grid';
                }
                fullWraps.forEach(wrap => {
                    wrap.style.display = 'none';
                });
            } else {
                // Hide title-only grid and show specific full playlist
                if (titleOnlyContainer) {
                    titleOnlyContainer.style.display = 'none';
                }
                fullWraps.forEach(wrap => {
                    if (String(wrap.getAttribute('data-pl-id')) === playlistId) {
                        wrap.style.display = 'block';
                    } else {
                        wrap.style.display = 'none';
                    }
                });
                
                // Also hide any remaining title-only items that might be visible
                const allTitleOnlyItems = card.querySelectorAll('.au-playlists .copella-playlist--title-only');
                allTitleOnlyItems.forEach(item => {
                    item.style.display = 'none';
                });
                
                // Hide any other playlist containers that might be showing
                const allPlaylistContainers = card.querySelectorAll('.au-playlists .au-pl-wrap');
                allPlaylistContainers.forEach(container => {
                    if (!container.classList.contains('au-pl-wrap--full') || 
                        String(container.getAttribute('data-pl-id')) !== playlistId) {
                        container.style.display = 'none';
                    }
                });
            }
        };

        tabsbar.addEventListener('click', tabsbar._clickHandler);
    }

    // Auto-initialize all author containers when DOM is ready
    function initializeAll() {
        const containers = document.querySelectorAll('.copella-authors');
        containers.forEach(initAuthorContainer);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAll);
    } else {
        initializeAll();
    }

    // Re-initialize when new content is dynamically added
    if (typeof window.MutationObserver !== 'undefined') {
        const pageObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList?.contains('copella-authors')) {
                            initAuthorContainer(node);
                        } else {
                            const containers = node.querySelectorAll?.('.copella-authors');
                            containers?.forEach(initAuthorContainer);
                        }
                    }
                });
            });
        });

        pageObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Export for potential manual initialization
    window.CopellaAuthors = {
        init: initAuthorContainer,
        initAll: initializeAll
    };
})();