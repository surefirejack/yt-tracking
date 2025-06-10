// Theme synchronization between DaisyUI and Filament
function syncDaisyThemeWithFilament() {
    const html = document.querySelector('html');
    
    if (!html) return;
    
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                const htmlClass = html.getAttribute('class');
                // Sync theme based on dark mode class
                if (htmlClass && htmlClass.includes('dark')) {
                    html.setAttribute('data-theme', 'dark');
                } else {
                    html.setAttribute('data-theme', 'light');
                }
            }
        });
    });

    observer.observe(html, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

// Plan switcher functionality
function initPlanSwitchers() {
    const planSwitchers = document.querySelectorAll('.plan-switcher a');

    planSwitchers.forEach((planSwitcher) => {
        planSwitcher.addEventListener('click', (event) => {
            event.preventDefault();

            // Reset all plan switcher elements
            document.querySelectorAll('.plan-switcher a').forEach((element) => {
                element.classList.remove('tab-active');
                element.removeAttribute('aria-selected');
            });

            // Set clicked element as active
            planSwitcher.classList.add('tab-active');
            planSwitcher.setAttribute('aria-selected', 'true');

            // Hide all plan containers
            document.querySelectorAll('.plans-container').forEach((container) => {
                container.classList.add('hidden');
                container.setAttribute('aria-hidden', 'true');
            });

            // Show target container
            const target = planSwitcher.getAttribute('data-target');
            if (target) {
                const targetElement = document.querySelector(`.${target}`);
                if (targetElement) {
                    targetElement.classList.remove('hidden');
                    targetElement.setAttribute('aria-hidden', 'false');
                }
            }
        });
    });

    // Auto-click first switcher if only one exists
    if (planSwitchers.length === 1) {
        planSwitchers[0].click();
    }
}

// Initialize smooth scrolling for anchor links
function initSmoothScrolling() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Initialize copy-to-clipboard functionality
function initClipboard() {
    const copyButtons = document.querySelectorAll('[data-clipboard-target]');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const targetId = button.getAttribute('data-clipboard-target');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                try {
                    await navigator.clipboard.writeText(targetElement.textContent || targetElement.value);
                    
                    // Visual feedback
                    const originalText = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.add('bg-green-500');
                    
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('bg-green-500');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy text: ', err);
                }
            }
        });
    });
}

// Main components initialization
function initComponents() {
    syncDaisyThemeWithFilament();
    initPlanSwitchers();
    initSmoothScrolling();
    initClipboard();
}

// Initialize when DOM is ready (if this script is loaded directly)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initComponents);
} else {
    initComponents();
}

// Export for dynamic imports
export default {
    syncDaisyThemeWithFilament,
    initPlanSwitchers,
    initSmoothScrolling,
    initClipboard,
    initComponents
};
