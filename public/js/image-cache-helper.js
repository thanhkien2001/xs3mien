/**
 * Image Cache Helper
 * Helper functions để cache ảnh load từ JavaScript
 */

class ImageCacheHelper {
    constructor() {
        this.cache = new Map();
        this.preloadedImages = new Set();
    }

    // Cache ảnh và return Promise
    cacheImage(src) {
        if (this.cache.has(src)) {
            return Promise.resolve(this.cache.get(src));
        }

        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                this.cache.set(src, img);
                resolve(img);
            };
            img.onerror = reject;
            img.src = src;
        });
    }

    // Preload ảnh với priority cao
    preloadImage(src) {
        if (this.preloadedImages.has(src)) return Promise.resolve();

        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = src;
        link.fetchPriority = 'high';
        document.head.appendChild(link);

        this.preloadedImages.add(src);
        return this.cacheImage(src);
    }

    // Get cached image
    getCachedImage(src) {
        return this.cache.get(src);
    }

    // Batch preload images
    preloadImages(srcs) {
        return Promise.all(srcs.map(src => this.preloadImage(src)));
    }

    // Create cached image element
    createCachedImage(src, alt = '', className = '') {
        const img = document.createElement('img');
        img.alt = alt;
        img.className = className;
        
        this.cacheImage(src).then(() => {
            img.src = src;
        });

        return img;
    }

    // Update existing image with cached version
    updateImageWithCache(imgElement, src) {
        if (this.cache.has(src)) {
            imgElement.src = src;
        } else {
            this.cacheImage(src).then(() => {
                imgElement.src = src;
            });
        }
    }

    // Lazy load với cache
    lazyLoadWithCache(imgElement, src) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.updateImageWithCache(imgElement, src);
                        observer.unobserve(imgElement);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            observer.observe(imgElement);
        } else {
            // Fallback cho browser cũ
            this.updateImageWithCache(imgElement, src);
        }
    }
}

// Global instance
window.imageCacheHelper = new ImageCacheHelper();

// Helper functions cho các file JS khác
window.preloadImage = (src) => window.imageCacheHelper.preloadImage(src);
window.cacheImage = (src) => window.imageCacheHelper.cacheImage(src);
window.getCachedImage = (src) => window.imageCacheHelper.getCachedImage(src);
window.preloadImages = (srcs) => window.imageCacheHelper.preloadImages(srcs);

// Export cho module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageCacheHelper;
}
