# Task List: Email Subscriber Content Gating

## Relevant Files

- `app/Models/EmailSubscriberContent.php` - Model for email-gated content similar to SubscriberContent ✅
- `app/Models/EmailVerificationRequest.php` - Model for tracking email verification requests ✅ 
- `app/Models/SubscriberAccessRecord.php` - Model for storing verified subscriber access data ✅
- `database/migrations/2025_06_19_032249_create_email_subscriber_contents_table.php` - Migration for email gated content ✅
- `database/migrations/2025_06_19_032305_create_email_verification_requests_table.php` - Migration for verification tracking ✅
- `database/migrations/2025_06_19_032328_create_subscriber_access_records_table.php` - Migration for access records ✅
- `database/migrations/2025_06_19_032517_add_email_integration_settings_to_tenants_table.php` - Migration for ESP settings in tenants table ✅
- `app/Services/EmailServiceProvider/EmailServiceProviderInterface.php` - Abstract interface for ESP integration ✅
- `app/Services/EmailServiceProvider/KitServiceProvider.php` - Kit/ConvertKit API implementation ✅
- `app/Services/EmailServiceProvider/EmailServiceProviderManager.php` - ESP factory/manager class ✅
- `app/Providers/AppServiceProvider.php` - Updated to register EmailServiceProviderManager ✅
- `app/Filament/Resources/EmailSubscriberContentResource.php` - Filament admin resource for content management
- `app/Http/Controllers/EmailGatedContentController.php` - Controller for handling content access and verification
- `app/Mail/EmailVerificationMail.php` - Mailable for sending verification emails
- `app/Jobs/ProcessEmailVerification.php` - Queue job for ESP API interactions
- `app/Jobs/CleanupExpiredVerifications.php` - Job to clean up expired verification requests
- `resources/views/email-gated-content/access-form.blade.php` - Content access form view
- `resources/views/email-gated-content/verification-success.blade.php` - Post-verification success page
- `resources/views/emails/email-verification.blade.php` - Email verification template
- `routes/tenant.php` - Routes for email-gated content (pattern: /p/{channelname}/{slug})
- `tests/Feature/EmailGatedContentTest.php` - Feature tests for the complete workflow
- `tests/Unit/KitServiceProviderTest.php` - Unit tests for Kit API integration

### Notes

- All database tables should include tenant_id for multi-tenancy support
- Email fields must use Laravel's encrypted casting for security
- ESP API calls should be queued to prevent blocking user experience
- Use existing tenant settings system for ESP configuration
- Follow Laravel 11+ patterns and register services in bootstrap/app.php
- Tests should cover the complete user flow from content access to verification

## Tasks

- [x] 1.0 Database Schema & Models Setup
  - [x] 1.1 Create migration for `email_subscriber_contents` table with fields: id, tenant_id, title, slug, content, required_tag_id, created_at, updated_at
  - [x] 1.2 Create migration for `email_verification_requests` table with fields: id, email (encrypted), verification_token, content_id, tenant_id, expires_at, verified_at, created_at, updated_at
  - [x] 1.3 Create migration for `subscriber_access_records` table with fields: id, email (encrypted), tenant_id, tags_json, cookie_token, last_verified_at, created_at, updated_at
  - [x] 1.4 Create `EmailSubscriberContent` model with encrypted email casting, tenant relationship, and slug generation
  - [x] 1.5 Create `EmailVerificationRequest` model with encrypted email casting, automatic token generation, and expiration handling
  - [x] 1.6 Create `SubscriberAccessRecord` model with encrypted email casting, JSON tag storage, and cookie token generation
  - [x] 1.7 Add email integration settings to existing tenant settings system (ESP type, API credentials, cookie duration)
- [x] 2.0 Email Service Provider Integration Architecture  
  - [x] 2.1 Create `EmailServiceProviderInterface` with methods: checkSubscriber(), getTags(), addSubscriber(), addTagToSubscriber()
  - [x] 2.2 Implement `KitServiceProvider` class using Kit API v3 for subscriber management
  - [x] 2.3 Implement `KitServiceProvider` tag management methods (list tags, create tags, assign tags)
  - [x] 2.4 Add ESP configuration validation and connection testing functionality
  - [x] 2.5 Create ESP factory/manager class to resolve correct provider based on tenant settings
  - [x] 2.6 Implement proper error handling and API response validation for Kit API calls
  - [x] 2.7 Add rate limiting protection and retry logic for ESP API calls
- [x] 3.0 Content Management & Admin Interface
  - [x] 3.1 Create `EmailSubscriberContentResource` in Filament copying structure from existing `SubscriberContentResource`
  - [x] 3.2 Add tag selection dropdown that dynamically fetches available tags from configured ESP
  - [x] 3.3 Implement "Create New Tag" functionality within the content creation form
  - [x] 3.4 Add ESP configuration section to tenant settings with API key input and connection testing
  - [x] 3.5 Create content preview functionality showing how the access form will appear to visitors
  - [x] 3.6 Add bulk operations for email-gated content (duplicate, delete, export)
  - [x] 3.7 Implement content analytics view showing email conversion metrics per piece of content
- [x] 4.0 Email Verification & Access Control System
  - [x] 4.1 Create `EmailGatedContentController` with methods for showing access form and handling email submission
  - [x] 4.2 Implement email validation and duplicate request prevention logic
  - [x] 4.3 Create `ProcessEmailVerification` queue job for ESP API interactions (check subscriber, add subscriber, assign tags)
  - [x] 4.4 Create verification token generation with cryptographically secure random strings
  - [x] 4.5 Implement `EmailVerificationMail` mailable with branded templates and verification links
  - [x] 4.6 Create verification link handler that validates tokens and grants content access
  - [x] 4.7 Implement cookie-based access control with encrypted subscriber access records
  - [x] 4.8 Add automatic tag synchronization logic (check ESP if local tags don't grant access)
  - [x] 4.9 Create `CleanupExpiredVerifications` scheduled job to remove expired verification requests
  - [x] 4.10 Implement immediate access flow for existing subscribers with required tags
- [ ] 5.0 Frontend UI & User Experience Implementation
  - [ ] 5.1 Create email-gated content access form with email input, video thumbnail, and subscription agreement
  - [ ] 5.2 Add frontend email validation with helpful error messages and loading states
  - [ ] 5.3 Implement video thumbnail display logic when utm_content parameter matches tenant's video
  - [ ] 5.4 Create verification success page with immediate access to requested content
  - [ ] 5.5 Add subscription agreement checkbox with modal explanation when unchecked
  - [ ] 5.6 Implement proper error handling for ESP API failures with user-friendly messages
  - [ ] 5.7 Create responsive design ensuring mobile-friendly email entry experience
  - [ ] 5.8 Add loading animations and progress indicators for verification process
  - [ ] 5.9 Implement routes using pattern `/p/{channelname}/{slug}` for email-gated content
  - [ ] 5.10 Add analytics event firing for email conversion tracking (email entry → verification → content access) 