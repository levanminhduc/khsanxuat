/**
 * Shared Header Component JavaScript
 * 
 * Handles mobile menu toggle, outside click closing, and responsive behavior.
 * No dependencies - vanilla JavaScript only.
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Header Component Controller
     */
    var HeaderComponent = {
        // DOM element references
        elements: {
            toggle: null,
            dropdown: null,
            navbar: null,
            placeholderRotators: null
        },

        // Configuration
        config: {
            mobileBreakpoint: 768,
            toggleId: 'navbar-toggle',
            dropdownId: 'navbar-dropdown',
            activeClass: 'is-active',
            openClass: 'is-open'
        },

        /**
         * Initialize the header component
         */
        init: function() {
            this.cacheElements();
            this.initSearchPlaceholderRotation();
            
            if (!this.elements.toggle || !this.elements.dropdown) {
                // Elements not found, component may not be on this page
                return;
            }

            this.bindEvents();
            this.handleResize();
        },

        /**
         * Cache DOM elements for performance
         */
        cacheElements: function() {
            this.elements.toggle = document.getElementById(this.config.toggleId);
            this.elements.dropdown = document.getElementById(this.config.dropdownId);
            this.elements.navbar = document.querySelector('.header-component .navbar');
            this.elements.placeholderRotators = document.querySelectorAll('.header-component [data-placeholder-rotator]');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Toggle button click
            this.elements.toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleMenu();
            });

            // Toggle button touch event for mobile
            this.elements.toggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleMenu();
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                self.handleOutsideClick(e);
            });

            // Close when touching outside on mobile
            document.addEventListener('touchstart', function(e) {
                self.handleOutsideClick(e);
            });

            // Close on window resize above breakpoint
            window.addEventListener('resize', function() {
                self.handleResize();
            });

            // Handle escape key to close menu
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.isMenuOpen()) {
                    self.closeMenu();
                    self.elements.toggle.focus();
                }
            });

            // Prevent dropdown content clicks from closing menu
            this.elements.dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Add ripple effect on touch for nav items
            this.addTouchEffects();
        },

        /**
         * Toggle the mobile menu open/closed
         */
        toggleMenu: function() {
            if (this.isMenuOpen()) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        },

        /**
         * Open the mobile menu
         */
        openMenu: function() {
            this.elements.toggle.classList.add(this.config.activeClass);
            this.elements.dropdown.classList.add(this.config.openClass);
            this.elements.toggle.setAttribute('aria-expanded', 'true');
            
            // Focus first focusable element in dropdown
            var firstFocusable = this.elements.dropdown.querySelector('a, button, input, select');
            if (firstFocusable) {
                setTimeout(function() {
                    firstFocusable.focus();
                }, 100);
            }
        },

        /**
         * Close the mobile menu
         */
        closeMenu: function() {
            this.elements.toggle.classList.remove(this.config.activeClass);
            this.elements.dropdown.classList.remove(this.config.openClass);
            this.elements.toggle.setAttribute('aria-expanded', 'false');
        },

        /**
         * Check if menu is currently open
         * @returns {boolean}
         */
        isMenuOpen: function() {
            return this.elements.dropdown.classList.contains(this.config.openClass);
        },

        /**
         * Handle clicks outside the menu
         * @param {Event} e - The click/touch event
         */
        handleOutsideClick: function(e) {
            if (!this.isMenuOpen()) {
                return;
            }

            var isClickInsideDropdown = this.elements.dropdown.contains(e.target);
            var isClickOnToggle = this.elements.toggle.contains(e.target);

            if (!isClickInsideDropdown && !isClickOnToggle) {
                this.closeMenu();
            }
        },

        /**
         * Handle window resize events
         */
        handleResize: function() {
            var windowWidth = window.innerWidth;

            // Close menu when resizing above mobile breakpoint
            if (windowWidth >= this.config.mobileBreakpoint && this.isMenuOpen()) {
                this.closeMenu();
            }
        },

        /**
         * Add touch effects for mobile interactions
         */
        addTouchEffects: function() {
            var navItems = document.querySelectorAll('.header-component .mobile-nav-item, .header-component .action-btn');
            
            navItems.forEach(function(item) {
                item.addEventListener('touchstart', function() {
                    this.style.opacity = '0.7';
                });

                item.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });

                item.addEventListener('touchcancel', function() {
                    this.style.opacity = '1';
                });
            });
        },

        /**
         * Rotate search hint text when configured by the page.
         */
        initSearchPlaceholderRotation: function() {
            var rotators = this.elements.placeholderRotators;

            if (!rotators || !rotators.length) {
                return;
            }

            rotators.forEach(function(rotator) {
                var input = rotator.querySelector('input[type="text"]');
                var text = rotator.querySelector('.search-placeholder-text');
                var phrases = [];
                var phraseIndex = 0;
                var timerId = null;

                if (!input || !text) {
                    return;
                }

                try {
                    phrases = JSON.parse(rotator.getAttribute('data-placeholder-phrases') || '[]');
                } catch (error) {
                    phrases = [];
                }

                phrases = phrases.filter(function(phrase) {
                    return String(phrase).trim() !== '';
                });

                if (!phrases.length) {
                    return;
                }

                function syncValueState() {
                    rotator.classList.toggle('has-value', input.value.length > 0);
                }

                function setPhrase(nextPhrase) {
                    text.textContent = nextPhrase;
                    rotator.classList.remove('is-rotating');
                    void rotator.offsetWidth;
                    rotator.classList.add('is-rotating');

                    window.setTimeout(function() {
                        rotator.classList.remove('is-rotating');
                    }, 320);
                }

                function rotatePhrase() {
                    if (input.value.length > 0 || phrases.length < 2) {
                        return;
                    }

                    phraseIndex = (phraseIndex + 1) % phrases.length;
                    setPhrase(phrases[phraseIndex]);
                }

                function startRotation() {
                    if (timerId || phrases.length < 2) {
                        return;
                    }

                    timerId = window.setInterval(rotatePhrase, 2200);
                }

                function stopRotation() {
                    if (!timerId) {
                        return;
                    }

                    window.clearInterval(timerId);
                    timerId = null;
                }

                text.textContent = phrases[0];
                syncValueState();
                startRotation();

                input.addEventListener('input', syncValueState);

                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        stopRotation();
                    } else {
                        startRotation();
                    }
                });
            });
        }
    };

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            HeaderComponent.init();
        });
    } else {
        // DOM is already ready
        HeaderComponent.init();
    }

    // Expose for debugging purposes (optional)
    window.HeaderComponent = HeaderComponent;

})();
