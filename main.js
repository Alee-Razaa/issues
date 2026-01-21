jQuery(document).ready(function ($) {
    let currentSlideShop = 0;
    const $slides = $('.slide');

    function changeSlide(direction) {
        // Fade out the current slide
        $slides.eq(currentSlideShop).removeClass('active');

        // Calculate the next slide index
        currentSlideShop = (currentSlideShop + direction + $slides.length) % $slides.length;

        // Fade in the next slide
        $slides.eq(currentSlideShop).addClass('active');
    }

    // Auto-play slider
    setInterval(() => changeSlide(1), 4000);
    $('.prev').click(function () {
        changeSlide(-1); // Go to previous slide
    });
    $('.faq-question').on('click', function () {
        $(this).parent('.faq-item').toggleClass('active');
        
    });
    $('.next').click(function () {
        changeSlide(1); // Go to next slide
    });
    $('.hamburger-menu').click(function () {
        $(this).toggleClass('open'); // Toggle hamburger icon to close (X)
        $('#mobile-menu').toggleClass('open'); // Toggle visibility of mobile menu
        $('body').toggleClass('menu-open'); // Prevent scrolling when the menu is open
        $('.logo').toggleClass('menu-opened');
        $('.hamburger-menu').toggleClass('menu-opened');
        $('.cart-svg').toggleClass('menu-opened');
    });
    $(".booking-filter-header a").click(function (e) {

        let targetId = $(this).data("target"); // Get target ID from data attribute

        if (targetId === "all") {
            // If "ALL" is clicked, scroll to the first carousel
            $("html, body").animate({ scrollTop: $(".carousel-container").first().offset().top - 100 }, 800);
        } else {
            let targetSection = $("#" + targetId);

            if (targetSection.length) {
                $("html, body").animate({ scrollTop: targetSection.offset().top - 100 }, 800);
            }
        }
    });
    /*
    $(".carousel-container").each(function (index) {
        let $carouselContainer = $(this);
        let $carousel = $carouselContainer.find(".carousel");
        let $slides = $carousel.find(".carousel-item");
        let $prevBtn = $carouselContainer.find(".prev");
        let $nextBtn = $carouselContainer.find(".next");

        let currentSlide = 0;
        let totalSlides;
        let itemWidth;
        let carouselWidth;
        let maxTranslateX;

        // Function to calculate total slides for each carousel separately
        function calculateTotalSlides() {
            let $visibleSlides = $carousel.find(".carousel-item:visible"); // Only count visible items
            const containerWidth = $carouselContainer.width();
            itemWidth = $visibleSlides.first().outerWidth(true) || 0; // Get width including margin
            carouselWidth = $visibleSlides.length * itemWidth;

            if (carouselWidth > containerWidth && itemWidth > 0) {
                totalSlides = Math.floor((carouselWidth - containerWidth) / itemWidth) + 2;
                maxTranslateX = carouselWidth - containerWidth;
            } else {
                totalSlides = 1;
            }

            // Reset slide index if needed
            if (currentSlide >= totalSlides) {
                currentSlide = 0;
            }
            updateCarouselPosition();
        }

        // Function to move to the next or previous slide
        function moveSlide(direction) {
            if (currentSlide === totalSlides - 1 && direction === 1) {
                return;
            }

            currentSlide += direction;

            if (currentSlide < 0) {
                currentSlide = totalSlides - 1;
            }
            if (currentSlide >= totalSlides) {
                currentSlide = totalSlides - 1;
            }

            updateCarouselPosition()
            toggleNavigationButtons();
        }
        function updateCarouselPosition() {
            let translateXValue = currentSlide * itemWidth;
            if (translateXValue > maxTranslateX) {
                translateXValue = maxTranslateX;
            }
            $carousel.css("transform", `translateX(-${translateXValue}px)`);
        }
        // Function to toggle prev/next button visibility per carousel
        function toggleNavigationButtons() {
            console.log("totalSlides", totalSlides);
            console.log("currentSlide", currentSlide);
            if (currentSlide === 0) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }

            if (currentSlide === totalSlides - 1 || currentSlide > totalSlides - 1) {
                $nextBtn.hide();
            } else {
                $nextBtn.show();
            }
        }

        // Attach click events specific to this carousel
        $prevBtn.click(function () {
            moveSlide(-1);
        });

        $nextBtn.click(function () {
            moveSlide(1);
        });

        // Recalculate on window resize
        $(window).resize(function () {
            calculateTotalSlides();
            itemWidth = $slides.first().outerWidth(true);
            toggleNavigationButtons();
        });

        // Initial calculations and UI setup
        calculateTotalSlides();
        toggleNavigationButtons();
        $(".filter-button").on("click", function (e) {
            e.preventDefault();

            $(".filter-button").removeClass("active");
            $(this).addClass("active");

            let selectedCategory = $(this).data("filter");

            $(".carousel-item").each(function () {
                let itemCategory = $(this).data("category");

                if (selectedCategory === "all" || itemCategory.toLowerCase() === selectedCategory) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            calculateTotalSlides();
            toggleNavigationButtons();
        });
    });
    */
   
    /*
    *   CSS Powered Carousel
    *   JS Is only needed to show/hide arrows and to scroll the carousel
    *   when clicked.
    */
    document.querySelectorAll(".carousel").forEach(carousel => {
    const prev = carousel.parentElement.querySelector(".prev");
    const next = carousel.parentElement.querySelector(".next");

    const updateArrows = () => {
        const maxScrollLeft = carousel.scrollWidth - carousel.clientWidth;
        if(carousel.scrollLeft > 0) { 
            prev.classList.remove('hidden');
            prev.classList.add('growIn');
        } else{
            prev.classList.add('hidden');
            prev.classList.remove('growIn');
        }
        console.log(carousel);
        console.log('scroll left is: '+carousel.scrollLeft);
        console.log('max scroll left is: '+maxScrollLeft);
        if(carousel.scrollLeft < maxScrollLeft - 1) {
            next.classList.remove('hidden');
            next.classList.add('growIn');
        } else{
            next.classList.add('hidden');
            next.classList.remove('growIn');
        }
    };

    // Initial update
    updateArrows();

    // Update on scroll
    carousel.addEventListener("scroll", updateArrows);

    // Optional: Update on resize (in case layout changes)
    window.addEventListener("resize", updateArrows);

    let firstItem = carousel.querySelector('.carousel-item');
    if(firstItem)
    {
        let scrollAmount = firstItem.getBoundingClientRect().width;
        prev.addEventListener('click', function(){
            carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        next.addEventListener('click', function () {
            carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
    }

    });




    const $toggle = $(".toggle");
    const $options = $(".toggle-container .toggle-option");
    const $dateDisplay = $(".date-display");
    const $scheduleContainers = $(".schedule-container");

    let currentRange = "today";  // Track whether we're in 'today' or '7-days'

    const filters = {
        teacher: "ALL",
        classType: "ALL",
        level: "ALL",
        timeOfDay: "ALL"
    };

    // Handle toggle (Today / 7 Days)
    $options.on("click", function () {
        const index = $options.index(this);
        currentRange = $(this).data("value");

        $toggle.css("left", `${index * 50}%`);

        $options.removeClass("active");
        $(this).addClass("active");

        updateDate(currentRange);

        // Apply filters including today/7-days logic
        filterClasses();
    });

    // Date formatter
    function formatDate(date) {
        const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        const months = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
        return `${days[date.getDay()]} ${date.getDate()} ${months[date.getMonth()]}`;
    }

    // Update date display above filters
    function updateDate(option) {
        const today = new Date();

        if (option === "today") {
            $dateDisplay.text(formatDate(today));
        } else if (option === "7-days") {
            const startDate = new Date(today);
            const endDate = new Date(today);
            endDate.setDate(today.getDate() + 6);

            $dateDisplay.text(`${formatDate(startDate)} - ${formatDate(endDate)}`);
        }
    }

    // Dropdown handling
    $(".dropdown-container .gray-btn").on("click", function (e) {
        e.stopPropagation();
        const $clickedDropdownOptions = $(this).siblings(".dropdown-options");
        $(".dropdown-options").not($clickedDropdownOptions).hide();
        $clickedDropdownOptions.toggle();
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest('.dropdown-options').length) {
            $(".dropdown-options").hide();
        }
    });
    function getQueryParam(param) {
        let urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    let selectedClass = getQueryParam("class");
    let selectedTeacher = getQueryParam("teacher");

    if (selectedClass) {
        selectedClass = decodeURIComponent(selectedClass).trim().toLowerCase();

        $(".dropdown-options .option").each(function () {
            let optionText = $(this).text().trim().toLowerCase();
            if (optionText === selectedClass) {

                let $dropdownMenu = $(this).parent(); // Get the dropdown container


                // Show the dropdown temporarily
                $dropdownMenu.show();

                setTimeout(() => {
                    $(this).trigger("mousedown").trigger("mouseup").trigger("click");
                    $options.trigger("click");
                }, 300);
                // Simulate user click

            }
        });
    }
    if (selectedTeacher) {
        selectedTeacher = decodeURIComponent(selectedTeacher).trim().toLowerCase();

        $(".dropdown-options .option").each(function () {
            let optionText = $(this).text().trim().toLowerCase();
            if (optionText === selectedTeacher) {

                let $dropdownMenu = $(this).parent(); // Get the dropdown container


                // Show the dropdown temporarily
                $dropdownMenu.show();

                setTimeout(() => {
                    $(this).trigger("mousedown").trigger("mouseup").trigger("click");
                    $options.trigger("click");
                }, 300);
                // Simulate user click

            }
        });
    }


    $(".dropdown-options").on("click", function (e) {
        e.stopPropagation();
    });

    $(".dropdown-options .option").on("click", function () {
        const selectedOption = $(this).text();
        const $filterResult = $(this).parent().siblings(".filter-result");
        $filterResult.text(selectedOption);

        const $parentDropdown = $(this).closest('.dropdown-container');
        const filterType = $parentDropdown.find('.gray-btn').text().trim().toLowerCase().replace(/\s+/g, '');

        const filterKeyMap = {
            "teacher": "teacher",
            "classtype": "classType",
            "level": "level",
            "timeofday": "timeOfDay"
        };

        const filterKey = filterKeyMap[filterType];
        if (filterKey) {
            filters[filterKey] = selectedOption;
        }

        $(this).parent().hide();

        // Apply filters immediately
        filterClasses();
    });

    // Combined filter (including Today/7-days logic)
    function filterClasses() {
        const todayFormatted = formatDate(new Date()).toUpperCase();

        $scheduleContainers.each(function () {
            const $container = $(this);
            const containerDate = $container.find('.schedule-container-title').text().trim();

            // Determine if this date should be visible based on the current toggle
            const isToday = (containerDate === todayFormatted);
            const dateMatches = (currentRange === "7-days") || (currentRange === "today" && isToday);

            if (!dateMatches) {
                $container.hide();
                return;  // Skip filtering the rows if the date doesn't match
            }

            const $rows = $container.find('.schedule-row').not(':first');  // Skip header row
            let anyVisible = false;

            $rows.each(function () {
                const $row = $(this);

                const className = $row.find('.schedule-column:nth-child(2)').clone()
                    .children().remove().end()
                    .text().trim().toUpperCase();

                const teacherName = $row.find('.schedule-column:nth-child(3)').clone()
                    .children().remove().end()
                    .text().trim().toUpperCase();

                const timeRange = $row.find('.schedule-column:nth-child(1)').text().trim();

                const matchesTeacher = (filters.teacher === "ALL" || teacherName.includes(filters.teacher.toUpperCase()));
                const matchesClassType = (filters.classType === "ALL" || className.includes(filters.classType.toUpperCase()));
                const matchesLevel = (filters.level === "ALL" || $row.find('.schedule-column:nth-child(2)').attr('data-level')?.toUpperCase() === filters.level.toUpperCase());

                const matchesTimeOfDay = checkTimeOfDayFilter(timeRange, filters.timeOfDay);

                if (matchesTeacher && matchesClassType && matchesLevel && matchesTimeOfDay) {
                    $row.show();
                    anyVisible = true;
                } else {
                    $row.hide();
                }
            });

            if (anyVisible) {
                $container.show();
            } else {
                $container.hide();
            }
        });
    }

    // "ALL" button functionality
    $(".horizontal-drag-button.active").on("click", function (e) {
        e.preventDefault();

        // Remove 'active' class from other buttons and activate "ALL"
        $(".filter-button").removeClass("active");
        $(this).addClass("active");

        // Show all carousel items
        $(".carousel-item").fadeIn();
    });
    // Time of Day filter helper
    function checkTimeOfDayFilter(timeRange, selectedTimeOfDay) {
        if (selectedTimeOfDay === "ALL") return true;

        const timeParts = timeRange.split('-');
        if (timeParts.length !== 2) return false;

        const startTime = parseTime(timeParts[0].trim());
        const timePeriod = getTimePeriod(startTime);

        return selectedTimeOfDay.toUpperCase() === timePeriod;
    }

    function parseTime(timeString) {
        const parts = timeString.match(/(\d+):(\d+) (\w+)/);
        if (!parts) return null;

        let hours = parseInt(parts[1], 10);
        const minutes = parseInt(parts[2], 10);
        const period = parts[3].toUpperCase();

        if (period === "PM" && hours < 12) hours += 12;
        if (period === "AM" && hours === 12) hours = 0;

        return new Date(2000, 1, 1, hours, minutes);
    }

    function getTimePeriod(date) {
        if (!date) return "ALL";

        const hours = date.getHours();
        if (hours >= 5 && hours < 9) return "EARLY MORNING";
        if (hours >= 9 && hours < 12) return "MORNING";
        if (hours >= 12 && hours < 17) return "AFTERNOON";
        if (hours >= 17 && hours <= 23) return "EVENING";
        return "ALL";
    }

    // Initialize default date + filter when page loads
    updateDate("today");
    filterClasses();


    $(".teacher-trigger").on("click", function (e) {
        e.stopPropagation();

        // Get staff data from data attributes
        const staffName = $(this).data('name');
        const staffImage = $(this).data('image');
        const staffBio = $(this).data('bio');
        const defaultAvatar = $(this).data('avatar');
        console.log("defaultAvatar", defaultAvatar);
        // Update popup content
        const $popup = $("#popup");
        $popup.find(".popup-avatar img").attr("src", staffImage || defaultAvatar);
        $popup.find(".popup-name").text(staffName || 'Name Not Available');
        $popup.find(".popup-description").text(staffBio || 'No biography available');

        // Show popup
        $popup.addClass("show");
    });

    // Close popup when clicking outside
    $(document).on("click", function (e) {
        if (!$(e.target).closest('.popup-content').length && !$(e.target).hasClass('teacher-trigger')) {
            $("#popup").removeClass("show");
        }
    });

    // Prevent popup from closing when clicking inside
    $(".popup-content").on("click", function (e) {
        e.stopPropagation();
    });

    // Close the popup when the close button is clicked
    $(".popup-close").on("click", function () {
        $("#popup").removeClass("show");
        $("#popup2").removeClass("show");
        $("#pricing-options").removeClass("show");
    });

    $(document).on('click', '.popup-close', function () {
        $(this).closest('#pricing-options').removeClass("show");
        $(this).closest('#pricing-options').html('<div class="popup-content"></div>');
    });
    // Close the popup when clicking outside the content
    $("#popup").on("click", function (e) {
        if ($(e.target).is("#popup")) {
            $("#popup").removeClass("show");
        }
    });
    $(".class-trigger").on("click", function (e) {
        e.stopPropagation();

        // Get class data from data attributes
        const className = $(this).data('class-name');
        const classDescription = $(this).data('class-description');

        // Update popup2 content
        const $popup2 = $("#popup2");
        $popup2.find(".popup-name").text(className || 'Class Name Not Available');
        $popup2.find(".popup-description").html(classDescription || 'No description available');

        // Show popup2
        $("#popup2").addClass("show");
    });
    $("#popup2").on("click", function (e) {
        if ($(e.target).is("#popup2")) {
            $("#popup2").removeClass("show");
        }
    });
    $(document).on("click", ".teahcer-dropdown-btn", function () {
        // Toggle the visibility of the associated description
        const description = $(this).siblings(".teacher-description");
        description.slideToggle(); // Smooth toggle effect

        // Toggle the + and - symbol
        const currentText = $(this).text().trim();
        console.log(currentText);
        $(this).text(currentText === "+" ? "-" : "+");
    });

    // Static Thumbnail Interaction
    const mainCarousel = $('.single-product-carousel').slick({
        arrows: true, // Enable arrows
        dots: false,
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        initialSlide: 0,
        prevArrow: $('.slick-prev'), // Custom previous button
        nextArrow: $('.slick-next'), // Custom next button
    });

    // Thumbnail click event
    $('.single-product-thumbnail-item').on('click', function () {
        // Get the index of the clicked thumbnail
        const index = $(this).data('index');

        // Change the active class on thumbnails
        $('.single-product-thumbnail-item img').removeClass('active');
        $(this).find('img').addClass('active');

        // Go to the corresponding slide in the main carousel (adjusted for zero-based indexing)
        mainCarousel.slick('slickGoTo', index); // Ensure index matches Slick's logic
    });

    // Set the first thumbnail as active on page load
    $('.single-product-thumbnail-item img').first().addClass('active');

    // Custom previous button functionality
    $('.slick-prev').on('click', function () {
        mainCarousel.slick('slickPrev');
    });

    // Custom next button functionality
    $('.slick-next').on('click', function () {
        mainCarousel.slick('slickNext');
    });
    // Event listener for the "Book Studio Class" button
    $('.book-class').on('click', function () {
        if (!userData.isUserLoggedIn) {
            alert('Please login or signup before booking.');
            return;
        }

        // Find the parent schedule section (where data-remainingclass and data-catetory are stored)
        console.log($('.scehdule-section').data('remainingclass'));
        const remainingClasses = parseInt($('.scehdule-section').data('remainingclass') || 0);
        const category = $('.scehdule-section').data('category');
        const classId = $(this).data('classid');
        const classDate = $(this).data('date');
        const classTime = $(this).data('time');
        const className = $(this).data('name');
        const classTeacher = $(this).data('teacher');
        console.log("category", category);
        // Case 1: User has remaining classes > 0 — skip pricing options and book directly
        if (remainingClasses > 0) {
            bookClassDirectly(classId, category);
            return; // Don't show pricing options
        }

        // Case 2: No remaining classes — show pricing options (your existing behavior)
        $('#pricing-options').addClass('show');

        // Populate visible class details
        $('#selected-class-name').text(className);
        $('#selected-class-date').text(classDate);
        $('#selected-class-time').text(classTime);
        $('#selected-class-teacher').text(classTeacher);
        $('#selected-class-id').text(classId);
    });

    // AJAX Function to Book Directly
    function bookClassDirectly(classId, category) {
        $('#popup2').addClass('show');
        $('#popup2 .popup-content').addClass('loading');
        $.ajax({
            url: woocommerce_params.ajax_url,  // Standard WordPress AJAX handler
            type: 'POST',
            data: {
                action: 'book_studio_class',  // This is the PHP handler we set up earlier
                class_id: classId,
                category: category,
            },
            success: function (response) {
                $('#popup2').removeClass('show');
                $('#popup2 .popup-content').removeClass('loading');
                if (response.success) {
                    alert(response.data.message);
                    // Optionally refresh the page or update remaining count if you want
                    location.reload();
                } else {
                    $('#popup2').removeClass('show');
                    $('#popup2 .popup-content').removeClass('loading');
                    alert('Booking failed: ' + response.data.message);
                }
            },
            error: function () {
                alert('Something went wrong while booking. Please try again.');
            }
        });
    }


    // Event listener for dynamically created "Select Pricing" buttons
    $('#pricing-options .select-pricing').on('click', function () {
        var serviceId = $(this).data('serviceid');  // Now set to 'single-class', '5-class', '10-class'
        var classId = Number($('#selected-class-id').html());
        console.log("serviceId", serviceId);
        $.ajax({
            url: woocommerce_params.ajax_url,
            method: 'POST',
            data: {
                action: 'add_mindbody_to_cart',
                serviceId: serviceId,
                classId: classId
            },
            dataType: 'json',
            success: function (response) {
                console.log("response", response);
                if (response.status === 'Success') {
                    window.location.href = response.checkout_url;  // Redirect to checkout
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error: ', status, error);
                alert('An error occurred while adding the class to the cart.');
            }
        });
    });

    // Event listener for dynamically created "Select Pricing" buttons
    $('.buy-button').on('click', function () {
        var productId = $(this).data('productid');  // Now set to 'single-class', '5-class', '10-class'
        var classId = Number($('#selected-class-id').html());
        console.log("productId", productId);
        $('#popup2').addClass('show');
        $('#popup2 .popup-content').addClass('loading');
        $.ajax({
            url: woocommerce_params.ajax_url,
            method: 'POST',
            data: {
                action: 'add_mindbody_to_cart',
                productId: productId,
                classId: classId
            },
            dataType: 'json',
            success: function (response) {
                $('#popup2').removeClass('show');
                $('#popup2 .popup-content').removeClass('loading');
                if (response.status === 'Success') {
                    window.location.href = response.checkout_url;  // Redirect to checkout
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error: ', status, error);
                alert('An error occurred while adding the class to the cart.');
            }
        });
    });


    $('.workshop-addcart').click(function () {
        if (!userData.isUserLoggedIn) {
            alert('Please login or signup before booking.');
            return;
        }
        var productId = $(this).data('productid');  // Now set to 'single-class', '5-class', '10-class'
        var enrollmentid = $(this).data('enrollmentid');
        console.log("productId", productId);
        $('#popup2').addClass('show');
        $('#popup2 .popup-content').addClass('loading');
        $.ajax({
            url: woocommerce_params.ajax_url,
            method: 'POST',
            data: {
                action: 'add_mindbody_to_cart',
                productId: productId,
                classId: enrollmentid
            },
            dataType: 'json',
            success: function (response) {
                $('#popup2').removeClass('show');
                $('#popup2 .popup-content').removeClass('loading');
                if (response.status === 'Success') {
                    window.location.href = response.checkout_url;  // Redirect to checkout
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error: ', status, error);
                alert('An error occurred while adding the class to the cart.');
            }
        });
    });


    //if we are on mobile..
    if(typeof screen.width != "undefined" && screen.width <= 884)
    {
        $('header .book-btn.header-book-btn').on('click', function(e) {
            e.preventDefault();
            $('.mobile-toggle-menu-book-buttons').toggleClass('active');
            if($('.mobile-toggle-menu-book-buttons').hasClass('active'))
            {   
                $(this).html('Cancel');
                $(this).addClass('has-home-blue-background-color text-white');
            }
            else
            {
                $(this).removeClass('has-home-blue-background-color text-white');
                $(this).html('Book');
            }
        });
    }
});


/* Define Global Variable */
let animObserver;

/* Function to handle callback for intersection observer */
let handleObserve = (entries, animObserver) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      let elem = entry.target;
      elem.classList.add("animate");
      animObserver.unobserve(elem);
    }
  });
};

/* Initiate the observer */
function createObserver() {
  animObserver = new IntersectionObserver(handleObserve);
}

/* Add target elements to global observer */
function attachAosObservers() {
  let targets = document.querySelectorAll(".aos, body > section, #main .entry-content > .wp-block-group");
  if (targets) {
    targets.forEach((target) => {
      animObserver.observe(target);
    });
  }
}

/* On page load initiate observer and attach elements */
document.addEventListener("DOMContentLoaded", function () {
  createObserver();
  attachAosObservers();

  let teacherImageLinks = document.querySelectorAll('.teacher-column > a');
  teacherImageLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault(); // Prevent default link behavior
      let toggleBtn = e.target.closest('.teacher-column').querySelector('.teahcer-dropdown-btn');
      toggleBtn.click();
    });
});

let bookNowBtns = document.querySelectorAll('body.single-workshops a[href^="https://clients.mindbody"]');
if(bookNowBtns && bookNowBtns.length > 0)
{
    bookNowBtns.forEach((bookNowBtn) => {
        bookNowBtn.addEventListener('click', function(e){
            let workshopName = document.title;
            triggerTentativeBookingEvent(workshopName);
        });
    });
}
});

function triggerTentativeBookingEvent(workshopName)
{
    if(typeof gtag == "undefined")
    {
        return;
    }

    /**
      *   The following event is sent when the page loads. You could
      *   wrap the event in a JavaScript function so the event is
      *   sent when the user performs some action.
      */
      gtag('event', 'tentative_booking', {
        'workshop_name': workshopName,
      });
}