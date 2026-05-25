
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo Flatpickr
    const datePickerLink = document.querySelector('.js-date-picker-vietlott');
    const resultContent = document.querySelector('.js-result-content');
    const resultsContainer = document.querySelector('.js-results-tbody');

    // Kiểm tra nếu trang được load với date parameter từ server
    const preloadDateElement = document.querySelector('[data-preload-date]');
    if (preloadDateElement) {
        const preloadDate = preloadDateElement.getAttribute('data-preload-date');
        if (preloadDate) {
            // Set date picker value và load data cho ngày đó
            if (datePickerLink) {
                // Convert YYYY-MM-DD to DD-MM-YYYY for date picker
                const [year, month, day] = preloadDate.split('-');
                const formattedDate = `${day}-${month}-${year}`;
                datePickerLink.value = formattedDate;
            }
        }
    }

    // Hàm render kết quả chính
    function renderMainResult(result) {
        if (!result) {
            resultContent.innerHTML = '<p class="text-center text-danger">Không tìm thấy kết quả cho ngày đã chọn.</p>';
            return;
        }

        const numbers = result.numbers.split(',');
        const mainNumbers = numbers.slice(0, 6);
        const bonusNumber = numbers[numbers.length - 1];

        const html = `
        <h3 class="text-center fw-bold fs-6 mb-3">Kỳ quay thưởng <span class="text-warning">${result.draw_number} ${new Date(result.draw_date).toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })}</span></h3>
        <div class="balls justify-content-center power-655 js-result-number" data-power="${result.id}">
            ${mainNumbers.map(number => `<span class="ball power big mx-1">${number.padStart(2, '0')}</span>`).join('')}
            <span class="ball green big mx-1">${bonusNumber.padStart(2, '0')}</span>
        </div>
        <div class="mt-3 mb-3">
            Jackpot 1: <span class="fw-bold fs-1 text-red d-block lh-1 js_power_jackpot1">${new Intl.NumberFormat('vi-VN').format(result.jackpot_amount)}₫</span>
        </div>
        <div>
            Jackpot 2: <span class="fw-bold fs-5 text-red d-block lh-1 js_power_jackpot2">${new Intl.NumberFormat('vi-VN').format(result.jackpot_2)}₫</span>
        </div>
        <div class="table-responsive mt-3 mb-3">
            <table class="table table-bordered text-start align-middle small vietlott__winners__table bg-dark-vietlott">
                <thead>
                    <tr>
                        <th>Giải</th>
                        <th>Trùng khớp</th>
                        <th class="text-end">SL trúng</th>
                        <th class="text-end">Giá trị</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jackpot 1</td>
                        <td><span class="small text-orange">●●●●●●</span></td>
                        <td class="text-end">${result.jackpot_1_sl}</td>
                        <td class="text-end text-white">${new Intl.NumberFormat('vi-VN').format(result.jackpot_amount)}₫</td>
                    </tr>
                    <tr>
                        <td>Jackpot 2</td>
                        <td><span class="small text-orange">●●●●●</span><span class="small text-success">●</span></td>
                        <td class="text-end">${result.jackpot_2_sl}</td>
                        <td class="text-end text-white">${new Intl.NumberFormat('vi-VN').format(result.jackpot_2)}₫</td>
                    </tr>
                    <tr>
                        <td>Giải nhất</td>
                        <td><span class="small text-orange">●●●●●</span></td>
                        <td class="text-end">${result.giai_nhat_sl}</td>
                        <td class="text-end text-white">${new Intl.NumberFormat('vi-VN').format(result.giai_nhat_gia_tri)}₫</td>
                    </tr>
                    <tr>
                        <td>Giải nhì</td>
                        <td><span class="small text-orange">●●●●</span></td>
                        <td class="text-end">${result.giai_nhi_sl}</td>
                        <td class="text-end text-white">${new Intl.NumberFormat('vi-VN').format(result.giai_nhi_gia_tri)}₫</td>
                    </tr>
                    <tr>
                        <td>Giải ba</td>
                        <td><span class="small text-orange">●●●</span></td>
                        <td class="text-end">${result.giai_ba_sl}</td>
                        <td class="text-end text-white">${new Intl.NumberFormat('vi-VN').format(result.giai_ba_gia_tri)}₫</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="fs-10 m-0 text-start">Lưu ý: Các con số dự thưởng phải trùng với số kết quả nhưng không cần theo đúng thứ tự.</p>
        <p class="fs-10 m-0 text-start">Thời hạn lĩnh thưởng của vé trúng thưởng: là 60 (sáu mươi) ngày, kể từ ngày xác định Kết quả quay số mở thưởng. Quá thời hạn trên, các vé trúng thưởng không còn giá trị lĩnh thưởng.</p>
    `;
        resultContent.innerHTML = html;
    }

    // Hàm render danh sách kỳ gần đây
    function renderRecentResults(results) {
        resultsContainer.innerHTML = '';
        if (!results || results.length === 0) {
            resultsContainer.innerHTML = '<tr><td colspan="4" class="text-center">Không có kết quả để hiển thị.</td></tr>';
            return;
        }

        results.forEach(result => {
            const numbers = result.numbers.split(',');
            const mainNumbers = numbers.slice(0, 6);
            const bonusNumber = numbers[numbers.length - 1];
            const html = `
            <tr>
                <td>
                    <a title="Kết quả xổ số Vietlott Power 6/55 ngày ${new Date(result.draw_date).toLocaleDateString('vi-VN')}"
                        href="/ket-qua-xoso-power-6-55-vietlott-${new Date(result.draw_date).toLocaleDateString('vi-VN').replace(/\//g, '-')}"
                        class="text-decoration-none js-power-date-link"
                        data-date="${new Date(result.draw_date).toISOString().slice(0,10)}">
                        ${new Date(result.draw_date).toLocaleDateString('vi-VN')}
                    </a>
                </td>
                <td>
                    <div class="balls">
                        ${mainNumbers.map(number => `<span class="ball power me-1">${number.padStart(2, '0')}</span>`).join('')}
                        <span class="ball green">${bonusNumber.padStart(2, '0')}</span>
                    </div>
                </td>
                <td class="text-end"><span class="text-nowrap">${new Intl.NumberFormat('vi-VN').format(result.jackpot_amount)}</span></td>
                <td class="text-end"><span class="text-nowrap">${new Intl.NumberFormat('vi-VN').format(result.jackpot_2)}</span></td>
            </tr>
        `;
            resultsContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    // Hàm xử lý chọn ngày (dùng cho cả Flatpickr và click link)
    function handleDateSelection(selectedDate) {
        
        
        fetch(`/vietlott655/byDate?date=${selectedDate}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    // Cập nhật URL mà không tải lại trang
                    const [year, month, day] = selectedDate.split("-");
                    const formattedDate = `${day}-${month}-${year}`;
                    const newUrl = `/ket-qua-xoso-power-6-55-vietlott-${formattedDate}.html`;
                    
                    // Update breadcrumb and title
                    updateBreadcrumbAndTitle(selectedDate, 'Power 6/55');
                    // const newUrl = `/xo-so-power-6-55-vietlott-${selectedDate.replace(/-/g, '-')}`;
                    window.history.pushState({
                        path: newUrl
                    }, '', newUrl);

                    // Render kết quả chính
                    renderMainResult(data.data);

                    // Cập nhật danh sách kỳ gần đây
                    fetch(`/vietlott655/results?page=1&limit=6`, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success' && data.data.length > 0) {
                                renderRecentResults(data.data);
                            } else {
                                renderRecentResults([]);
                            }
                        })
                        .catch(error => {
                            console.error('Lỗi khi tải danh sách kỳ gần đây:', error);
                            renderRecentResults([]);
                        });
                } else {
                    renderMainResult(null);
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải kết quả theo ngày:', error);
                renderMainResult(null);
            });
    }
    
    function updateBreadcrumbAndTitle(selectedDate, gameType) {
        console.log('updateBreadcrumbAndTitle called with:', selectedDate, gameType);
        
        const [year, month, day] = selectedDate.split("-");
        const date = new Date(year, month - 1, day);
        const dayNames = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        const dayName = dayNames[date.getDay()];
        const formattedDate = `${day}/${month}/${year}`;
        const formattedDateShort = `${day}-${month}`;
        
        const breadcrumbText = `Kết Quả Xổ Số ${gameType} Ngày ${formattedDate} (${dayName} ${formattedDateShort})`;
        const titleText = `Kết Quả Xổ Số ${gameType} Ngày ${formattedDate} (${dayName} ${formattedDateShort})`;
        
        // Show game breadcrumb when selecting specific date
        const gameBreadcrumb = document.getElementById('game-breadcrumb');
        console.log('gameBreadcrumb element:', gameBreadcrumb);
        if (gameBreadcrumb) {
            gameBreadcrumb.style.display = 'block';
            console.log('gameBreadcrumb displayed');
        }
        
        // Update breadcrumb
        const dateBreadcrumb = document.getElementById('date-breadcrumb');
        console.log('dateBreadcrumb element:', dateBreadcrumb);
        if (dateBreadcrumb) {
            dateBreadcrumb.innerHTML = `<span>${breadcrumbText}</span>`;
            console.log('dateBreadcrumb updated');
        }
        
        // Update title
        const pageTitle = document.getElementById('page-title');
        console.log('pageTitle element:', pageTitle);
        if (pageTitle) {
            pageTitle.textContent = titleText;
            console.log('pageTitle updated');
        }
        
        // Update document title
        document.title = titleText;
        console.log('document title updated');
    }

    // Khởi tạo Flatpickr
    if (datePickerLink) {
        flatpickr(datePickerLink, {
            dateFormat: 'd-m-Y',
            disable: [
                function(date) {
                    // Chỉ cho phép chọn thứ 3, 5, 7 (day-of-week-used: [2, 4, 6])
                    return ![2, 4, 6].includes(date.getDay());
                }
            ],
            maxDate: 'today',
            onChange: function(selectedDates, dateStr) {
                if (!dateStr) return;
                // Chuyển đổi dateStr từ dd-mm-yyyy sang yyyy-mm-dd
                const [day, month, year] = dateStr.split('-');
                const apiDate = `${year}-${month}-${day}`;
                handleDateSelection(apiDate);
            }
        });
    }

    // Xử lý nút "Xem thêm kết quả"
    const loadMoreBtn = document.querySelector('.js-btn-load-more-power');
    // const resultsContainer = document.querySelector('.js-results-tbody');

    if (loadMoreBtn && resultsContainer) {
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.getAttribute('data-page'));
            const limit = parseInt(this.getAttribute('data-limit'));

            fetch(`/vietlott655/results?page=${page}&limit=${limit}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach(result => {
                            const numbers = result.numbers.split(',');
                            const mainNumbers = numbers.slice(0, 6);
                            const bonusNumber = numbers[numbers.length - 1];
                            let html = `
                        <tr>
                            <td>
                                <a title="Kết quả xổ số Vietlott Power 6/55 ngày ${new Date(result.draw_date).toLocaleDateString('vi-VN')}"
                                    href="/ket-qua-xoso-power-6-55-vietlott-${new Date(result.draw_date).toLocaleDateString('vi-VN').replace(/\//g, '-')}"
                                    class="text-decoration-none">
                                    ${new Date(result.draw_date).toLocaleDateString('vi-VN')}
                                </a>
                            </td>
                            <td>
                                <div class="balls">
                                    ${mainNumbers.map(number => `<span class="ball power me-1">${number.padStart(2, '0')}</span>`).join('')}
                                    <span class="ball green">${bonusNumber.padStart(2, '0')}</span>
                                </div>
                            </td>
                            <td class="text-end"><span class="text-nowrap">${new Intl.NumberFormat('vi-VN').format(result.jackpot_amount)}</span></td>
                            <td class="text-end"><span class="text-nowrap">${new Intl.NumberFormat('vi-VN').format(result.jackpot_2)}</span></td>
                        </tr>
                    `;
                            resultsContainer.insertAdjacentHTML('beforeend', html);
                        });
                        this.setAttribute('data-page', page + 1);
                    } else {
                        this.disabled = true;
                        this.innerHTML = 'Không còn kết quả để tải';
                    }
                })
                .catch(error => {
                    console.error('Lỗi khi tải thêm kết quả:', error);
                });
        });
    } else {
        console.error('Không tìm thấy nút load more hoặc container kết quả');
    }

    // Xử lý click vào các ngày trong danh sách
    document.addEventListener('click', function(event) {
        const link = event.target.closest('.js-power-date-link');
        if (link) {
            // Cho phép link hoạt động bình thường để có thể bookmark và share
            // event.preventDefault();
            // const selectedDate = link.getAttribute('data-date');
            // handleDateSelection(selectedDate);
        }
    });
});
