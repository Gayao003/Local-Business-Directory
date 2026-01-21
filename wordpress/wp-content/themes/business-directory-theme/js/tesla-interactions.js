// Tesla-inspired smooth scroll and interactions
(function($) {
    'use strict';

    // Smooth scrolling for anchor links
    $('a[href*="#"]').on('click', function(e) {
        if (this.hash !== '') {
            const hash = this.hash;
            const target = $(hash);
            
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 600, 'swing');
            }
        }
    });

    // Navbar scroll effect
    let lastScroll = 0;
    const navbar = $('.tesla-nav');
    
    $(window).on('scroll', function() {
        const currentScroll = $(this).scrollTop();
        
        if (currentScroll > 100) {
            navbar.addClass('scrolled');
        } else {
            navbar.removeClass('scrolled');
        }
        
        lastScroll = currentScroll;
    });

    // Fade in elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('tesla-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    $('.tesla-card, .tesla-stat, .tesla-section-header').each(function() {
        observer.observe(this);
    });

    // Card hover effects
    $('.tesla-card').on('mouseenter', function() {
        $(this).find('.tesla-card-image').css('transform', 'scale(1.05)');
    }).on('mouseleave', function() {
        $(this).find('.tesla-card-image').css('transform', 'scale(1)');
    });

    // Button ripple effect
    $('.tesla-btn').on('click', function(e) {
        const ripple = $('<span class="ripple"></span>');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.css({
            width: size + 'px',
            height: size + 'px',
            left: x + 'px',
            top: y + 'px'
        });
        
        $(this).append(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    });

    // Parallax effect for hero images
    $(window).on('scroll', function() {
        const scroll = $(this).scrollTop();
        $('.tesla-hero-image').css('transform', 'translateY(' + scroll * 0.5 + 'px)');
    });

    // Loading animation
    $(window).on('load', function() {
        $('body').addClass('loaded');
        
        // Stagger animation for cards
        $('.tesla-card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
    });

    // Form enhancements
    $('.tesla-input, .tesla-select').on('focus', function() {
        $(this).parent().addClass('focused');
    }).on('blur', function() {
        if (!$(this).val()) {
            $(this).parent().removeClass('focused');
        }
    });

    // Number counter animation
    $('.tesla-stat-value').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.text().replace(/,/g, ''));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum).toLocaleString());
            },
            complete: function() {
                $this.text(countTo.toLocaleString());
            }
        });
    });

    // Toggle map view
    $('#toggleMapView').on('click', function() {
        const $mapContainer = $('#mapViewContainer');
        const $listContainer = $('#listViewContainer');
        
        $mapContainer.slideToggle(400);
        
        if ($mapContainer.is(':visible')) {
            $(this).html('📋 Show List');
            $('html, body').animate({
                scrollTop: $mapContainer.offset().top - 100
            }, 400);
        } else {
            $(this).html('🗺️ Show Map');
        }
    });

})(jQuery);

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .tesla-btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    body {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    body.loaded {
        opacity: 1;
    }
`;
document.head.appendChild(style);
