// Admin panel functionality without Bootstrap dependency

// Navigation link handling
document.querySelectorAll('.nav-link').forEach((navLink) => {
    navLink.addEventListener('click', (event) => {
        const collapsableArrow = navLink.querySelector('.collapsable-arrow');
        if (collapsableArrow) {
            collapsableArrow.classList.toggle('expanded');
        }
    });
});

// Menu toggle functionality
document.querySelectorAll('.toggle-menu-button').forEach((toggleMenu) => {
    toggleMenu.addEventListener('click', (event) => {
        const sideMenu = document.querySelector('.side-menu-container');
        const pageContent = document.querySelector('.page-content-container');
        
        if (sideMenu) sideMenu.classList.toggle('active');
        if (pageContent) pageContent.classList.toggle('full-width');
    });
});

// Click outside to close menu on mobile
document.addEventListener('click', (event) => {
    if (!event.target.closest('.side-menu-container') && !event.target.closest('.toggle-menu')) {
        if (window.innerWidth < 1200) {
            const sideMenu = document.querySelector('.side-menu-container');
            const pageContent = document.querySelector('.page-content-container');
            
            if (sideMenu) sideMenu.classList.remove('active');
            if (pageContent) pageContent.classList.add('full-width');
        }
    }
});

// Responsive menu handling
window.addEventListener('resize', (event) => {
    if (window.innerWidth < 1200) {
        const sideMenu = document.querySelector('.side-menu-container');
        const pageContent = document.querySelector('.page-content-container');
        
        if (sideMenu) sideMenu.classList.remove('active');
        if (pageContent) pageContent.classList.add('full-width');
    }
});

// Password generation
document.querySelectorAll('.generate-password-button').forEach((generatePasswordButton) => {
    generatePasswordButton.addEventListener('click', (event) => {
        event.preventDefault();
        const passwordInput = document.querySelector('#password');
        if (passwordInput) {
            passwordInput.value = generateRandomPassword();
        }
    });
});

// Simple toast functionality without Bootstrap
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    const toast = createToast(message, type);
    
    toastContainer.appendChild(toast);
    
    // Show toast with animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function createToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close me-2 m-auto" onclick="this.closest('.toast').remove()" aria-label="Close"></button>
        </div>
    `;
    
    return toast;
}

// Livewire event handlers (if Livewire is available)
if (typeof Livewire !== 'undefined') {
    Livewire.on('laraveltable:action:confirm', (actionType, actionIdentifier, modelPrimary, confirmationQuestion) => {
        if (window.confirm(confirmationQuestion)) {
            Livewire.emit('laraveltable:action:confirmed', actionType, actionIdentifier, modelPrimary);
        }
    });

    Livewire.on('laraveltable:action:feedback', (feedbackMessage) => {
        showToast(feedbackMessage, 'success');
    });
}

// Show existing toasts on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toast').forEach((toast) => {
        setTimeout(() => toast.classList.add('show'), 100);
    });
});

// Password generation utility
function generateRandomPassword() {
    const length = 18;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~|}{[]:;?><,./-=";
    let password = "";
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    return password;
}

// Export for dynamic imports
export default {
    showToast,
    generateRandomPassword
};
