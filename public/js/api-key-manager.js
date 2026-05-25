class ApiKeyManager {
    constructor() {
        this.apiKeys = {};
        this.secretToken = 'xskt_phalcon_secret_token_2024_secure';
        this.cacheTime = 5 * 60 * 1000; // 5 minutes
        this.lastFetch = 0;
        this.baseUrl = '/vietlott-live/get-api-keys.html';
    }

    async getApiKey(endpoint) {
        if (this.isCacheValid() && this.apiKeys[endpoint]) {
            return this.apiKeys[endpoint];
        }
        await this.fetchApiKeys();
        return this.apiKeys[endpoint] || null;
    }

    isCacheValid() {
        return (Date.now() - this.lastFetch) < this.cacheTime;
    }

    async fetchApiKeys() {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Referer': window.location.href
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            this.apiKeys = {
                mega645: data.mega645,
                power655: data.power655,
                max3d: data.max3d,
                max3dpro: data.max3dpro
            };
            this.lastFetch = Date.now();
            
            console.log('API Keys updated:', {
                timestamp: data.timestamp,
                date: data.date,
                keys: Object.keys(this.apiKeys)
            });
        } catch (error) {
            console.error('Failed to fetch API keys:', error);
            // Fallback keys - sẽ được generate từ server
            this.apiKeys = {
                mega645: 'mega645_secure_0c928769e8f0a037d8dfe9c64d2fc6f64da2c45e07b472e83e34e86033da1e70',
                power655: 'power655_secure_7d3c03d554c0f5177109af010330b1a406907377e00d47ab59de04f2491a9f7a',
                max3d: 'max3d_secure_4e224c59ac2b8747a1d59a0d3026ea8907af29cf065921b408ffae8a6d024451',
                max3dpro: 'max3dpro_secure_1876f9141a2e7408b55020e043bcd7b792f45d7682b98907e376f39a43274d82'
            };
        }
    }

    async buildApiUrl(endpoint, params = {}) {
        const apiKey = await this.getApiKey(endpoint);
        if (!apiKey) {
            throw new Error(`No API key available for ${endpoint}`);
        }

        const baseUrl = `/vietlott-live/check-${endpoint}.html`;
        const queryParams = new URLSearchParams({
            ...params,
            api_key: apiKey
        });

        return `${baseUrl}?${queryParams.toString()}`;
    }

    async callApi(endpoint, params = {}) {
        try {
            const url = await this.buildApiUrl(endpoint, params);
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Referer': window.location.href
                    // Tạm thời không gửi X-Secret-Token
                    // 'X-Secret-Token': this.secretToken
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    // Refresh API keys and retry
                    this.lastFetch = 0;
                    const newUrl = await this.buildApiUrl(endpoint, params);
                    const newResponse = await fetch(newUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Referer': window.location.href
                            // 'X-Secret-Token': this.secretToken
                        }
                    });
                    return await newResponse.json();
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error(`API call failed for ${endpoint}:`, error);
            throw error;
        }
    }

    async refreshApiKeys() {
        this.lastFetch = 0;
        await this.fetchApiKeys();
    }
}

// Initialize global instance
window.apiKeyManager = new ApiKeyManager();

// Export for Node.js if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiKeyManager;
}