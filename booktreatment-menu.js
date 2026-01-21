/**
 * Book Treatment Menu - Active State Handler
 * Sets the active menu item based on current page URL
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initBookTreatmentMenu();
    });

    function initBookTreatmentMenu() {
        // Get the book-treatment-menu navigation
        const menu = document.querySelector('nav.book-treatment-menu');
        if (!menu) return;

        console.log('[BookTreatmentMenu] Initializing...');

        // Get all navigation items
        const navItems = menu.querySelectorAll('.wp-block-navigation-item');
        if (navItems.length === 0) return;

        // Get current page URL
        const currentUrl = window.location.href.toLowerCase();
        const currentPath = window.location.pathname.toLowerCase();

        // URL aliases - maps additional URLs to menu item text
        // Key: part of URL path, Value: menu item text to activate
        const urlAliases = {
            'book-pilates-mat': 'mat classes',
            'book-yoga-mat': 'mat classes',
            'book-mat': 'mat classes'
        };

        let activeItemFound = false;

        // First, check URL aliases
        for (const [urlPart, menuText] of Object.entries(urlAliases)) {
            if (currentPath.includes(urlPart)) {
                navItems.forEach(function(item) {
                    const link = item.querySelector('a');
                    if (link && link.textContent.toLowerCase().trim() === menuText) {
                        item.classList.add('active');
                        activeItemFound = true;
                        console.log('[BookTreatmentMenu] Active via alias:', link.textContent);
                    }
                });
                break;
            }
        }

        // If no alias match, try to match current URL with menu item links
        if (!activeItemFound) {
            navItems.forEach(function(item) {
                const link = item.querySelector('a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href) return;

                const linkPath = href.toLowerCase();
                
                // Check if current URL matches this menu item's link
                if (currentUrl.includes(linkPath) || currentPath.includes(linkPath.replace(/https?:\/\/[^\/]+/, ''))) {
                    item.classList.add('active');
                    activeItemFound = true;
                    console.log('[BookTreatmentMenu] Active item found:', link.textContent);
                }
            });
        }

        // If no active item found, leave all items inactive (no default)
        if (!activeItemFound) {
            console.log('[BookTreatmentMenu] No URL match found, no item activated');
        }

        // Add click handlers to update active state on click
        navItems.forEach(function(item) {
            item.addEventListener('click', function() {
                // Remove active from all items
                navItems.forEach(function(navItem) {
                    navItem.classList.remove('active');
                });
                // Add active to clicked item
                item.classList.add('active');
            });
        });
    }
})();

