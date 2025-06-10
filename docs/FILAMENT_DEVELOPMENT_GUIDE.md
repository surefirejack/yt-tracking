# Filament Development Guide

This document contains important patterns, learnings, and gotchas discovered during the development of this Laravel + Filament application.

## 🏗️ Resource Page Layout Issues

### Problem: Dashboard Layout Breaking with Forms

**Issue**: When creating custom resource pages that extend `Filament\Resources\Pages\Page`, using the `HasForms` interface can break the dashboard layout, causing the page to render with the public site layout instead of the dashboard layout.

**Symptoms**:
- Page shows public site header/layout instead of dashboard sidebar
- Correct URL with tenant UUID but wrong visual layout
- Forms work but layout is completely wrong

**Root Cause**: 
The `HasForms` interface and `InteractsWithForms` trait conflict with the layout rendering system for custom resource pages.

### ✅ Solution: Use Modal-Based Forms in Header Actions

Instead of implementing `HasForms` on the page class, use Filament's modal system within header actions:

```php
// ❌ AVOID: Don't use HasForms on custom resource pages
class VideoPerformance extends Page implements HasForms
{
    use InteractsWithForms;
    
    public function form(Form $form): Form
    {
        // This breaks the layout!
    }
}

// ✅ CORRECT: Use modal forms in header actions
class VideoPerformance extends Page
{
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('select_video')
                ->label('Select Video & Time Period')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modal()
                ->form([
                    Select::make('video_id')
                        ->label('Select YouTube Video')
                        ->options($this->getVideoOptions())
                        ->searchable(),
                        
                    Select::make('interval')
                        ->label('Time Period')
                        ->options(AnalyticsInterval::options())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Handle form submission
                }),
        ];
    }
}
```

### 📋 Best Practices for Custom Resource Pages

1. **Use `Filament\Resources\Pages\Page`** for custom resource pages
2. **Avoid `HasForms` interface** on custom resource pages
3. **Use modal forms in header actions** for form inputs
4. **Follow existing dashboard patterns** (check other working resource pages)
5. **Use Livewire properties** for simple state management instead of form state

### 🎯 When This Pattern Applies

This issue specifically occurs with:
- Custom resource pages extending `Filament\Resources\Pages\Page`
- Pages that need form inputs but aren't standard CRUD operations
- Dashboard pages that require custom layouts with forms

### 💡 Alternative Approaches

If you need more complex forms, consider:
1. **Separate dedicated pages** using `Filament\Pages\Page` (not resource pages)
2. **Livewire components** embedded in the view
3. **Multiple modal actions** for different form sections
4. **Wizard-style modals** for multi-step forms

---

## 🎨 Diamonds Theme System

### Theme Structure

This application uses a theming system with a "diamonds" theme located at `resources/views/diamonds/`. This directory mirrors the structure of the main `resources/views/` directory but provides an alternate theme implementation.

### 📁 Directory Structure

```
resources/views/
├── components/          # Default theme components
├── filament/           # Default Filament views
├── layouts/            # Default layouts
└── diamonds/           # 🎨 Diamonds Theme
    ├── components/     # Theme-specific components
    ├── filament/       # Theme-specific Filament views
    └── layouts/        # Theme-specific layouts
```

### ✅ Best Practices for Blade Files

**When creating new blade files:**

1. **Create files in the diamonds theme directory** to maintain theme consistency
2. **Mirror the standard structure** within the diamonds directory
3. **Use standard blade paths in PHP code** - no path changes needed

### 📝 Example

```php
// ✅ In your PHP code, use standard paths:
protected static string $view = 'filament.analytics.video-performance';

// ✅ But create the actual file at:
// resources/views/diamonds/filament/analytics/video-performance.blade.php
// 
// NOT at:
// resources/views/filament/analytics/video-performance.blade.php
```

### ⚙️ How It Works

- **Config-driven**: The theme path mapping is handled by configuration files
- **Transparent**: PHP code references don't change between themes
- **Override system**: Diamonds theme files take precedence over default views
- **Fallback**: If a view doesn't exist in diamonds, Laravel falls back to default

### 🚨 Common Mistakes

❌ **Don't put blade files in the default `resources/views/` structure**  
❌ **Don't change PHP view paths to include "diamonds"**  
✅ **Do create files in `resources/views/diamonds/` with standard paths**  
✅ **Do maintain the same directory structure within diamonds**

### 🔍 Debugging Theme Issues

If views aren't loading correctly:
1. **Check file location**: Ensure blade is in `diamonds/` directory
2. **Verify directory structure**: Mirror the standard structure exactly
3. **Check config**: Verify theme configuration is pointing to diamonds
4. **Clear view cache**: `php artisan view:clear`

---

## 🔍 Debugging Layout Issues

### Quick Diagnostic Steps

1. **Check the URL**: Ensure it includes the tenant UUID
2. **Verify route name**: Should be `filament.dashboard.resources.*`
3. **Test with minimal content**: Remove complex content to isolate the issue
4. **Compare with working pages**: Look at similar pages that work correctly
5. **Clear all caches**: `php artisan cache:clear && php artisan view:clear`

### 🚨 Red Flags

- Page renders without dashboard sidebar
- Breadcrumbs show but layout is wrong
- Forms work but styling is off
- Debug shows correct tenant context but wrong layout

---

*Last updated: January 2025*  
*If you encounter new Filament gotchas, please document them here!* 