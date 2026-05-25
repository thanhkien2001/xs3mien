const SCHEDULE_MT = {
        1: ["Huế", "Phú Yên"],
        2: ["Đắk Lắk", "Quảng Nam"],
        3: ["Đà Nẵng", "Khánh Hòa"],
        4: ["Bình Định", "Quảng Trị", "Quảng Bình"],
        5: ["Gia Lai", "Ninh Thuận"],
        6: ["Đà Nẵng", "Quảng Ngãi", "Đắk Nông"],
        0: ["Khánh Hòa", "Kon Tum", "Huế"]
    };

    const PAGE_ID_BY_PROV_MT = {
        "Bình Định": 171,
        "Gia Lai": 172,
        "Khánh Hòa": 173,
        "Kon Tum": 174,
        "Ninh Thuận": 175,
        "Phú Yên": 176,
        "Quảng Bình": 177,
        "Quảng Nam": 178,
        "Quảng Trị": 179,
        "Huế": 180,
        "Đắk Nông": 181,
        "Quảng Ngãi": 182,
        "Đà Nẵng": 183,
        "Đắk Lắk": 184
    };

    const ALIASES_MT = {
        "kontum": "Kon Tum",
        "kon tum": "Kon Tum",
        "da nang": "Đà Nẵng",
        "dak lak": "Đắk Lắk",
        "dak nong": "Đắk Nông",
        "khanh hoa": "Khánh Hòa",
        "thua thien hue": "Huế",
        "hue": "Huế",
    };
    const rmAccents = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const norm = s => rmAccents(String(s || '').toLowerCase().trim().replace(/\s+/g, ' '));

    function resolveProvinceName(label) {
        const n = norm(label);
        if (ALIASES_MT[n]) return ALIASES_MT[n];
        for (const key of Object.keys(PAGE_ID_BY_PROV_MT)) {
            if (norm(key) === n) return key;
        }
        return label;
    }

    const todayVN = () => new Date().toLocaleDateString('vi-VN');
    const randInt = (a, b) => Math.floor(Math.random() * (b - a + 1)) + a;
    const randN = n => String(randInt(0, 10 ** n - 1)).padStart(n, '0');
    const PRIZES_MT = [{
            label: 'G.8',
            id_giai: 9,
            count: 1,
            digits: 2,
            cls: 'number big text-red'
        },
        {
            label: 'G.7',
            id_giai: 8,
            count: 1,
            digits: 3,
            cls: 'number'
        },
        {
            label: 'G.6',
            id_giai: 7,
            count: 3,
            digits: 4,
            cls: 'number'
        },
        {
            label: 'G.5',
            id_giai: 6,
            count: 1,
            digits: 4,
            cls: 'number'
        },
        {
            label: 'G.4',
            id_giai: 5,
            count: 7,
            digits: 5,
            cls: 'number'
        },
        {
            label: 'G.3',
            id_giai: 4,
            count: 2,
            digits: 5,
            cls: 'number'
        },
        {
            label: 'G.2',
            id_giai: 3,
            count: 1,
            digits: 5,
            cls: 'number'
        },
        {
            label: 'G.1',
            id_giai: 2,
            count: 1,
            digits: 5,
            cls: 'number'
        },
        {
            label: 'G.ĐB',
            id_giai: 1,
            count: 1,
            digits: 6,
            cls: 'number medium text-red'
        },
    ];

    let DRAW_TOKEN = 0;
    let CURRENT_KQ_IDS = [];
    let CURRENT_PROVINCES = [];

    function buildCombinedTableMT(provinces) {
        const wrap = document.querySelector('.js-wrap-content-quay-thu');
        if (!wrap) return;
        const fixed = provinces.map(resolveProvinceName).filter(p => PAGE_ID_BY_PROV_MT[p]);
        if (fixed.length === 0) return;

        DRAW_TOKEN++;
        CURRENT_PROVINCES = [...fixed];
        CURRENT_KQ_IDS = fixed.map(() => Math.floor(Math.random() * 90000) + 1000);

        const dateStr = todayVN();
        const isSingle = fixed.length === 1;
        const listText = fixed.join(', ');
        const titleText = isSingle ? `Bảng quay thử ${fixed[0]} ngày ${dateStr}` :
            `Bảng quay thử XSMT ngày ${dateStr}`;
        const subLottoText = isSingle ? fixed[0] : 'Miền Trung';

        const theadCols = fixed.map(p =>
            `<th class="bg-body-secondary fw-normal"><a href="#" title="Kết quả xổ số ${p}">${p}</a></th>`
        ).join('');

        const bodyRows = PRIZES_MT.map(pr => {
            const tds = fixed.map((p, colIdx) => {
                const pageId = PAGE_ID_BY_PROV_MT[p] || 170;
                const kqId = CURRENT_KQ_IDS[colIdx];
                const spans = Array.from({
                    length: pr.count
                }, (_, i) => `
          <span class="${kqId} ${pr.cls}"
                data-page-id="${pageId}" data-id-giai="${pr.id_giai}" data-num=""
                id="number_${kqId}${pr.id_giai}${i}" has-animate="1">...</span>
        `).join('');
                return `<td>${spans}</td>`;
            }).join('');
            return `<tr><td class="small">${pr.label}</td>${tds}</tr>`;
        }).join('');

        const lottoHead = fixed.map(p => `<th>${p}</th>`).join('');
        const lottoRows = [...Array(10)].map((_, d) => `
      <tr>
        <td><span class="text-red fw-bold js-hl-number" data-highlight-number="${d}">${d}</span></td>
        ${fixed.map(p =>
          `<td><div class="js-number-dau-duoi" data-find="dau" data-page-id="${PAGE_ID_BY_PROV_MT[p]||170}" data-num="${d}"></div></td>`
        ).join('')}
      </tr>
    `).join('');

        wrap.innerHTML = `
      <div class="kqxs">
        <h2 class="bg-orange p-2 px-3 fs-6 mb-0 text-uppercase lh-base">
          ${titleText}${!isSingle ? `</br><span class="fw-normal text-capitalize">${listText}</span>` : ''}
        </h2>
        <div class="table-responsive p-0 js-table-quay-thu-live js-table-result" data-ketqua-id="${CURRENT_KQ_IDS.join(',')}">
          <table class="table table-bordered align-middle kq-table quaythu js-kq-table">
            <thead class="align-middle">
              <tr>
                <th class="small bg-body-secondary fw-normal">Giải</th>
                ${theadCols}
              </tr>
            </thead>
            <tbody>${bodyRows}</tbody>
          </table>

          <p class="bg-primary-subtle p-2 px-3 mb-0 fw-medium text-center">Lô tô quay thử ${subLottoText}</p>
          <div class="row gx-0">
            <table class="table table-bordered fs-8 align-middle text-center">
              <thead class="align-middle"><tr><th>Đầu</th>${lottoHead}</tr></thead>
              <tbody>${lottoRows}</tbody>
            </table>
          </div>
        </div>
      </div>`;
    }

    /* ===== Lô tô đầu ===== */
    function pushDauDuoiMT(pageId, num) {
        const last2 = num.slice(-2);
        const d = +last2[0];
        const el = document.querySelector(`.js-number-dau-duoi[data-find="dau"][data-page-id="${pageId}"][data-num="${d}"]`);
        if (!el) return;
        el.textContent = el.textContent ? (el.textContent + ', ' + last2) : last2;
    }

    /* ===== Reset UI ===== */
    function resetCombinedUIMT() {
        const table = document.querySelector('.js-table-quay-thu-live'); // phạm vi đúng bảng
        if (!table) return;

        table.classList.remove('complete');
        table.querySelectorAll('.number').forEach(el => {
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
    async function startDrawMT({
        fast = false
    } = {}) {
        const myToken = ++DRAW_TOKEN;
        resetCombinedUIMT();
        setButtonsDisabled(true);

        const spin = fast ? 300 : 1000;
        const gap = fast ? 100 : 400;

        try {
            for (const pr of PRIZES_MT) {
                for (let col = 0; col < CURRENT_PROVINCES.length; col++) {
                    const kqId = CURRENT_KQ_IDS[col];
                    const pageId = PAGE_ID_BY_PROV_MT[CURRENT_PROVINCES[col]] || 170;

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
                        pushDauDuoiMT(pageId, final);

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

    function setupDropdownMT() {
        const sel = document.querySelector('.js-select-other-day');
        if (!sel) return;

        function onChange(e) {
            const opt = e.target.options[e.target.selectedIndex];
            if (!opt) return;
            const value = (opt.value || '').trim();
            const label = resolveProvinceName((opt.textContent || '').trim());
            if (value && value.startsWith('/')) {
                window.location.href = value;
                return;
            }
            if (PAGE_ID_BY_PROV_MT[label]) {
                buildCombinedTableMT([label]);
            }
        }
        sel.removeEventListener('change', onChange);
        sel.addEventListener('change', onChange);
        sel.removeEventListener('input', onChange);
        sel.addEventListener('input', onChange);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const wd = new Date().getDay();
        let list = (SCHEDULE_MT[wd] || []).map(resolveProvinceName);

        // Nếu có provinceCode từ controller, sử dụng tỉnh đó
        if (typeof window.provinceCode !== 'undefined') {
            const provinceMapping = {
                'xsbdinh': 'Bình Định',
                'xsgl': 'Gia Lai',
                'xskh': 'Khánh Hòa',
                'xskt': 'Kon Tum',
                'xsnt': 'Ninh Thuận',
                'xspy': 'Phú Yên',
                'xsqb': 'Quảng Bình',
                'xsqn': 'Quảng Nam',
                'xsqtri': 'Quảng Trị',
                'xsh': 'Huế',
                'xsdnong': 'Đắk Nông',
                'xsqng': 'Quảng Ngãi',
                'xsdng': 'Đà Nẵng',
                'xsdlk': 'Đắk Lắk'
            };

            if (provinceMapping[window.provinceCode]) {
                list = [provinceMapping[window.provinceCode]];
            }
        }

        buildCombinedTableMT(list.filter(p => PAGE_ID_BY_PROV_MT[p])); // render theo lịch hôm nay

        document.querySelector('.js-btn-quay-thu-detail')?.addEventListener('click', () => startDrawMT({
            fast: false
        }));
        document.querySelector('.js-btn-quay-thu-nhanh')?.addEventListener('click', () => startDrawMT({
            fast: true
        }));

        setupDropdownMT();

        const typeSel = document.querySelector('.js-select-type-quay-thu');
        const bdWrap = document.querySelector('.js-birthday-select-wrap');
        typeSel?.addEventListener('change', () => bdWrap?.classList.toggle('hidden', typeSel.value !== 'birthday'));
    });