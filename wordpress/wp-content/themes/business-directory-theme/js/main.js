/* Frontend Main JavaScript */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Mobile Menu Toggle
        $('.menu-toggle').on('click', function() {
            const nav = $('.main-navigation');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            nav.toggleClass('toggled');
            
            // Update button text
            if (nav.hasClass('toggled')) {
                $(this).html('<span class="menu-icon">✕</span> Close');
            } else {
                $(this).html('<span class="menu-icon">☰</span> Menu');
            }
        });

        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.site-header').length) {
                $('.main-navigation').removeClass('toggled');
                $('.menu-toggle').attr('aria-expanded', 'false')
                    .html('<span class="menu-icon">☰</span> Menu');
            }
        });

        // Close mobile menu when window is resized to desktop
        $(window).on('resize', function() {
            if ($(window).width() >= 768) {
                $('.main-navigation').removeClass('toggled');
                $('.menu-toggle').attr('aria-expanded', 'false')
                    .html('<span class="menu-icon">☰</span> Menu');
            }
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.hash);
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
                
                // Close mobile menu after clicking anchor
                $('.main-navigation').removeClass('toggled');
                $('.menu-toggle').attr('aria-expanded', 'false')
                    .html('<span class="menu-icon">☰</span> Menu');
            }
        });

        // Lazy load images
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                img.src = img.dataset.src || img.src;
            });
        } else {
            // Fallback for browsers that don't support lazy loading
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
            document.body.appendChild(script);
        }

        console.log('BDB Theme initialized - Mobile optimized');
    });

})(jQuery);
