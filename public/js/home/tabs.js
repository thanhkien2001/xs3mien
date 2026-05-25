/**
 * Bootstrap Tabs - Vanilla JS Implementation
 * Thay thế Bootstrap JS để xử lý tab navigation
 * Không cần jQuery
 */

(function() {
    'use strict';

    /**
     * Khởi tạo tab navigation
     */
    function initTabs() {
        // Lấy tất cả các tab triggers
        var tabTriggers = document.querySelectorAll('[data-toggle="pill"]');
        
        if (tabTriggers.length === 0) {
            console.warn('⚠️ No tabs found with [data-toggle="pill"]');
            return;
        }

        // Xử lý click event cho mỗi tab
        tabTriggers.forEach(function(trigger) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                
                var targetId = this.getAttribute('href');
                if (!targetId || targetId === '#') {
                    console.warn('⚠️ Tab trigger missing href:', this);
                    return;
                }

                console.log('🔄 Switching to tab:', targetId);

                // Deactivate tất cả các tabs
                deactivateAllTabs(trigger);

                // Activate tab được click
                activateTab(trigger, targetId);
            });
        });

        console.log('✅ Tabs initialized successfully:', tabTriggers.length, 'tabs found');
    }

    /**
     * Deactivate tất cả tabs trong cùng group
     */
    function deactivateAllTabs(currentTrigger) {
        // Tìm parent tab list
        var tabList = currentTrigger.closest('[role="tablist"]');
        if (!tabList) {
            console.warn('⚠️ Tab list not found');
            return;
        }

        // Remove active class từ tất cả tab links
        var allTabs = tabList.querySelectorAll('.nav-link');
        allTabs.forEach(function(tab) {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });

        // Remove active class từ tất cả tab panes
        var allPanes = document.querySelectorAll('.tab-pane');
        allPanes.forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
    }

    /**
     * Activate tab được chọn
     */
    function activateTab(trigger, targetId) {
        // Activate tab link
        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');

        // Activate tab pane
        var targetPane = document.querySelector(targetId);
        if (targetPane) {
            // Add classes với small delay để animation hoạt động
            setTimeout(function() {
                targetPane.classList.add('show', 'active');
            }, 10);

            console.log('✅ Tab activated:', targetId);

            // Trigger custom event (nếu cần)
            try {
                var event = new CustomEvent('tab:shown', {
                    detail: { 
                        tabId: targetId,
                        trigger: trigger 
                    }
                });
                targetPane.dispatchEvent(event);
            } catch (e) {
                console.warn('⚠️ Cannot dispatch custom event:', e);
            }
        } else {
            console.error('❌ Tab pane not found:', targetId);
        }
    }

    /**
     * Khởi tạo khi DOM ready
     */
    function init() {
        console.log('🚀 Initializing tabs...');
        initTabs();
    }

    // Auto init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export functions (nếu cần sử dụng từ bên ngoài)
    window.TabsManager = {
        init: initTabs,
        activateTab: function(tabId) {
            var trigger = document.querySelector('[href="' + tabId + '"]');
            if (trigger) {
                trigger.click();
            } else {
                console.error('❌ Tab trigger not found:', tabId);
            }
        }
    };
})();

