/*
 * Mobile Navigation JavaScript
 * File: assets/js/mobile-nav.js
 * Fungsi navigasi mobile yang konsisten untuk semua halaman
 */

// Mobile Navigation Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileNav = document.getElementById('mobileNav');
    const navOverlay = document.getElementById('navOverlay');
    const body = document.body;

    // Functions
    function toggleMobileMenu() {
        const isActive = mobileToggle.classList.contains('active');
        
        if (isActive) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }

    function openMobileMenu() {
        mobileToggle.classList.add('active');
        mobileNav.classList.add('active');
        navOverlay.classList.add('active');
        body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileToggle.classList.remove('active');
        mobileNav.classList.remove('active');
        navOverlay.classList.remove('active');
        body.style.overflow = '';
    }

    // Event Listeners
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }

    if (navOverlay) {
        navOverlay.addEventListener('click', closeMobileMenu);
    }

    // Close mobile menu when clicking on nav links
    document.querySelectorAll('.nav-links.mobile .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.getAttribute('href').startsWith('#')) {
                closeMobileMenu();
            }
        });
    });

    // Close mobile menu on window resize if desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            closeMobileMenu();
        }
    });

    // Close mobile menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileNav && mobileNav.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    // Mobile swipe gestures
    if ('ontouchstart' in window) {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;

        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;

            const deltaX = endX - startX;
            const deltaY = endY - startY;

            // Swipe right to open menu (from left edge)
            if (startX < 50 && deltaX > 100 && Math.abs(deltaY) < 100) {
                if (mobileNav && !mobileNav.classList.contains('active')) {
                    openMobileMenu();
                }
            }

            // Swipe left to close menu (when menu is open)
            if (mobileNav && mobileNav.classList.contains('active') && deltaX < -100 && Math.abs(deltaY) < 100) {
                closeMobileMenu();
            }
        }, { passive: true });
    }

    console.log('Mobile navigation initialized successfully! 📱✨');
});