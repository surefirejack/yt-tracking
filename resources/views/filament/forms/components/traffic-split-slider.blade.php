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
            onchange="updateTrafficSplit(this.value)"
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
    
    // Update display
    document.getElementById('variant-a-percentage').textContent = percentage;
    document.getElementById('variant-b-percentage').textContent = variantB;
    
    // Update the hidden field that Filament uses
    const hiddenField = document.querySelector('input[name="traffic_percentage"]');
    if (hiddenField) {
        hiddenField.value = percentage;
        hiddenField.dispatchEvent(new Event('input'));
    }
    
    // Trigger Livewire update
    if (window.Livewire) {
        window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('data.traffic_percentage', percentage);
    }
}

// Style the slider
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('traffic-split-slider');
    if (slider) {
        slider.style.background = `linear-gradient(to right, #3b82f6 0%, #3b82f6 ${slider.value}%, #e5e7eb ${slider.value}%, #e5e7eb 100%)`;
        
        slider.addEventListener('input', function() {
            this.style.background = `linear-gradient(to right, #3b82f6 0%, #3b82f6 ${this.value}%, #e5e7eb ${this.value}%, #e5e7eb 100%)`;
        });
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
}
</style> 