## Relevant Files

- `database/migrations/2025_06_11_004220_create_subscriber_content_table.php` - Migration for storing tenant's member content ✅
- `database/migrations/2025_06_11_004306_create_subscriber_users_table.php` - Migration for subscriber login data and subscription cache ✅
- `database/migrations/2025_06_11_004519_create_tenant_referrals_table.php` - Migration for referral tracking ✅
- `database/migrations/2025_06_11_004527_create_content_downloads_table.php` - Migration for file download activity tracking ✅
- `database/migrations/2025_06_11_004535_add_subscriber_fields_to_tenants_table.php` - Migration to add new columns to tenants table ✅
- `app/Models/SubscriberContent.php` - Eloquent model for subscriber content ✅
- `app/Models/SubscriberUser.php` - Eloquent model for subscriber users ✅
- `app/Models/TenantReferral.php` - Eloquent model for referral tracking ✅
- `app/Models/ContentDownload.php` - Eloquent model for download tracking ✅
- `app/Models/Tenant.php` - Updated with subscriber relationships and LMS accessor methods ✅
- `app/Http/Controllers/SubscriberAuthController.php` - Handles Google OAuth and subscription verification
- `app/Http/Controllers/SubscriberDashboardController.php` - Handles subscriber dashboard and content pages
- `app/Http/Controllers/SubscriberContentController.php` - Handles content display and file downloads
- `app/Http/Controllers/ReferralController.php` - Handles referral link tracking
- `app/Http/Middleware/VerifySubscription.php` - Middleware to check subscription status
- `app/Services/YouTubeSubscriptionService.php` - Service for YouTube API subscription verification
- `app/Filament/Resources/SubscriberContentResource.php` - Filament resource for tenant content management
- `resources/views/diamonds/subscriber/login.blade.php` - Login page for subscribers (diamonds theme)
- `resources/views/diamonds/subscriber/access-denied.blade.php` - Access denied page (diamonds theme)
- `resources/views/diamonds/subscriber/dashboard.blade.php` - Subscriber dashboard page (diamonds theme)
- `resources/views/diamonds/subscriber/content.blade.php` - Individual content page (diamonds theme)
- `resources/views/diamonds/layouts/subscriber.blade.php` - Layout for subscriber pages (diamonds theme)
- `resources/views/diamonds/filament/subscriber-content/settings.blade.php` - Custom Filament settings page (diamonds theme)
- `routes/web.php` - Web routes for subscriber area
- `tests/Feature/SubscriberAuthTest.php` - Feature tests for subscriber authentication
- `tests/Feature/SubscriberContentTest.php` - Feature tests for content access
- `tests/Unit/YouTubeSubscriptionServiceTest.php` - Unit tests for YouTube service
- `tests/Unit/SubscriberContentTest.php` - Unit tests for SubscriberContent model

### Notes

- **Diamonds Theme**: All blade views must be created in `resources/views/diamonds/` directory to maintain theme consistency
- PHP controllers reference standard view paths (e.g., 'subscriber.login') but files are created in diamonds theme directory
- Unit tests should be placed alongside the code files they are testing when possible
- Use `php artisan test` to run all tests or `php artisan test --filter=SubscriberAuth` to run specific test classes
- File uploads will be stored in `storage/app/subscriber-content/` with tenant-specific subdirectories
- YouTube API integration will extend the existing `YouTubeApiService` from Settings.php
- Follow Filament best practices: avoid `HasForms` on custom resource pages, use modal forms in header actions instead

## Tasks

- [x] 1.0 Database Schema & Models Setup
  - [x] 1.1 Create migration for `subscriber_content` table with fields: id, tenant_id, title, slug, content, youtube_video_url, file_paths (JSON), created_at, updated_at
  - [x] 1.2 Create migration for `subscriber_users` table with fields: id, tenant_id, google_id, email, name, profile_picture, subscription_verified_at, created_at, updated_at
  - [x] 1.3 Create migration for `tenant_referrals` table with fields: id, tenant_id, clicks, conversions, created_at, updated_at
  - [x] 1.4 Create migration for `content_downloads` table with fields: id, subscriber_user_id, subscriber_content_id, file_name, downloaded_at
  - [x] 1.5 Create migration to add columns to `tenants` table: can_use_subscriber_only_lms (boolean), subscriber_only_lms_status (boolean), subscription_cache_days, logout_redirect_url, member_login_text, member_profile_image
  - [x] 1.6 Create `SubscriberContent` Eloquent model with tenant relationship and slug generation
  - [x] 1.7 Create `SubscriberUser` Eloquent model with tenant relationship and subscription verification methods
  - [x] 1.8 Create `TenantReferral` Eloquent model with tenant relationship
  - [x] 1.9 Create `ContentDownload` Eloquent model with relationships to SubscriberUser and SubscriberContent
  - [x] 1.10 Update `Tenant` model to include relationships with new subscriber models and accessor methods for LMS permissions

- [ ] 2.0 Authentication & YouTube API Integration
  - [ ] 2.1 Create `YouTubeSubscriptionService` that extends existing YouTubeApiService to check subscription status
  - [ ] 2.2 Create `SubscriberAuthController` with Google OAuth login method using YouTube API v3 readonly scope
  - [ ] 2.3 Implement subscription verification logic that checks if user is subscribed to tenant's channel
  - [ ] 2.4 Implement subscription caching based on tenant's `subscription_cache_days` setting
  - [ ] 2.5 Create logout method that redirects to tenant's custom logout URL or default
  - [ ] 2.6 Implement "remember me" session management for subscriber users
  - [ ] 2.7 Add error handling for YouTube API failures and rate limits
  - [ ] 2.8 Create helper methods to store and retrieve subscriber user data from Google OAuth response

- [ ] 3.0 Routing & Middleware Implementation
  - [ ] 3.1 Create `VerifySubscription` middleware that checks cached subscription status
  - [ ] 3.2 Register middleware in `bootstrap/app.php` following Laravel 11+ structure
  - [ ] 3.3 Define routes in `routes/web.php` for `/s/{channelname}` pattern with route model binding
  - [ ] 3.4 Create route for login page: `/s/{channelname}/{slug}` (when not authenticated)
  - [ ] 3.5 Create route for dashboard: `/s/{channelname}` (when authenticated)
  - [ ] 3.6 Create route for content pages: `/s/{channelname}/{slug}` (when authenticated)
  - [ ] 3.7 Create routes for OAuth callback and logout
  - [ ] 3.8 Create route for referral link tracking
  - [ ] 3.9 Implement route model binding to resolve tenant by channelname (lowercase YouTube channel)
  - [ ] 3.10 Add route parameter validation and conflict resolution

- [ ] 4.0 Subscriber-Facing Pages & User Experience
  - [ ] 4.1 Create subscriber layout template in diamonds theme with tenant's channel banner as header
  - [ ] 4.2 Create login page in diamonds theme that displays content title, tenant's profile image, and custom login text
  - [ ] 4.3 Create access denied page in diamonds theme with link to tenant's YouTube channel and "try again" functionality
  - [ ] 4.4 Create subscriber dashboard in diamonds theme showing all content as cards with title, description, and thumbnails
  - [ ] 4.5 Create individual content page in diamonds theme displaying rich text, embedded YouTube video, and file download buttons
  - [ ] 4.6 Implement logout functionality with custom redirect URL support
  - [ ] 4.7 Add "Powered by [App Name]" footer link to all subscriber pages
  - [ ] 4.8 Ensure all pages are mobile-responsive using Tailwind CSS
  - [ ] 4.9 Implement navigation between dashboard and content pages
  - [ ] 4.10 Add loading states and error messages for better UX

- [ ] 5.0 Tenant Content Management Interface
  - [ ] 5.1 Create `SubscriberContentResource`