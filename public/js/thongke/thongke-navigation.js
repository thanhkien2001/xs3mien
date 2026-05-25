(function () {
    'use strict';

    // Mapping province slug cho các trang thống kê
    const provinceSlugMap = {
        // Miền Nam
        '7': 'xshcm', '11': 'xsvt', '19': 'xsvl', '20': 'xsbd', '21': 'xstv',
        '22': 'xsla', '26': 'xsdl', '23': 'xsbp', '24': 'xshg', '25': 'xskg',
        '27': 'xstg', '16': 'xstn', '8': 'xsdt', '17': 'xsag', '10': 'xsbt',
        '18': 'xsbth', '13': 'xsdn', '12': 'xsbl', '14': 'xsct', '15': 'xsst', '9': 'xscm',
        // Miền Trung
        '28': 'xsdng', '31': 'xsbdinh', '35': 'xstth', '38': 'xsdlk', '30': 'xsqng',
        '37': 'xsqt', '36': 'xsqb', '29': 'xsqn', '41': 'xsdno', '39': 'xsgl',
        '40': 'xsnt', '32': 'xspy', '33': 'xskh', '34': 'xs-kontum',
    };

    const provinceNameMap = {
        // Miền Nam
        '7': 'TP. Hồ Chí Minh', '11': 'Vũng Tàu', '19': 'Vĩnh Long', '20': 'Bình Dương',
        '21': 'Trà Vinh', '22': 'Long An', '26': 'Đồng Nai', '23': 'Bình Phước',
        '24': 'Hậu Giang', '25': 'Kiên Giang', '27': 'Tiền Giang', '16': 'Tây Ninh',
        '8': 'Đồng Tháp', '17': 'An Giang', '10': 'Bến Tre', '18': 'Bình Thuận',
        '13': 'Đồng Nai', '12': 'Bạc Liêu', '14': 'Cần Thơ', '15': 'Sóc Trăng', '9': 'Cà Mau',
        // Miền Trung
        '28': 'Đà Nẵng', '31': 'Bình Định', '35': 'Thừa Thiên Huế', '38': 'Đắk Lắk',
        '30': 'Quảng Ngãi', '37': 'Quảng Trị', '36': 'Quảng Bình', '29': 'Quảng Nam',
        '41': 'Đắk Nông', '39': 'Gia Lai', '40': 'Ninh Thuận', '32': 'Phú Yên',
        '33': 'Khánh Hòa', '34': 'Kon Tum',
    };

    // Xác định loại trang từ URL hiện tại
    function getCurrentPageType() {
        const path = window.location.pathname;
        if (path.includes('lo-gan')) return 'logan';
        if (path.includes('dac-biet')) return 'dacbiet';
        if (path.includes('dau-duoi')) return 'dauduoi';
        if (path.includes('tan-suat')) return 'tansuat';
        return 'thongke';
    }

    // Tạo URL mới dựa trên province_id hoặc region
    function generateNewUrl(provinceValue, pageType) {
        // Nếu là region mode (REGION:XSMN, REGION:XSMT, REGION:XSMB)
        if (provinceValue.startsWith('REGION:')) {
            const region = provinceValue.replace('REGION:', '').toLowerCase();
            const urlMap = {
                'xsmn': {
                    'thongke': '/thong-ke-xo-so-mien-nam-tk-xsmn.html',
                    'logan': '/thong-ke-lo-gan-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
                    'dacbiet': '/thong-ke-dac-biet-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
                    'dauduoi': '/thong-ke-dau-duoi-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html',
                    'tansuat': '/thong-ke-tan-suat-xsmn-thong-ke-xo-so-mien-nam-tk-xsmn.html'
                },
                'xsmt': {
                    'thongke': '/thong-ke-xo-so-mien-trung-tk-xsmt.html',
                    'logan': '/thong-ke-lo-gan-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
                    'dacbiet': '/thong-ke-dac-biet-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
                    'dauduoi': '/thong-ke-dau-duoi-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html',
                    'tansuat': '/thong-ke-tan-suat-xsmt-thong-ke-xo-so-mien-trung-tk-xsmt.html'
                },
                'xsmb': {
                    'thongke': '/thong-ke-xo-so-mien-bac-tk-xsmb.html',
                    'logan': '/lo-gan-xsmb.html',
                    'dacbiet': '/dac-biet-xsmb.html',
                    'dauduoi': '/',
                    'tansuat': '/tan-suat-lo-to-xsmb.html'
                }
            };
            return urlMap[region] ? urlMap[region][pageType] : null;
        }

        // Nếu là tỉnh cụ thể
        const slug = provinceSlugMap[provinceValue];
        if (!slug) return null;

        const urlPatterns = {
            'thongke': `/thong-ke-${slug}.html`,
            'logan': `/thong-ke-lo-gan-${slug}.html`,
            'dacbiet': `/thong-ke-dac-biet-${slug}.html`,
            'dauduoi': `/thong-ke-dau-duoi-${slug}.html`,
            'tansuat': `/thong-ke-tan-suat-${slug}.html`
        };

        return urlPatterns[pageType] || null;
    }

    // Update URL và title khi chọn tỉnh
    function updateUrlAndTitle(provinceValue, provinceName, pageType) {
        const newUrl = generateNewUrl(provinceValue, pageType);
        if (!newUrl) return;

        // Update browser URL without reload
        if (window.history && window.history.pushState) {
            window.history.pushState({ provinceValue: provinceValue }, '', newUrl);
        }

        // Update page title (optional - có thể bỏ comment nếu muốn)
        // const pageTitles = {
        //     'thongke': `Thống kê ${provinceName}`,
        //     'logan': `Lô gan ${provinceName}`,
        //     'dacbiet': `Thống kê đặc biệt ${provinceName}`,
        //     'dauduoi': `Thống kê đầu đuôi ${provinceName}`,
        //     'tansuat': `Tần suất ${provinceName}`
        // };
        // document.title = pageTitles[pageType] || document.title;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.js-thong-ke-redirect-form');
        if (!form) return;

        const provinceSelect = form.querySelector('select[name="province_id"]');
        if (!provinceSelect) return;

        // Lưu form action gốc
        const originalAction = form.action || window.location.pathname;
        const pageType = getCurrentPageType();

        // Xử lý khi chọn tỉnh
        provinceSelect.addEventListener('change', function (e) {
            const selectedValue = this.value;
            const selectedText = this.options[this.selectedIndex].text;

            // Update URL
            updateUrlAndTitle(selectedValue, selectedText, pageType);

            // Submit form để load dữ liệu mới
            form.submit();
        });

        // Xử lý khi thay đổi số lần quay
        const totalDaySelect = form.querySelector('select[name="total_day"]');
        if (totalDaySelect) {
            totalDaySelect.addEventListener('change', function () {
                form.submit();
            });
        }

        // Handle popstate (back/forward buttons)
        window.addEventListener('popstate', function (event) {
            // Reload page when using browser back/forward
            window.location.reload();
        });
    });
})();

