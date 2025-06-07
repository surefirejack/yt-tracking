<div class="space-y-4">
    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
        <span>Variant A</span>
        <span>Variant B</span>
    </div>
    
    <div class="relative">
        <input 
            type="range" 
            min="10" 
            max="90" 
            value="{{ $percentage }}" 
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 slider"
            id="traffic-split-slider"
            wire:model.live="data.traffic_percentage"
            onchange="updateTrafficSplit(this.value)"
            oninput="updateTrafficSplit(this.value)"
        >
        <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>10%</span>
            <span>50%</span>
            <span>90%</span>
        </div>
    </div>
    
    <div class="flex justify-center">
        <div class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-sm font-medium">
            <span id="variant-a-percentage">{{ $percentage }}</span>% / <span id="variant-b-percentage">{{ 100 - $percentage }}</span>%
        </div>
    </div>
</div>

<script>
function updateTrafficSplit(value) {
    const percentage = parseInt(value);
    const variantB = 100 - percentage;
    
    // Update display immediately
    document.getElementById('variant-a-percentage').textContent = percentage;
    document.getElementById('variant-b-percentage').textContent = variantB;
    
    // Find and update the hidden traffic_percentage field
    const hiddenField = document.querySelector('input[name="traffic_percentage"]');
    if (hiddenField) {
        hiddenField.value = percentage;
        // Trigger change event for Livewire
        hiddenField.dispatchEvent(new Event('change', { bubbles: true }));
        hiddenField.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    // Alternative: Directly trigger Livewire component update
    if (window.Livewire) {
        // Find the current Livewire component
        const wireComponent = document.querySelector('[wire\\:id]');
        if (wireComponent) {
            const componentId = wireComponent.getAttribute('wire:id');
            const livewireComponent = window.Livewire.find(componentId);
            if (livewireComponent) {
                // Update the traffic_percentage data
                livewireComponent.set('data.traffic_percentage', percentage);
            }
        }
    }
    
    // Force update the slider background
    updateSliderBackground(percentage);
}

function updateSliderBackground(percentage) {
    const slider = document.getElementById('traffic-split-slider');
    if (slider) {
        slider.style.background = `linear-gradient(to right, #3b82f6 0%, #3b82f6 ${percentage}%, #e5e7eb ${percentage}%, #e5e7eb 100%)`;
    }
}

// Initialize slider styling
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('traffic-split-slider');
    if (slider) {
        updateSliderBackground(slider.value);
        
        // Add both input and change event listeners
        slider.addEventListener('input', function() {
            updateTrafficSplit(this.value);
        });
        
        slider.addEventListener('change', function() {
            updateTrafficSplit(this.value);
        });
    }
});

// Also listen for Livewire updates to sync display
document.addEventListener('livewire:updated', function() {
    const slider = document.getElementById('traffic-split-slider');
    if (slider) {
        updateSliderBackground(slider.value);
        updateTrafficSplit(slider.value);
    }
});
</script>

<style>
.slider::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.slider::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    border: none;
}

.slider::-moz-track {
    height: 8px;
    border-radius: 4px;
}
</style> 