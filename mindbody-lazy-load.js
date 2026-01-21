/**
 * Mindbody Widget Container Fix
 * 
 * Solution for page-id-97 scroll issue
 * Date: 2026-01-12
 * 
 * PROBLEM: Multiple Mindbody widgets in hidden containers were
 * adding ~4800px of extra document height beyond the footer.
 * 
 * SOLUTION: Let widgets load normally, but contain them with CSS
 * so hidden widgets don't affect page height. Widgets are pre-loaded
 * and ready when user clicks.
 * 
 * TO REVERT: 
 * 1. Remove this script from functions.php
 * 2. Remove the CSS added below
 */

(function() {
    'use strict';
    
    // Only run on pages with treatment groups
    const groupContentSection = document.querySelector('.group-content-section');
    if (!groupContentSection) return;
    
    console.log('[MindbodyContainer] Initializing...');
    
    /**
     * Add CSS to properly contain hidden widget groups
     * This prevents iframes from affecting page height
     */
    function addContainmentStyles() {
        const style = document.createElement('style');
        style.id = 'mindbody-containment-styles';
        style.textContent = `
            /* Container for all treatment groups - use clip to contain without affecting iframe sizing */
            .group-content-section {
                position: relative !important;
                contain: layout !important;
            }
            
            /* Hidden groups: clip and hide without breaking iframe internals */
            .group-content-section [class*="group-"][class*="-content"]:not(.visible) {
                clip: rect(0, 0, 0, 0) !important;
                clip-path: inset(50%) !important;
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                margin: -1px !important;
                padding: 0 !important;
                overflow: hidden !important;
                white-space: nowrap !important;
                border: 0 !important;
                contain: strict !important; /* Prevents affecting parent layout */
            }
            
            /* Visible groups: normal flow */
            .group-content-section [class*="group-"][class*="-content"].visible {
                clip: auto !important;
                clip-path: none !important;
                position: relative !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                overflow: visible !important;
                white-space: normal !important;
            }
        `;
        document.head.appendChild(style);
        console.log('[MindbodyContainer] Containment styles added');
    }
    
    /**
     * Force a layout recalculation after widgets load
     */
    function fixLayoutAfterLoad() {
        // Wait for iframes to potentially load
        setTimeout(() => {
            // Force layout recalc
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = '';
            
            // Scroll to ensure correct position
            window.scrollTo(window.scrollX, window.scrollY);
            
            console.log('[MindbodyContainer] Layout recalculated');
        }, 2000);
    }
    
    /**
     * Scroll to the widget section smoothly
     */
    function scrollToWidget(group) {
        // Find the mindbody widget inside this group
        const widget = group.querySelector('.mindbody-widget');
        
        if (widget) {
            // Small delay to let the group fully appear
            setTimeout(() => {
                // Get the widget position
                const rect = widget.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Calculate position with offset for header (adjust as needed)
                const headerOffset = 120; // Offset for fixed header/admin bar
                const targetPosition = rect.top + scrollTop - headerOffset;
                
                // Smooth scroll to the widget
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                console.log('[MindbodyContainer] Scrolled to widget');
            }, 400); // Wait for animation to start
        }
    }
    
    /**
     * Watch for groups becoming visible and fix layout
     */
    function setupVisibilityHandler() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    
                    if (target.classList.contains('visible')) {
                        console.log('[MindbodyContainer] Group became visible, fixing layout...');
                        
                        // Small delay then fix layout
                        setTimeout(() => {
                            window.dispatchEvent(new Event('resize'));
                        }, 100);
                        
                        // Scroll to the widget
                        scrollToWidget(target);
                    }
                }
            });
        });
        
        const groups = groupContentSection.querySelectorAll('[class*="group-"][class*="-content"]');
        groups.forEach(group => {
            observer.observe(group, { attributes: true, attributeFilter: ['class'] });
        });
        
        console.log(`[MindbodyContainer] Watching ${groups.length} groups`);
    }
    
    // Initialize
    function init() {
        addContainmentStyles();
        setupVisibilityHandler();
        fixLayoutAfterLoad();
        console.log('[MindbodyContainer] Initialization complete');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
