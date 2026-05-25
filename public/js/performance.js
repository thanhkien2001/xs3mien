/**
 * Performance Optimization Script
 * Improves Core Web Vitals and overall performance
 */

(function () {
    'use strict';

    // Performance monitoring
    const performanceObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
            if (entry.entryType === 'largest-contentful-paint') {
                console.log('LCP:', entry.startTime);
                // Send to analytics if needed
            }
            if (entry.entryType === 'first-input') {
                console.log('FID:', entry.processingStart - entry.startTime);
                // Send to analytics if needed
            }
        }
    });

    try {
        performanceObserver.observe({ entryTypes: ['largest-contentful-paint', 'first-input'] });
    } catch (e) {
        // Fallback for older browsers
    }

    // Lazy load images with Intersection Observer
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('util-lazy-load');
                            img.classList.add('util-loaded');
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.classList.remove('util-lazy-load');
                img.classList.add('util-loaded');
                img.removeAttribute('data-src');
            });
        }
    }

    // Preload critical resources
    function preloadCriticalResources() {
        const criticalResources = [
            '/img/logo-xosomientrung-white.png'
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = 'image';
            document.head.appendChild(link);
        });
    }

    // Defer non-critical JavaScript
    function loadDeferredScripts() {
        const deferredScripts = [
            'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
            'https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js',
            '/js/menu.js',
        ];

        deferredScripts.forEach(src => {
            const script = document.createElement('script');
            script.src = src;
            script.defer = true;
            document.body.appendChild(script);
        });
    }

    // Optimize table rendering for large datasets
    function optimizeTableRendering() {
        const tables = document.querySelectorAll('.lottery-table');
        tables.forEach(table => {
            if (table.rows.length > 20) {
                // Add virtual scrolling for large tables
                table.classList.add('virtual-scroll');
            }
        });
    }

    // Service Worker registration with performance optimization
    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            // Delay registration to improve initial page load
            setTimeout(() => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered:', registration);
                    })
                    .catch(error => {
                        console.log('SW registration failed:', error);
                    });
            }, 5000); // Delay by 5 seconds
        }
    }

    // Resource hints for external domains
    function addResourceHints() {
        const hints = [
            { rel: 'dns-prefetch', href: 'https://cdnjs.cloudflare.com' },
            { rel: 'dns-prefetch', href: 'https://cdn.jsdelivr.net' },
            { rel: 'preconnect', href: 'https://cdnjs.cloudflare.com', crossorigin: true },
            { rel: 'preconnect', href: 'https://cdn.jsdelivr.net', crossorigin: true }
        ];

        hints.forEach(hint => {
            const link = document.createElement('link');
            Object.assign(link, hint);
            document.head.appendChild(link);
        });
    }

    // Optimize form interactions
    function optimizeForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Add debouncing to form inputs
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                let timeout;
                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        // Handle input changes
                    }, 300);
                });
            });
        });
    }

    // Initialize performance optimizations
    function init() {
        // Run immediately with requestIdleCallback
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
                preloadCriticalResources();
                addResourceHints();
            });
        } else {
            // Fallback for older browsers
            setTimeout(() => {
                preloadCriticalResources();
                addResourceHints();
            }, 100);
        }

        // Run after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                requestIdleCallback(() => {
                    initLazyLoading();
                    optimizeTableRendering();
                    optimizeForms();
                });
            });
        } else {
            requestIdleCallback(() => {
                initLazyLoading();
                optimizeTableRendering();
                optimizeForms();
            });
        }

        // Run after page load
        window.addEventListener('load', () => {
            requestIdleCallback(() => {
                loadDeferredScripts();
                registerServiceWorker();
            });
        });
    }

    // Start optimizations
    init();

})();
