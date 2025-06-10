import Alpine from 'alpinejs'
import intersect from '@alpinejs/intersect'

// Alpine plugins setup
Alpine.plugin(intersect)

// Dynamic imports for better code splitting
const dynamicImports = {
    // Lazy load components when needed
    async loadComponents() {
        const { default: components } = await import('./components.js');
        return components;
    },
    
    // Lazy load admin scripts when needed
    async loadAdmin() {
        if (document.querySelector('[data-admin]')) {
            const { default: admin } = await import('./admin.js');
            return admin;
        }
    },
    
    // Lazy load analytics when needed
    async loadAnalytics() {
        if (document.querySelector('[data-analytics]')) {
            const { default: analytics } = await import('./analytics-charts.js');
            return analytics;
        }
    }
};

// Initialize tab slider functionality
function initTabSliders() {
    const tabSliders = document.querySelectorAll(".tab-slider");

    tabSliders.forEach(tabSlider => {
        const tabs = tabSlider.querySelectorAll(".tab");
        const panels = tabSlider.querySelectorAll(".tab-panel");

        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                const tabTarget = tab.getAttribute("aria-controls");
                
                // Reset all tabs
                tabs.forEach(tab => {
                    tab.setAttribute("data-active-tab", "false");
                    tab.setAttribute("aria-selected", "false");
                });

                // Set clicked tab as active
                tab.setAttribute("data-active-tab", "true");
                tab.setAttribute("aria-selected", "true");

                // Handle panels
                panels.forEach(panel => {
                    const panelId = panel.getAttribute("id");
                    
                    if (tabTarget === panelId) {
                        panel.classList.remove("hidden", "opacity-0");
                        panel.classList.add("block", "opacity-100");
                        
                        // Smooth fade in animation
                        panel.animate([
                            { opacity: 0, maxHeight: 0 },
                            { opacity: 1, maxHeight: "100%" }
                        ], {
                            duration: 500,
                            easing: "ease-in-out",
                            fill: "forwards"
                        });
                    } else {
                        panel.classList.remove("block", "opacity-100");
                        panel.classList.add("hidden", "opacity-0");
                        
                        // Smooth fade out animation
                        panel.animate([
                            { opacity: 1, maxHeight: "100%" },
                            { opacity: 0, maxHeight: 0 }
                        ], {
                            duration: 500,
                            easing: "ease-in-out",
                            fill: "forwards"
                        });
                    }
                });
            });
        });

        // Initialize first active tab
        const activeTab = tabSlider.querySelector(".tab[data-active-tab='true']");
        if (activeTab) {
            activeTab.click();
        }
    });
}

// Performance optimization: Use Intersection Observer for lazy loading
function initLazyLoading() {
    const lazyElements = document.querySelectorAll('[data-lazy-load]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const loadType = element.dataset.lazyLoad;
                    
                    // Load specific modules based on data attribute
                    if (loadType && dynamicImports[`load${loadType.charAt(0).toUpperCase() + loadType.slice(1)}`]) {
                        dynamicImports[`load${loadType.charAt(0).toUpperCase() + loadType.slice(1)}`]();
                    }
                    
                    observer.unobserve(element);
                }
            });
        });
        
        lazyElements.forEach(element => observer.observe(element));
    }
}

// Main initialization function
function initApp() {
    initTabSliders();
    initLazyLoading();
    
    // Load admin assets if admin elements are present
    if (document.querySelector('[data-admin]')) {
        dynamicImports.loadAdmin();
    }
    
    // Load analytics if chart elements are present
    if (document.querySelector('[data-analytics]')) {
        dynamicImports.loadAnalytics();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initApp);

// Start Alpine.js
window.Alpine = Alpine;
Alpine.start();
