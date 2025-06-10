## Relevant Files

- `app/Filament/Resources/AnalyticsResource.php` - Main Filament resource for analytics dashboard.
- `app/Filament/Resources/AnalyticsResource/Pages/VideoPerformance.php` - Custom page for video performance analytics view.
- `app/Filament/Resources/AnalyticsResource/Pages/UrlPerformance.php` - Custom page for destination URL performance analytics view.
- `app/Services/DubAnalyticsService.php` - Service class for Dub.co API integration and data processing.
- `app/Http/Controllers/Api/AnalyticsController.php` - API controller for AJAX requests from dashboard.
- `resources/views/diamonds/filament/analytics/video-performance.blade.php` - Blade template for video performance view with charts.
- `resources/views/diamonds/filament/analytics/url-performance.blade.php` - Blade template for URL performance view with tables.
- `resources/js/analytics-charts.js` - JavaScript file for Chart.js implementation and interactivity.
- `config/cache.php` - Cache configuration for analytics data storage strategy.
- `tests/Feature/AnalyticsServiceTest.php` - Feature tests for analytics service functionality.
- `tests/Unit/DubAnalyticsServiceTest.php` - Unit tests for Dub.co API service methods.

### Notes

- Blade files are placed in `resources/views/diamonds/` directory due to custom Filament theme, but referenced normally in code (e.g., `filament.analytics.video-performance`) thanks to config path routing.
- Unit tests should be placed alongside the code files they are testing.
- Use `php artisan test` to run Laravel tests.

## Tasks

- [x] 1.0 Set up Dub.co Analytics API Integration and Caching System
  - [x] 1.1 Create DubAnalyticsService class with methods for retrieving analytics data using existing API patterns
  - [x] 1.2 Implement tag-based filtering for video analytics using yt-video-{id} pattern
  - [x] 1.3 Add support for top_urls groupBy parameter for destination URL analysis
  - [x] 1.4 Implement Laravel caching strategy with tenant-specific keys (15-30 minute TTL)
  - [x] 1.5 Add error handling and rate limiting for Dub.co API calls
  - [x] 1.6 Create cache invalidation logic for manual refresh functionality

- [x] 2.0 Implement Video Performance Analytics View
  - [x] 2.1 Create VideoPerformance custom page within AnalyticsResource
  - [x] 2.2 Build video selection dropdown filtered by tenant using yt_videos table
  - [x] 2.3 Implement aggregated metrics calculation (clicks, leads, sales, conversion rates)
  - [x] 2.4 Create data processing logic to combine analytics for all links tagged with selected video
  - [x] 2.5 Add revenue per click and conversion rate calculations
  - [x] 2.6 Implement tenant-based data isolation using Filament::getTenant()

- [ ] 3.0 Implement Destination URL Performance Analytics View
  - [ ] 3.1 Create UrlPerformance custom page within AnalyticsResource
  - [ ] 3.2 Build destination URL selection dropdown from links table original_url field
  - [ ] 3.3 Implement analytics aggregation for all links pointing to selected destination URL
  - [ ] 3.4 Create sortable table showing associated videos or "None" for unassociated links
  - [ ] 3.5 Add traffic volume sorting and ranking functionality
  - [ ] 3.6 Implement cross-referencing between links and yt_videos tables

- [ ] 4.0 Build Filament UI Components and Dashboard Layout
  - [ ] 4.1 Update AnalyticsResource navigation to include new dashboard pages
  - [ ] 4.2 Implement tabbed interface for "Video Performance" and "URL Performance" views
  - [ ] 4.3 Create Chart.js integration for time-series line charts
  - [ ] 4.4 Build key metrics cards component showing totals and percentages
  - [ ] 4.5 Implement Filament table builder for sortable data displays
  - [ ] 4.6 Add loading states and error messages for API calls
  - [ ] 4.7 Ensure responsive design for mobile/tablet viewing

- [ ] 5.0 Implement Filtering and Data Management Features
  - [ ] 5.1 Add time-based filtering using Dub.co predefined intervals (24h, 7d, 30d, 90d, 1y, mtd, qtd, ytd, all)
  - [ ] 5.2 Implement UTM parameter filtering using existing links table UTM data
  - [ ] 5.3 Create manual refresh button with cache invalidation
  - [ ] 5.4 Add default 30-day view setting
  - [ ] 5.5 Implement filter state persistence during user session
  - [ ] 5.6 Add filter reset functionality to return to default view 