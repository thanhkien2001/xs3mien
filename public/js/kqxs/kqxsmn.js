(function () {
  "use strict";

  const patterns = {
    xsmb: (d, m, y) =>
      `/xsmb-${d}-${m}-ket-qua-xo-so-mien-bac-ngay-${d}-${m}-${y}.html`,
    xsmn: (d, m, y) =>
      `/xsmn-${d}-${m}-ket-qua-xo-so-mien-nam-ngay-${d}-${m}-${y}.html`,
    xsmt: (d, m, y) =>
      `/xsmt-${d}-${m}-ket-qua-xo-so-mien-trung-ngay-${d}-${m}-${y}.html`,
  };

  function parseYMD(val) {
    if (!val || !/^\d{4}-\d{2}-\d{2}$/.test(val)) return null;
    const y = parseInt(val.slice(0, 4), 10);
    const m = parseInt(val.slice(5, 7), 10);
    const d = parseInt(val.slice(8, 10), 10);
    return {
      y,
      m,
      d,
    };
  }

  window.__gotoLotteryDate = function (region, ymd) {
    const p = parseYMD(ymd);
    if (!p || !patterns[region]) return;
    const url = patterns[region](p.d, p.m, p.y);
    window.location.href = url;
  };

  function isMobile() {
    return /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );
  }

  let isInitialized = false;

  function setupDatePicker() {
    // Chỉ khởi tạo một lần duy nhất
    if (isInitialized) return;
    
    const inputs = document.querySelectorAll(
      '.lottery-date-form input[type="date"][data-region]'
    );

    if (inputs.length === 0) return;

    inputs.forEach((input, index) => {
      // Đánh dấu input đã được xử lý
      if (input.dataset.initialized === "true") return;
      input.dataset.initialized = "true";

      if (isMobile()) {
        // Thêm CSS đơn giản cho mobile
        input.style.cssText = `
          width: 100%;
          padding: 10px;
          font-size: 14px;
          border: 1px solid #ddd;
          border-radius: 6px;
          background: #fff;
          box-sizing: border-box;
        `;

        if (!input.parentNode.classList.contains("date-input-wrapper")) {
          const wrapper = document.createElement("div");
          wrapper.className = "date-input-wrapper";
          wrapper.style.cssText = `
            position: sticky;
            top: 0;
            background: #fff;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          `;

          const icon = document.createElement("div");
          icon.innerHTML = "📅";
          icon.style.cssText = `
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            pointer-events: none;
            z-index: 10;
          `;

          // Tìm thẻ position và đưa wrapper vào đó
          const positionElement = document.querySelector('[id^="position-"]');
          if (positionElement) {
            // Đưa wrapper vào đầu thẻ position
            positionElement.insertBefore(wrapper, positionElement.firstChild);
            wrapper.appendChild(input);
            wrapper.appendChild(icon);
          } else {
            // Fallback: đưa vào vị trí cũ
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(icon);
          }
        }
      }

      // Chỉ thêm event listener một lần
      input.addEventListener("change", function (e) {
        const region = this.getAttribute("data-region");
        if (region && this.value) {
          window.__gotoLotteryDate(region, this.value);
        }
      });

      if (isMobile()) {
        // Không cần focus khi click, để iOS tự xử lý
        // input.addEventListener("click", function (e) {
        //   this.focus();
        // });

        // Không cần touchstart, để iOS tự xử lý
        // input.addEventListener(
        //   "touchstart",
        //   function (e) {
        //     this.focus();
        //   },
        //   {
        //     passive: true,
        //   }
        // );
      }
    });

    isInitialized = true;
  }

  function init() {
    setupDatePicker();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  window.testDatePicker = function () {
    const input = document.querySelector(
      '.lottery-date-form input[type="date"][data-region]'
    );
    if (input) {
      input.focus();
      input.click();

      if (input.showPicker) {
        try {
          input.showPicker();
        } catch (e) {
          const event = new MouseEvent("click", {
            bubbles: true,
            cancelable: true,
          });
          input.dispatchEvent(event);
        }
      }
    }
  };
})();

document.addEventListener("DOMContentLoaded", function () {
  const tocLinks = document.querySelectorAll(".js-go-to");

  tocLinks.forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      const targetId = this.getAttribute("href").substring(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        const stickyHeader = document.querySelector(".kqxs__header.sticky-top");
        const headerHeight = stickyHeader ? stickyHeader.offsetHeight : 0;
        
        const dateWrapper = document.querySelector(".date-input-wrapper");
        const dateWrapperHeight = dateWrapper ? dateWrapper.offsetHeight : 0;
        
        const isMobileDevice = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const extraPadding = isMobileDevice ? 30 : 30; // More padding on mobile
        const totalOffset = headerHeight + dateWrapperHeight + extraPadding;

        const targetPosition = targetElement.offsetTop - totalOffset;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });

        tocLinks.forEach((l) => l.classList.remove("active"));
        this.classList.add("active");
      }
    });
  });

  if (tocLinks.length > 0) {
    tocLinks[0].classList.add("active");
  }
});
