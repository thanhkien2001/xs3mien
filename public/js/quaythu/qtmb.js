const SCHEDULE = {
  MB: {
    1: "Thủ Đô Hà Nội",
    2: "Quảng Ninh",
    3: "Bắc Ninh",
    4: "Thủ Đô Hà Nội",
    5: "Hải Phòng",
    6: "Nam Định",
    0: "Thái Bình",
  },
  MN: {
    1: ["Cà Mau", "Hồ Chí Minh", "Đồng Tháp"],
    2: ["Bạc Liêu", "Bến Tre", "Vũng Tàu"],
    3: ["Cần Thơ", "Sóc Trăng", "Đồng Nai"],
    4: ["An Giang", "Bình Thuận", "Tây Ninh"],
    5: ["Bình Dương", "Trà Vinh", "Vĩnh Long"],
    6: ["Bình Phước", "Hậu Giang", "Hồ Chí Minh", "Long An"],
    0: ["Kiên Giang", "Tiền Giang", "Đà Lạt"],
  },
  MT: {
    1: ["Huế", "Phú Yên"],
    2: ["Đắk Lắk", "Quảng Nam"],
    3: ["Đà Nẵng", "Khánh Hòa"],
    4: ["Bình Định", "Quảng Trị", "Quảng Bình"],
    5: ["Gia Lai", "Ninh Thuận"],
    6: ["Đà Nẵng", "Quảng Ngãi", "Đắk Nông"],
    0: ["Khánh Hòa", "Kon Tum", "Huế"],
  },
};

const PROVINCE_TO_REGION = {
  // MB
  "Thủ Đô Hà Nội": "MB",
  "Quảng Ninh": "MB",
  "Bắc Ninh": "MB",
  "Hải Phòng": "MB",
  "Nam Định": "MB",
  "Thái Bình": "MB",
  // MN
  "An Giang": "MN",
  "Hồ Chí Minh": "MN",
  "Tây Ninh": "MN",
  "Vĩnh Long": "MN",
  "Bình Dương": "MN",
  "Đồng Tháp": "MN",
  "Đồng Nai": "MN",
  "Bến Tre": "MN",
  "Bình Phước": "MN",
  "Tiền Giang": "MN",
  "Bạc Liêu": "MN",
  "Bình Thuận": "MN",
  "Cà Mau": "MN",
  "Cần Thơ": "MN",
  "Hậu Giang": "MN",
  "Kiên Giang": "MN",
  "Long An": "MN",
  "Đà Lạt": "MN",
  "Sóc Trăng": "MN",
  "Trà Vinh": "MN",
  "Vũng Tàu": "MN",
  // MT
  "Bình Định": "MT",
  "Gia Lai": "MT",
  "Khánh Hòa": "MT",
  "Kon Tum": "MT",
  "Ninh Thuận": "MT",
  "Phú Yên": "MT",
  "Quảng Bình": "MT",
  "Quảng Nam": "MT",
  "Quảng Trị": "MT",
  "Huế": "MT",
  "Đắk Nông": "MT",
  "Quảng Ngãi": "MT",
  "Đà Nẵng": "MT",
  "Đắk Lắk": "MT",
};

const todayVN = () => new Date().toLocaleDateString("vi-VN");
const randInt = (a, b) => Math.floor(Math.random() * (b - a + 1)) + a;
const randN = (n) => String(randInt(0, 10 ** n - 1)).padStart(n, "0");

const ENGINES = {
  MB: {
    pageId: 160,
    header: (p, d) => `Bảng quay thử XSMB – ${p} ngày ${d}`,
    TABLE: [
      {
        label: "Giải 1",
        id_giai: 2,
        count: 1,
        digits: 5,
        w: "util-w-100",
        big: false,
        red: false,
      },
      {
        label: "Giải 2",
        id_giai: 3,
        count: 2,
        digits: 5,
        w: "util-w-50",
        big: false,
        red: false,
      },
      {
        label: "Giải 3",
        id_giai: 4,
        count: 6,
        digits: 5,
        w: "util-w-33",
        big: false,
        red: false,
      },
      {
        label: "Giải 4",
        id_giai: 5,
        count: 4,
        digits: 4,
        w: "util-w-25",
        big: false,
        red: false,
      },
      {
        label: "Giải 5",
        id_giai: 6,
        count: 6,
        digits: 4,
        w: "util-w-33",
        big: false,
        red: false,
      },
      {
        label: "Giải 6",
        id_giai: 7,
        count: 3,
        digits: 3,
        w: "util-w-33",
        big: false,
        red: false,
      },
      {
        label: "Giải 7",
        id_giai: 8,
        count: 4,
        digits: 2,
        w: "util-w-25",
        big: true,
        red: true,
      },
      {
        label: "Giải ĐB",
        id_giai: 1,
        count: 1,
        digits: 5,
        w: "util-w-100",
        big: true,
        red: true,
      },
    ],
  },
};
let CURRENT = {
  region: "MB",
  province: "Thủ Đô Hà Nội",
  pageId: 160,
  table: ENGINES.MB.TABLE,
};
let KQ_ID = Math.floor(Math.random() * 90000) + 1000;
let DRAW_TOKEN = 0;

function resetDauDuoiIn(container) {
  for (let i = 0; i < 10; i++) {
    container
      .querySelector(`.js-number-dau-duoi[data-find="dau"][data-num="${i}"]`)
      ?.replaceChildren();
    container
      .querySelector(`.js-number-dau-duoi[data-find="duoi"][data-num="${i}"]`)
      ?.replaceChildren();
  }
}

function pushDauDuoiIn(container, num) {
  const last2 = num.slice(-2);
  const d = +last2[0],
    u = +last2[1];
  const dEl = container.querySelector(
    `.js-number-dau-duoi[data-find="dau"][data-num="${d}"]`
  );
  const uEl = container.querySelector(
    `.js-number-dau-duoi[data-find="duoi"][data-num="${u}"]`
  );
  if (dEl)
    dEl.textContent = (dEl.textContent ? dEl.textContent + ", " : "") + last2;
  if (uEl)
    uEl.textContent = (uEl.textContent ? uEl.textContent + ", " : "") + last2;
}

function buildSkeletonMB(province) {
  const wrap = document.querySelector(".js-wrap-content-quay-thu");
  if (!wrap) return;
  const dateStr = todayVN();
  const maDB = "4NR-14NR-5NR-10NR-2NR-15NR";
  const rows = ENGINES.MB.TABLE.map((p) => {
    const spans = Array.from(
      {
        length: p.count,
      },
      (_, i) => {
        const cls = ["lottery-number", String(KQ_ID)];
        if (p.w) cls.push(p.w);
        if (p.big) cls.push("big");
        if (p.red) cls.push("util-text-red");
        return `<span class="${cls.join(" ")}" data-page-id="${
          ENGINES.MB.pageId
        }" data-id-giai="${p.id_giai}" id="number_${KQ_ID}${
          p.id_giai
        }${i}">...</span>`;
      }
    ).join("");
    return `<tr><td class="util-small util-text-nowrap">${p.label}</td><td><div class="util-flex util-flex-wrap util-justify-center util-gap-2">${spans}</div></td></tr>`;
  }).join("");
  const dauDuoiRows = [...Array(10)]
    .map(
      (_, i) => `
    <tr>
      <td><span class="util-text-red util-font-bold js-hl-number" data-highlight-number="${i}">${i}</span></td>
      <td><div class="js-number-dau-duoi" data-find="dau"  data-num="${i}"></div></td>
      <td><div class="js-number-dau-duoi" data-find="duoi" data-num="${i}"></div></td>
      <td><span class="util-text-red util-font-bold js-hl-number" data-highlight-number="${i}">${i}</span></td>
    </tr>`
    )
    .join("");

  wrap.innerHTML = `
    <div class="lottery-result js-table-quay-thu-live js-table-result" data-ketqua-id="${KQ_ID}" id="id_ketqua_${KQ_ID}">
      <h2 class="util-bg-orange util-p-2 util-px-3 util-fs-6 util-mb-0 util-text-uppercase util-lh-base">${ENGINES.MB.header(
        province,
        dateStr
      )}</h2>
      <table class="comp-table util-align-items-center lottery-table xsmb js-lottery-kq-table">
        <tbody>
          <tr><th class="util-fs-6" colspan="2"></th></tr>
          ${rows}
        </tbody>
      </table>
      <p class="util-bg-primary-subtle util-p-2 util-px-3 util-mb-0 util-font-medium util-text-center">Lô tô quay thử ${province}</p>
      <table class="comp-table util-table-bordered util-fs-8 util-align-items-center util-text-center">
        <thead class="util-align-items-center"><tr><th>Đầu</th><th>Lô tô</th><th>Lô tô</th><th>Đuôi</th></tr></thead>
        <tbody>${dauDuoiRows}</tbody>
      </table>
    </div>`;
  resetDauDuoiIn(document.getElementById(`id_ketqua_${KQ_ID}`));
}

function resetBoardUI() {
  const box = document.getElementById(`id_ketqua_${KQ_ID}`);
  if (!box) return;
  box.classList.remove("complete");
  ENGINES.MB.TABLE.forEach((p) => {
    for (let i = 0; i < p.count; i++) {
      const el = document.getElementById(`number_${KQ_ID}${p.id_giai}${i}`);
      if (el) {
        el.textContent = "...";
        el.removeAttribute("data-num");
      }
    }
  });
  resetDauDuoiIn(box);
}

function setButtonsDisabled(disabled, txt = "Đang quay...") {
  const btns = [
    document.querySelector(".js-btn-quay-thu-detail"),
    document.querySelector(".js-btn-quay-thu-nhanh"),
  ].filter(Boolean);
  btns.forEach((b) => {
    if (disabled) {
      if (!b.dataset.orig) b.dataset.orig = b.textContent.trim();
      b.textContent = txt;
      b.disabled = true;
      b.classList.add("disabled");
    } else {
      b.textContent = b.dataset.orig || b.textContent;
      b.disabled = false;
      b.classList.remove("disabled");
    }
  });
}

async function startDraw({ fast = false } = {}) {
  const myToken = ++DRAW_TOKEN;
  resetBoardUI();
  setButtonsDisabled(true);
  const spin = fast ? 700 : 1200,
    gap = fast ? 120 : 500;
  const box = document.getElementById(`id_ketqua_${KQ_ID}`);

  const ops = [];
  ENGINES.MB.TABLE.forEach((p) => {
    for (let i = 0; i < p.count; i++) {
      ops.push({
        el: document.getElementById(`number_${KQ_ID}${p.id_giai}${i}`),
        digits: p.digits,
      });
    }
  });

  try {
    for (const { el, digits } of ops) {
      if (myToken !== DRAW_TOKEN) return;
      const t0 = performance.now();
      await new Promise((res) => {
        function tick(now) {
          if (myToken !== DRAW_TOKEN) {
            res();
            return;
          }
          if (now - t0 >= spin) {
            res();
            return;
          }
          el.textContent = randN(digits);
          setTimeout(() => requestAnimationFrame(tick), 150);
        }
        requestAnimationFrame(tick);
      });
      if (myToken !== DRAW_TOKEN) return;
      const final = randN(digits);
      el.textContent = final;
      el.setAttribute("data-num", final);
      pushDauDuoiIn(box, final);
      await new Promise((r) => setTimeout(r, gap));
    }
    if (myToken === DRAW_TOKEN) box.classList.add("complete");
  } finally {
    if (myToken === DRAW_TOKEN) setButtonsDisabled(false);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const wd = new Date().getDay();
  const defaultProvince = SCHEDULE.MB[wd] || "Thủ Đô Hà Nội";
  CURRENT.province = defaultProvince;
  KQ_ID = Math.floor(Math.random() * 90000) + 1000;
  buildSkeletonMB(defaultProvince);

  document
    .querySelector(".js-btn-quay-thu-detail")
    ?.addEventListener("click", () =>
      startDraw({
        fast: false,
      })
    );
  document
    .querySelector(".js-btn-quay-thu-nhanh")
    ?.addEventListener("click", () =>
      startDraw({
        fast: true,
      })
    );

  const sel = document.querySelector(".js-select-other-day");
  sel?.addEventListener("change", (e) => {
    const opt = e.target.selectedOptions?.[0];
    const v = opt?.value?.trim();
    const lbl = opt?.textContent?.trim();

    if (v === "/quay-thu-xsmn.html" || v === "/quay-thu-xsmt.html") {
      window.location.href = v;
      return;
    }
    if (lbl && PROVINCE_TO_REGION[lbl]) {
      if (PROVINCE_TO_REGION[lbl] !== "MB") {
      }
      CURRENT.province = lbl;
      KQ_ID = Math.floor(Math.random() * 90000) + 1000;
      buildSkeletonMB(lbl);
    }
  });
  const typeSelect = document.querySelector(".js-select-type-quay-thu");
  const birthdayWrap = document.querySelector(".js-birthday-select-wrap");

  typeSelect?.addEventListener("change", (e) => {
    const selectedValue = e.target.value;
    if (selectedValue === "birthday") {
      birthdayWrap?.classList.remove("hidden");
    } else {
      birthdayWrap?.classList.add("hidden");
    }
  });
});
