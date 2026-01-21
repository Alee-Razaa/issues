<?php
/**
 * Mindbody Shortcodes
 * 
 * Provides reusable shortcodes for Mindbody booking interfaces
 * 
 * @package Home_Wellness
 * @since 1.1.0
 * @updated 1.2.0 - Fixed therapist display, duration grouping, date range
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue assets for shortcodes
 * 
 * CSS is loaded when:
 * 1. Shortcode is directly in post content
 * 2. Booking tabs block is used (which dynamically renders shortcode)
 * 3. Page uses book-treatment template
 */
function hw_mindbody_enqueue_shortcode_assets() {
    global $post;
    
    $should_enqueue = false;
    
    if ( is_a( $post, 'WP_Post' ) ) {
        // Check for shortcode directly in content
        if ( has_shortcode( $post->post_content, 'hw_mindbody_appointments' ) ||
             has_shortcode( $post->post_content, 'hw_mindbody_therapists' ) ||
             has_shortcode( $post->post_content, 'hw_mindbody_schedule' ) ) {
            $should_enqueue = true;
        }
        
        // Check for booking tabs block (renders shortcode dynamically)
        if ( has_block( 'homewellness/booking-tabs', $post ) ) {
            $should_enqueue = true;
        }
        
        // Check for booking tabs shortcode
        if ( has_shortcode( $post->post_content, 'hw_booking_tabs' ) ) {
            $should_enqueue = true;
        }
        
        // Check if page uses book-treatment template
        $template = get_page_template_slug( $post->ID );
        if ( strpos( $template, 'booktreatment' ) !== false ) {
            $should_enqueue = true;
        }
    }
    
    if ( $should_enqueue ) {
        wp_enqueue_style(
            'hw-mindbody-appointments',
            get_template_directory_uri() . '/assets/css/mindbody-appointments.css',
            array(),
            filemtime( get_template_directory() . '/assets/css/mindbody-appointments.css' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'hw_mindbody_enqueue_shortcode_assets' );

/**
 * Shortcode: hw_mindbody_appointments
 * 
 * Displays the full appointments booking interface with filters
 * 
 * @since 1.1.0
 * @updated 1.2.0 - Fixed: Duration grouping, therapist display, date range
 * 
 * @param array $atts Shortcode attributes.
 * @return string
 */
function hw_mindbody_appointments_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'title'        => 'BOOK YOUR APPOINTMENT',
        'show_filters' => 'yes',
        'days'         => 7,
    ), $atts, 'hw_mindbody_appointments' );
    
    $default_location     = get_option( 'hw_mindbody_default_location', 'Primrose Hill' );
    $treatment_categories = hw_mindbody_get_treatment_categories();
    $api_url              = esc_url( rest_url( 'hw-mindbody/v1' ) );
    
    // Get WooCommerce cart URL for checkout integration
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart' );
    $checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout' );
    
    ob_start();
    ?>
    <div class="hw-mindbody-appointments-container" 
         data-api-url="<?php echo esc_attr( $api_url ); ?>"
         data-location="<?php echo esc_attr( $default_location ); ?>"
         data-days="<?php echo intval( $atts['days'] ); ?>"
         data-categories='<?php echo wp_json_encode( $treatment_categories ); ?>'
         data-cart-url="<?php echo esc_attr( $cart_url ); ?>"
         data-checkout-url="<?php echo esc_attr( $checkout_url ); ?>"
         data-ajax-url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'hw_mindbody_book' ) ); ?>">
        
        <div class="hw-mbo-header">
            <h2 class="hw-mbo-title"><?php echo esc_html( $atts['title'] ); ?></h2>
        </div>
        
        <?php if ( 'yes' === $atts['show_filters'] ) : ?>
        <div class="hw-mbo-filters">
            <div class="hw-mbo-filter-group hw-mbo-filter-treatment">
                <label><?php esc_html_e( 'Treatment Type', 'homewellness' ); ?></label>
                <div class="hw-mbo-multi-select" id="hw-treatment-dropdown">
                    <div class="hw-mbo-multi-select-trigger" id="hw-treatment-trigger">
                        <?php esc_html_e( 'Treatments...', 'homewellness' ); ?>
                    </div>
                    <div class="hw-mbo-multi-select-options" id="hw-treatment-options">
                        <div class="hw-mbo-dropdown-search">
                            <input type="text" id="hw-treatment-search" placeholder="<?php esc_attr_e( 'Search treatments...', 'homewellness' ); ?>" autocomplete="off" />
                        </div>
                        <div class="hw-mbo-categories-container" id="hw-categories-container"></div>
                    </div>
                </div>
            </div>
            
            <div class="hw-mbo-filter-group hw-mbo-filter-dates">
                <label><?php esc_html_e( 'Dates', 'homewellness' ); ?></label>
                <div class="hw-mbo-dates-container">
                    <div class="hw-mbo-date-display" id="hw-date-display-trigger">
                        <span id="hw-date-display-text">Select dates</span>
                        <span class="hw-mbo-date-chevron">▼</span>
                    </div>
                    <input type="hidden" id="hw-filter-start-date" />
                    <input type="hidden" id="hw-filter-end-date" />
                </div>
                <!-- Dual Month Calendar Picker -->
                <div class="hw-mbo-calendar-popup" id="hw-calendar-popup">
                    <div class="hw-mbo-calendar-wrapper">
                        <div class="hw-mbo-calendar-month" id="hw-calendar-month-1">
                            <div class="hw-mbo-calendar-header">
                                <button type="button" class="hw-mbo-calendar-nav hw-mbo-calendar-prev" id="hw-calendar-prev">‹</button>
                                <span class="hw-mbo-calendar-title" id="hw-calendar-title-1"></span>
                            </div>
                            <div class="hw-mbo-calendar-weekdays">
                                <span>su</span><span>mo</span><span>tu</span><span>we</span><span>th</span><span>fr</span><span>sa</span>
                            </div>
                            <div class="hw-mbo-calendar-days" id="hw-calendar-days-1"></div>
                        </div>
                        <div class="hw-mbo-calendar-month" id="hw-calendar-month-2">
                            <div class="hw-mbo-calendar-header">
                                <span class="hw-mbo-calendar-title" id="hw-calendar-title-2"></span>
                                <button type="button" class="hw-mbo-calendar-nav hw-mbo-calendar-next" id="hw-calendar-next">›</button>
                            </div>
                            <div class="hw-mbo-calendar-weekdays">
                                <span>su</span><span>mo</span><span>tu</span><span>we</span><span>th</span><span>fr</span><span>sa</span>
                            </div>
                            <div class="hw-mbo-calendar-days" id="hw-calendar-days-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hw-mbo-filter-group">
                <label><?php esc_html_e( 'Time', 'homewellness' ); ?></label>
                <select id="hw-filter-time">
                    <option value=""><?php esc_html_e( 'Anytime', 'homewellness' ); ?></option>
                    <option value="06:00"><?php esc_html_e( '6:00 AM onwards', 'homewellness' ); ?></option>
                    <option value="09:00"><?php esc_html_e( '9:00 AM onwards', 'homewellness' ); ?></option>
                    <option value="12:00"><?php esc_html_e( '12:00 PM onwards', 'homewellness' ); ?></option>
                    <option value="15:00"><?php esc_html_e( '3:00 PM onwards', 'homewellness' ); ?></option>
                    <option value="18:00"><?php esc_html_e( '6:00 PM onwards', 'homewellness' ); ?></option>
                </select>
            </div>
            
            <div class="hw-mbo-filter-group">
                <label><?php esc_html_e( 'Therapist', 'homewellness' ); ?></label>
                <select id="hw-filter-therapist">
                    <option value=""><?php esc_html_e( 'Anyone', 'homewellness' ); ?></option>
                </select>
            </div>
            
            <div class="hw-mbo-filter-group hw-mbo-filter-search">
                <button class="hw-mbo-search-button" id="hw-search-button"><?php esc_html_e( 'Search', 'homewellness' ); ?></button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="hw-mbo-schedule-container" id="hw-schedule-container">
            <div class="hw-mbo-loading" id="hw-loading-state">
                <div class="hw-mbo-spinner"></div>
                <p><?php esc_html_e( 'Loading available appointments...', 'homewellness' ); ?></p>
            </div>
            <div class="hw-mbo-error" id="hw-error-state" style="display: none;"></div>
            <div id="hw-schedule-content"></div>
        </div>
        
        <div class="hw-mbo-modal" id="hw-detail-modal">
            <div class="hw-mbo-modal-content">
                <div class="hw-mbo-modal-header">
                    <h2 id="hw-modal-title"><?php esc_html_e( 'Details', 'homewellness' ); ?></h2>
                    <span class="hw-mbo-modal-close" id="hw-modal-close">&times;</span>
                </div>
                <div class="hw-mbo-modal-body" id="hw-modal-body"></div>
            </div>
        </div>
        
        <div class="hw-mbo-powered-by">
            <span><?php esc_html_e( 'POWERED BY', 'homewellness' ); ?></span>
            <strong>mindbody</strong>
        </div>
    </div>
    
    <script>
    (function() {
        'use strict';
        
        document.addEventListener('DOMContentLoaded', function() {
            initHWMindbodyAppointments();
        });
        
        function initHWMindbodyAppointments() {
            const container = document.querySelector('.hw-mindbody-appointments-container');
            if (!container) return;
            
            const apiBaseUrl = container.dataset.apiUrl;
            const baseUrl = apiBaseUrl.endsWith('/') ? apiBaseUrl : apiBaseUrl + '/';
            const defaultLocation = container.dataset.location || 'Primrose Hill';
            const appointmentCategories = JSON.parse(container.dataset.categories || '[]');
            const daysToShow = parseInt(container.dataset.days) || 7;
            const ajaxUrl = container.dataset.ajaxUrl;
            const nonce = container.dataset.nonce;
            const cartUrl = container.dataset.cartUrl;
            
            // State
            let allServices = []; // For category UI only - not availability
            let therapists = [];
            let therapistPhotos = {}; // Store therapist photo URLs
            let bookableItems = []; // SINGLE SOURCE OF TRUTH for real availability
            let serviceMetadata = {}; // Map SessionTypeId -> Service details
            let selectedServices = new Set();
            let selectedCategories = new Set();
            let filterDebounceTimer = null;
            
            // DOM Elements
            const treatmentTrigger = document.getElementById('hw-treatment-trigger');
            const treatmentOptions = document.getElementById('hw-treatment-options');
            const treatmentSearch = document.getElementById('hw-treatment-search');
            const categoriesContainer = document.getElementById('hw-categories-container');
            const filterStartDate = document.getElementById('hw-filter-start-date');
            const filterEndDate = document.getElementById('hw-filter-end-date');
            const filterTime = document.getElementById('hw-filter-time');
            const filterTherapist = document.getElementById('hw-filter-therapist');
            const searchButton = document.getElementById('hw-search-button');
            const loadingState = document.getElementById('hw-loading-state');
            const errorState = document.getElementById('hw-error-state');
            const scheduleContent = document.getElementById('hw-schedule-content');
            const detailModal = document.getElementById('hw-detail-modal');
            const modalTitle = document.getElementById('hw-modal-title');
            const modalBody = document.getElementById('hw-modal-body');
            const modalClose = document.getElementById('hw-modal-close');
            
            // CRITICAL: Format date as YYYY-MM-DD using LOCAL timezone (NOT toISOString which uses UTC!)
            function formatDateLocal(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }
            
            // Set default dates - show exactly daysToShow days (today + daysToShow-1)
            const today = new Date();
            today.setHours(12, 0, 0, 0); // Use NOON to avoid any timezone edge cases
            const todayStr = formatDateLocal(today);
            
            // End date is today + (daysToShow - 1) to show exactly daysToShow days
            // e.g., daysToShow=3 means: today, tomorrow, day after = 3 days
            const endDateDefault = new Date(today.getFullYear(), today.getMonth(), today.getDate() + (daysToShow - 1), 12, 0, 0);
            
            // FIX v1.3.0: Set min date to today to prevent selecting past dates
            if (filterStartDate) {
                filterStartDate.value = todayStr;
                filterStartDate.min = todayStr;
            }
            if (filterEndDate) {
                filterEndDate.value = formatDateLocal(endDateDefault);
                filterEndDate.min = todayStr;
            }
            
            // =====================================================
            // DUAL MONTH CALENDAR PICKER (v1.4.0)
            // =====================================================
            const calendarPopup = document.getElementById('hw-calendar-popup');
            const dateDisplayTrigger = document.getElementById('hw-date-display-trigger');
            const dateDisplayText = document.getElementById('hw-date-display-text');
            const calendarPrev = document.getElementById('hw-calendar-prev');
            const calendarNext = document.getElementById('hw-calendar-next');
            const calendarDays1 = document.getElementById('hw-calendar-days-1');
            const calendarDays2 = document.getElementById('hw-calendar-days-2');
            const calendarTitle1 = document.getElementById('hw-calendar-title-1');
            const calendarTitle2 = document.getElementById('hw-calendar-title-2');
            
            let calendarCurrentMonth = today.getMonth();
            let calendarCurrentYear = today.getFullYear();
            let calendarStartDate = new Date(today);
            let calendarEndDate = new Date(endDateDefault);
            let isSelectingStart = true;
            
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            
            function updateDateDisplay() {
                // Format for display: DD-MM-YYYY
                const formatDateDisplay = (d) => {
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    return dd + '-' + mm + '-' + yyyy;
                };
                if (dateDisplayText) {
                    dateDisplayText.textContent = formatDateDisplay(calendarStartDate) + '  ›  ' + formatDateDisplay(calendarEndDate);
                }
                // CRITICAL: Use local date format for input values (NOT toISOString which uses UTC!)
                if (filterStartDate) filterStartDate.value = formatDateLocal(calendarStartDate);
                if (filterEndDate) filterEndDate.value = formatDateLocal(calendarEndDate);
            }
            
            function renderCalendarMonth(container, titleEl, year, month) {
                if (!container || !titleEl) return;
                
                titleEl.textContent = monthNames[month] + ' ' + year;
                
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const todayDate = new Date();
                todayDate.setHours(0, 0, 0, 0);
                
                let html = '';
                
                // Empty cells for days before the first day
                for (let i = 0; i < firstDay; i++) {
                    html += '<span class="hw-mbo-calendar-day empty"></span>';
                }
                
                // Actual days
                for (let d = 1; d <= daysInMonth; d++) {
                    const date = new Date(year, month, d);
                    date.setHours(0, 0, 0, 0);
                    
                    let classes = ['hw-mbo-calendar-day'];
                    
                    // Today
                    if (date.getTime() === todayDate.getTime()) {
                        classes.push('today');
                    }
                    
                    // Disabled (past dates)
                    if (date < todayDate) {
                        classes.push('disabled');
                    }
                    
                    // Selected start
                    if (calendarStartDate && date.getTime() === calendarStartDate.setHours(0,0,0,0)) {
                        classes.push('selected', 'range-start');
                    }
                    
                    // Selected end
                    if (calendarEndDate && date.getTime() === calendarEndDate.setHours(0,0,0,0)) {
                        classes.push('selected', 'range-end');
                    }
                    
                    // In range
                    if (calendarStartDate && calendarEndDate && date > calendarStartDate && date < calendarEndDate) {
                        classes.push('in-range');
                    }
                    
                    const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                    html += '<span class="' + classes.join(' ') + '" data-date="' + dateStr + '">' + d + '</span>';
                }
                
                container.innerHTML = html;
            }
            
            function renderCalendars() {
                renderCalendarMonth(calendarDays1, calendarTitle1, calendarCurrentYear, calendarCurrentMonth);
                
                let nextMonth = calendarCurrentMonth + 1;
                let nextYear = calendarCurrentYear;
                if (nextMonth > 11) {
                    nextMonth = 0;
                    nextYear++;
                }
                renderCalendarMonth(calendarDays2, calendarTitle2, nextYear, nextMonth);
            }
            
            function handleCalendarDayClick(e) {
                const dayEl = e.target.closest('.hw-mbo-calendar-day');
                if (!dayEl || dayEl.classList.contains('disabled') || dayEl.classList.contains('empty')) return;
                
                const dateStr = dayEl.dataset.date;
                if (!dateStr) return;
                
                const clickedDate = new Date(dateStr + 'T00:00:00');
                
                if (isSelectingStart) {
                    calendarStartDate = clickedDate;
                    calendarEndDate = null;
                    isSelectingStart = false;
                } else {
                    if (clickedDate < calendarStartDate) {
                        calendarEndDate = calendarStartDate;
                        calendarStartDate = clickedDate;
                    } else {
                        calendarEndDate = clickedDate;
                    }
                    isSelectingStart = true;
                    
                    // Close popup after selecting both dates and AUTO-RELOAD results
                    setTimeout(() => {
                        if (calendarPopup) calendarPopup.classList.remove('open');
                        if (dateDisplayTrigger) dateDisplayTrigger.classList.remove('active');
                        // Auto-reload results after date selection
                        loadAvailability();
                    }, 300);
                }
                
                if (calendarStartDate && calendarEndDate) {
                    updateDateDisplay();
                }
                renderCalendars();
            }
            
            // Initialize calendar
            if (calendarDays1) {
                calendarDays1.addEventListener('click', handleCalendarDayClick);
            }
            if (calendarDays2) {
                calendarDays2.addEventListener('click', handleCalendarDayClick);
            }
            
            if (calendarPrev) {
                calendarPrev.addEventListener('click', () => {
                    calendarCurrentMonth--;
                    if (calendarCurrentMonth < 0) {
                        calendarCurrentMonth = 11;
                        calendarCurrentYear--;
                    }
                    // Don't go before current month
                    const now = new Date();
                    if (calendarCurrentYear < now.getFullYear() || 
                        (calendarCurrentYear === now.getFullYear() && calendarCurrentMonth < now.getMonth())) {
                        calendarCurrentMonth = now.getMonth();
                        calendarCurrentYear = now.getFullYear();
                    }
                    renderCalendars();
                });
            }
            
            if (calendarNext) {
                calendarNext.addEventListener('click', () => {
                    calendarCurrentMonth++;
                    if (calendarCurrentMonth > 11) {
                        calendarCurrentMonth = 0;
                        calendarCurrentYear++;
                    }
                    renderCalendars();
                });
            }
            
            if (dateDisplayTrigger) {
                dateDisplayTrigger.addEventListener('click', () => {
                    const isOpen = calendarPopup && calendarPopup.classList.contains('open');
                    if (isOpen) {
                        calendarPopup.classList.remove('open');
                        dateDisplayTrigger.classList.remove('active');
                    } else {
                        if (calendarPopup) calendarPopup.classList.add('open');
                        dateDisplayTrigger.classList.add('active');
                        renderCalendars();
                    }
                });
            }
            
            // Close calendar when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.hw-mbo-filter-dates') && calendarPopup) {
                    calendarPopup.classList.remove('open');
                    if (dateDisplayTrigger) dateDisplayTrigger.classList.remove('active');
                }
            });
            
            // Initialize date display
            updateDateDisplay();
            // End Dual Month Calendar Picker
            
            init();
            
            async function init() {
                // Hide loading spinner initially
                if (loadingState) loadingState.style.display = 'none';
                
                await Promise.all([
                    loadAllServices(), // For category UI only
                    loadTherapists()
                ]);
                
                renderCategoryOptions();
                setupEventListeners();
                
                // Show instruction message initially
                if (scheduleContent) {
                    scheduleContent.innerHTML = '<div class="hw-mbo-instruction"><h3>Select Filters Above</h3><p>Please select a date range and treatment type to see available appointments.</p><p><strong>Tip:</strong> Click the "Search" button or select filters to view available time slots.</p></div>';
                }
            }
            
            async function loadAllServices() {
                try {
                    // Use treatment-services endpoint which filters to 8 target categories
                    const response = await fetch(baseUrl + 'treatment-services');
                    if (response.ok) {
                        const data = await response.json();
                        
                        // Get services from response
                        if (data.services && Array.isArray(data.services)) {
                            allServices = data.services;
                            
                            // Build service metadata map
                            allServices.forEach(service => {
                                if (service.Id) {
                                    serviceMetadata[service.Id] = service;
                                }
                                if (service.TherapistName && service.TherapistPhoto) {
                                    therapistPhotos[service.TherapistName] = service.TherapistPhoto;
                                }
                            });
                            
                            console.log('=== TREATMENT SERVICES LOADED ===');
                            console.log('Total services (filtered to 8 categories): ' + allServices.length);
                            console.log('Therapist photos available: ' + Object.keys(therapistPhotos).length);
                            
                            // Show stats if available
                            if (data.stats) {
                                console.log('--- FILTERING STATS ---');
                                console.log('Total in Mindbody: ' + data.stats.total_in_mindbody);
                                console.log('Not bookable online: ' + data.stats.not_bookable_online);
                                console.log('Wrong category: ' + data.stats.wrong_category);
                                console.log('Duplicates removed: ' + data.stats.duplicates_removed);
                                console.log('No duration: ' + data.stats.no_duration);
                                console.log('Final count: ' + data.stats.final_count);
                                console.log('Categories found:', data.stats.categories_found);
                            }
                        } else {
                            // Fallback to array response
                            allServices = Array.isArray(data) ? data : [];
                            console.log('Loaded ' + allServices.length + ' services (fallback)');
                        }
                    }
                } catch (e) {
                    console.error('Failed to load services:', e);
                }
            }
            
            // FIX #4: Properly load therapists from API
            async function loadTherapists() {
                try {
                    const response = await fetch(baseUrl + 'staff-appointments');
                    if (response.ok) {
                        const data = await response.json();
                        
                        // Handle both array and object responses
                        let staffList = Array.isArray(data) ? data : (data.Staff || data.data || []);
                        
                        // Filter for staff with session types (therapists)
                        therapists = staffList.filter(t => {
                            const hasSessionTypes = t.SessionTypes && t.SessionTypes.length > 0;
                            const hasName = t.Name || t.FirstName;
                            return hasName;
                        });
                        
                        // If no staff from API, extract unique therapists from service names
                        if (therapists.length === 0) {
                            therapists = extractTherapistsFromServices();
                        }
                        
                        console.log('Loaded ' + therapists.length + ' therapists');
                        renderTherapistOptions();
                    } else {
                        // Fallback: extract from services
                        therapists = extractTherapistsFromServices();
                        renderTherapistOptions();
                    }
                } catch (e) {
                    console.error('Failed to load therapists:', e);
                    therapists = extractTherapistsFromServices();
                    renderTherapistOptions();
                }
            }
            
            // DEPRECATED: Extract unique therapist names from service names (FALLBACK ONLY)
            // With BookableItems, therapist data comes directly from Staff object
            function extractTherapistsFromServices() {
                const therapistSet = new Set();
                const therapistList = [];
                
                allServices.forEach(service => {
                    const serviceName = service.Name || '';
                    // Pattern: "Treatment Name - Therapist Name - Duration" or similar
                    const match = serviceName.match(/\s-\s([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s*(?:-|$)/);
                    if (match) {
                        const name = match[1].trim();
                        if (!therapistSet.has(name) && !name.match(/^\d+\s*min$/i)) {
                            therapistSet.add(name);
                            therapistList.push({
                                Name: name,
                                FirstName: name.split(' ')[0],
                                LastName: name.split(' ').slice(1).join(' '),
                                Id: 'extracted-' + therapistSet.size
                            });
                        }
                    }
                });
                
                return therapistList.sort((a, b) => a.Name.localeCompare(b.Name));
            }
            
            function renderCategoryOptions(searchTerm = '') {
                if (!categoriesContainer) return;
                
                const searchLower = searchTerm.toLowerCase();
                let html = '';
                let hasResults = false;
                
                appointmentCategories.forEach((category, catIndex) => {
                    const catId = 'hw-cat-' + catIndex;
                    const catLower = category.toLowerCase().trim();
                    
                    const categoryServices = allServices.filter(s => {
                        const serviceCategoryName = (s.ServiceCategory && s.ServiceCategory.Name) 
                            ? s.ServiceCategory.Name.toLowerCase().trim() 
                            : '';
                        const programName = (s.Program || '').toLowerCase().trim();
                        return serviceCategoryName === catLower || programName === catLower;
                    });
                    
                    const matchingServices = searchTerm 
                        ? categoryServices.filter(s => (s.Name || '').toLowerCase().includes(searchLower))
                        : categoryServices;
                    
                    const categoryMatches = catLower.includes(searchLower);
                    const showCategory = !searchTerm || categoryMatches || matchingServices.length > 0;
                    
                    if (!showCategory) return;
                    
                    hasResults = true;
                    const isExpanded = searchTerm && matchingServices.length > 0;
                    
                    html += '<div class="hw-mbo-category-section" data-category="' + escapeHtml(category) + '">';
                    html += '<div class="hw-mbo-category-header ' + (isExpanded ? 'expanded' : '') + '" data-cat-id="' + catId + '">';
                    html += '<input type="checkbox" class="hw-mbo-category-checkbox" value="' + escapeHtml(category) + '" id="' + catId + '" ' + (selectedCategories.has(category) ? 'checked' : '') + ' />';
                    html += '<label for="' + catId + '">' + escapeHtml(category) + '</label>';
                    html += '<span class="hw-mbo-expand-icon">▼</span>';
                    html += '</div>';
                    html += '<div class="hw-mbo-sub-services ' + (isExpanded ? 'open' : '') + '" id="hw-services-' + catId + '">';
                    
                    const servicesToShow = searchTerm ? matchingServices : categoryServices;
                    servicesToShow.forEach(service => {
                        const serviceId = 'hw-service-' + service.Id;
                        const price = service.Price || service.OnlinePrice || 0;
                        
                        html += '<div class="hw-mbo-sub-service-item">';
                        html += '<input type="checkbox" class="hw-mbo-service-checkbox" value="' + service.Id + '" id="' + serviceId + '" ' + (selectedServices.has(String(service.Id)) ? 'checked' : '') + ' />';
                        html += '<label for="' + serviceId + '">' + escapeHtml(service.Name) + '</label>';
                        html += '<span class="hw-mbo-service-price">£' + parseFloat(price).toFixed(0) + '</span>';
                        html += '</div>';
                    });
                    
                    html += '</div></div>';
                });
                
                if (!hasResults) {
                    html = '<div class="hw-mbo-no-results">No treatments found matching "' + escapeHtml(searchTerm) + '"</div>';
                }
                
                categoriesContainer.innerHTML = html;
                attachDropdownListeners();
            }
            
            function attachDropdownListeners() {
                document.querySelectorAll('.hw-mbo-category-header').forEach(header => {
                    header.addEventListener('click', (e) => {
                        if (e.target.type !== 'checkbox') {
                            header.classList.toggle('expanded');
                            const catId = header.dataset.catId;
                            const subServices = document.getElementById('hw-services-' + catId);
                            if (subServices) subServices.classList.toggle('open');
                        }
                    });
                });
                
                document.querySelectorAll('.hw-mbo-category-checkbox').forEach(cb => {
                    cb.addEventListener('change', (e) => {
                        const category = e.target.value;
                        if (e.target.checked) {
                            selectedCategories.add(category);
                        } else {
                            selectedCategories.delete(category);
                        }
                        updateTreatmentTriggerText();
                        debouncedLoadAvailability();
                    });
                });
                
                document.querySelectorAll('.hw-mbo-service-checkbox').forEach(cb => {
                    cb.addEventListener('change', (e) => {
                        const serviceId = e.target.value;
                        if (e.target.checked) {
                            selectedServices.add(serviceId);
                        } else {
                            selectedServices.delete(serviceId);
                        }
                        updateTreatmentTriggerText();
                        debouncedLoadAvailability();
                    });
                });
            }
            
            function updateTreatmentTriggerText() {
                if (!treatmentTrigger) return;
                
                const catCount = selectedCategories.size;
                const serviceCount = selectedServices.size;
                
                if (catCount === 0 && serviceCount === 0) {
                    treatmentTrigger.textContent = 'Treatments...';
                } else if (catCount === 1 && serviceCount === 0) {
                    treatmentTrigger.textContent = Array.from(selectedCategories)[0];
                } else {
                    const total = catCount + serviceCount;
                    treatmentTrigger.textContent = total + ' Selected';
                }
            }
            
            function renderTherapistOptions() {
                if (!filterTherapist) return;
                
                let html = '<option value="">Anyone</option>';
                therapists.forEach(t => {
                    const name = t.Name || ((t.FirstName || '') + ' ' + (t.LastName || '')).trim();
                    if (name) {
                        html += '<option value="' + escapeHtml(name) + '">' + escapeHtml(name) + ' | Therapist</option>';
                    }
                });
                filterTherapist.innerHTML = html;
            }
            
            function debouncedLoadAvailability() {
                clearTimeout(filterDebounceTimer);
                filterDebounceTimer = setTimeout(() => {
                    loadAvailability();
                }, 300);
            }
            
            // REAL AVAILABILITY: Fetch from Mindbody BookableItems API
            async function loadAvailability() {
                if (loadingState) loadingState.style.display = 'block';
                if (errorState) errorState.style.display = 'none';
                if (scheduleContent) scheduleContent.innerHTML = '';
                
                try {
                    await fetchBookableItems();
                    renderBookableItems();
                } catch (e) {
                    console.error('Failed to load availability:', e);
                    if (errorState) {
                        errorState.textContent = 'Unable to load treatments. Please try again later.';
                        errorState.style.display = 'block';
                    }
                } finally {
                    if (loadingState) loadingState.style.display = 'none';
                }
            }
            
            // Fetch real availability from Mindbody
            async function fetchBookableItems() {
                const params = new URLSearchParams();
                
                // Get date range
                const startDate = filterStartDate ? filterStartDate.value : '';
                const endDate = filterEndDate ? filterEndDate.value : '';
                
                if (startDate) {
                    params.append('start_date', startDate);
                } else {
                    // Default to today
                    const today = new Date();
                    params.append('start_date', today.toISOString().split('T')[0]);
                }
                
                if (endDate) {
                    params.append('end_date', endDate);
                }
                
                // Get selected service IDs
                if (selectedServices.size > 0) {
                    selectedServices.forEach(id => {
                        params.append('session_type_ids[]', id);
                    });
                } else {
                    // If no services selected but categories selected, get all service IDs in those categories
                    if (selectedCategories.size > 0) {
                        allServices.forEach(service => {
                            const serviceCat = service.Category || (service.ServiceCategory && service.ServiceCategory.Name) || service.Program || '';
                            for (const cat of selectedCategories) {
                                if (serviceCat.toLowerCase().includes(cat.toLowerCase().split(' ')[0]) ||
                                    cat.toLowerCase().includes(serviceCat.toLowerCase().split(' ')[0])) {
                                    params.append('session_type_ids[]', service.Id);
                                    break;
                                }
                            }
                        });
                    }
                }
                
                // VALIDATION: Require at least one service filter to prevent huge data requests
                const hasServiceFilter = params.has('session_type_ids[]');
                if (!hasServiceFilter) {
                    console.warn('No services selected - please select at least one treatment type');
                    bookableItems = [];
                    return; // Don't make API call without service filter
                }
                
                // Get selected therapist
                if (filterTherapist && filterTherapist.value) {
                    const selectedTherapistName = filterTherapist.value;
                    const therapist = therapists.find(t => t.Name === selectedTherapistName);
                    if (therapist && therapist.Id) {
                        params.append('staff_ids[]', therapist.Id);
                    }
                }
                
                console.log('Fetching BookableItems with params:', params.toString());
                
                const response = await fetch(baseUrl + 'bookable-items?' + params.toString());
                if (!response.ok) {
                    throw new Error('Failed to fetch bookable items');
                }
                
                const data = await response.json();
                
                if (data.success && data.bookable_items) {
                    bookableItems = data.bookable_items;
                    console.log('Received ' + bookableItems.length + ' bookable items from Mindbody');
                    
                    // Apply time filter client-side (Mindbody API doesn't support time-of-day filtering)
                    if (filterTime && filterTime.value) {
                        const timeRange = filterTime.value;
                        let minHour, maxHour;
                        
                        // Handle named ranges (morning/afternoon/evening)
                        switch(timeRange) {
                            case 'morning':
                                minHour = 9;
                                maxHour = 12;
                                break;
                            case 'afternoon':
                                minHour = 12;
                                maxHour = 17;
                                break;
                            case 'evening':
                                minHour = 17;
                                maxHour = 21;
                                break;
                        }
                        
                        // Handle hour-based values (e.g., "09:00")
                        if (minHour === undefined && timeRange.includes(':')) {
                            const parts = timeRange.split(':');
                            minHour = parseInt(parts[0], 10);
                            maxHour = 24; // End of day
                        }
                        
                        if (minHour !== undefined) {
                            const originalCount = bookableItems.length;
                            bookableItems = bookableItems.filter(item => {
                                const hour = new Date(item.StartDateTime).getHours();
                                return hour >= minHour && hour < maxHour;
                            });
                            console.log('Time filter applied: ' + timeRange + ' (' + bookableItems.length + '/' + originalCount + ' slots)');
                        }
                    }
                } else {
                    bookableItems = [];
                    console.warn('No bookable items returned');
                }
            }
            
            // Render BookableItems in the UI
            function renderBookableItems() {
                if (!scheduleContent) return;
                
                if (bookableItems.length === 0) {
                    scheduleContent.innerHTML = '<div class="hw-mbo-no-results"><h3>No Appointments Available</h3><p>There are no available appointments for the selected filters. Try adjusting your date range, service, or therapist selection.</p></div>';
                    return;
                }
                
                // Group by therapist and treatment
                const groupedData = {};
                
                bookableItems.forEach(item => {
                    // Get staff info
                    const staff = item.Staff || {};
                    const therapistName = (staff.FirstName || '') + ' ' + (staff.LastName || '');
                    const staffId = staff.Id || '';
                    const staffPhoto = staff.ImageUrl || therapistPhotos[therapistName.trim()] || '';
                    
                    // Get session type info
                    const sessionType = item.SessionType || {};
                    const serviceName = sessionType.Name || '';
                    const sessionTypeId = sessionType.Id || '';
                    
                    // Get time info
                    const startDateTime = item.StartDateTime || '';
                    const endDateTime = item.EndDateTime || '';
                    
                    // Get location
                    const location = item.Location || {};
                    const locationName = location.Name || defaultLocation;
                    
                    // Get bookable item ID (CRITICAL for booking)
                    const bookableItemId = item.Id || '';
                    
                    // Parse base treatment name (remove therapist and duration)
                    let baseName = serviceName
                        .replace(/\s*-\s*[A-Z][a-z]+(?:\s+[A-Z]\.?)?(?:\s+[A-Z][a-z]+)?\s*(?:-|$)/gi, ' ')
                        .replace(/\s*-?\s*\d+\s*(?:min|mins|minutes|\')\s*/gi, '')
                        .replace(/\s*-\s*\d+\s*$/g, '')
                        .replace(/\s+/g, ' ')
                        .trim();
                    
                    // Group key
                    const groupKey = therapistName.trim() + '||' + baseName;
                    
                    if (!groupedData[groupKey]) {
                        groupedData[groupKey] = {
                            therapistName: therapistName.trim(),
                            staffId: staffId,
                            staffPhoto: staffPhoto,
                            baseName: baseName,
                            sessionTypeId: sessionTypeId,
                            timeSlots: []
                        };
                    }
                    
                    // Add this time slot
                    groupedData[groupKey].timeSlots.push({
                        bookableItemId: bookableItemId,
                        startDateTime: startDateTime,
                        endDateTime: endDateTime,
                        locationName: locationName,
                        serviceName: serviceName
                    });
                });
                
                // Sort time slots by date/time
                Object.values(groupedData).forEach(group => {
                    group.timeSlots.sort((a, b) => {
                        return new Date(a.startDateTime) - new Date(b.startDateTime);
                    });
                });
                
                // Render table
                let html = '<table class="hw-mbo-schedule-table"><thead><tr>';
                html += '<th>Therapist</th>';
                html += '<th>Treatment</th>';
                html += '<th>Duration</th>';
                html += '<th>Price</th>';
                html += '<th>Available Times</th>';
                html += '<th>Action</th>';
                html += '</tr></thead><tbody>';
                
                const groups = Object.values(groupedData);
                groups.forEach(group => {
                    const therapistName = group.therapistName;
                    const staffId = group.staffId;
                    const staffPhoto = group.staffPhoto;
                    const baseName = group.baseName;
                    const timeSlots = group.timeSlots;
                    
                    // Get service metadata with fallback
                    const serviceMeta = serviceMetadata[group.sessionTypeId] || {};
                    
                    // Calculate duration from time slots if not in metadata
                    let duration = serviceMeta.Duration || '';
                    if (!duration && group.timeSlots.length > 0) {
                        const firstSlot = group.timeSlots[0];
                        if (firstSlot.startDateTime && firstSlot.endDateTime) {
                            const start = new Date(firstSlot.startDateTime);
                            const end = new Date(firstSlot.endDateTime);
                            duration = Math.round((end - start) / 60000); // Convert ms to minutes
                        }
                    }
                    
                    // Get price from metadata, or fallback to SessionType in BookableItem
                    let price = serviceMeta.Price || serviceMeta.OnlinePrice || 0;
                    if (!price && group.timeSlots.length > 0) {
                        // Try to get price from first BookableItem's SessionType
                        const matchingItem = bookableItems.find(item => 
                            item.SessionType && item.SessionType.Id == group.sessionTypeId
                        );
                        if (matchingItem && matchingItem.SessionType) {
                            price = matchingItem.SessionType.Price || matchingItem.SessionType.OnlinePrice || 0;
                        }
                    }
                    
                    // Therapist photo/initials
                    const initials = therapistName.split(' ').map(n => n.charAt(0)).join('').substring(0, 2);
                    const photoHtml = staffPhoto 
                        ? '<img src="' + escapeHtml(staffPhoto) + '" alt="' + escapeHtml(therapistName) + '" class="hw-mbo-therapist-photo" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-flex\';" /><span class="hw-mbo-therapist-initials" style="display:none;">' + escapeHtml(initials) + '</span>'
                        : '<span class="hw-mbo-therapist-initials">' + escapeHtml(initials) + '</span>';
                    
                    html += '<tr>';
                    html += '<td class="hw-mbo-therapist-cell"><div class="hw-mbo-therapist-info">' + photoHtml + '<a href="#" class="hw-mbo-therapist-name" data-staff-id="' + staffId + '">' + escapeHtml(therapistName) + ' | Therapist</a></div></td>';
                    html += '<td><a href="#" class="hw-mbo-treatment-name" data-session-type-id="' + group.sessionTypeId + '">' + escapeHtml(baseName) + '</a></td>';
                    html += '<td>' + escapeHtml(duration) + ' min</td>';
                    html += '<td class="hw-mbo-price">£' + parseFloat(price).toFixed(0) + '</td>';
                    
                    // Show available times (REAL from Mindbody)
                    html += '<td class="hw-mbo-times-cell"><div class="hw-mbo-times-grid">';
                    
                    timeSlots.forEach(slot => {
                        const startDate = new Date(slot.startDateTime);
                        const timeStr = String(startDate.getHours()).padStart(2, '0') + ':' + String(startDate.getMinutes()).padStart(2, '0');
                        const dateStr = startDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                        
                        html += '<button class="hw-mbo-time-slot" ';
                        html += 'data-bookable-item-id="' + slot.bookableItemId + '" ';
                        html += 'data-session-type-id="' + group.sessionTypeId + '" ';
                        html += 'data-staff-id="' + staffId + '" ';
                        html += 'data-start-datetime="' + escapeHtml(slot.startDateTime) + '" ';
                        html += 'data-end-datetime="' + escapeHtml(slot.endDateTime) + '" ';
                        html += 'data-service-name="' + escapeHtml(slot.serviceName) + '" ';
                        html += 'data-therapist-name="' + escapeHtml(therapistName) + '" ';
                        html += 'data-price="' + price + '" ';
                        html += 'data-location="' + escapeHtml(slot.locationName) + '">';
                        html += '<span class="time">' + timeStr + '</span>';
                        html += '<span class="date">' + dateStr + '</span>';
                        html += '</button>';
                    });
                    
                    html += '</div></td>';
                    html += '<td><button class="hw-mbo-book-button" disabled>Select Time</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                
                scheduleContent.innerHTML = html;
                
                // Attach time slot click handlers
                document.querySelectorAll('.hw-mbo-time-slot').forEach(btn => {
                    btn.addEventListener('click', handleTimeSlotSelection);
                });
            }
            
            // Handle time slot selection
            function handleTimeSlotSelection(e) {
                e.preventDefault();
                const btn = e.currentTarget;
                const row = btn.closest('tr');
                
                // Remove previous selection in this row
                row.querySelectorAll('.hw-mbo-time-slot').forEach(b => b.classList.remove('selected'));
                
                // Mark this slot as selected
                btn.classList.add('selected');
                
                // Enable book button
                const bookBtn = row.querySelector('.hw-mbo-book-button');
                if (bookBtn) {
                    bookBtn.disabled = false;
                    bookBtn.textContent = 'Book Now';
                    bookBtn.onclick = (ev) => handleConfirmBooking(ev, btn);
                }
            }
            
            // FIX #1 & #2: Group same treatments with different durations, show all therapists
            async function loadServicesSchedule() {
                let services = [...allServices];
                
                // Filter by selected categories (use Category field from API)
                if (selectedCategories.size > 0) {
                    services = services.filter(s => {
                        const serviceCat = s.Category || (s.ServiceCategory && s.ServiceCategory.Name) || s.Program || '';
                        for (const cat of selectedCategories) {
                            if (serviceCat.toLowerCase().includes(cat.toLowerCase().split(' ')[0]) ||
                                cat.toLowerCase().includes(serviceCat.toLowerCase().split(' ')[0])) {
                                return true;
                            }
                        }
                        return false;
                    });
                }
                
                // Filter by selected services
                if (selectedServices.size > 0) {
                    const selectedIds = Array.from(selectedServices);
                    services = services.filter(s => selectedIds.includes(String(s.Id)));
                }
                
                // Filter by therapist name if selected (use TherapistName field from API)
                const selectedTherapistName = filterTherapist ? filterTherapist.value : '';
                if (selectedTherapistName) {
                    services = services.filter(s => {
                        const therapist = (s.TherapistName || '').toLowerCase();
                        const serviceName = (s.Name || '').toLowerCase();
                        return therapist.includes(selectedTherapistName.toLowerCase()) ||
                               serviceName.includes(selectedTherapistName.toLowerCase());
                    });
                }
                
                // Group services by therapist + base treatment name (combining durations)
                const groupedByTherapistAndTreatment = {};
                
                services.forEach(service => {
                    const serviceName = service.Name || '';
                    
                    // Use TherapistName from API if available, otherwise extract from name
                    let therapistName = service.TherapistName || '';
                    if (!therapistName) {
                        const therapistMatch = serviceName.match(/\s-\s([A-Z][a-z]+(?:\s+[A-Z]\.?)?(?:\s+[A-Z][a-z]+)?)\s*(?:-|$|\d|\')/i);
                        if (therapistMatch) {
                            therapistName = therapistMatch[1].trim();
                            // Make sure it's not a duration
                            if (/^\d+\s*(min|mins)?$/i.test(therapistName)) {
                                therapistName = 'General';
                            }
                        } else {
                            therapistName = 'General';
                        }
                    }
                    
                    // Use Duration from API (already extracted server-side)
                    const duration = parseInt(service.Duration) || 0;
                    
                    // Get base treatment name (remove therapist and duration from name)
                    let baseName = serviceName
                        .replace(/\s*-\s*[A-Z][a-z]+(?:\s+[A-Z]\.?)?(?:\s+[A-Z][a-z]+)?\s*(?:-|$)/gi, ' ')
                        .replace(/\s*-?\s*\d+\s*(?:min|mins|minutes|\')\s*/gi, '')
                        .replace(/\s*-\s*\d+\s*$/g, '')
                        .replace(/\s+/g, ' ')
                        .trim();
                    
                    if (!baseName) baseName = serviceName;
                    
                    const groupKey = therapistName + '||' + baseName;
                    
                    if (!groupedByTherapistAndTreatment[groupKey]) {
                        groupedByTherapistAndTreatment[groupKey] = {
                            therapist: therapistName,
                            baseName: baseName,
                            category: service.Category || '',
                            variants: []
                        };
                    }
                    
                    // Check for duplicate durations before adding
                    const existingDurations = groupedByTherapistAndTreatment[groupKey].variants.map(v => v.duration);
                    if (!existingDurations.includes(duration) || duration === 0) {
                        // If duration is 0, still add but only once
                        if (duration === 0 && existingDurations.includes(0)) {
                            return; // Skip duplicate zero-duration
                        }
                        
                        groupedByTherapistAndTreatment[groupKey].variants.push({
                            id: service.Id,
                            duration: duration,
                            price: parseFloat(service.Price) || 0,
                            fullName: serviceName,
                            service: service
                        });
                    }
                });
                
                // Sort variants by duration within each group and remove any remaining duplicates
                Object.values(groupedByTherapistAndTreatment).forEach(group => {
                    // Deduplicate by duration (keep first occurrence)
                    const seen = new Set();
                    group.variants = group.variants.filter(v => {
                        if (seen.has(v.duration)) return false;
                        seen.add(v.duration);
                        return true;
                    });
                    // Sort by duration
                    group.variants.sort((a, b) => a.duration - b.duration);
                });
                
                renderServicesSchedule(groupedByTherapistAndTreatment);
            }
            
            function renderServicesSchedule(groupedData) {
                if (!scheduleContent) return;
                
                const groups = Object.values(groupedData);
                
                if (groups.length === 0) {
                    scheduleContent.innerHTML = '<div class="hw-mbo-no-results"><h3>No Treatments Found</h3><p>Try adjusting your filters to find available treatments.</p></div>';
                    return;
                }
                
                // Group by therapist for display
                const byTherapist = {};
                groups.forEach(group => {
                    if (!byTherapist[group.therapist]) {
                        byTherapist[group.therapist] = [];
                    }
                    byTherapist[group.therapist].push(group);
                });
                
                const therapistNames = Object.keys(byTherapist).sort();
                
                let html = '';
                
                // Get today's date at NOON LOCAL time (to avoid DST/timezone issues)
                const now = new Date();
                const todayYear = now.getFullYear();
                const todayMonth = now.getMonth();
                const todayDay = now.getDate();
                const today = new Date(todayYear, todayMonth, todayDay, 12, 0, 0);
                
                // Parse dates from YYYY-MM-DD input fields - use NOON to avoid timezone issues
                function parseLocalDate(dateStr) {
                    if (!dateStr) return null;
                    const parts = dateStr.split('-');
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1; // JS months are 0-indexed
                    const day = parseInt(parts[2], 10);
                    return new Date(year, month, day, 12, 0, 0); // Use noon to avoid DST issues
                }
                
                let startDate, endDate;
                
                if (filterStartDate && filterStartDate.value) {
                    startDate = parseLocalDate(filterStartDate.value);
                } else {
                    startDate = new Date(todayYear, todayMonth, todayDay, 12, 0, 0);
                }
                
                if (filterEndDate && filterEndDate.value) {
                    endDate = parseLocalDate(filterEndDate.value);
                } else {
                    endDate = new Date(todayYear, todayMonth, todayDay + daysToShow - 1, 12, 0, 0);
                }
                
                // Calculate days to show
                const daysDiff = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                const maxDays = Math.min(daysDiff, daysToShow);
                
                console.log('Date Debug:', {
                    filterStart: filterStartDate ? filterStartDate.value : 'none',
                    filterEnd: filterEndDate ? filterEndDate.value : 'none',
                    startDate: startDate.toDateString(),
                    endDate: endDate.toDateString(),
                    daysDiff: daysDiff,
                    maxDays: maxDays
                });
                
                for (let dayOffset = 0; dayOffset < maxDays; dayOffset++) {
                    // Create date for this day explicitly using year/month/day
                    const currentDate = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + dayOffset, 12, 0, 0);
                    
                    if (currentDate > endDate) continue;
                    
                    const dayName = currentDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }).toUpperCase();
                    
                    html += '<div class="hw-mbo-day-section">';
                    html += '<div class="hw-mbo-day-header"><h3>' + escapeHtml(dayName) + '</h3></div>';
                    html += '<div class="hw-mbo-table-wrapper">';
                    html += '<table class="hw-mbo-table">';
                    html += '<thead><tr>';
                    html += '<th>Therapist</th>';
                    html += '<th>Treatment</th>';
                    html += '<th>Location</th>';
                    html += '<th>Duration</th>';
                    html += '<th>Start Time</th>';
                    html += '<th>Price/Package</th>';
                    html += '<th>Availability</th>';
                    html += '</tr></thead><tbody>';
                    
                    // FIX #2: Show ALL therapists for each day
                    therapistNames.forEach(therapistName => {
                        const treatments = byTherapist[therapistName];
                        
                        treatments.forEach(treatment => {
                            const variants = treatment.variants;
                            const defaultVariant = variants[0];
                            
                            // FIX #1: Create duration dropdown if multiple variants
                            let durationHtml = '';
                            if (variants.length > 1) {
                                durationHtml = '<select class="hw-mbo-duration-select" data-treatment-key="' + escapeHtml(therapistName + '||' + treatment.baseName) + '">';
                                variants.forEach((v, idx) => {
                                    durationHtml += '<option value="' + v.id + '" data-price="' + v.price + '" data-duration="' + v.duration + '"' + (idx === 0 ? ' selected' : '') + '>' + v.duration + ' min</option>';
                                });
                                durationHtml += '</select>';
                            } else {
                                durationHtml = (defaultVariant.duration || '-') + ' min';
                            }
                            
                            const timeSlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];
                            const randomStartIndex = Math.floor(Math.random() * 3);
                            const availableTimes = timeSlots.slice(randomStartIndex, randomStartIndex + 4);
                            
                            const serviceData = {
                                id: defaultVariant.id,
                                name: treatment.baseName,
                                therapist: therapistName,
                                price: defaultVariant.price,
                                duration: defaultVariant.duration,
                                location: defaultLocation,
                                variants: variants
                            };
                            
                            // Get therapist photo
                            const therapistPhoto = therapistPhotos[therapistName] || '';
                            const photoHtml = therapistPhoto 
                                ? '<img src="' + escapeHtml(therapistPhoto) + '" alt="' + escapeHtml(therapistName) + '" class="hw-mbo-therapist-photo" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-flex\';" /><span class="hw-mbo-therapist-initials" style="display:none;">' + escapeHtml(therapistName.split(' ').map(n => n.charAt(0)).join('').substring(0,2)) + '</span>'
                                : '<span class="hw-mbo-therapist-initials">' + escapeHtml(therapistName.split(' ').map(n => n.charAt(0)).join('').substring(0,2)) + '</span>';
                            
                            html += '<tr data-treatment-key="' + escapeHtml(therapistName + '||' + treatment.baseName) + '">';
                            html += '<td class="hw-mbo-therapist-cell"><div class="hw-mbo-therapist-info">' + photoHtml + '<a href="#" class="hw-mbo-therapist-name" data-staff-name="' + escapeHtml(therapistName) + '">' + escapeHtml(therapistName) + ' | Therapist</a></div></td>';
                            html += '<td><a href="#" class="hw-mbo-treatment-name" data-session-type-id="' + defaultVariant.id + '" data-session-type-name="' + escapeHtml(treatment.baseName) + '">' + escapeHtml(treatment.baseName) + '</a></td>';
                            html += '<td class="hw-mbo-location">' + escapeHtml(defaultLocation) + '</td>';
                            html += '<td class="hw-mbo-duration-cell">' + durationHtml + '</td>';
                            html += '<td><select class="hw-mbo-time-select">' + availableTimes.map(t => '<option value="' + t + '">' + t + '</option>').join('') + '</select></td>';
                            html += '<td class="hw-mbo-price" data-base-price="' + defaultVariant.price + '">£' + parseFloat(defaultVariant.price).toFixed(0) + '</td>';
                            html += '<td><button class="hw-mbo-book-btn" data-service=\'' + JSON.stringify(serviceData).replace(/'/g, "&#39;") + '\'>Book Now</button></td>';
                            html += '</tr>';
                        });
                    });
                    
                    html += '</tbody></table></div></div>';
                }
                
                scheduleContent.innerHTML = html;
                
                // Attach event handlers
                document.querySelectorAll('.hw-mbo-therapist-name').forEach(el => {
                    el.addEventListener('click', handleTherapistClick);
                });
                
                document.querySelectorAll('.hw-mbo-treatment-name').forEach(el => {
                    el.addEventListener('click', handleTreatmentClick);
                });
                
                document.querySelectorAll('.hw-mbo-book-btn').forEach(btn => {
                    btn.addEventListener('click', handleBookNow);
                });
                
                // FIX #1: Handle duration dropdown change to update price
                document.querySelectorAll('.hw-mbo-duration-select').forEach(select => {
                    select.addEventListener('change', handleDurationChange);
                });
            }
            
            // FIX #1: Update price when duration changes
            function handleDurationChange(e) {
                const select = e.target;
                const selectedOption = select.options[select.selectedIndex];
                const newPrice = selectedOption.dataset.price;
                const newDuration = selectedOption.dataset.duration;
                const newServiceId = select.value;
                
                const row = select.closest('tr');
                if (row) {
                    // Update price display
                    const priceCell = row.querySelector('.hw-mbo-price');
                    if (priceCell) {
                        priceCell.textContent = '£' + parseFloat(newPrice).toFixed(0);
                    }
                    
                    // Update book button data
                    const bookBtn = row.querySelector('.hw-mbo-book-btn');
                    if (bookBtn) {
                        const serviceData = JSON.parse(bookBtn.dataset.service.replace(/&#39;/g, "'"));
                        serviceData.id = newServiceId;
                        serviceData.price = parseFloat(newPrice);
                        serviceData.duration = parseInt(newDuration);
                        bookBtn.dataset.service = JSON.stringify(serviceData).replace(/'/g, "&#39;");
                    }
                }
            }
            
            function handleTherapistClick(e) {
                e.preventDefault();
                const staffName = e.target.dataset.staffName;
                
                if (modalTitle) modalTitle.textContent = 'Therapist: ' + staffName;
                if (modalBody) modalBody.innerHTML = '<div class="hw-mbo-loading"><div class="hw-mbo-spinner"></div><p>Loading...</p></div>';
                if (detailModal) detailModal.classList.add('open');
                
                fetch(baseUrl + 'staff-details?staff_name=' + encodeURIComponent(staffName))
                    .then(response => response.json())
                    .then(data => {
                        renderStaffModal(data);
                    })
                    .catch(() => {
                        if (modalBody) modalBody.innerHTML = '<div class="hw-mbo-error">Failed to load therapist details.</div>';
                    });
            }
            
            // Enhanced therapist modal with image and more info (v1.4.0)
            function renderStaffModal(staff) {
                if (!modalBody) return;
                
                const staffName = staff.Name || ((staff.FirstName || '') + ' ' + (staff.LastName || '')).trim();
                const imageUrl = staff.ImageUrl || staff.ImageURL || staff.Photo || '';
                const initials = staffName.split(' ').map(n => n.charAt(0).toUpperCase()).join('').substring(0, 2);
                
                let html = '<div class="hw-mbo-staff-modal">';
                
                // Staff image or initials avatar
                html += '<div class="hw-mbo-staff-image-section">';
                if (imageUrl) {
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(staffName) + '" class="hw-mbo-staff-photo" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" />';
                    html += '<div class="hw-mbo-staff-initials" style="display: none;">' + escapeHtml(initials) + '</div>';
                } else {
                    html += '<div class="hw-mbo-staff-initials">' + escapeHtml(initials) + '</div>';
                }
                html += '</div>';
                
                // Staff details
                html += '<div class="hw-mbo-modal-details">';
                
                if (staffName) {
                    html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Name:</div><div class="hw-mbo-modal-value">' + escapeHtml(staffName) + '</div></div>';
                }
                
                html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Role:</div><div class="hw-mbo-modal-value">' + escapeHtml(staff.Role || 'Therapist') + '</div></div>';
                html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Location:</div><div class="hw-mbo-modal-value">' + escapeHtml(defaultLocation) + '</div></div>';
                
                // Email if available
                if (staff.Email) {
                    html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Email:</div><div class="hw-mbo-modal-value"><a href="mailto:' + escapeHtml(staff.Email) + '" style="color: var(--hw-blue);">' + escapeHtml(staff.Email) + '</a></div></div>';
                }
                
                // Phone if available
                if (staff.MobilePhone || staff.HomePhone) {
                    const phone = staff.MobilePhone || staff.HomePhone;
                    html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Phone:</div><div class="hw-mbo-modal-value"><a href="tel:' + escapeHtml(phone) + '" style="color: var(--hw-blue);">' + escapeHtml(phone) + '</a></div></div>';
                }
                
                // Session types / Services offered
                if (staff.SessionTypes && staff.SessionTypes.length > 0) {
                    const services = staff.SessionTypes.map(s => s.Name || s).filter(Boolean);
                    if (services.length > 0) {
                        html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Services:</div><div class="hw-mbo-modal-value">' + services.map(s => escapeHtml(s)).join(', ') + '</div></div>';
                    }
                }
                
                html += '</div>';
                
                // Bio section
                if (staff.Bio) {
                    html += '<div class="hw-mbo-modal-bio"><strong>About:</strong><br>' + staff.Bio + '</div>';
                }
                
                html += '</div>';
                
                modalBody.innerHTML = html;
            }
            
            function handleTreatmentClick(e) {
                e.preventDefault();
                const sessionTypeId = e.target.dataset.sessionTypeId;
                const sessionTypeName = e.target.dataset.sessionTypeName;
                
                if (modalTitle) modalTitle.textContent = 'Treatment: ' + sessionTypeName;
                if (modalBody) modalBody.innerHTML = '<div class="hw-mbo-loading"><div class="hw-mbo-spinner"></div><p>Loading...</p></div>';
                if (detailModal) detailModal.classList.add('open');
                
                fetch(baseUrl + 'service-details?service_id=' + encodeURIComponent(sessionTypeId))
                    .then(response => response.json())
                    .then(data => {
                        renderServiceModal(data);
                    })
                    .catch(() => {
                        if (modalBody) modalBody.innerHTML = '<div class="hw-mbo-error">Failed to load treatment details.</div>';
                    });
            }
            
            function renderServiceModal(service) {
                if (!modalBody) return;
                
                let html = '<div class="hw-mbo-modal-details">';
                
                if (service.Name) {
                    html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Name:</div><div class="hw-mbo-modal-value">' + escapeHtml(service.Name) + '</div></div>';
                }
                
                html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Location:</div><div class="hw-mbo-modal-value">' + escapeHtml(defaultLocation) + '</div></div>';
                
                if (service.Price || service.OnlinePrice) {
                    html += '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Price:</div><div class="hw-mbo-modal-value hw-mbo-price">£' + parseFloat(service.Price || service.OnlinePrice).toFixed(2) + '</div></div>';
                }
                
                html += '</div>';
                
                if (service.Description) {
                    html += '<div class="hw-mbo-modal-bio">' + service.Description + '</div>';
                }
                
                modalBody.innerHTML = html;
            }
            
            // FIX #3: Book Now integration with WooCommerce
            function handleBookNow(e) {
                const serviceData = JSON.parse(e.target.dataset.service.replace(/&#39;/g, "'"));
                const row = e.target.closest('tr');
                const timeSelect = row ? row.querySelector('.hw-mbo-time-select') : null;
                const selectedTime = timeSelect ? timeSelect.value : '10:00';
                
                // Get current date from the day section
                const daySection = e.target.closest('.hw-mbo-day-section');
                const dayHeader = daySection ? daySection.querySelector('.hw-mbo-day-header h3') : null;
                const selectedDate = dayHeader ? dayHeader.textContent : 'Today';
                
                if (modalTitle) modalTitle.textContent = 'Book Appointment';
                if (modalBody) {
                    modalBody.innerHTML = 
                        '<div class="hw-mbo-modal-details">' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Treatment:</div><div class="hw-mbo-modal-value">' + escapeHtml(serviceData.name) + '</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Therapist:</div><div class="hw-mbo-modal-value">' + escapeHtml(serviceData.therapist) + '</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Date:</div><div class="hw-mbo-modal-value">' + escapeHtml(selectedDate) + '</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Time:</div><div class="hw-mbo-modal-value">' + escapeHtml(selectedTime) + '</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Duration:</div><div class="hw-mbo-modal-value">' + (serviceData.duration || '-') + ' min</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Location:</div><div class="hw-mbo-modal-value">' + escapeHtml(serviceData.location) + '</div></div>' +
                        '<div class="hw-mbo-modal-row"><div class="hw-mbo-modal-label">Price:</div><div class="hw-mbo-modal-value hw-mbo-price">£' + parseFloat(serviceData.price).toFixed(2) + '</div></div>' +
                        '</div>' +
                        '<div class="hw-mbo-modal-action">' +
                        '<button class="hw-mbo-book-btn hw-mbo-add-to-cart-btn" id="hw-confirm-booking" data-service-id="' + serviceData.id + '" data-price="' + serviceData.price + '" data-name="' + escapeHtml(serviceData.name) + '" data-therapist="' + escapeHtml(serviceData.therapist) + '" data-time="' + escapeHtml(selectedTime) + '" data-date="' + escapeHtml(selectedDate) + '">Add to Cart & Checkout</button>' +
                        '</div>';
                }
                if (detailModal) detailModal.classList.add('open');
                
                // Attach confirm booking handler
                const confirmBtn = document.getElementById('hw-confirm-booking');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', handleConfirmBooking);
                }
            }
            
            // Handle confirm booking - add to WooCommerce cart
            async function handleConfirmBooking(e, timeSlotBtn) {
                const btn = e.target;
                const originalText = btn.textContent;
                btn.textContent = 'Processing...';
                btn.disabled = true;
                
                // Get data from time slot button
                const bookableItemId = timeSlotBtn.dataset.bookableItemId;
                const sessionTypeId = timeSlotBtn.dataset.sessionTypeId;
                const staffId = timeSlotBtn.dataset.staffId;
                const startDateTime = timeSlotBtn.dataset.startDatetime;
                const endDateTime = timeSlotBtn.dataset.endDatetime;
                const serviceName = timeSlotBtn.dataset.serviceName;
                const therapistName = timeSlotBtn.dataset.therapistName;
                const price = timeSlotBtn.dataset.price;
                const location = timeSlotBtn.dataset.location;
                
                const formData = new FormData();
                formData.append('action', 'hw_validate_and_add_to_cart');
                formData.append('nonce', nonce);
                formData.append('bookable_item_id', bookableItemId);
                formData.append('session_type_id', sessionTypeId);
                formData.append('staff_id', staffId);
                formData.append('start_datetime', startDateTime);
                formData.append('end_datetime', endDateTime);
                formData.append('service_name', serviceName);
                formData.append('therapist_name', therapistName);
                formData.append('price', price);
                formData.append('location', location);
                
                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Redirect to cart/checkout
                        window.location.href = cartUrl;
                    } else {
                        alert('Booking Failed: ' + (data.data || data.message || 'This appointment is no longer available'));
                        btn.textContent = originalText;
                        btn.disabled = false;
                        // Refresh availability
                        await loadAvailability();
                    }
                } catch (error) {
                    console.error('Booking error:', error);
                    alert('An error occurred. Please try again.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            }
            
            function setupEventListeners() {
                if (treatmentTrigger) {
                    treatmentTrigger.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (treatmentOptions) treatmentOptions.classList.toggle('open');
                        treatmentTrigger.classList.toggle('open');
                        if (treatmentOptions && treatmentOptions.classList.contains('open') && treatmentSearch) {
                            treatmentSearch.focus();
                        }
                    });
                }
                
                if (treatmentSearch) {
                    treatmentSearch.addEventListener('input', (e) => {
                        renderCategoryOptions(e.target.value);
                    });
                }
                
                if (treatmentOptions) {
                    treatmentOptions.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }
                
                document.addEventListener('click', () => {
                    if (treatmentOptions) treatmentOptions.classList.remove('open');
                    if (treatmentTrigger) treatmentTrigger.classList.remove('open');
                });
                
                if (filterStartDate) filterStartDate.addEventListener('change', debouncedLoadAvailability);
                if (filterEndDate) filterEndDate.addEventListener('change', debouncedLoadAvailability);
                if (filterTime) filterTime.addEventListener('change', debouncedLoadAvailability);
                if (filterTherapist) filterTherapist.addEventListener('change', debouncedLoadAvailability);
                
                if (searchButton) searchButton.addEventListener('click', loadAvailability);
                
                if (modalClose) {
                    modalClose.addEventListener('click', () => {
                        if (detailModal) detailModal.classList.remove('open');
                    });
                }
                
                if (detailModal) {
                    detailModal.addEventListener('click', (e) => {
                        if (e.target === detailModal) detailModal.classList.remove('open');
                    });
                }
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hw_mindbody_appointments', 'hw_mindbody_appointments_shortcode' );

/**
 * AJAX Handler: Add Mindbody treatment to WooCommerce cart
 */
function hw_add_mindbody_treatment_to_cart() {
    check_ajax_referer( 'hw_mindbody_book', 'nonce' );
    
    $service_id   = sanitize_text_field( wp_unslash( $_POST['service_id'] ?? '' ) );
    $service_name = sanitize_text_field( wp_unslash( $_POST['service_name'] ?? '' ) );
    $price        = floatval( $_POST['price'] ?? 0 );
    $therapist    = sanitize_text_field( wp_unslash( $_POST['therapist'] ?? '' ) );
    $appt_time    = sanitize_text_field( wp_unslash( $_POST['appointment_time'] ?? '' ) );
    $appt_date    = sanitize_text_field( wp_unslash( $_POST['appointment_date'] ?? '' ) );
    
    if ( empty( $service_id ) || empty( $service_name ) || $price <= 0 ) {
        wp_send_json_error( 'Invalid service data' );
    }
    
    // Check if WooCommerce is active
    if ( ! function_exists( 'WC' ) ) {
        wp_send_json_error( 'WooCommerce is not active' );
    }
    
    // Find or create a WooCommerce product for this service
    $product_id = wc_get_product_id_by_sku( 'mb-' . $service_id );
    
    if ( ! $product_id ) {
        // Create a simple product
        $product = new WC_Product_Simple();
        $product->set_name( $service_name );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' );
        $product->set_sku( 'mb-' . $service_id );
        $product->set_regular_price( $price );
        $product->set_price( $price );
        $product->set_virtual( true );
        $product->set_sold_individually( true );
        $product_id = $product->save();
        
        // Set category
        wp_set_object_terms( $product_id, 'Treatment', 'product_cat' );
    } else {
        // Update price if changed
        $product = wc_get_product( $product_id );
        if ( $product && $product->get_price() != $price ) {
            $product->set_regular_price( $price );
            $product->set_price( $price );
            $product->save();
        }
    }
    
    // Add to cart with custom data
    $cart_item_data = array(
        'mindbody_service_id' => $service_id,
        'mindbody_therapist'  => $therapist,
        'mindbody_date'       => $appt_date,
        'mindbody_time'       => $appt_time,
    );
    
    $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
    
    if ( $cart_item_key ) {
        wp_send_json_success( array( 'cart_key' => $cart_item_key ) );
    } else {
        wp_send_json_error( 'Failed to add to cart' );
    }
}
add_action( 'wp_ajax_hw_add_mindbody_treatment_to_cart', 'hw_add_mindbody_treatment_to_cart' );
add_action( 'wp_ajax_nopriv_hw_add_mindbody_treatment_to_cart', 'hw_add_mindbody_treatment_to_cart' );

/**
 * AJAX Handler: Validate and add Mindbody treatment to WooCommerce cart (NEW)
 * 
 * This handler validates availability in real-time before adding to cart.
 */
function hw_validate_and_add_to_cart() {
    check_ajax_referer( 'hw_mindbody_book', 'nonce' );
    
    // Get parameters
    $bookable_item_id = intval( $_POST['bookable_item_id'] ?? 0 );
    $session_type_id  = intval( $_POST['session_type_id'] ?? 0 );
    $staff_id         = intval( $_POST['staff_id'] ?? 0 );
    $start_datetime   = sanitize_text_field( wp_unslash( $_POST['start_datetime'] ?? '' ) );
    $end_datetime     = sanitize_text_field( wp_unslash( $_POST['end_datetime'] ?? '' ) );
    $service_name     = sanitize_text_field( wp_unslash( $_POST['service_name'] ?? '' ) );
    $therapist_name   = sanitize_text_field( wp_unslash( $_POST['therapist_name'] ?? '' ) );
    $price            = floatval( $_POST['price'] ?? 0 );
    $location         = sanitize_text_field( wp_unslash( $_POST['location'] ?? 'Primrose Hill' ) );
    
    if ( ! $bookable_item_id || ! $session_type_id || ! $start_datetime ) {
        wp_send_json_error( array( 'message' => 'Invalid booking data' ) );
    }
    
    // Check WooCommerce
    if ( ! function_exists( 'WC' ) ) {
        wp_send_json_error( array( 'message' => 'WooCommerce is not active' ) );
    }
    
    // STEP 1: VALIDATE - Re-query Mindbody to verify slot is still available
    $api = new HW_Mindbody_API();
    
    // Build params to check this specific slot
    $verify_params = array(
        'SessionTypeIds' => array( $session_type_id ),
        'StaffIds'       => array( $staff_id ),
        'StartDate'      => gmdate( 'Y-m-d\TH:i:s', strtotime( $start_datetime ) - 3600 ), // 1 hour before
        'EndDate'        => gmdate( 'Y-m-d\TH:i:s', strtotime( $start_datetime ) + 3600 ), // 1 hour after
        'Limit'          => 100,
    );
    
    $bookable_items = $api->get_bookable_items( $verify_params );
    
    if ( is_wp_error( $bookable_items ) ) {
        wp_send_json_error( array( 'message' => 'Unable to verify availability. Please try again.' ) );
    }
    
    // Check if our specific BookableItemId exists and is available
    $found = false;
    foreach ( $bookable_items as $item ) {
        if ( $item['Id'] == $bookable_item_id && 
             isset( $item['StartDateTime'] ) && 
             $item['StartDateTime'] == $start_datetime ) {
            $found = true;
            break;
        }
    }
    
    if ( ! $found ) {
        wp_send_json_error( array( 
            'message' => 'This appointment is no longer available. Please select another time.' 
        ) );
    }
    
    // STEP 2: Create/find WooCommerce product
    $product_id = wc_get_product_id_by_sku( 'mb-' . $session_type_id );
    
    if ( ! $product_id ) {
        $product = new WC_Product_Simple();
        $product->set_name( $service_name );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' );
        $product->set_sku( 'mb-' . $session_type_id );
        $product->set_regular_price( $price );
        $product->set_price( $price );
        $product->set_virtual( true );
        $product->set_sold_individually( true );
        $product_id = $product->save();
        
        wp_set_object_terms( $product_id, 'Treatment', 'product_cat' );
    } else {
        $product = wc_get_product( $product_id );
        if ( $product && $product->get_price() != $price ) {
            $product->set_regular_price( $price );
            $product->set_price( $price );
            $product->save();
        }
    }
    
    // STEP 3: Add to cart with complete metadata
    $cart_item_data = array(
        '_bookable_item_id'   => $bookable_item_id, // CRITICAL for final booking
        '_session_type_id'    => $session_type_id,
        '_staff_id'           => $staff_id,
        '_start_datetime'     => $start_datetime,
        '_end_datetime'       => $end_datetime,
        '_location'           => $location,
        'mindbody_therapist'  => $therapist_name,
        'mindbody_date'       => gmdate( 'Y-m-d', strtotime( $start_datetime ) ),
        'mindbody_time'       => gmdate( 'H:i', strtotime( $start_datetime ) ),
    );
    
    $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
    
    if ( $cart_item_key ) {
        // Clear cache for this time range
        $cache_key_pattern = 'mindbody_bookable_*';
        // Note: WordPress doesn't support wildcard transient deletion easily
        // Consider implementing proper cache invalidation based on booking
        
        wp_send_json_success( array( 
            'message'  => 'Appointment added to cart',
            'cart_key' => $cart_item_key 
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to add to cart' ) );
    }
}
add_action( 'wp_ajax_hw_validate_and_add_to_cart', 'hw_validate_and_add_to_cart' );
add_action( 'wp_ajax_nopriv_hw_validate_and_add_to_cart', 'hw_validate_and_add_to_cart' );


/**
 * Display custom cart item data
 */
function hw_display_mindbody_cart_item_data( $item_data, $cart_item ) {
    if ( isset( $cart_item['mindbody_therapist'] ) && ! empty( $cart_item['mindbody_therapist'] ) ) {
        $item_data[] = array(
            'key'   => 'Therapist',
            'value' => sanitize_text_field( $cart_item['mindbody_therapist'] ),
        );
    }
    if ( isset( $cart_item['mindbody_date'] ) && ! empty( $cart_item['mindbody_date'] ) ) {
        $item_data[] = array(
            'key'   => 'Date',
            'value' => sanitize_text_field( $cart_item['mindbody_date'] ),
        );
    }
    if ( isset( $cart_item['mindbody_time'] ) && ! empty( $cart_item['mindbody_time'] ) ) {
        $item_data[] = array(
            'key'   => 'Time',
            'value' => sanitize_text_field( $cart_item['mindbody_time'] ),
        );
    }
    return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'hw_display_mindbody_cart_item_data', 10, 2 );

/**
 * Save custom cart item data to order
 */
function hw_save_mindbody_order_item_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['mindbody_service_id'] ) ) {
        $item->add_meta_data( '_mindbody_service_id', $values['mindbody_service_id'], true );
    }
    if ( isset( $values['mindbody_therapist'] ) ) {
        $item->add_meta_data( 'Therapist', $values['mindbody_therapist'], true );
    }
    if ( isset( $values['mindbody_date'] ) ) {
        $item->add_meta_data( 'Appointment Date', $values['mindbody_date'], true );
    }
    if ( isset( $values['mindbody_time'] ) ) {
        $item->add_meta_data( 'Appointment Time', $values['mindbody_time'], true );
    }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'hw_save_mindbody_order_item_meta', 10, 4 );

/**
 * Shortcode: hw_mindbody_therapists
 */
function hw_mindbody_therapists_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'title' => 'Our Therapists',
        'limit' => 12,
    ), $atts, 'hw_mindbody_therapists' );
    
    $api = hw_mindbody_api();
    
    if ( ! $api->is_configured() ) {
        return '<p>' . esc_html__( 'Mindbody API is not configured.', 'homewellness' ) . '</p>';
    }
    
    $therapists = $api->get_appointment_instructors();
    
    if ( is_wp_error( $therapists ) ) {
        return '<p>' . esc_html__( 'Unable to load therapists.', 'homewellness' ) . '</p>';
    }
    
    if ( empty( $therapists ) ) {
        return '<p>' . esc_html__( 'No therapists found.', 'homewellness' ) . '</p>';
    }
    
    $therapists = array_slice( $therapists, 0, intval( $atts['limit'] ) );
    
    ob_start();
    ?>
    <div class="hw-mbo-therapists">
        <h2><?php echo esc_html( $atts['title'] ); ?></h2>
        <div class="hw-mbo-therapists-grid">
            <?php foreach ( $therapists as $therapist ) : ?>
                <?php
                $name      = trim( ( $therapist['FirstName'] ?? '' ) . ' ' . ( $therapist['LastName'] ?? '' ) );
                $image_url = $therapist['ImageUrl'] ?? '';
                $bio       = $therapist['Bio'] ?? '';
                ?>
                <div class="hw-mbo-therapist-card">
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="hw-mbo-therapist-image" />
                    <?php endif; ?>
                    <h3><?php echo esc_html( $name ); ?></h3>
                    <?php if ( $bio ) : ?>
                        <p><?php echo esc_html( wp_trim_words( $bio, 20, '...' ) ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hw_mindbody_therapists', 'hw_mindbody_therapists_shortcode' );

/**
 * Shortcode: hw_mindbody_schedule
 */
function hw_mindbody_schedule_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'title' => 'Class Schedule',
        'days'  => 7,
    ), $atts, 'hw_mindbody_schedule' );
    
    $api = hw_mindbody_api();
    
    if ( ! $api->is_configured() ) {
        return '<p>' . esc_html__( 'Mindbody API is not configured.', 'homewellness' ) . '</p>';
    }
    
    $classes = $api->get_classes( array(
        'StartDateTime' => gmdate( 'Y-m-d\TH:i:s' ),
        'EndDateTime'   => gmdate( 'Y-m-d\TH:i:s', strtotime( '+' . intval( $atts['days'] ) . ' days' ) ),
    ) );
    
    if ( is_wp_error( $classes ) ) {
        return '<p>' . esc_html__( 'Unable to load class schedule.', 'homewellness' ) . '</p>';
    }
    
    if ( empty( $classes ) ) {
        return '<p>' . esc_html__( 'No classes scheduled.', 'homewellness' ) . '</p>';
    }
    
    ob_start();
    ?>
    <div class="hw-mbo-schedule">
        <h2><?php echo esc_html( $atts['title'] ); ?></h2>
        <div class="hw-mbo-schedule-list">
            <?php foreach ( $classes as $class ) : ?>
                <?php
                $class_name  = $class['ClassDescription']['Name'] ?? $class['Name'] ?? 'Class';
                $start_time  = isset( $class['StartDateTime'] ) ? gmdate( 'l, M j g:i A', strtotime( $class['StartDateTime'] ) ) : '';
                $teacher     = trim( ( $class['Staff']['FirstName'] ?? '' ) . ' ' . ( $class['Staff']['LastName'] ?? '' ) );
                $location    = $class['Location']['Name'] ?? '';
                ?>
                <div class="hw-mbo-class-item">
                    <h3><?php echo esc_html( $class_name ); ?></h3>
                    <?php if ( $start_time ) : ?>
                        <p class="hw-mbo-class-time"><?php echo esc_html( $start_time ); ?></p>
                    <?php endif; ?>
                    <?php if ( $teacher ) : ?>
                        <p class="hw-mbo-class-teacher"><?php esc_html_e( 'with', 'homewellness' ); ?> <?php echo esc_html( $teacher ); ?></p>
                    <?php endif; ?>
                    <?php if ( $location ) : ?>
                        <p class="hw-mbo-class-location"><?php echo esc_html( $location ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hw_mindbody_schedule', 'hw_mindbody_schedule_shortcode' );
