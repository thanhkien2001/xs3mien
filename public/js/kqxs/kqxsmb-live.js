// === CONFIG ===
const SCHEDULE = {
    start: {
        hour: 1,
        minute: 15
    },
    end: {
        hour: 23,
        minute: 59
    }
};

const API_CSRF = '/lottery/csrf';
const API_URL = '/get-token';
const isLocal = window.location.hostname.includes('xs-clone.code') ||
    window.location.hostname.includes('localhost') ||
    window.location.hostname.includes('127.0.0.1');

const WS_BASE_URL = isLocal
    ? "wss://xs-clone.code/ws"
    : "wss://soicau247.com/ws";

const provinces = window.appProvinces || {};

const prizeMapping = {
    '0': '0',
    '1': '1',
    '2': '2',
    '3': '3',
    '4': '4',
    '5': '5',
    '6': '6',
    '7': '7',
    '8': '8'
};

// Tìm tất cả các bảng: bảng chính (0) và latest result đầu tiên (1)
const tables = document.querySelectorAll('.js-kq-table');
const tablesToUpdate = [tables[0], tables[1]].filter(Boolean);
const dauDuoiCells = document.querySelectorAll('.js-number-dau-duoi');

let ws = null,
    reconnectTimer = null,
    isConnecting = false,
    ticking = null,
    endTimer = null,
    randomIntervals = {}, // Store intervals for random number animation
    randomTimers = {}; // Store animation timers

const urlParams = new URLSearchParams(window.location.search);
const region = urlParams.get('region') || 'mb';

const CSRF_COOKIE = 'ws_csrf';
const TOKEN_COOKIE = 'ws_token';

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

// === RENDER ===
function handleMessage(event) {
    const data = JSON.parse(event.data);
    if (data.error) return;

    const provinceIds = Object.keys(data.results || {});
    const provinceIdMapping = {};
    provinceIds.forEach(id => {
        if (provinces[id]) provinceIdMapping[id] = id;
    });

    // Loop qua từng bảng cần cập nhật
    tablesToUpdate.forEach(table => {
        for (const [provinceId, results] of Object.entries(data.results || {})) {
            const mappedProvinceId = provinceIdMapping[provinceId] || null;
            if (!mappedProvinceId) continue;

            for (const [prizeKey, numbers] of Object.entries(results || {})) {
                const mappedPrize = prizeMapping[prizeKey];
                if (!mappedPrize) continue;

                // Tìm TẤT CẢ các cells có cùng data-page-id và data-id-giai trong bảng này
                // Chuyển sang Array để đảm bảo thứ tự đúng theo DOM
                const cells = Array.from(table.querySelectorAll(
                    `span[data-page-id="${mappedProvinceId}"][data-id-giai="${mappedPrize}"]`
                ));
                if (cells.length === 0) continue;

                // Chuyển numbers thành array nếu chưa phải
                const numbersArray = Array.isArray(numbers) ? numbers : [numbers];

                // Xử lý từng cell tương ứng với từng số theo đúng thứ tự
                for (let cellIndex = 0; cellIndex < cells.length; cellIndex++) {
                    const cell = cells[cellIndex];

                    // Nếu có số tương ứng với cell này
                    if (cellIndex < numbersArray.length) {
                        const val = numbersArray[cellIndex];

                        // Kiểm tra trạng thái hiện tại
                        const hasSpinner = cell.querySelector('.spinner-border');
                        const hasRandom = cell.querySelector('.number-random');
                        const hasNumber = cell.querySelector('.number:not(.number-random)');

                        if (typeof val === 'string' && val.includes('*')) {
                            // Có dấu * → Giữ nguyên spinner loading (không làm gì)
                            // Spinner ban đầu từ HTML sẽ tiếp tục hiển thị
                        } else if (typeof val === 'string' && val.includes('+')) {
                            // Có dấu + → Hiển thị số random chạy, bỏ spinner
                            if (!hasRandom) {
                                cell.innerHTML = '';
                                const s = document.createElement('span');
                                s.className = 'number number-random';
                                const digits = mappedPrize === '1' ? 5 : (mappedPrize === '8' ? 2 : 5);
                                s.textContent = generateRandomNumber(digits);
                                startRandomAnimation(s, digits);
                                cell.appendChild(s);
                            }
                        } else if (val) {
                            // Số thực - chỉ cập nhật nếu chưa có hoặc khác số hiện tại
                            const currentNum = hasNumber ? hasNumber.textContent : '';
                            if (currentNum !== val) {
                                // Dừng animation nếu có
                                if (hasRandom) stopRandomAnimation(hasRandom);

                                cell.innerHTML = '';
                                const s = document.createElement('span');
                                s.className = 'number';
                                s.textContent = val;
                                cell.appendChild(s);
                            }
                        }
                    }
                    // Nếu không có số tương ứng, giữ nguyên spinner ban đầu
                }
            }
        }
    });

    // Xử lý dauDuoiCells cho tất cả các bảng
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
    // Silent error handling
}

function handleClose(e) {
    isConnecting = false;
    const now = new Date();
    const win = getWindowFor(now);
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
        return;
    }
    const WS_URL = `${WS_BASE_URL}?region=${encodeURIComponent(region)}`;
    ws = new WebSocket(WS_URL);
    ws.onopen = function () {
        if (reconnectTimer) clearTimeout(reconnectTimer);
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
        } catch (e) { }
        ws = null;
    }
    isConnecting = false;
    clearAllRandomAnimations(); // Stop all random number animations
}

function tick() {
    const now = new Date();
    const win = getWindowFor(now);
    if (withinWindow(now, win)) {
        if (!isWsOpen() && !isConnecting) {
            getTokenAndConnect(region);
        }
        return;
    }
    if (now >= win.end && now.getHours() < 24) {
        if (ticking) clearInterval(ticking);
        disconnectWebSocket();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 5, 0); // reset ngay đầu ngày
        return;
    }
    if (now < win.start) {
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

(function () {
    const now = new Date();
    const win = getWindowFor(now);
    if (withinWindow(now, win)) {
        getTokenAndConnect(region);
        startWindow();
    } else if (now < win.start) {
        // Chờ đến khung giờ
        setTimeout(() => {
            getTokenAndConnect(region);
            startWindow();
        }, msUntil(win.start));
    }
})();

