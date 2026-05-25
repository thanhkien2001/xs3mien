// Function to initialize menu
function initializeMenu() {
  const menuButtons = document.querySelectorAll(".js-nav-toggle-btn");
  const mobileMenuAside = document.querySelector("#nav-mobile-sidebar");
  const panelBackdrop = document.querySelector(".nav-backdrop");

  // Xóa toàn bộ event listener cũ trước khi gán mới
  function cleanupOldListeners() {
    const buttons = document.querySelectorAll(".js-nav-toggle-btn");
    buttons.forEach((button) => {
      if (button._menuClickHandler) {
        button.removeEventListener("click", button._menuClickHandler);
        button.removeEventListener("touchend", button._menuClickHandler);
        delete button._menuClickHandler;
        delete button._isProcessing;
      }
    });
  }

  if (menuButtons.length > 0 && mobileMenuAside && panelBackdrop) {
    const style = document.createElement("style");
    style.textContent = `
            #nav-mobile-sidebar {
                transition: transform 0.4s ease-out !important;
                transform: translateX(100%) !important;
                opacity: 1 !important;
                visibility: hidden !important;
                right: 0 !important;
                left: auto !important;
            }
            body.is-mobile-menu-active #nav-mobile-sidebar {
                transition: transform 0.45s ease-out !important;
                transform: translateX(0) !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            body.is-mobile-menu-active.is-menu-closing #nav-mobile-sidebar,
            body.is-menu-closing #nav-mobile-sidebar {
                transition: transform 0.45s ease-in !important;
                transform: translateX(100%) !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            #nav-mobile-sidebar {
                will-change: transform !important;
            }
            .nav-backdrop {
                transition: opacity 0.45s ease-out !important;
                opacity: 0 !important;
                visibility: hidden !important;
            }
            body.is-mobile-menu-active .nav-backdrop {
                transition: opacity 0.45s ease-out !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            body.is-mobile-menu-active.is-menu-closing .nav-backdrop,
            body.is-menu-closing .nav-backdrop {
                transition: opacity 0.45s ease-in !important;
                opacity: 0 !important;
                visibility: visible !important;
            }
            .nav-level-2 {
                transition: max-height 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.6s ease !important;
                overflow: hidden !important;
            }
            .js-nav-toggle-btn,
            .js-nav-toggle-btn:focus,
            .js-nav-toggle-btn:active,
            .js-nav-toggle-btn:hover,
            .js-nav-toggle-btn.is-active,
            .nav-toggle-btn,
            .nav-toggle-btn:focus,
            .nav-toggle-btn:active,
            .nav-toggle-btn:hover {
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
                box-shadow: 0 0 0 0 transparent !important;
                pointer-events: auto !important;
                cursor: pointer !important;
                z-index: 10000 !important;
                position: relative !important;
            }
            
            .js-nav-toggle-btn:disabled {
                pointer-events: none !important;
                opacity: 0.7 !important;
            }
            
            .js-nav-toggle-btn:focus-visible,
            .nav-toggle-btn:focus-visible {
                outline: none !important;
                border: none !important;
                box-shadow: none !important;
            }
            
            body.is-mobile-menu-active #nav-mobile-sidebar,
            body.is-mobile-menu-active #nav-mobile-sidebar *,
            body.is-mobile-menu-active .nav-backdrop,
            body.is-menu-closing #nav-mobile-sidebar,
            body.is-menu-closing #nav-mobile-sidebar * {
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
            }
        `;
    document.head.appendChild(style);

    // Xóa listener cũ
    cleanupOldListeners();

    // Gán listener mới
    menuButtons.forEach(function (menuButton) {
      // Đảm bảo button có cursor pointer
      menuButton.style.cursor = "pointer";
      menuButton.style.webkitTouchCallout = "none";
      menuButton.style.webkitUserSelect = "none";

      menuButton._menuClickHandler = function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Ngăn click nhanh liên tiếp
        if (menuButton._isProcessing) return;
        menuButton._isProcessing = true;

        setTimeout(() => {
          menuButton._isProcessing = false;
        }, 300);

        // Debug log để kiểm tra
        console.log('Menu button clicked, current state:', document.body.classList.contains("is-mobile-menu-active"));

        if (document.body.classList.contains("is-mobile-menu-active")) {
          // Đóng menu
          document.body.classList.add("is-menu-closing");
          document.body.style.overflow = "";
          setTimeout(() => {
            document.body.classList.remove("is-menu-closing");
            document.body.classList.remove("is-mobile-menu-active");
            menuButtons.forEach((btn) => btn.classList.remove("is-active"));
            console.log('Menu closed');
          }, 450);
        } else {
          // Mở menu
          menuButtons.forEach((btn) => btn.classList.add("is-active"));
          document.body.classList.add("is-mobile-menu-active");
          document.body.style.overflow = "hidden";
          console.log('Menu opened');
        }
      };

      menuButton.addEventListener("click", menuButton._menuClickHandler);
      menuButton.addEventListener("touchend", menuButton._menuClickHandler);
    });

    // Submenu
    const menuItems = mobileMenuAside.querySelectorAll("li");
    menuItems.forEach((item) => {
      const submenu = item.querySelector(".nav-level-2-alt");
      const link = item.querySelector("a");
      if (submenu && link) {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();
          menuItems.forEach((otherItem) => {
            if (otherItem !== item) {
              const otherSubmenu = otherItem.querySelector(".nav-level-2-alt");
              if (otherSubmenu && otherSubmenu.classList.contains("is-visible")) {
                otherSubmenu.style.maxHeight = "0";
                otherSubmenu.classList.remove("is-visible");
              }
            }
          });
          if (submenu.classList.contains("is-visible")) {
            submenu.style.maxHeight = "0";
            submenu.classList.remove("is-visible");
          } else {
            submenu.style.maxHeight = submenu.scrollHeight + "px";
            submenu.classList.add("is-visible");
          }
        });
      }
    });

    // Đóng menu khi click ngoài
    document.addEventListener("click", function (e) {
      if (
        window.innerWidth < 1200 &&
        document.body.classList.contains("is-mobile-menu-active")
      ) {
        if (
          e.target.classList.contains("nav-backdrop") ||
          (!mobileMenuAside.contains(e.target) &&
            !Array.from(menuButtons).some((btn) => btn.contains(e.target)) &&
            !e.target.closest("a"))
        ) {
          document.body.classList.add("is-menu-closing");
          document.body.style.overflow = "";
          setTimeout(() => {
            document.body.classList.remove("is-menu-closing");
            document.body.classList.remove("is-mobile-menu-active");
            menuButtons.forEach((btn) => btn.classList.remove("is-active"));
          }, 450);
        }
      }
    });

    // Đóng menu khi nhấn Escape
    document.addEventListener("keydown", function (e) {
      if (
        e.key === "Escape" &&
        document.body.classList.contains("is-mobile-menu-active")
      ) {
        document.body.classList.add("is-menu-closing");
        document.body.style.overflow = "";

        setTimeout(() => {
          document.body.classList.remove("is-menu-closing");
          document.body.classList.remove("is-mobile-menu-active");
          menuButtons.forEach((btn) => btn.classList.remove("is-active"));
        }, 450);
      }
    });
  }

  // Khi chuyển trang, xóa listener cũ để tránh xung đột
  window.addEventListener("beforeunload", function () {
    cleanupOldListeners();
  });
}

// Initialize menu when DOM is ready
document.addEventListener("DOMContentLoaded", initializeMenu);

// Also initialize if DOM is already loaded (for dynamic content)
if (document.readyState === 'loading') {
  document.addEventListener("DOMContentLoaded", initializeMenu);
} else {
  initializeMenu();
}

// Export function for manual reinitialization if needed
window.reinitializeMenu = initializeMenu;

document.addEventListener("DOMContentLoaded", function () {
  const scrollContainer = document.querySelector(".nav-mobile-menu .nav-level-1");
  if (scrollContainer) {
    let scrollTimeout;
    const style = document.createElement("style");
    style.textContent = `
            .nav-mobile-menu .nav-level-1.scrollbar-hidden::-webkit-scrollbar {
                display: none !important;
            }
            .nav-mobile-menu .nav-level-1.scrollbar-visible::-webkit-scrollbar {
                display: block !important;
                height: 3px !important;
                transform: translateY(100%) !important;
            }
        `;
    document.head.appendChild(style);
    scrollContainer.addEventListener("scroll", function () {
      scrollContainer.classList.remove("scrollbar-hidden");
      scrollContainer.classList.add("scrollbar-visible");
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(function () {
        scrollContainer.classList.remove("scrollbar-visible");
        scrollContainer.classList.add("scrollbar-hidden");
      }, 1000);
    });
    scrollContainer.addEventListener("mouseenter", function () {
      scrollContainer.classList.remove("scrollbar-hidden");
      scrollContainer.classList.add("scrollbar-visible");
    });
    scrollContainer.addEventListener("mouseleave", function () {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(function () {
        scrollContainer.classList.remove("scrollbar-visible");
        scrollContainer.classList.add("scrollbar-hidden");
      }, 500);
    });
    scrollContainer.classList.add("scrollbar-hidden");
  }
});
document.addEventListener("DOMContentLoaded", function () {
  let lastScrollTop = 0;
  const header = document.getElementById("layout-header");
  const mobileMenu = document.querySelector(".nav-mobile-menu");

  window.addEventListener("scroll", function () {
    if (window.innerWidth < 1200) {
      const scrollTop =
        window.pageYOffset || document.documentElement.scrollTop;
      const hideThreshold = 50;

      if (scrollTop > lastScrollTop && scrollTop > hideThreshold) {
        if (header) {
          header.classList.add("hidden");
        }
      } else if (scrollTop <= hideThreshold) {
        if (header) {
          header.classList.remove("hidden");
        }
      }

      lastScrollTop = scrollTop;
    } else {
      if (header) {
        header.classList.remove("hidden");
      }
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // Check if it's mobile device
  function isMobile() {
    return window.innerWidth <= 991; // Bootstrap lg breakpoint
  }

  function setActiveMenu() {
    const currentPath = window.location.pathname;
    const allMenuLinks = document.querySelectorAll(
      ".nav-level-1 a, .nav-level-1-alt a, .nav-mobile-menu .nav-level-1 a"
    );
    allMenuLinks.forEach((link) => link.classList.remove("is-active"));

    allMenuLinks.forEach((link) => {
      const href = link.getAttribute("href");
      if (!href) return;
      if (href === "/" && currentPath === "/") link.classList.add("is-active");
      else if (href !== "/" && currentPath.includes(href.replace(/^\//, "")))
        link.classList.add("is-active");
    });
  }

  setActiveMenu();

  function reorderMenuItems(clickedText) {
    // Only run on mobile
    if (!isMobile()) return;

    const vietlottMenus = ["Power", "Mega", "Keno", "Max3D", "Max3DPro"];

    const toolMenus = [
      "XSMB",
      "XSMN",
      "XSMT",
      "Dự đoán",
      "Thống kê",
      "Quay thử",
    ];

    const containers = [
      document.querySelector(".nav-level-1.util-hidden.util-flex-xl"),
      document.querySelector(".nav-mobile-menu .nav-level-1"),
      document.querySelector("#nav-mobile-sidebar .nav-level-1-alt"),
    ];

    const isVietlott = vietlottMenus.some((menu) => clickedText === menu);
    const isTool = toolMenus.some((menu) => clickedText === menu);

    containers.forEach((container) => {
      if (container) {
        container.style.opacity = "0";
        container.style.transition = "opacity 0.15s ease";
      }
    });

    requestAnimationFrame(() => {
      containers.forEach((container) => {
        if (!container) return;

        const items = Array.from(container.children);
        const clickedItem = items.find((item) => {
          const link = item.querySelector("a");
          return link && link.textContent.trim() === clickedText;
        });
        if (!clickedItem) return;

        const trangChu = items.find((item) => {
          const link = item.querySelector("a");
          return link && link.textContent.trim() === "Trang chủ";
        });

        let groupItems = [];
        let targetMenus = [];

        if (isVietlott) {
          targetMenus = vietlottMenus;
        } else if (isTool) {
          targetMenus = toolMenus;
        }

        if (targetMenus.length > 0) {
          groupItems = items.filter((item) => {
            const link = item.querySelector("a");
            if (!link) return false;
            const text = link.textContent.trim();
            return targetMenus.some((menu) => text.includes(menu));
          });

          groupItems.forEach((item) => item.remove());

          let insertAfter = trangChu;
          groupItems.forEach((item) => {
            if (insertAfter) {
              insertAfter.insertAdjacentElement("afterend", item);
              insertAfter = item;
            } else {
              container.insertBefore(item, container.firstChild);
              insertAfter = item;
            }
          });
        } else {
          if (trangChu && clickedItem !== trangChu)
            trangChu.insertAdjacentElement("afterend", clickedItem);
          else if (!trangChu)
            container.insertBefore(clickedItem, container.firstChild);
        }

        container
          .querySelectorAll("a")
          .forEach((a) => a.classList.remove("is-active"));
        const newClickedLink = Array.from(container.querySelectorAll("a")).find(
          (a) => a.textContent.trim() === clickedText
        );
        if (newClickedLink) newClickedLink.classList.add("is-active");
      });

      requestAnimationFrame(() => {
        containers.forEach((container) => {
          if (container) {
            container.style.opacity = "1";
          }
        });
      });
    });
  }

  const menuLinks = document.querySelectorAll(
    ".nav-level-1 a, .nav-level-1-alt a, .nav-mobile-menu .nav-level-1 a"
  );
  menuLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");
      const text = this.textContent.trim();

      // Only store session data and reorder on mobile
      if (isMobile()) {
        sessionStorage.setItem("activeMenuItem", text);
        sessionStorage.setItem("activeMenuHref", href);
        reorderMenuItems(text);
      }

      if (href && href.startsWith("#")) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
          menuLinks.forEach((l) => l.classList.remove("is-active"));
          this.classList.add("is-active");
        }
      }
    });
  });

  // Only restore menu state on mobile
  if (isMobile()) {
    const activeMenuItem = sessionStorage.getItem("activeMenuItem");
    const activeMenuHref = sessionStorage.getItem("activeMenuHref");

    if (activeMenuItem && activeMenuHref) {
      setTimeout(() => {
        reorderMenuItems(activeMenuItem);
        setTimeout(setActiveMenu, 50);
      }, 100);

      setTimeout(() => {
        sessionStorage.removeItem("activeMenuItem");
        sessionStorage.removeItem("activeMenuHref");
      }, 1000);
    }
  }
});
