class KenoLiveManager {
    constructor() {
        this.dataFile = '/cache/keno_data.json';
        this.baseInterval = 30000;
        this.currentInterval = this.baseInterval;
        this.isVisible = !document.hidden;
        this.isRunning = false;
        this.countdownTimer = null;
        this.nextDrawTime = null;
        this.initialLoadDone = false;   
        this.drawTimes = [
            '06:00','06:08','06:16','06:24','06:32','06:40','06:48','06:56',
            '07:04','07:12','07:20','07:28','07:36','07:44','07:52',
            '08:00','08:08','08:16','08:24','08:32','08:40','08:48','08:56',
            '09:04','09:12','09:20','09:28','09:36','09:44','09:52',
            '10:00','10:08','10:16','10:24','10:32','10:40','10:48','10:56',
            '11:04','11:12','11:20','11:28','11:36','11:44','11:52',
            '12:00','12:08','12:16','12:24','12:32','12:40','12:48','12:56',
            '13:04','13:12','13:20','13:28','13:36','13:44','13:52',
            '14:00','14:08','14:16','14:24','14:32','14:40','14:48','14:56',
            '15:04','15:12','15:20','15:28','15:36','15:44','15:52',
            '16:00','16:08','16:16','16:24','16:32','16:40','16:48','16:56',
            '17:04','17:12','17:20','17:28','17:36','17:44','17:52',
            '18:00','18:08','18:16','18:24','18:32','18:40','18:48','18:56',
            '19:04','19:12','19:20','19:28','19:36','19:44','19:52',
            '20:00','20:08','20:16','20:24','20:32','20:40','20:48','20:56',
            '21:04','21:12','21:20','21:28','21:36','21:44','21:52'
        ];
        this.init();
    }

    init() {
        this.loadInitialData();
        setTimeout(() => {
            this.startAutoUpdate();
        }, 1000);
        
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            this.adjustUpdateFrequency();
        });
    }

    isInRestPeriod() {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        const currentTime = currentHour * 60 + currentMinute;   
        
        return currentTime >= 1320 || currentTime <= 330;
    }

    async loadInitialData() {
        try {
            const response = await fetch(this.dataFile, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.updateUI(data);
            this.startCountdown(data.countdown);
            this.initialLoadDone = true;    
        } catch (error) {
            this.showError();
        }
    }

    startAutoUpdate() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.scheduleNextUpdate();
    }

    scheduleNextUpdate() {
        if (!this.isRunning) return;
        
        if (this.isInRestPeriod()) {
            setTimeout(() => {
                this.scheduleNextUpdate();
            }, 300000); // 5 phút
            return;
        }
        
        this.calculateOptimalInterval();
        const interval = this.isVisible ? this.currentInterval : this.currentInterval * 2;
        
        setTimeout(() => {
            if (this.isRunning) {
                this.updateData();
                this.scheduleNextUpdate();
            }
        }, interval);
    }

    calculateOptimalInterval() {
        const now = new Date();
        const currentTime = now.toTimeString().substr(0, 5);
        const nextDraw = this.getNextDrawTime(currentTime);
        
        if (!nextDraw) {
            this.currentInterval = 300000;
            return;
        }
        
        const timeDiff = this.getTimeDifferenceInMinutes('', nextDraw);
        
        if (timeDiff <= 1) {
            this.currentInterval = 5000;
        } else if (timeDiff <= 2) {
            this.currentInterval = 5000;
        } else if (timeDiff <= 5) {
            this.currentInterval = 30000;
        } else if (timeDiff < 6) {
            this.currentInterval = 30000;
        } else if (timeDiff <= 8) {
            this.currentInterval = 5000;
        } else {
            this.currentInterval = 60000;
        }
    }

    getNextDrawTime(currentTime) {
        for (let i = 0; i < this.drawTimes.length; i++) {
            if (this.drawTimes[i] > currentTime) {
                return this.drawTimes[i];
            }
        }
        return this.drawTimes[0];
    }

    getNextDrawTimeAfter(currentTime) {
        for (let i = 0; i < this.drawTimes.length; i++) {
            if (this.drawTimes[i] > currentTime) {
                return this.drawTimes[i];
            }
        }
        return this.drawTimes[0];
    }

    getTimeDifferenceInMinutes(time1, time2) {
        const now = new Date();
        const [h2, m2] = time2.split(':').map(Number);
        const nextDrawDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h2, m2, 0);
        
        if (nextDrawDate <= now) {
            nextDrawDate.setDate(nextDrawDate.getDate() + 1);
        }
        
        const diffMs = nextDrawDate.getTime() - now.getTime();
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        return Math.max(0, diffMinutes);
    }

    async updateData() {
        if (this.isInRestPeriod()) {
            return;     
        }
        
        try {
            const response = await fetch(this.dataFile, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (this.hasNewData(data)) {
                this.updateUI(data);
                this.startCountdown(data.countdown);
            }
        } catch (error) {
        }
    }

    hasNewData(newData) {
        const currentData = this.getCurrentData();
        if (!currentData) return true;
        
        return newData.timestamp !== currentData.timestamp || 
               newData.current_draw?.draw_number !== currentData.current_draw?.draw_number;
    }

    getCurrentData() {
        return this.currentData;
    }

    updateUI(data) {
        this.currentData = data;
        this.hideElement('keno-loading');
        this.hideElement('keno-error');
        this.showElement('keno-current-draw');
        
        this.updateCurrentDraw(data.current_draw);
        this.updateNextDrawTime(data.next_draw);
        this.updateRecentResults(data.recent_results || []);
    }

    updateCurrentDraw(drawData) {
        if (!drawData) return;
        
        this.setText('current-draw-number', drawData.draw_number || '-');
        
        const drawTime = this.formatDrawTime(drawData.draw_date, drawData.draw_time);
        this.setText('current-draw-time', drawTime);
        
        this.updateNumbers('current-draw-numbers', drawData.numbers || []);
        this.updateCounts(drawData);
    }

    updateNumbers(containerId, numbers) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        numbers.forEach(number => {
            const numberDiv = document.createElement('div');
            numberDiv.className = 'util-w-10 util-p-1';
            numberDiv.innerHTML = `<span class="ball keno">${String(number).padStart(2, '0')}</span>`;
            container.appendChild(numberDiv);
        });
    }

    updateCounts(drawData) {
        const evenCount = drawData.even_count || 0;
        const oddCount = drawData.odd_count || 0;
        
        this.setText('current-even-count', `Chẵn:<span>${evenCount}</span>`);
        this.setText('current-odd-count', `Lẻ:<span>${oddCount}</span>`);
        
        this.highlightCount('current-even-count', evenCount > oddCount);
        this.highlightCount('current-odd-count', oddCount > evenCount);
        
        const largeCount = drawData.large_count || 0;
        const smallCount = drawData.small_count || 0;
        
        this.setText('current-large-count', `Lớn:<span>${largeCount}</span>`);
        this.setText('current-small-count', `Nhỏ:<span>${smallCount}</span>`);
        
        this.highlightCount('current-large-count', largeCount > smallCount);
        this.highlightCount('current-small-count', smallCount > largeCount);
    }

    highlightCount(elementId, shouldHighlight) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        if (shouldHighlight) {
            element.classList.add('util-text-decoration-underline', 'util-font-medium');
        } else {
            element.classList.remove('util-text-decoration-underline', 'util-font-medium');
        }
    }

    updateNextDrawTime(nextDraw) {
        if (!nextDraw) return;
        
        const now = new Date();
        const currentTime = now.toTimeString().substr(0, 5);
        const nextDrawTime = this.getNextDrawTime(currentTime);
        
        this.setText('next-draw-time', nextDrawTime || '-');
        this.nextDrawTime = nextDrawTime;
    }

    updateRecentResults(recentResults) {
        const container = document.getElementById('recent-results-container');
        if (!container) return;
        
        if (recentResults.length === 0) {
            container.innerHTML = '<div class="util-text-center util-py-4"><p class="util-text-muted">Chưa có dữ liệu</p></div>';
            return;
        }
        
        let html = '';
        recentResults.forEach(result => {
            const drawTime = this.formatDrawTime(result.draw_date, result.draw_time);
            const numbers = result.numbers || [];
            
            html += `
                <div class="util-flex util-flex-nowrap util-border-bottom util-mb-2 util-pb-2 js-keno-item done">
                    <div class="util-small util-me-3">
                        <div class="util-text-nowrap">Kỳ quay:<span class="util-text-red">${result.draw_number || '-'}</span></div>
                        <span class="util-block util-font-medium util-text-nowrap">${drawTime}</span>
                    </div>
                    <div class="util-flex util-flex-wrap">
                        ${numbers.map(num => 
                            `<div class="util-w-10 util-p-1"><span class="ball keno">${String(num).padStart(2, '0')}</span></div>`
                        ).join('')}
                    </div>
                    <div class="util-ms-3 util-small">
                        <div class="util-text-nowrap util-flex util-mb-2">
                            <div class="${result.even_count > result.odd_count ? 'util-text-decoration-underline util-font-medium' : ''}">Chẵn:<span>${result.even_count || 0}</span></div>
                            <div class="util-px-2">/</div>
                            <div class="${result.odd_count > result.even_count ? 'util-text-decoration-underline util-font-medium' : ''}">Lẻ:<span>${result.odd_count || 0}</span></div>
                        </div>
                        <div class="util-text-nowrap util-flex">
                            <div class="${result.large_count > result.small_count ? 'util-text-decoration-underline util-font-medium' : ''}">Lớn:<span>${result.large_count || 0}</span></div>
                            <div class="util-px-2">/</div>
                            <div class="${result.small_count > result.large_count ? 'util-text-decoration-underline util-font-medium' : ''}">Nhỏ:<span>${result.small_count || 0}</span></div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    startCountdown(seconds) {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
        
        const now = new Date();
        const currentTime = now.toTimeString().substr(0, 5);
        const nextDrawTime = this.getNextDrawTime(currentTime);
        
        if (!nextDrawTime) {
            this.setText('countdown-timer', '');
            return;
        }
        
        const countdownSeconds = this.getCountdownSeconds(currentTime, nextDrawTime);
        
        if (countdownSeconds <= 0) {
            const nextNextDraw = this.getNextDrawTimeAfter(nextDrawTime);
            if (nextNextDraw) {
                const nextCountdown = this.getCountdownSeconds(currentTime, nextNextDraw);
                if (nextCountdown > 0) {
                    this.startCountdownWithTime(nextCountdown);
                    return;
                }
            }
            this.setText('countdown-timer', '');
            return;
        }
        
        let remaining = countdownSeconds;
        this.updateCountdownDisplay(remaining);
        
        this.countdownTimer = setInterval(() => {
            remaining--;
            this.updateCountdownDisplay(remaining);
            
            if (remaining <= 0) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
                this.updateData();
                this.countdownTimer = null;
                setTimeout(() => {
                    this.startCountdown();
                }, 2000);
            }
        }, 1000);
    }

    startCountdownWithTime(seconds) {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
        
        if (seconds <= 0) {
            this.setText('countdown-timer', '');
            return;
        }
        
        let remaining = seconds;
        this.updateCountdownDisplay(remaining);
        
        this.countdownTimer = setInterval(() => {
            remaining--;
            this.updateCountdownDisplay(remaining);
            
            if (remaining <= 0) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
                this.updateData();
                this.countdownTimer = null;
                setTimeout(() => {
                    this.startCountdown();
                }, 2000);
            }
        }, 1000);
    }

    getCountdownSeconds(currentTime, nextDrawTime) {
        const now = new Date();
        const [h2, m2] = nextDrawTime.split(':').map(Number);
        const nextDrawDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h2, m2, 0);
        
        if (nextDrawDate <= now) {
            nextDrawDate.setDate(nextDrawDate.getDate() + 1);
        }
        
        const diffMs = nextDrawDate.getTime() - now.getTime();
        const diffSeconds = Math.floor(diffMs / 1000);
        return Math.max(0, diffSeconds);
    }

    updateCountdownDisplay(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        const timeString = `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        this.setText('countdown-timer', `(Còn ${timeString})`);
    }

    formatDrawTime(drawDate, drawTime) {
        if (!drawDate) return '-';
        
        try {
            let date;
            
            // Handle YYYY-MM-DD HH:MM:SS format (from database)
            if (drawDate.includes(' ') && drawDate.includes('-')) {
                const [datePart, timePart] = drawDate.split(' ');
                const [year, month, day] = datePart.split('-');
                date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                
                // Extract time from timePart if available
                if (timePart) {
                    const [hours, minutes] = timePart.split(':');
                    date.setHours(parseInt(hours), parseInt(minutes), 0);
                }
            }
            // Handle DD-MM-YYYY format (legacy)
            else if (drawDate.includes('-') && drawDate.split('-').length === 3) {
                const [day, month, year] = drawDate.split('-');
                date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            } else {
                date = new Date(drawDate);
            }
            
            if (isNaN(date.getTime())) {
                console.warn('Invalid date:', drawDate);
                return drawTime || drawDate;
            }
            
            const time = drawTime || date.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
            const dateStr = date.toLocaleDateString('vi-VN');
            return `${time} ${dateStr}`;
        } catch (error) {
            console.error('Error formatting draw time:', error, 'drawDate:', drawDate, 'drawTime:', drawTime);
            return drawTime || drawDate;
        }
    }

    chunkArray(array, size) {
        const chunks = [];
        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }
        return chunks;
    }

    showError() {
        this.hideElement('keno-loading');
        this.hideElement('keno-current-draw');
        this.showElement('keno-error');
    }

    showElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'block';
        }
    }

    hideElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'none';
        }
    }

    setText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = text;
        }
    }

    adjustUpdateFrequency() {
        this.isRunning = false;
        setTimeout(() => {
            this.startAutoUpdate();
        }, 1000);
    }

    destroy() {
        this.isRunning = false;
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.kenoLiveManager = new KenoLiveManager();
});

window.addEventListener('beforeunload', () => {
    if (window.kenoLiveManager) {
        window.kenoLiveManager.destroy();
    }
});