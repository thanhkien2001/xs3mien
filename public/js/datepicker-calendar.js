/**
 * Custom Datepicker for KQXS
 * Hiển thị lịch và cho phép chọn ngày để xem kết quả xổ số
 */
(function() {
    'use strict';

    const DatePicker = {
        currentDate: new Date(),
        selectedDate: new Date(),
        viewingDate: null, // Ngày đang xem (từ URL)
        
        init: function(containerId) {
            this.container = document.getElementById(containerId);
            if (!this.container) return;
            
            // Parse viewing date from URL
            this.parseViewingDateFromURL();
            
            this.render();
            this.attachEvents();
        },
        
        parseViewingDateFromURL: function() {
            // Check if URL matches /kqxs-ngay-DD-MM-YYYY.html
            const match = window.location.pathname.match(/\/kqxs-ngay-(\d{2})-(\d{2})-(\d{4})\.html/);
            if (match) {
                const day = parseInt(match[1]);
                const month = parseInt(match[2]) - 1; // JS months are 0-indexed
                const year = parseInt(match[3]);
                this.viewingDate = new Date(year, month, day);
                this.currentDate = new Date(year, month, day);
            }
        },
        
        render: function() {
            const html = this.generateCalendarHTML();
            this.container.innerHTML = html;
        },
        
        generateCalendarHTML: function() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const today = new Date();
            
            // Các tháng và thứ
            const monthNames = [
                'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
            ];
            const dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            
            // Ngày đầu tiên của tháng (0 = Sunday, 6 = Saturday)
            const firstDay = new Date(year, month, 1).getDay();
            const adjustedFirstDay = firstDay === 0 ? 6 : firstDay - 1; // Chuyển sang Monday = 0
            
            // Số ngày trong tháng
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            // Check if next month is disabled (future)
            const isNextDisabled = (year > today.getFullYear()) || 
                                  (year === today.getFullYear() && month >= today.getMonth());
            
            let html = `
            <div class="datepicker datepicker-inline">
                <div class="datepicker-days" style="display: block;">
                    <table class="table-condensed">
                        <thead>
                            <tr><th colspan="7" class="datepicker-title" style="display: none;"></th></tr>
                            <tr>
                                <th class="prev" data-action="prev"><i class="icon-chevron-left"></i></th>
                                <th colspan="5" class="datepicker-switch">${monthNames[month]} ${year}</th>
                                <th class="next ${isNextDisabled ? 'disabled' : ''}" data-action="next">
                                    <i class="icon-chevron-right"></i>
                                </th>
                            </tr>
                            <tr>`;
            
            // Header: days of week
            dayNames.forEach(day => {
                html += `<th class="dow">${day}</th>`;
            });
            
            html += `</tr></thead><tbody>`;
            
            // Body: dates
            let dayCount = 1;
            let nextMonthDay = 1;
            let totalRows = Math.ceil((adjustedFirstDay + daysInMonth) / 7);
            
            for (let row = 0; row < totalRows; row++) {
                // Check if current week
                const isCurrentWeek = this.isCurrentWeek(year, month, dayCount, adjustedFirstDay, row);
                html += `<tr${isCurrentWeek ? ' class="currentWeek"' : ''}>`;
                
                for (let col = 0; col < 7; col++) {
                    const cellIndex = row * 7 + col;
                    
                    if (cellIndex < adjustedFirstDay) {
                        // Previous month days
                        const prevDay = daysInPrevMonth - adjustedFirstDay + cellIndex + 1;
                        const prevMonth = month - 1;
                        const prevYear = month === 0 ? year - 1 : year;
                        const actualMonth = month === 0 ? 11 : prevMonth;
                        const timestamp = new Date(prevYear, actualMonth, prevDay).getTime();
                        
                        html += `<td class="old day" data-date="${timestamp}">${prevDay}</td>`;
                    } else if (dayCount <= daysInMonth) {
                        // Current month days
                        const timestamp = new Date(year, month, dayCount).getTime();
                        const isToday = (dayCount === today.getDate() && 
                                       month === today.getMonth() && 
                                       year === today.getFullYear());
                        
                        // Check if this is the viewing date (active)
                        const isViewing = this.viewingDate && 
                                         (dayCount === this.viewingDate.getDate() && 
                                          month === this.viewingDate.getMonth() && 
                                          year === this.viewingDate.getFullYear());
                        
                        const isFuture = new Date(year, month, dayCount) > today;
                        
                        const classes = ['day'];
                        if (isToday) classes.push('today');
                        if (isViewing) classes.push('active'); // Add active class for viewing date
                        if (isFuture) classes.push('disabled');
                        
                        html += `<td class="${classes.join(' ')}" data-date="${timestamp}">${dayCount}</td>`;
                        dayCount++;
                    } else {
                        // Next month days
                        const nextMonth = month + 1;
                        const nextYear = month === 11 ? year + 1 : year;
                        const actualMonth = month === 11 ? 0 : nextMonth;
                        const timestamp = new Date(nextYear, actualMonth, nextMonthDay).getTime();
                        
                        html += `<td class="new disabled day" data-date="${timestamp}">${nextMonthDay}</td>`;
                        nextMonthDay++;
                    }
                }
                
                html += `</tr>`;
            }
            
            html += `
                        </tbody>
                        <tfoot>
                            <tr><th colspan="7" class="today" style="display: none;">Today</th></tr>
                            <tr><th colspan="7" class="clear" style="display: none;">Clear</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>`;
            
            return html;
        },
        
        isCurrentWeek: function(year, month, dayCount, adjustedFirstDay, row) {
            const today = new Date();
            if (year !== today.getFullYear() || month !== today.getMonth()) {
                return false;
            }
            
            const cellIndex = row * 7;
            const startDay = cellIndex < adjustedFirstDay ? 1 : cellIndex - adjustedFirstDay + 1;
            const endDay = Math.min(startDay + 6, new Date(year, month + 1, 0).getDate());
            
            return today.getDate() >= startDay && today.getDate() <= endDay;
        },
        
        attachEvents: function() {
            const self = this;
            
            // Event delegation for all clicks
            this.container.addEventListener('click', function(e) {
                const target = e.target.closest('td.day, th[data-action]');
                if (!target) return;
                
                // Handle day click
                if (target.classList.contains('day') && !target.classList.contains('disabled')) {
                    const timestamp = parseInt(target.getAttribute('data-date'));
                    const date = new Date(timestamp);
                    self.selectDate(date);
                }
                
                // Handle prev/next month
                if (target.hasAttribute('data-action')) {
                    const action = target.getAttribute('data-action');
                    if (action === 'prev') {
                        self.prevMonth();
                    } else if (action === 'next' && !target.classList.contains('disabled')) {
                        self.nextMonth();
                    }
                }
            });
        },
        
        selectDate: function(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            
            // Redirect to result page
            window.location.href = `/kqxs-ngay-${day}-${month}-${year}.html`;
        },
        
        prevMonth: function() {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.render();
            this.attachEvents();
        },
        
        nextMonth: function() {
            const today = new Date();
            const nextMonth = new Date(this.currentDate);
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            
            // Don't allow future months
            if (nextMonth <= today) {
                this.currentDate = nextMonth;
                this.render();
                this.attachEvents();
            }
        }
    };
    
    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            DatePicker.init('datepicker');
        });
    } else {
        DatePicker.init('datepicker');
    }
    
})();

