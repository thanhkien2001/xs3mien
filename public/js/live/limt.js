const SCHEDULE = {
    start: {
        hour: 17,
        minute: 15
    },
    end: {
        hour: 18,
        minute: 0
    }
};

const API_CSRF = '/lottery/csrf';
const API_URL = '/get-token';
const isLocal = window.location.hostname.includes('xskt_phalcon.code') || 
               window.location.hostname.includes('localhost') || 
               window.location.hostname.includes('127.0.0.1');

const WS_BASE_URL = isLocal 
    ? "wss://xskt_phalcon.code:8443/ws"
    : "wss://soicau247.com/ws";

const provinces = window.appProvinces || {}; 
if (Object.keys(provinces).length === 0) {
  console.warn(
    "Provinces data is empty or not found. Ensure window.appProvinces is set in the HTML."
  );
}

const prizeMapping = {
    '0': '1',
    '1': '2',
    '2': '3',
    '3': '4',
    '4': '5',
    '5': '6',
    '6': '7',
    '7': '8',
    '8': '9'
};

const table = document.querySelector('.js-kq-table');
const dauDuoiCells = document.querySelectorAll('.js-number-dau-duoi');

let ws = null,
    reconnectTimer = null,
    isConnecting = false,
    ticking = null,
    endTimer = null,
    randomIntervals = {}, // Store intervals for random number animation
    randomTimers = {}; // Store animation timers

const urlParams = new URLSearchParams(window.location.search);
const region = urlParams.get('region') || 'mt';

const CSRF_COOKIE = 'ws_csrf';
const TOKEN_COOKIE = 'ws_token';

const liveBanner = document.querySelector('.live-banner');
const liveDot = liveBanner ? liveBanner.querySelector('.live-dot') : null;
const textLive = liveBanner ? liveBanner.querySelector('.text-live') : null;

function resetBannerToWaiting(targetDate) {
    if (!liveBanner) return;
    if (liveDot) {
        liveDot.textContent = '⏳ Đang chờ';
    }
    if (textLive) {
        textLive.innerHTML = `
        Kết quả xổ số Miền Trung sẽ được trực tiếp vào lúc 
        <strong>17h15</strong> ngày ${targetDate.getDate()}/${targetDate.getMonth()+1}/${targetDate.getFullYear()}.
        Vui lòng quay lại sau!
    `;
    }
}

function updateBannerToLive() {
    if (!liveBanner) return;
    if (liveDot) {
        liveDot.textContent = '🔴 LIVE';
    }
    if (textLive) {
        textLive.innerHTML = `
        Cập nhật kết quả xổ số Miền Trung (XSMT) trực tiếp vào lúc <strong>17h15</strong> hôm nay ${new Date().toLocaleDateString('vi-VN')},
        nhanh chóng – chính xác – uy tín, được công bố từ hội đồng xổ số kiến thiết Miền Trung.
    `;
    }
}

function updateBannerToCompleted() {
    if (!liveBanner) return;
    if (liveDot) {
        liveDot.textContent = '✅ Kết quả đã cập nhật'
    }
    if (textLive) {
        textLive.innerHTML = `
        Kết quả xổ số Miền Trung ngày ${new Date().toLocaleDateString('vi-VN')} đã được cập nhật đầy đủ.
        Cảm ơn bạn đã theo dõi! <a href="/xsmt-xo-so-kien-thiet-mien-trung-sxmt-kqxsmt.html" title="Kết quả xổ số Miền Trung">Xem kết quả XSMT</a>
    `;
    }
}


function cookieGet(name) {
    const raw = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(name + '='));
    return raw ? decodeURIComponent(raw.split('=').slice(1).join('=')) : null;
}

function isWsOpen() {
    return ws && ws.readyState === WebSocket.OPEN;
}

function getWindowFor(date) {
    const start = new Date(date);
    start.setHours(SCHEDULE.start.hour, SCHEDULE.start.minute, 0, 0);
    const end = new Date(date);
    end.setHours(SCHEDULE.end.hour, SCHEDULE.end.minute, 0, 0);
    if (end <= start) end.setDate(end.getDate() + 1);
    return {
        start,
        end
    };
}

function msUntil(t) {
    return t - new Date();
}

function withinWindow(now, win) {
    return now >= win.start && now < win.end;
}

// === RANDOM NUMBER HELPERS ===
function generateRandomNumber(digits) {
    const min = Math.pow(10, digits - 1);
    const max = Math.pow(10, digits) - 1;
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function startRandomAnimation(element, digits) {
    const key = element.dataset.randomKey || Math.random().toString(36);
    element.dataset.randomKey = key;
    
    // Clear existing animation if any
    if (randomTimers[key]) {
        randomTimers[key].stop = true;
    }
    
    // Dùng requestAnimationFrame như trong qtmb.js - mượt mà hơn
    const timer = { stop: false };
    randomTimers[key] = timer;
    
    function tick() {
        if (timer.stop) return;
        
        // Cập nhật số mới
        element.textContent = generateRandomNumber(digits);
        
        // Thêm animation class
        element.classList.remove('number-changing');
        void element.offsetWidth; // Force reflow
        element.classList.add('number-changing');
        
        // Tiếp tục animation sau 300ms
        setTimeout(() => {
            if (!timer.stop) {
                requestAnimationFrame(tick);
            }
        }, 300); // Delay giữa mỗi lần đổi số - smooth và dễ nhìn
    }
    
    requestAnimationFrame(tick);
}

function stopRandomAnimation(element) {
    const key = element.dataset.randomKey;
    if (key && randomTimers[key]) {
        randomTimers[key].stop = true;
        delete randomTimers[key];
    }
    element.classList.remove('number-changing');
}

function clearAllRandomAnimations() {
    Object.keys(randomTimers).forEach(key => {
        if (randomTimers[key]) {
            randomTimers[key].stop = true;
        }
    });
    randomTimers = {};
}

function handleMessage(event) {
    const data = JSON.parse(event.data);
    if (data.error) return;

    const provinceIds = Object.keys(data.results || {});
    const provinceIdMapping = {};
    provinceIds.forEach(id => {
        if (provinces[id]) provinceIdMapping[id] = id;
    });

    for (const [provinceId, results] of Object.entries(data.results || {})) {
        const mappedProvinceId = provinceIdMapping[provinceId] || null;
        if (!mappedProvinceId) continue;

        for (const [prizeKey, numbers] of Object.entries(results || {})) {
            const mappedPrize = prizeMapping[prizeKey];
            if (!mappedPrize) continue;

            const cell = table.querySelector(
                `span[data-page-id="${mappedProvinceId}"][data-id-giai="${mappedPrize}"]`
            );
            if (!cell) continue;

            // Chuyển numbers thành array và serialize để so sánh
            const numbersArray = Array.isArray(numbers) ? numbers : [numbers];
            const newDataStr = JSON.stringify(numbersArray);
            const oldDataStr = cell.getAttribute('data-current');
            
            // Chỉ update nếu dữ liệu thay đổi
            if (oldDataStr !== newDataStr) {
                // Dừng tất cả animations cũ trước khi clear
                const oldRandomElements = cell.querySelectorAll('.number-random');
                oldRandomElements.forEach(el => stopRandomAnimation(el));
                
                // Lưu dữ liệu mới
                cell.setAttribute('data-current', newDataStr);
                
                // Clear và tạo lại
                cell.innerHTML = '';
                cell.style.display = 'flex';
                cell.style.flexDirection = 'column';
                cell.style.alignItems = 'flex-end';
                
                numbersArray.forEach((val, idx) => {
                    const s = document.createElement('span');
                    s.style.margin = '5px';    
                    if (typeof val === 'string' && val.includes('*')) {
                        // Có dấu * = giữ spinner loading
                        s.className = 'spinner-border spinner-border-sm text-warning fs-6';
                        s.setAttribute('role', 'status');
                    } else if (typeof val === 'string' && val.includes('+')) {
                        // Có dấu + = số random chạy
                        s.className = 'number number-random';
                        const digits = mappedPrize === '1' ? 5 : (mappedPrize === '9' ? 2 : 5);
                        s.textContent = generateRandomNumber(digits);
                        startRandomAnimation(s, digits);
                    } else {
                        // Số thực
                        s.className = 'number';
                        s.textContent = val;
                    }
                    cell.appendChild(s);
                });
            }
        }
    }

    dauDuoiCells.forEach(cell => {
        const htmlProvinceId = cell.getAttribute('data-page-id');
        const head = cell.getAttribute('data-num');
        const numbers = [];
        if (!data.results || !data.results[htmlProvinceId]) {
            cell.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        for (const [_prizeKey, result] of Object.entries(data.results[htmlProvinceId])) {
            const pushIfMatch = num => {
                const s = num.toString();
                if (s.length >= 2 && s.charAt(s.length - 2) === head) numbers.push(s.slice(-2));
            };
            if (Array.isArray(result)) result.forEach(pushIfMatch);
            else pushIfMatch(result);
        }
        const unique = [...new Set(numbers)].sort();
        cell.textContent = unique.length ? unique.join(', ') : '-';
    });
}

function handleError(e) {
    // console.error('WebSocket error', e); 
}

function handleClose(e) {
    isConnecting = false;
    const now = new Date();
    const win = getWindowFor(now);
    if (!withinWindow(now, win)) {
        updateBannerToCompleted();
        return;
    }
    if (withinWindow(now, win)) {
        if (reconnectTimer) clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(() => getTokenAndConnect(region), 5000);
    }
}

function connectWebSocket() {
    if (isWsOpen()) return;
    const now = new Date();
    const win = getWindowFor(now);
    if (!withinWindow(now, win)) {
        updateBannerToCompleted();
        return;
    }
    const WS_URL = `${WS_BASE_URL}?region=${encodeURIComponent(region)}`;
    ws = new WebSocket(WS_URL);
    ws.onopen = function() {
        if (reconnectTimer) clearTimeout(reconnectTimer);
        updateBannerToLive();
    };
    ws.onmessage = handleMessage;
    ws.onerror = handleError;
    ws.onclose = handleClose;
}

function disconnectWebSocket() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }
    if (ws) {
        try {
            ws.onclose = null;
            ws.close();
        } catch (e) {}
        ws = null;
    }
    isConnecting = false;
    clearAllRandomAnimations(); // Stop all random number animations
    updateBannerToCompleted();
}

function tick() {
    const now = new Date();
    const win = getWindowFor(now);

    if (withinWindow(now, win)) {
        if (!isWsOpen() && !isConnecting) {
            getTokenAndConnect(region);
        }
        updateBannerToLive();
        return;
    }

    if (now >= win.end && now.getHours() < 24) {
        updateBannerToCompleted();
        if (ticking) clearInterval(ticking);
        disconnectWebSocket();

        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 5, 0); // reset ngay đầu ngày
        setTimeout(() => {
            const nextWin = getWindowFor(new Date());
            resetBannerToWaiting(nextWin.start);
        }, msUntil(tomorrow));
        return;
    }

    if (now < win.start) {
        resetBannerToWaiting(win.start);
        return;
    }
}

function startWindow() {
    const win = getWindowFor(new Date());
    if (endTimer) clearTimeout(endTimer);
    endTimer = setTimeout(() => {
        if (ticking) clearInterval(ticking);
        disconnectWebSocket();
    }, msUntil(win.end));
    tick();
    if (ticking) clearInterval(ticking);
    ticking = setInterval(tick, 3000);
}

async function ensureCsrf() {
    const csrfBefore = cookieGet(CSRF_COOKIE);
    if (!csrfBefore) {
        const r = await fetch(API_CSRF, {
            method: 'POST',
            credentials: 'include'
        });
        if (!r.ok) throw new Error(`CSRF HTTP ${r.status}`);
        await r.json().catch(() => ({}));
        const csrfAfter = cookieGet(CSRF_COOKIE);
        if (!csrfAfter) throw new Error('CSRF cookie not set');
    }
}

function getTokenAndConnect(region) {
    if (isConnecting) return;
    isConnecting = true;

    ensureCsrf()
        .then(() => {
            const csrf = cookieGet(CSRF_COOKIE) || '';
            return fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                credentials: 'include',
                body: JSON.stringify({
                    region
                })
            });
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.ok) {
                setTimeout(() => connectWebSocket(), 100);
            } else {
                setTimeout(() => getTokenAndConnect(region), 5000);
            }
        })
        .catch(error => {
            setTimeout(() => getTokenAndConnect(region), 5000);
        })
        .finally(() => {
            isConnecting = false;
        });
}

(function() {
    const now = new Date();
    const win = getWindowFor(now);
    if (withinWindow(now, win)) {
        getTokenAndConnect(region);
        startWindow();
    }
    else if (now < win.start) {
        resetBannerToWaiting(win.start);
        setTimeout(() => {
            getTokenAndConnect(region);
            startWindow();
        }, msUntil(win.start));
    } 
    else {
        updateBannerToCompleted();
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 5, 0);
        setTimeout(() => {
            const nextWin = getWindowFor(new Date());
            resetBannerToWaiting(nextWin.start);
        }, msUntil(tomorrow));
    }
})();