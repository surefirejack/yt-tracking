# Email Subscriber Content Gating System

## Overview

The Email Subscriber Content Gating system allows content creators to gate valuable content behind email verification, growing their email lists while providing exclusive access to subscribers. This system integrates with Email Service Providers (ESP) like Kit/ConvertKit to manage subscribers and tags.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Database Schema](#database-schema)
3. [Access Control Flow](#access-control-flow)
4. [ESP Integration](#esp-integration)
5. [User Journey](#user-journey)
6. [Admin Interface](#admin-interface)
7. [Security Features](#security-features)
8. [Configuration](#configuration)
9. [API Endpoints](#api-endpoints)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)
12. [Uncovered Scenarios](#uncovered-scenarios)

## System Architecture

### Core Components

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend UI   │    │   Laravel App    │    │  Email Service  │
│                 │    │                  │    │   Provider      │
│ • Access Forms  │◄──►│ • Controllers    │◄──►│  (Kit/ConvertKit)│
│ • Verification  │    │ • Models         │    │                 │
│ • Content Pages │    │ • Jobs/Queues    │    │ • Subscribers   │
└─────────────────┘    └──────────────────┘    │ • Tags          │
                                               └─────────────────┘
```

### Key Models

- **EmailSubscriberContent**: Gated content with required tags
- **EmailVerificationRequest**: Temporary verification tokens
- **SubscriberAccessRecord**: Verified user access with tags
- **Tenant**: Multi-tenant configuration and ESP settings

## Database Schema

### email_subscriber_contents
```sql
id                    bigint      Primary key
tenant_id            bigint      Foreign key to tenants
title                varchar     Content title
slug                 varchar     URL-friendly identifier
content              longtext    HTML content body
required_tag_id      varchar     ESP tag ID required for access
youtube_video_url    varchar     Optional embedded video
file_paths           json        Array of uploaded file paths
file_names           json        Array of human-readable file names
cta_youtube_video_url varchar    Call-to-action video URL
created_at           timestamp
updated_at           timestamp
```

### email_verification_requests
```sql
id                   bigint      Primary key
email                varchar     Encrypted email address
verification_token   varchar     Unique verification token
content_id           bigint      Foreign key to email_subscriber_contents
tenant_id            bigint      Foreign key to tenants
expires_at           timestamp   Token expiration (2 hours)
verified_at          timestamp   Verification completion time
created_at           timestamp
updated_at           timestamp
```

### subscriber_access_records
```sql
id                   bigint      Primary key
email                varchar     Encrypted email address
tenant_id            bigint      Foreign key to tenants
tags_json            json        Array of ESP tag IDs user has
cookie_token         varchar     Random token for cookie authentication
last_verified_at     timestamp   Last ESP sync time
created_at           timestamp
updated_at           timestamp
```

## Access Control Flow

### 1. Initial Access Check

```mermaid
flowchart TD
    A[User Visits Content URL] --> B{Has Access Cookie?}
    B -->|Yes| C[Lookup Access Record]
    B -->|No| D[Show Access Form]
    C --> E{Cookie Valid & Not Expired?}
    E -->|Yes| F{Has Required Tag?}
    E -->|No| D
    F -->|Yes| G[Show Content]
    F -->|No| H[Sync Tags from ESP]
    H --> I{Now Has Required Tag?}
    I -->|Yes| G
    I -->|No| D
```

### 2. Email Verification Flow

```mermaid
flowchart TD
    A[User Submits Email] --> B{Email Valid?}
    B -->|No| C[Show Error]
    B -->|Yes| D{Already Has Access?}
    D -->|Yes| E[Set Cookie & Show Content]
    D -->|No| F{ESP Check: Is Subscriber?}
    F -->|Yes| G{Has Required Tag?}
    F -->|No| H[Queue: Add to ESP with Tag]
    G -->|Yes| I[Create Access Record]
    G -->|No| J[Queue: Add Required Tag]
    H --> K[Send Verification Email]
    J --> K
    I --> L[Set Cookie & Show Content]
    K --> M[User Clicks Verification Link]
    M --> N{Token Valid & Not Expired?}
    N -->|Yes| O[Complete ESP Integration]
    N -->|No| P[Show Expired Page]
    O --> Q[Create Access Record & Set Cookie]
    Q --> R[Show Content]
```

### 3. Cookie-Based Authentication

**Cookie Structure:**
- **Name**: `email_access_{tenant_id}`
- **Value**: 64-character random token
- **Duration**: Configurable (default 30 days)
- **Security**: HttpOnly, Secure flags

**Access Record Lookup:**
```php
// 1. Extract cookie token
$cookieToken = $request->cookie('email_access_' . $tenant->id);

// 2. Find access record
$accessRecord = SubscriberAccessRecord::where('cookie_token', $cookieToken)
    ->where('tenant_id', $tenant->id)
    ->first();

// 3. Verify expiration
$cookieDuration = $tenant->email_verification_cookie_duration_days ?? 30;
$isExpired = $accessRecord->last_verified_at->addDays($cookieDuration)->isPast();

// 4. Check required tag
$userTags = $accessRecord->tags_json ?? [];
$hasAccess = in_array($content->required_tag_id, $userTags);
```

## ESP Integration

### Supported Providers
- **Kit/ConvertKit** (Primary)
- Extensible architecture for additional providers

### Kit API Integration

**Base URL**: `https://api.convertkit.com/v3`

**Key Methods:**
```php
// Check if email is subscribed
$subscriber = $provider->checkSubscriber($email);

// Get available tags
$tags = $provider->getTags();

// Add subscriber via form
$subscriber = $provider->addSubscriber($email, $tags);

// Add tag to existing subscriber
$provider->addTagToSubscriber($email, $tagId);
```

### API Error Handling
- **Rate Limiting**: Automatic retry with exponential backoff
- **Network Failures**: Graceful degradation with user-friendly messages
- **Invalid Credentials**: Clear configuration error messages
- **Queue Processing**: All ESP calls are queued to prevent blocking

## User Journey

### New Visitor Flow
1. **Content Discovery**: User clicks link from YouTube video description
2. **Access Form**: Presented with email input and subscription agreement
3. **Email Submission**: System validates email and checks ESP
4. **Verification Email**: Sent if user needs to be added to ESP
5. **Email Verification**: User clicks link to complete process
6. **Content Access**: Immediate access with cookie set for future visits

### Returning Visitor Flow
1. **Cookie Check**: System validates existing access cookie
2. **Tag Verification**: Confirms user still has required tag
3. **Immediate Access**: Content displayed without re-verification
4. **Tag Sync**: If needed, fresh tags pulled from ESP

### Existing Subscriber Flow
1. **Email Submission**: User enters email on access form
2. **ESP Check**: System verifies subscription and tags
3. **Immediate Access**: If tags match, instant content access
4. **Tag Addition**: If missing required tag, added automatically

## Admin Interface

### Content Management (Filament)

**EmailSubscriberContentResource Features:**
- Content creation with rich text editor
- Tag selection from ESP (live API integration)
- File upload support (multiple files per content)
- YouTube video embedding
- Call-to-action video selection
- Content analytics and metrics

**ESP Configuration:**
- Provider selection (Kit/ConvertKit)
- API credential management
- Connection testing
- Cookie duration settings

### Analytics & Reporting
- Email conversion rates per content
- Tag assignment success rates
- Access patterns and popular content
- ESP integration health monitoring

## Security Features

### Data Protection
- **Email Encryption**: All email addresses encrypted at rest using Laravel's encryption
- **Secure Tokens**: Cryptographically secure verification tokens (64 characters)
- **Cookie Security**: HttpOnly and Secure flags prevent XSS attacks
- **CSRF Protection**: All forms protected against cross-site request forgery

### Access Control
- **Token Expiration**: Verification tokens expire after 2 hours
- **Cookie Expiration**: Configurable cookie duration with automatic cleanup
- **Tenant Isolation**: Strict tenant-based data segregation
- **Rate Limiting**: API call limits prevent abuse

### Data Validation
- **Email Format**: Server-side email validation
- **ESP Response**: All API responses validated before processing
- **File Uploads**: Type and size validation for content files
- **Input Sanitization**: All user inputs properly sanitized

## Configuration

### Environment Variables
```env
# ESP Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password

# Queue Configuration (for ESP API calls)
QUEUE_CONNECTION=database
```

### Tenant Settings
```php
// Email Service Provider Settings
'email_service_provider' => 'kit',
'esp_api_credentials' => [
    'api_key' => 'your_kit_api_key'
],
'email_verification_cookie_duration_days' => 30,
```

### Scheduled Jobs
```php
// routes/console.php
Schedule::job(CleanupExpiredVerifications::class)->hourly();
```

## API Endpoints

### Public Routes
```php
// Content access
GET  /p/{channelname}/{slug}
POST /p/{channelname}/{slug}/submit-email

// Email verification
GET  /email-verification/{tenantId}/{token}

// File downloads
GET  /p/{channelname}/{slug}/download/{filename}
```

### Route Parameters
- **channelname**: YouTube channel handle (without @)
- **slug**: Content URL identifier
- **token**: Email verification token
- **filename**: File name for download

## Testing

### Feature Tests
```bash
# Run email gating tests
php artisan test tests/Feature/EmailGatedContentTest.php

# Run ESP integration tests
php artisan test tests/Unit/KitServiceProviderTest.php
```

### Test Coverage
- ✅ Complete user verification flow
- ✅ Cookie-based access control
- ✅ ESP API integration
- ✅ File download functionality
- ✅ Multi-tenant isolation
- ✅ Error handling scenarios

### Manual Testing Checklist
- [ ] New user email verification flow
- [ ] Existing subscriber immediate access
- [ ] Cookie persistence across sessions
- [ ] File download functionality
- [ ] ESP tag synchronization
- [ ] Mobile responsive design
- [ ] Email delivery and formatting

## Troubleshooting

### Common Issues

**1. "View not found" errors**
```bash
# Clear view cache
php artisan view:clear

# Verify theme structure
ls -la resources/views/diamonds/
```

**2. ESP API failures**
```bash
# Check logs
tail -f storage/logs/laravel.log | grep ESP

# Test ESP connection
php artisan tinker
>>> $tenant = App\Models\Tenant::find(1);
>>> $provider = app('esp.manager')->getProviderForTenant($tenant);
>>> $provider->getTags();
```

**3. Cookie issues**
```bash
# Check cookie settings in browser dev tools
# Verify tenant ID in cookie name
# Confirm HttpOnly and Secure flags
```

**4. Queue processing**
```bash
# Start queue worker
php artisan queue:work

# Check failed jobs
php artisan queue:failed
```

### Debug Mode
```php
// Add to .env for detailed logging
LOG_LEVEL=debug
APP_DEBUG=true
```

## Uncovered Scenarios

### 1. Data Migration & Cleanup
**Scenario**: What happens when tenants want to:
- Switch from Kit to another ESP
- Bulk migrate existing subscribers
- Clean up old access records

**Current Gap**: No migration tools or bulk operations

**Recommended Solution**:
```php
// Command to migrate ESP data
php artisan esp:migrate --from=kit --to=mailchimp --tenant=123

// Command to cleanup expired records
php artisan email-gating:cleanup --older-than=90days
```

### 2. Advanced Tag Logic
**Scenario**: Content requiring multiple tags or tag hierarchies
- Content requiring "Premium" AND "Course-1" tags
- Tag expiration (temporary access)
- Tag-based content progression

**Current Gap**: Only supports single required tag

**Recommended Solution**:
```php
// Enhanced tag requirements
'required_tags' => [
    'all_of' => ['premium', 'course-1'],
    'any_of' => ['vip', 'lifetime'],
    'expires_at' => '2025-12-31'
]
```

### 3. Compliance & Privacy
**Scenario**: GDPR, CAN-SPAM, and other regulations
- Right to be forgotten requests
- Data export for users
- Consent management
- Unsubscribe handling

**Current Gap**: Limited privacy controls

**Recommended Solution**:
```php
// Privacy compliance features
Route::get('/privacy/export/{email}', 'PrivacyController@exportData');
Route::post('/privacy/forget/{email}', 'PrivacyController@forgetUser');
Route::get('/unsubscribe/{token}', 'PrivacyController@unsubscribe');
```

### 4. Advanced Analytics
**Scenario**: Detailed conversion tracking
- A/B testing different access forms
- Conversion funnel analysis
- Revenue attribution
- Cohort analysis

**Current Gap**: Basic event tracking only

**Recommended Solution**:
```php
// Enhanced analytics events
Event::dispatch(new ContentAccessRequested($content, $source));
Event::dispatch(new EmailVerificationCompleted($user, $conversionTime));
Event::dispatch(new ContentEngagementTracked($content, $timeSpent));
```

### 5. Content Scheduling & Automation
**Scenario**: Time-based content release
- Drip content campaigns
- Scheduled content publication
- Automatic tag assignment based on behavior
- Content expiration

**Current Gap**: All content is immediately available

**Recommended Solution**:
```php
// Content scheduling
'published_at' => '2025-07-01 00:00:00',
'expires_at' => '2025-12-31 23:59:59',
'drip_schedule' => [
    'delay_days' => 7,
    'prerequisite_content' => [1, 2, 3]
]
```

### 6. Multi-Language Support
**Scenario**: International audiences
- Translated content
- Localized email templates
- Regional ESP providers
- Currency-specific offers

**Current Gap**: English-only interface

**Recommended Solution**:
```php
// Localization support
'title' => [
    'en' => 'English Title',
    'es' => 'Título en Español',
    'fr' => 'Titre Français'
],
'verification_email_template' => 'emails.verification.{locale}'
```

### 7. Integration Webhooks
**Scenario**: Real-time ESP synchronization
- Webhook endpoints for tag changes
- Subscriber status updates
- Automatic access revocation
- Third-party integrations

**Current Gap**: Polling-based synchronization only

**Recommended Solution**:
```php
// Webhook handlers
Route::post('/webhooks/kit/subscriber-updated', 'WebhookController@kitSubscriberUpdated');
Route::post('/webhooks/kit/tag-removed', 'WebhookController@kitTagRemoved');
```

### 8. Performance Optimization
**Scenario**: High-traffic content
- CDN integration for file downloads
- Redis caching for access records
- Database query optimization
- ESP API response caching

**Current Gap**: Basic database caching only

**Recommended Solution**:
```php
// Performance enhancements
Cache::remember("user_tags_{$email}_{$tenant_id}", 3600, function() {
    return $this->syncUserTags($accessRecord, $tenant, $content);
});

// CDN file serving
Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes(60));
```

---

## Contributing

When extending this system:

1. **Follow Laravel 11+ patterns** (no service providers, use bootstrap/app.php)
2. **Maintain multi-tenant isolation** (always include tenant_id checks)
3. **Queue ESP API calls** (never block user requests)
4. **Encrypt sensitive data** (emails, tokens, credentials)
5. **Write comprehensive tests** (feature and unit tests)
6. **Update this documentation** (keep it current with changes)

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Laravel logs for error details
3. Test ESP connectivity and credentials
4. Verify theme file locations and paths
5. Confirm queue workers are running for background jobs 