/**
 * Mobile Performance Optimization JavaScript
 * Tối ưu hóa JavaScript cho mobile
 */

class MobileOptimizer {
    constructor() {
        this.isMobile = this.detectMobile();
        this.init();
    }

    detectMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               window.innerWidth <= 768;
    }

    init() {
        if (this.isMobile) {
            this.optimizeImages();
            this.optimizeScrolling();
            this.optimizeTouch();
            this.optimizePerformance();
        }
    }

    // Lazy loading cho hình ảnh
    optimizeImages() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback cho browser cũ
            images.forEach(img => {
                img.src = img.dataset.src;
                img.classList.add('loaded');
            });
        }
    }

    // Tối ưu hóa scrolling
    optimizeScrolling() {
        let ticking = false;
        
        const optimizeScroll = () => {
            // Throttle scroll events
            if (!ticking) {
                requestAnimationFrame(() => {
                    // Optimize scroll performance
                    document.body.style.willChange = 'auto';
                    ticking = false;
                });
                ticking = true;
            }
        };

        window.addEventListener('scroll', optimizeScroll, { passive: true });
    }

    // Tối ưu hóa touch events
    optimizeTouch() {
        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Optimize touch feedback
        document.addEventListener('touchstart', (e) => {
            if (e.target.classList.contains('btn-base') || e.target.classList.contains('nav-link')) {
                e.target.style.transform = 'scale(0.98)';
            }
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (e.target.classList.contains('btn-base') || e.target.classList.contains('nav-link')) {
                e.target.style.transform = '';
            }
        }, { passive: true });
    }

    // Tối ưu hóa performance
    optimizePerformance() {
        // Reduce animations on mobile
        if (this.isMobile) {
            const style = document.createElement('style');
            style.textContent = `
                * {
                    animation-duration: 0.3s !important;
                    transition-duration: 0.3s !important;
                }
            `;
            document.head.appendChild(style);
        }

        // Optimize table scrolling
        const tables = document.querySelectorAll('.comp-table-responsive');
        tables.forEach(table => {
            table.style.webkitOverflowScrolling = 'touch';
        });

        // Preload critical resources
        this.preloadCriticalResources();
    }

    // Preload resources quan trọng
    preloadCriticalResources() {
        const criticalImages = document.querySelectorAll('img[data-critical]');
        criticalImages.forEach(img => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = img.dataset.src || img.src;
            document.head.appendChild(link);
        });
    }
}

// Service Worker cho caching
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Debounce function cho performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function cho scroll events
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Initialize mobile optimizer
document.addEventListener('DOMContentLoaded', () => {
    new MobileOptimizer();
});

// Optimize form inputs for mobile
document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]');
    inputs.forEach(input => {
        // Prevent zoom on focus (iOS)
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            input.addEventListener('focus', () => {
                input.style.fontSize = '16px';
            });
        }
    });
});
