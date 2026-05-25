document.addEventListener('DOMContentLoaded', function() {
    if ('requestIdleCallback' in window) {
        requestIdleCallback(initNonCriticalFeatures);
    } else {
        setTimeout(initNonCriticalFeatures, 100);
    }
});

function initNonCriticalFeatures() {
    initLazyLoading();
    initAnalytics();
    initSocialSharing();
    initTooltips();
}
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}
function initAnalytics() {
    let analyticsLoaded = false;
    const loadAnalytics = () => {
        if (!analyticsLoaded) {
            analyticsLoaded = true;
        }
    };
    document.addEventListener('click', loadAnalytics, { once: true });
    document.addEventListener('scroll', loadAnalytics, { once: true });
}
function initSocialSharing() {
    const socialButtons = document.querySelectorAll('.social-share');
    if (socialButtons.length > 0) {
        const script = document.createElement('script');
        script.src = 'https://platform.twitter.com/widgets.js';
        script.async = true;
        document.head.appendChild(script);
    }
}
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = event.target.dataset.tooltip;
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - 30) + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}
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
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
        });
    });
}
function initSearch() {
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
        const debouncedSearch = debounce(function(e) {
        }, 300);
        
        searchInput.addEventListener('input', debouncedSearch);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initFormValidation();
        initSearch();
    }, 0);
});
