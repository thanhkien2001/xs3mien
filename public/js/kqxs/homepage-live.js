/**
 * Homepage Live Update Script
 * Handles WebSocket connections for all 3 regions (XSMB, XSMT, XSMN) simultaneously
 */

// === CONFIG ===
const SCHEDULES = {
    mb: { start: { hour: 18, minute: 15 }, end: { hour: 19, minute: 0 } },
    mt: { start: { hour: 17, minute: 15 }, end: { hour: 17, minute: 30 } },
    mn: { start: { hour: 16, minute: 15 }, end: { hour: 17, minute: 0 } }
};

const API_CSRF = '/lottery/csrf';
const API_URL = '/get-token';
const isLocal = window.location.hostname.includes('xs-clone.code') ||
    window.location.hostname.includes('localhost') ||
    window.location.hostname.includes('127.0.0.1');

const WS_BASE_URL = isLocal
    ? "wss://xs-clone.code/ws"
    : "wss://soicau247.com/ws";

const CSRF_COOKIE = 'ws_csrf';
const TOKEN_COOKIE = 'ws_token';

const prizeMapping = {
    '0': '0', '1': '1', '2': '2', '3': '3', '4': '4',
    '5': '5', '6': '6', '7': '7', '8': '8'
};

// === STATE ===
const connections = {
    mb: { ws: null, reconnectTimer: null, isConnecting: false, ticking: null, endTimer: null },
    mt: { ws: null, reconnectTimer: null, isConnecting: false, ticking: null, endTimer: null },
    mn: { ws: null, reconnectTimer: null, isConnecting: false, ticking: null, endTimer: null }
};

let randomTimers = {};

// === HELPERS ===
function cookieGet(name) {
    const raw = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(name + '='));
    return raw ? decodeURIComponent(raw.split('=').slice(1).join('=')) : null;
}

function isWsOpen(region) {
    const conn = connections[region];
    return conn.ws && conn.ws.readyState === WebSocket.OPEN;
}

function getWindowFor(date, region) {
    const schedule = SCHEDULES[region];
    const start = new Date(date);
    start.setHours(schedule.start.hour, schedule.start.minute, 0, 0);
    const end = new Date(date);
    end.setHours(schedule.end.hour, schedule.end.minute, 0, 0);
    if (end <= start) end.setDate(end.getDate() + 1);
    return { start, end };
}

function msUntil(t) {
    return t - new Date();
}

function withinWindow(now, win) {
    return now >= win.start && now < win.end;
}

function generateRandomNumber(digits) {
    const min = Math.pow(10, digits - 1);
    const max = Math.pow(10, digits) - 1;
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function startRandomAnimation(element, digits) {
    const key = element.dataset.randomKey || Math.random().toString(36);
    element.dataset.randomKey = key;

    if (randomTimers[key]) {
        randomTimers[key].stop = true;
    }

    const timer = { stop: false };
    randomTimers[key] = timer;

    function tick() {
        if (timer.stop) return;
        element.textContent = generateRandomNumber(digits);
        element.classList.remove('number-changing');
        void element.offsetWidth;
        element.classList.add('number-changing');
        setTimeout(() => {
            if (!timer.stop) requestAnimationFrame(tick);
        }, 300);
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
        if (randomTimers[key]) randomTimers[key].stop = true;
    });
    randomTimers = {};
}

// === MESSAGE HANDLER ===
function handleMessage(region, event) {
    const data = JSON.parse(event.data);
    if (data.error) return;

    // No mapping needed - WebSocket uses keyid, HTML uses keyid

    // Tìm bảng theo region code
    let regionCode = region.toUpperCase();
    if (['MB', 'MT', 'MN'].includes(regionCode)) {
        regionCode = 'XS' + regionCode;
    }
    const tableSelector = `.table-result[data-code="${regionCode}"]`;
    const table = document.querySelector(tableSelector);

    console.log(`[DEBUG] Region: ${region}, Searching for table: ${tableSelector}, Found: ${!!table}`);

    if (!table) {
        console.error(`[CRITICAL] Table NOT found for selector: ${tableSelector}`);
        // Log all available tables to see what's going on
        document.querySelectorAll('.table-result').forEach(t => {
            console.log(`Available table: data-code="${t.getAttribute('data-code')}"`);
        });
        return;
    }

    // Update prizes
    for (const [provinceId, results] of Object.entries(data.results || {})) {
        console.log(`[DEBUG] Processing provinceId: ${provinceId}`);
        for (const [prizeKey, numbers] of Object.entries(results || {})) {
            if (prizeKey === 'dau') continue; // Skip dau data

            const mappedPrize = prizeMapping[prizeKey];
            if (!mappedPrize) continue;

            const numbersArray = Array.isArray(numbers) ? numbers : [numbers];

            // For XSMB (single province)
            if (regionCode === 'XSMB') {
                const selector = `td[data-page-id="${provinceId}"][data-id-giai="${mappedPrize}"]`;
                const cell = table.querySelector(selector);

                console.log(`[DEBUG] XSMB Selector: ${selector}, Found: ${!!cell}`);

                if (cell) {
                    // XSMB can have multiple numbers per cell (e.g., G2, G3)
                    updateCellMulti(cell, numbersArray, mappedPrize);
                }
            } else {
                // For XSMT/XSMN (multi province)
                const selector = `td[data-page-id="${provinceId}"][data-id-giai="${mappedPrize}"]`;
                const cell = table.querySelector(selector);

                console.log(`[DEBUG] ${regionCode} Selector: ${selector}, Found: ${!!cell}`);

                if (cell) {
                    updateCellMulti(cell, numbersArray, mappedPrize);
                } else {
                    console.warn(`[DEBUG] Cell NOT found for ${selector}`);
                }
            }
        }
    }

    // Update dau-duoi
    const dauDuoiCells = table.closest('.tab-pane').querySelectorAll('.js-number-dau-duoi[data-num]');
    dauDuoiCells.forEach(cell => {
        const htmlProvinceId = cell.getAttribute('data-page-id');
        const head = cell.getAttribute('data-num');
        const numbers = [];

        if (!data.results || !data.results[htmlProvinceId]) {
            cell.innerHTML = '<span class="text-muted">-</span>';
            return;
        }

        if (data.results[htmlProvinceId].dau && data.results[htmlProvinceId].dau[head]) {
            const dauData = data.results[htmlProvinceId].dau[head];
            if (Array.isArray(dauData)) {
                numbers.push(...dauData);
            } else if (dauData) {
                numbers.push(dauData.toString());
            }
        } else {
            for (const [_prizeKey, result] of Object.entries(data.results[htmlProvinceId])) {
                if (_prizeKey === 'dau') continue;
                const pushIfMatch = num => {
                    const s = num.toString();
                    if (s.length >= 2 && s.charAt(s.length - 2) === head) numbers.push(s.slice(-2));
                };
                if (Array.isArray(result)) result.forEach(pushIfMatch);
                else pushIfMatch(result);
            }
        }

        const unique = [...new Set(numbers)].sort();
        cell.textContent = unique.length ? unique.join(', ') : '-';
    });
}

function updateCell(cell, val, mappedPrize) {
    const hasSpinner = cell.querySelector('.spinner-border');
    const hasRandom = cell.querySelector('.number-random');
    const hasNumber = cell.querySelector('.number:not(.number-random)');

    if (typeof val === 'string' && val.includes('*')) {
        // Keep spinner
    } else if (typeof val === 'string' && val.includes('+')) {
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
        const currentNum = hasNumber ? hasNumber.textContent : '';
        if (currentNum !== val) {
            if (hasRandom) stopRandomAnimation(hasRandom);
            cell.innerHTML = '';
            const s = document.createElement('span');
            s.className = 'number';
            s.textContent = val;
            cell.appendChild(s);
        }
    }
}

function updateCellMulti(cell, numbersArray, mappedPrize) {
    const newDataStr = JSON.stringify(numbersArray);
    const oldDataStr = cell.getAttribute('data-current');

    if (oldDataStr !== newDataStr) {
        const oldRandomElements = cell.querySelectorAll('.number-random');
        oldRandomElements.forEach(el => stopRandomAnimation(el));

        cell.setAttribute('data-current', newDataStr);
        cell.innerHTML = '';

        const pageId = cell.getAttribute('data-page-id');
        const idGiai = cell.getAttribute('data-id-giai');

        numbersArray.forEach(val => {
            const container = document.createElement('span');
            container.className = 'text-number';
            const digits = mappedPrize === '1' ? 5 : (mappedPrize === '9' ? 2 : 5);
            container.setAttribute('nc', digits.toString());
            if (pageId) container.setAttribute('data-page-id', pageId);
            if (idGiai) container.setAttribute('data-id-giai', idGiai);

            if (typeof val === 'string' && val.includes('*')) {
                const icon = document.createElement('i');
                icon.className = 'icon icon-loading123';
                container.appendChild(icon);
            } else if (typeof val === 'string' && val.includes('+')) {
                const s = document.createElement('span');
                s.className = 'number number-random number-changing';
                s.textContent = generateRandomNumber(digits);
                startRandomAnimation(s, digits);
                container.appendChild(s);
            } else {
                const s = document.createElement('span');
                s.className = 'number';
                s.textContent = val;
                container.appendChild(s);
            }
            cell.appendChild(container);
            cell.appendChild(document.createTextNode(' '));
        });
    }
}

// === CONNECTION MANAGEMENT ===
function connectWebSocket(region) {
    const conn = connections[region];
    if (conn.ws && conn.ws.readyState === WebSocket.OPEN) return;

    const now = new Date();
    const win = getWindowFor(now, region);
    if (!withinWindow(now, win)) return;

    const WS_URL = `${WS_BASE_URL}?region=${encodeURIComponent(region)}`;
    conn.ws = new WebSocket(WS_URL);

    conn.ws.onopen = function () {
        console.log(`[${region.toUpperCase()}] WebSocket connected`);
        if (conn.reconnectTimer) clearTimeout(conn.reconnectTimer);
    };

    conn.ws.onmessage = (event) => handleMessage(region, event);

    conn.ws.onerror = function (e) {
        console.error(`[${region.toUpperCase()}] WebSocket error:`, e);
    };

    conn.ws.onclose = function (e) {
        console.log(`[${region.toUpperCase()}] WebSocket closed`);
        conn.isConnecting = false;
        const now = new Date();
        const win = getWindowFor(now, region);
        if (withinWindow(now, win)) {
            if (conn.reconnectTimer) clearTimeout(conn.reconnectTimer);
            conn.reconnectTimer = setTimeout(() => getTokenAndConnect(region), 5000);
        }
    };
}

function disconnectWebSocket(region) {
    const conn = connections[region];
    if (conn.reconnectTimer) {
        clearTimeout(conn.reconnectTimer);
        conn.reconnectTimer = null;
    }
    if (conn.ws) {
        try {
            conn.ws.onclose = null;
            conn.ws.close();
        } catch (e) { }
        conn.ws = null;
    }
    conn.isConnecting = false;
}

function tick(region) {
    const conn = connections[region];
    const now = new Date();
    const win = getWindowFor(now, region);

    if (withinWindow(now, win)) {
        if (!isWsOpen(region) && !conn.isConnecting) {
            getTokenAndConnect(region);
        }
        return;
    }

    if (now >= win.end && now.getHours() < 24) {
        if (conn.ticking) clearInterval(conn.ticking);
        disconnectWebSocket(region);
        return;
    }
}

function startWindow(region) {
    const conn = connections[region];
    const win = getWindowFor(new Date(), region);

    if (conn.endTimer) clearTimeout(conn.endTimer);
    conn.endTimer = setTimeout(() => {
        if (conn.ticking) clearInterval(conn.ticking);
        disconnectWebSocket(region);
    }, msUntil(win.end));

    tick(region);
    if (conn.ticking) clearInterval(conn.ticking);
    conn.ticking = setInterval(() => tick(region), 3000);
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
    const conn = connections[region];
    if (conn.isConnecting) return;
    conn.isConnecting = true;

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
                body: JSON.stringify({ region })
            });
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.ok) {
                setTimeout(() => connectWebSocket(region), 100);
            } else {
                setTimeout(() => getTokenAndConnect(region), 5000);
            }
        })
        .catch(error => {
            console.error(`[${region.toUpperCase()}] Token error:`, error);
            setTimeout(() => getTokenAndConnect(region), 5000);
        })
        .finally(() => {
            conn.isConnecting = false;
        });
}

// === INIT ===
function init() {
    const now = new Date();

    ['mb', 'mt', 'mn'].forEach(region => {
        const win = getWindowFor(now, region);
        if (withinWindow(now, win)) {
            console.log(`[${region.toUpperCase()}] Starting live update`);
            getTokenAndConnect(region);
            startWindow(region);
        } else if (now < win.start) {
            const delay = msUntil(win.start);
            console.log(`[${region.toUpperCase()}] Scheduling start in ${Math.round(delay / 1000)}s`);
            setTimeout(() => {
                getTokenAndConnect(region);
                startWindow(region);
            }, delay);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
