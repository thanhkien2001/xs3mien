class QuayThuDuDoan {
    constructor() {
        this.apiUrl = '/api/quaythu-dudoan.php';
        this.slug = this.getSlugFromUrl();
        this.cachePrefix = 'quaythu_cache_';
        this.cacheTTL = 100 * 60 * 1000; // 100 phút cache
        this.quayThuElements = null; // Cache DOM elements
        this.init();
    }
    getSlugFromUrl() {
        const path = window.location.pathname;
        
        // Try new format: /du-doan-{slug}.html or /du-doan-{slug}
        let matches = path.match(/\/du-doan-([^\/]+)\.html/);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        // Try without .html extension
        matches = path.match(/\/du-doan-([^\/]+)$/);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        // Try old format: /du-doan-xo-so-soi-cau-[^\/]+\/([^\/]+)\.html
        matches = path.match(/\/du-doan-xo-so-soi-cau-[^\/]+\/([^\/]+)\.html/);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        return null;
    }

    init() {
        if (!this.slug) {
            return;
        }
        this.autoGenerateResults();
    }

    /**
     * Lưu kết quả vào server
     */
    async saveResults(results) {
        try {
            // Try with slug as-is first
            let response = await fetch(`${this.apiUrl}?action=save&slug=${encodeURIComponent(this.slug)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    results: results
                })
            });
            
            let data = await response.json();
            if (data.success && data.results) {
                return data.results;
            }
            
            // If failed, try with 'du-doan-' prefix
            const fullSlug = 'du-doan-' + this.slug;
            response = await fetch(`${this.apiUrl}?action=save&slug=${encodeURIComponent(fullSlug)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    results: results
                })
            });
            
            data = await response.json();
            if (data.success && data.results) {
                return data.results;
            }
            
            return results;
        } catch (error) {
            return results;
        }
    }

    /**
     * Lấy cache từ localStorage
     */
    getCacheKey() {
        return `${this.cachePrefix}${this.slug}`;
    }

    /**
     * Lưu cache vào localStorage
     */
    saveToCache(data) {
        try {
            const cacheData = {
                data: data,
                timestamp: Date.now(),
                slug: this.slug
            };
            localStorage.setItem(this.getCacheKey(), JSON.stringify(cacheData));
        } catch (error) {
            // localStorage có thể bị đầy hoặc bị disable
        }
    }

    /**
     * Lấy cache từ localStorage
     */
    getFromCache() {
        try {
            const cacheKey = this.getCacheKey();
            const cached = localStorage.getItem(cacheKey);
            
            if (!cached) return null;
            
            const cacheData = JSON.parse(cached);
            
            // Kiểm tra TTL và slug
            if (cacheData.slug !== this.slug) {
                localStorage.removeItem(cacheKey);
                return null;
            }
            
            const isExpired = (Date.now() - cacheData.timestamp) > this.cacheTTL;
            if (isExpired) {
                localStorage.removeItem(cacheKey);
                return null;
            }
            
            return cacheData.data;
        } catch (error) {
            return null;
        }
    }

    /**
     * Clear cache cho slug hiện tại
     */
    clearCache() {
        try {
            const cacheKey = this.getCacheKey();
            localStorage.removeItem(cacheKey);
        } catch (error) {
            // Ignore errors
        }
    }

    /**
     * Clear tất cả cache quay thử
     */
    clearAllCache() {
        try {
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith(this.cachePrefix)) {
                    localStorage.removeItem(key);
                }
            });
        } catch (error) {
            // Ignore errors
        }
    }

    /**
     * Debug cache info
     */
    getCacheInfo() {
        try {
            const cacheKey = this.getCacheKey();
            const cached = localStorage.getItem(cacheKey);
            
            if (!cached) {
                return { exists: false, message: 'No cache found' };
            }
            
            const cacheData = JSON.parse(cached);
            const age = Date.now() - cacheData.timestamp;
            const isExpired = age > this.cacheTTL;
            
            return {
                exists: true,
                slug: cacheData.slug,
                timestamp: new Date(cacheData.timestamp).toLocaleString(),
                age: Math.round(age / 1000) + 's',
                ttl: Math.round(this.cacheTTL / 1000) + 's',
                isExpired: isExpired,
                dataKeys: Object.keys(cacheData.data || {})
            };
        } catch (error) {
            return { exists: false, message: 'Error reading cache: ' + error.message };
        }
    }

    /**
     * Lấy kết quả từ server
     */
    async getStoredResults() {
        // Kiểm tra cache trước với slug
        const cached = this.getFromCache();
        if (cached) {
            return cached;
        }
        
        // Try with slug, then try with prefix if needed
        let responseSlug = this.slug;
        
        try {
            // First try with the slug as-is
            let response = await fetch(`${this.apiUrl}?action=get&slug=${encodeURIComponent(responseSlug)}`);
            let data = await response.json();
            
            if (data.success && data.results && Object.keys(data.results).length > 0) {
                // Lưu vào cache
                this.saveToCache(data.results);
                return data.results;
            }
            
            // If failed, try with 'du-doan-' prefix
            responseSlug = 'du-doan-' + this.slug;
            response = await fetch(`${this.apiUrl}?action=get&slug=${encodeURIComponent(responseSlug)}`);
            data = await response.json();
            
            if (data.success && data.results && Object.keys(data.results).length > 0) {
                // Lưu vào cache
                this.saveToCache(data.results);
                return data.results;
            }
            
            return {};
        } catch (error) {
            console.error('Error fetching quaythu results:', error);
            return {};
        }
    }

    /**
     * Lấy DOM elements (có cache)
     */
    getQuayThuElements() {
        if (!this.quayThuElements) {
            this.quayThuElements = document.querySelectorAll('.js-quaythu-result');
        }
        return this.quayThuElements;
    }

    /**
     * Tự động tạo kết quả cho tất cả bảng quay thử
     */
    async autoGenerateResults() {
        let stored = await this.getStoredResults();
        let hasNewResults = false;
        
        const quayThuElements = this.getQuayThuElements();
        
        quayThuElements.forEach(element => {
            const id = element.id;
            const province = element.getAttribute('data-province');
            const region = element.getAttribute('data-region');
            const provinceCount = element.getAttribute('data-province-count');
            
            let key = id.replace('quaythu-', '');
            let name = province || region || 'XSMB';
            if (provinceCount && parseInt(provinceCount) > 1) {
                const newMultiResults = this.generateMultiProvinceResults(element, stored, key);
                if (newMultiResults) hasNewResults = true;
            } else {
                if (!stored[key]) {
                    const result = this.generateLotteryResult(name);
                    stored[key] = result;
                    hasNewResults = true;
                }
                this.displaySingleResult(element, stored[key]);
            }
        });
        
        // Chỉ save khi có kết quả mới
        if (hasNewResults) {
            const finalResults = await this.saveResults(stored);
            
            // Cập nhật cache với kết quả cuối cùng
            if (finalResults && Object.keys(finalResults).length > 0) {
                this.saveToCache(finalResults);
                
                const isDifferent = JSON.stringify(stored) !== JSON.stringify(finalResults);
                
                if (isDifferent) {
                    quayThuElements.forEach(element => {
                        const id = element.id;
                        const provinceCount = element.getAttribute('data-province-count');
                        let key = id.replace('quaythu-', '');
                        
                        if (provinceCount && parseInt(provinceCount) > 1) {
                            const provinceHeaders = element.querySelectorAll('thead th[data-province]');
                            provinceHeaders.forEach(header => {
                                const provinceName = header.getAttribute('data-province');
                                const provinceKey = `${key}-${this.getProvinceKey(provinceName)}`;
                                
                                if (finalResults[provinceKey]) {
                                    this.displayMultiProvinceResult(element, provinceName, finalResults[provinceKey]);
                                }
                            });
                        } else {
                            if (finalResults[key]) {
                                this.displaySingleResult(element, finalResults[key]);
                            }
                        }
                    });
                }
            }
        }
    }

    /**
     * Hiển thị kết quả cho trường hợp 1 tỉnh hoặc XSMB
     */
    displaySingleResult(container, result) {
        const spansByGiai = {};
        const allSpans = container.querySelectorAll('span[data-id-giai]');
        
        allSpans.forEach(span => {
            const giai = span.getAttribute('data-id-giai');
            if (!spansByGiai[giai]) {
                spansByGiai[giai] = [];
            }
            spansByGiai[giai].push(span);
        });
        
        if (spansByGiai['1']) this.fillSpans(spansByGiai['1'], [result.special_prize]);
        if (spansByGiai['2']) this.fillSpans(spansByGiai['2'], [result.first_prize]);
        if (spansByGiai['3']) this.fillSpans(spansByGiai['3'], result.second_prize);
        if (spansByGiai['4']) this.fillSpans(spansByGiai['4'], result.third_prize);
        if (spansByGiai['5']) this.fillSpans(spansByGiai['5'], result.fourth_prize);
        if (spansByGiai['6']) this.fillSpans(spansByGiai['6'], result.fifth_prize);
        if (spansByGiai['7']) this.fillSpans(spansByGiai['7'], result.sixth_prize);
        if (spansByGiai['8']) this.fillSpans(spansByGiai['8'], result.seventh_prize);
        if (spansByGiai['9']) this.fillSpans(spansByGiai['9'], result.eighth_prize || [result.special_prize]);
        this.generateLotoTable(container, result);
    }

    /**
     * Tạo bảng lô tô từ kết quả
     */
    generateLotoTable(container, result) {
        const lotoTable = container.querySelector('.loto-table');
        
        if (!lotoTable) {
            return;
        }
        const allNumbers = [];
        
        const addTwoDigits = (numbers) => {
            if (!numbers) return;
            const arr = Array.isArray(numbers) ? numbers : [numbers];
            arr.forEach(num => {
                if (num) {
                    const str = String(num);
                    const last2 = str.slice(-2);
                    if (last2.length === 2) {
                        allNumbers.push(last2);
                    }
                }
            });
        };
        
        addTwoDigits(result.special_prize);
        addTwoDigits(result.first_prize);
        addTwoDigits(result.second_prize);
        addTwoDigits(result.third_prize);
        addTwoDigits(result.fourth_prize);
        addTwoDigits(result.fifth_prize);
        addTwoDigits(result.sixth_prize);
        addTwoDigits(result.seventh_prize);
        addTwoDigits(result.eighth_prize);
        
        const lotoByHead = {};  
        const lotoByTail = {};  
        
        for (let i = 0; i <= 9; i++) {
            lotoByHead[i] = [];
            lotoByTail[i] = [];
        }
        
        allNumbers.forEach(num => {
            const head = parseInt(num[0]);  
            const tail = parseInt(num[1]);  
            
            if (!isNaN(head) && head >= 0 && head <= 9) {
                lotoByHead[head].push(num);
            }
            
            if (!isNaN(tail) && tail >= 0 && tail <= 9) {
                lotoByTail[tail].push(num);
            }
        });
        
        for (let i = 0; i <= 9; i++) {
            lotoByHead[i] = [...new Set(lotoByHead[i])].sort();
            lotoByTail[i] = [...new Set(lotoByTail[i])].sort();
        }
        
        for (let num = 0; num <= 9; num++) {
            const dauCell = lotoTable.querySelector(`td[data-find="dau"][data-num="${num}"]`);
            if (dauCell) {
                const numbers = lotoByHead[num];
                if (numbers.length > 0) {
                    dauCell.innerHTML = numbers.map(n => 
                        `<span class="number">${n}</span>`
                    ).join(', ');
                } else {
                    dauCell.innerHTML = '';
                }
            }
            
            const duoiCell = lotoTable.querySelector(`td[data-find="duoi"][data-num="${num}"]`);
            if (duoiCell) {
                const numbers = lotoByTail[num];
                if (numbers.length > 0) {
                    duoiCell.innerHTML = numbers.map(n => 
                        `<span class="number">${n}</span>`
                    ).join(', ');
                } else {
                    duoiCell.innerHTML = '';
                }
            }
        }
    }

    /**
     * Tạo kết quả cho nhiều tỉnh
     */
    generateMultiProvinceResults(element, stored, baseKey) {
        const provinceHeaders = element.querySelectorAll('thead th[data-province]');
        let hasNewResults = false;
        
        provinceHeaders.forEach(header => {
            const provinceName = header.getAttribute('data-province');
            const provinceKey = `${baseKey}-${this.getProvinceKey(provinceName)}`;
            
            if (!stored[provinceKey]) {
                stored[provinceKey] = this.generateLotteryResult(provinceName);
                hasNewResults = true;
            }
            
            this.displayMultiProvinceResult(element, provinceName, stored[provinceKey]);
        });
        
        return hasNewResults;
    }

    /**
     * Hiển thị kết quả cho 1 tỉnh trong bảng nhiều tỉnh
     */
    displayMultiProvinceResult(container, provinceName, result) {
        const allSpans = container.querySelectorAll(`span[data-province="${provinceName}"]`);
        const spansByGiai = {};
        allSpans.forEach(span => {
            const giai = span.getAttribute('data-id-giai');
            if (!spansByGiai[giai]) {
                spansByGiai[giai] = [];
            }
            spansByGiai[giai].push(span);
        });
        
        if (spansByGiai['1']) this.fillSpans(spansByGiai['1'], [result.special_prize]);
        if (spansByGiai['2']) this.fillSpans(spansByGiai['2'], [result.first_prize]);
        if (spansByGiai['3']) this.fillSpans(spansByGiai['3'], result.second_prize);
        if (spansByGiai['4']) this.fillSpans(spansByGiai['4'], result.third_prize);
        if (spansByGiai['5']) this.fillSpans(spansByGiai['5'], result.fourth_prize);
        if (spansByGiai['6']) this.fillSpans(spansByGiai['6'], result.fifth_prize);
        if (spansByGiai['7']) this.fillSpans(spansByGiai['7'], result.sixth_prize);
        if (spansByGiai['8']) this.fillSpans(spansByGiai['8'], result.seventh_prize);
        if (spansByGiai['9']) this.fillSpans(spansByGiai['9'], result.eighth_prize || [result.special_prize]);
    }

    /**
     * Fill số vào các span
     */
    fillSpans(spans, numbers) {
        spans.forEach((span, index) => {
            if (numbers[index]) {
                span.textContent = numbers[index];
                span.setAttribute('data-num', numbers[index]);
            }
        });
    }


    /**
     * Tạo key cho tỉnh (giữ nguyên tiếng Việt có dấu)
     */
    getProvinceKey(province) {
        return province.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\u00C0-\u1EF9a-z0-9-]/g, ''); 
    }

    generateLotteryResult(provinceName) {
        const isXsmb = provinceName === 'XSMB';
        
        if (isXsmb) {
            return {
                special_prize: this.generateNumber(5),
                first_prize: this.generateNumber(5),
                second_prize: [this.generateNumber(5), this.generateNumber(5)],
                third_prize: [
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5),
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5)
                ],
                fourth_prize: [
                    this.generateNumber(5), this.generateNumber(5), 
                    this.generateNumber(5), this.generateNumber(5)
                ],
                fifth_prize: [
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5),
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5)
                ],
                sixth_prize: [
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5)
                ],
                seventh_prize: [
                    this.generateNumber(5), this.generateNumber(5), 
                    this.generateNumber(5), this.generateNumber(5)
                ],
                eighth_prize: null
            };
        } else {
            // XSMN/XSMT    
            return {
                special_prize: this.generateNumber(6),
                first_prize: this.generateNumber(5),
                second_prize: [this.generateNumber(5)],
                third_prize: [this.generateNumber(5), this.generateNumber(5)],
                fourth_prize: [
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5),
                    this.generateNumber(5), this.generateNumber(5), this.generateNumber(5),
                    this.generateNumber(5)
                ],
                fifth_prize: [this.generateNumber(4)],
                sixth_prize: [
                    this.generateNumber(4), this.generateNumber(4), this.generateNumber(4)
                ],
                seventh_prize: [this.generateNumber(3)],
                eighth_prize: [this.generateNumber(2)]
            };
        }
    }
    generateNumber(length) {
        let result = '';
        for (let i = 0; i < length; i++) {
            result += Math.floor(Math.random() * 10);
        }
        return result;
    }

}
document.addEventListener('DOMContentLoaded', function() {
    window.quayThuInstance = new QuayThuDuDoan();
    
    // Debug methods
    window.clearQuayThuCache = () => {
        if (window.quayThuInstance) {
            window.quayThuInstance.clearCache();
        }
    };
    
    window.getQuayThuCacheInfo = () => {
        if (window.quayThuInstance) {
            const info = window.quayThuInstance.getCacheInfo();
            console.table(info);
            return info;
        }
    };
    
    window.clearAllQuayThuCache = () => {
        if (window.quayThuInstance) {
            window.quayThuInstance.clearAllCache();
        }
    };
});


