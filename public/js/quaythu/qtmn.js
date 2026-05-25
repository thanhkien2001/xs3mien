const SCHEDULE_MN = {
        1: ["Cà Mau", "Hồ Chí Minh", "Đồng Tháp"],
        2: ["Bạc Liêu", "Bến Tre", "Vũng Tàu"],
        3: ["Cần Thơ", "Sóc Trăng", "Đồng Nai"],
        4: ["An Giang", "Bình Thuận", "Tây Ninh"],
        5: ["Bình Dương", "Trà Vinh", "Vĩnh Long"],
        6: ["Bình Phước", "Hậu Giang", "Hồ Chí Minh", "Long An"],
        0: ["Kiên Giang", "Tiền Giang", "Đà Lạt"]
    };

    const PAGE_ID_BY_PROV = {
        "Bình Phước": 84,
        "Hậu Giang": 86,
        "Hồ Chí Minh": 74,
        "Long An": 90,
        "An Giang": 81,
        "Bình Dương": 75,
        "Bình Thuận": 72,
        "Bạc Liêu": 73,
        "Bến Tre": 76,
        "Cà Mau": 77,
        "Cần Thơ": 78,
        "Đà Lạt": 79,
        "Đồng Nai": 80,
        "Đồng Tháp": 82,
        "Kiên Giang": 83,
        "Sóc Trăng": 85,
        "Tây Ninh": 87,
        "Tiền Giang": 88,
        "Trà Vinh": 89,
        "Vĩnh Long": 91,
        "Vũng Tàu": 92
    };

    const todayVN = () => new Date().toLocaleDateString('vi-VN');
    const randInt = (a, b) => Math.floor(Math.random() * (b - a + 1)) + a;
    const randN = n => String(randInt(0, 10 ** n - 1)).padStart(n, '0');

    const PRIZES_SM = [{
            label: 'Giải 8',
            id_giai: 9,
            count: 1,
            digits: 2,
            cls: 'lottery-number big util-text-red'
        },
        {
            label: 'Giải 7',
            id_giai: 8,
            count: 1,
            digits: 3,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 6',
            id_giai: 7,
            count: 3,
            digits: 4,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 5',
            id_giai: 6,
            count: 1,
            digits: 4,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 4',
            id_giai: 5,
            count: 7,
            digits: 5,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 3',
            id_giai: 4,
            count: 2,
            digits: 5,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 2',
            id_giai: 3,
            count: 1,
            digits: 5,
            cls: 'lottery-number'
        },
        {
            label: 'Giải 1',
            id_giai: 2,
            count: 1,
            digits: 5,
            cls: 'lottery-number'
        },
        {
            label: 'Giải ĐB',
            id_giai: 1,
            count: 1,
            digits: 6,
            cls: 'lottery-number medium util-text-red'
        },
    ];

    let DRAW_TOKEN = 0;
    let CURRENT_KQ_IDS = [];
    let CURRENT_PROVINCES = [];

    function buildCombinedTable(provinces) {
        const wrap = document.querySelector('.js-wrap-content-quay-thu');
        if (!wrap) return;
        DRAW_TOKEN++;
        CURRENT_PROVINCES = [...provinces];
        CURRENT_KQ_IDS = provinces.map(() => Math.floor(Math.random() * 90000) + 1000);
        const dateStr = todayVN();
        const isSingle = provinces.length === 1;
        const listText = provinces.join(', ');
        const titleText = isSingle ?
            `Bảng quay thử ${provinces[0]} ngày ${dateStr}` :
            `Bảng quay thử XSMN ngày ${dateStr}`;
        const subLottoText = isSingle ? provinces[0] : 'Miền Nam';
        const theadCols = provinces.map(p =>
            `<th class="util-bg-body-secondary util-font-normal"><a href="#" title="Kết quả xổ số ${p}">${p}</a></th>`
        ).join('');
        const bodyRows = PRIZES_SM.map(pr => {
            const tds = provinces.map((p, colIdx) => {
                const pageId = PAGE_ID_BY_PROV[p] || 70;
                const kqId = CURRENT_KQ_IDS[colIdx];
                const spans = Array.from({
                    length: pr.count
                }, (_, i) => `
        <span class="${kqId} ${pr.cls} loadingjs"
              data-page-id="${pageId}" data-id-giai="${pr.id_giai}" data-num=""
              id="number_${kqId}${pr.id_giai}${i}" has-animate="1">...</span>
      `).join('');
                return `<td>${spans}</td>`;
            }).join('');
            return `<tr><td class="util-small">${pr.label}</td>${tds}</tr>`;
        }).join('');

        const lottoHead = provinces.map(p => `<th>${p}</th>`).join('');
        const lottoRows = [...Array(10)].map((_, d) => `
    <tr>
      <td><span class="util-text-red util-font-bold js-hl-number" data-highlight-number="${d}">${d}</span></td>
      ${provinces.map(p=>`<td><div class="js-number-dau-duoi" data-find="dau" data-page-id="${PAGE_ID_BY_PROV[p]||70}" data-num="${d}"></div></td>`).join('')}
    </tr>
  `).join('');
        wrap.innerHTML = `
    <div class="lottery-result">
      <h2 class="util-bg-orange util-p-2 util-px-3 util-fs-6 util-mb-0 util-text-uppercase util-lh-base">
        ${titleText}${!isSingle ? `</br><span class="util-font-normal util-text-capitalize">${listText}</span>` : ''}
      </h2>
      <div class="comp-table-responsive util-p-0 js-table-quay-thu-live js-table-result" data-ketqua-id="${CURRENT_KQ_IDS.join(',')}">
        <table class="comp-table util-table-bordered util-align-items-center lottery-table quaythu js-lottery-kq-table">
          <thead class="util-align-items-center">
            <tr>
              <th class="util-small util-bg-body-secondary util-font-normal">Giải</th>
              ${theadCols}
            </tr>
          </thead>
          <tbody>${bodyRows}</tbody>
        </table>

        <p class="util-bg-primary-subtle util-p-2 util-px-3 util-mb-0 util-font-medium util-text-center">Lô tô quay thử ${subLottoText}</p>
        <div class="layout-row util-gx-0">
          <table class="comp-table util-table-bordered util-fs-8 util-align-items-center util-text-center">
            <thead class="util-align-items-center"><tr><th>Đầu</th>${lottoHead}</tr></thead>
            <tbody>${lottoRows}</tbody>
          </table>
        </div>
      </div>
    </div>`;
    }

    function pushDauDuoi(pageId, num) {
        const last2 = num.slice(-2);
        const d = +last2[0];
        const el = document.querySelector(`.js-number-dau-duoi[data-find="dau"][data-page-id="${pageId}"][data-num="${d}"]`);
        if (!el) return;
        el.textContent = el.textContent ? (el.textContent + ', ' + last2) : last2;
    }

    function resetCombinedUI() {
        const table = document.querySelector('.js-table-result');
        if (!table) return;
        table.classList.remove('complete');
        table.querySelectorAll('.lottery-number').forEach(el => {
            el.textContent = '...';
            el.removeAttribute('data-num');
        });
        table.querySelectorAll('.js-number-dau-duoi').forEach(el => {
            el.textContent = '';
        });
    }

    function setButtonsDisabled(disabled, txt = 'Đang quay...') {
        const btns = [
            document.querySelector('.js-btn-quay-thu-detail'),
            document.querySelector('.js-btn-quay-thu-nhanh')
        ].filter(Boolean);
        btns.forEach(b => {
            if (disabled) {
                if (!b.dataset.orig) b.dataset.orig = b.textContent.trim();
                b.textContent = txt;
                b.disabled = true;
                b.classList.add('disabled');
            } else {
                b.textContent = b.dataset.orig || b.textContent;
                b.disabled = false;
                b.classList.remove('disabled');
            }
        });
    }
    async function startDraw({
        fast = false
    } = {}) {
        const myToken = ++DRAW_TOKEN;
        resetCombinedUI();
        setButtonsDisabled(true);

        const spin = fast ? 300 : 1000;
        const gap = fast ? 100 : 400;

        try {
            for (const pr of PRIZES_SM) {
                for (let col = 0; col < CURRENT_PROVINCES.length; col++) {
                    const kqId = CURRENT_KQ_IDS[col];
                    const pageId = PAGE_ID_BY_PROV[CURRENT_PROVINCES[col]] || 70;

                    for (let i = 0; i < pr.count; i++) {
                        if (myToken !== DRAW_TOKEN) return;
                        const el = document.getElementById(`number_${kqId}${pr.id_giai}${i}`);
                        if (!el) continue;

                        const t0 = performance.now();
                        await new Promise(res => {
                            function tick(now) {
                                if (myToken !== DRAW_TOKEN) return res();
                                if (now - t0 >= spin) return res();
                                el.textContent = randN(pr.digits);
                                setTimeout(() => requestAnimationFrame(tick), 150);
                            }
                            requestAnimationFrame(tick);
                        });

                        if (myToken !== DRAW_TOKEN) return;
                        const final = randN(pr.digits);
                        el.textContent = final;
                        el.setAttribute('data-num', final);
                        pushDauDuoi(pageId, final);

                        await new Promise(r => setTimeout(r, gap));
                    }
                }
            }
            if (myToken === DRAW_TOKEN) {
                document.querySelector('.js-table-result')?.classList.add('complete');
            }
        } finally {
            if (myToken === DRAW_TOKEN) setButtonsDisabled(false);
        }
    }

    function setupDropdown() {
        const sel = document.querySelector('.js-select-other-day');
        sel?.addEventListener('change', e => {
            const opt = e.target.selectedOptions?.[0];
            const v = opt?.value?.trim();
            const lbl = opt?.textContent?.trim();

            // Chọn Miền → điều hướng
            if (v && v.startsWith('/')) {
                window.location.href = v;
                return;
            }
            if (lbl && PAGE_ID_BY_PROV[lbl]) {
                buildCombinedTable([lbl]);
            }
        });
    }
    document.addEventListener('DOMContentLoaded', () => {
        const wd = new Date().getDay();
        let list = SCHEDULE_MN[wd] || [];

        // Nếu có provinceCode từ controller, sử dụng tỉnh đó
        if (typeof window.provinceCode !== 'undefined') {
            const provinceMapping = {
                'xsag': 'An Giang',
                'xshcm': 'Hồ Chí Minh',
                'xstn': 'Tây Ninh',
                'xsvl': 'Vĩnh Long',
                'xsbd': 'Bình Dương',
                'xsdt': 'Đồng Tháp',
                'xsdn': 'Đồng Nai',
                'xsbt': 'Bến Tre',
                'xsbp': 'Bình Phước',
                'xstg': 'Tiền Giang',
                'xsbl': 'Bạc Liêu',
                'xsbth': 'Bình Thuận',
                'xscm': 'Cà Mau',
                'xsct': 'Cần Thơ',
                'xshg': 'Hậu Giang',
                'xskg': 'Kiên Giang',
                'xsla': 'Long An',
                'xsdl': 'Đà Lạt',
                'xsst': 'Sóc Trăng',
                'xstv': 'Trà Vinh',
                'xsvt': 'Vũng Tàu'
            };

            if (provinceMapping[window.provinceCode]) {
                list = [provinceMapping[window.provinceCode]];
            }
        }

        buildCombinedTable(list);
        document.querySelector('.js-btn-quay-thu-detail')?.addEventListener('click', () => startDraw({
            fast: false
        }));
        document.querySelector('.js-btn-quay-thu-nhanh')?.addEventListener('click', () => startDraw({
            fast: true
        }));
        setupDropdown();
        const typeSel = document.querySelector('.js-select-type-quay-thu');
        const bdWrap = document.querySelector('.js-birthday-select-wrap');
        typeSel?.addEventListener('change', () => bdWrap?.classList.toggle('hidden', typeSel.value !== 'birthday'));
    });