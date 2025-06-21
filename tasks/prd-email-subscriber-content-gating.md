# Product Requirements Document: Email Subscriber Content Gating

## Introduction/Overview

This feature expands the existing YouTube subscriber content gating system to support email-based subscriber verification. Instead of checking YouTube subscriptions, the system will validate if visitors are subscribed to the tenant's email list (starting with Kit/ConvertKit) and have the required tag before granting access to exclusive content.

**Problem**: YouTube marketers want to grow their email lists by offering valuable content in exchange for email subscriptions, but need a seamless way to gate content behind email verification while maintaining their existing subscriber content workflow.

**Goal**: Create a parallel content gating system that validates email subscribers through ESP APIs, grows email lists through content incentives, and provides the same smooth user experience as the existing YouTube subscriber system.

## Goals

1. **Seamless Email List Growth**: Convert YouTube video viewers into verified email subscribers
2. **Flexible ESP Integration**: Start with Kit API v3 but design for multiple email service providers
3. **Consistent User Experience**: Mirror the existing subscriber content workflow and UI patterns
4. **Secure Verification**: Implement email verification with encrypted storage and time-limited links
5. **Smart Tag Management**: Automatically sync and manage subscriber tags between ESP and local database
6. **Tenant Control**: Give tenants full control over email provider settings and content gating rules

## User Stories

### Tenant Stories
- **As a YouTube marketer**, I want to create email-gated content so that I can grow my email list while providing value to viewers
- **As a content creator**, I want to specify which email tag grants access to my content so that I can segment my audience appropriately
- **As a business owner**, I want to configure my Kit account once so that all my content uses the same email verification system
- **As a marketer**, I want to see which video drove traffic to my gated content so that I can optimize my video descriptions and calls-to-action

### Visitor Stories
- **As a YouTube viewer**, I want to easily enter my email to access promised content so that I can get the value mentioned in the video
- **As a potential subscriber**, I want clear information about what I'm agreeing to so that I feel comfortable providing my email
- **As a returning visitor**, I want to be automatically recognized so that I don't have to re-verify my email for content I should have access to
- **As an existing subscriber**, I want immediate access to content if I already have the required tag

## Functional Requirements

### 1. Database & Models
1.1. Create `EmailSubscriberContent` model mirroring `SubscriberContent` with additional `required_tag_id` field
1.2. Create `email_verification_requests` table with fields: id, email (encrypted), verification_token, content_id, tenant_id, expires_at, verified_at
1.3. Create `subscriber_access_records` table with fields: id, email (encrypted), tenant_id, tags_json, cookie_token, last_verified_at
1.4. Add email integration settings to tenant settings system

### 2. Email Service Provider Integration
2.1. Create abstract `EmailServiceProvider` interface with methods: checkSubscriber(), getTags(), addSubscriber(), addTagToSubscriber()
2.2. Implement `KitServiceProvider` class using Kit API v3 endpoints for subscriber and tag management
2.3. Store ESP credentials securely in tenant settings (API key for Kit)
2.4. Handle API failures gracefully with queued job retry logic

### 3. Content Management
3.1. Create new Filament resource `EmailSubscriberContentResource` copying `SubscriberContentResource` structure
3.2. Add tag selection dropdown that fetches available tags from configured ESP
3.3. Add ability to create new tags via ESP API from the content creation form
3.4. Use route pattern `/p/{channelname}/{slug}` instead of `/s/{channelname}/{slug}`

### 4. Access Verification Flow
4.1. Display content access form with email input, video thumbnail (if utm_content matches video_id), and subscription agreement checkbox
4.2. Validate email format on frontend and backend
4.3. Check if email already has access via cookie/database lookup
4.4. For new emails: send verification email with time-limited token (2-hour expiration)
4.5. For existing subscribers: check ESP for current tags and grant immediate access if tag exists

### 5. Email Verification System
5.1. Generate secure verification tokens for email confirmation links
5.2. Send branded verification emails from platform with unique verification URLs
5.3. Handle verification link clicks: validate token, add subscriber to ESP with required tag, create access record, set cookie
5.4. Clean up expired verification requests (2-hour cleanup job)

### 6. Cookie & Session Management
6.1. Create secure cookies with tenant-configurable duration pointing to subscriber access records
6.2. Store encrypted email and tags JSON in access records for quick validation
6.3. Implement automatic tag syncing: if local tags don't grant access, check ESP for updates
6.4. Add missing tags to both ESP and local records when needed

### 7. UI/UX Components
7.1. Content access page with headline "You're About to Get Access to {content name}"
7.2. Email input with placeholder "yourbestemail@gmail.com" and helper text about confirmation
7.3. Pre-checked subscription agreement checkbox with channel name
7.4. Video thumbnail display when utm_content parameter matches tenant's video
7.5. Loading states and error handling for API calls
7.6. Verification success page with access to requested content

### 8. Settings & Configuration
8.1. Add "Email Integration" tab to tenant settings
8.2. ESP selection dropdown (Kit for now, extensible for future providers)
8.3. API credentials configuration fields
8.4. Global cookie duration setting
8.5. Test connection functionality

### 9. Error Handling & Edge Cases
9.1. Handle ESP API downtime with user-friendly messages and retry options
9.2. Prevent multiple verification emails for same email/content combination
9.3. Handle checkbox unchecking with modal explanation
9.4. Validate email format with helpful error messages
9.5. Handle expired verification links gracefully

### 10. Analytics & Events
10.1. Fire "email conversion" event when verification completes successfully
10.2. Track conversion funnel: email entry → verification → content access
10.3. Associate conversions with source videos via utm_content parameter

## Non-Goals (Out of Scope)

- Multiple email providers in initial release (design for extensibility only)
- Advanced tag logic (multiple required tags, tag hierarchies)
- Email template customization (use platform templates)
- Real-time webhook integrations with ESPs
- Migration tools from YouTube-gated to email-gated content
- Advanced analytics dashboard (events only for now)
- Custom verification email sending schedules
- Integration with email automation sequences

## Design Considerations

### ESP Integration Architecture
- Use Strategy pattern for different email service providers
- Store provider-specific configurations in JSON settings field
- Design API abstraction layer that can accommodate different provider data structures
- Plan for rate limiting and API quota management

### Security Requirements
- Encrypt all stored email addresses using Laravel encryption
- Use cryptographically secure tokens for verification links
- Implement proper CSRF protection on all forms
- Validate all ESP API responses before processing
- Store API credentials using Laravel's encrypted casting

### Performance Considerations
- Use queued jobs for all ESP API calls to prevent blocking user experience
- Implement database indexing on frequently queried fields (tenant_id, cookie_token, email hashes)
- Cache ESP tag lists to reduce API calls during content creation
- Use efficient JSON operations for tag storage and querying

## Technical Considerations

### Database Design
- Use encrypted casting for email fields to ensure automatic encryption/decryption
- Index verification tokens and cookie tokens for fast lookup
- Consider soft deletes for audit trail on verification requests
- Use JSON column type for tags storage with appropriate database-level validation

### Kit API Integration
Based on [Kit API v3 documentation](https://developers.kit.com/api-reference/v3/overview), implement:
- Subscriber lookup and creation endpoints
- Tag management (list, create, assign to subscriber)
- Error handling for API rate limits and failures
- Proper authentication using API keys

### Code Reusability
- Extend existing SubscriberContent patterns rather than duplicating code
- Share common UI components between YouTube and email gating systems
- Reuse existing tenant resolution and routing patterns
- Maintain consistent styling with diamonds theme system

## Success Metrics

### Primary Success Criteria
1. **Complete Workflow Test**: Tenant can create email-gated content, generate access links, and visitors can successfully verify emails and access content
2. **Subscription Flow**: Non-subscribers receive verification emails, get added to ESP with correct tags, and gain immediate content access
3. **Returning User Experience**: Cookied users with appropriate tags get immediate access without re-verification
4. **Tag Synchronization**: System correctly syncs tags between local database and ESP, adding missing tags as needed

### Tenant Success Indicators
- Intuitive content creation process mirroring existing subscriber content workflow
- Reliable ESP integration with clear error messages for configuration issues
- Smooth email verification process with high completion rates
- Effective email list growth through content incentives

### User Experience Validation
- Clear value proposition on access forms with video context
- Seamless verification process with helpful status updates
- Immediate access after email confirmation
- Consistent branding and user experience across all touchpoints

## Open Questions

1. **ESP Rate Limiting**: How should we handle Kit API rate limits during high-traffic periods?
2. **Tag Naming Conventions**: Should we enforce any naming standards for tags created through our interface?
3. **Data Retention**: How long should we keep verified email records after cookie expiration?
4. **Duplicate Content**: Should tenants be able to have the same content available through both YouTube and email gating simultaneously?
5. **Mobile Experience**: Any specific mobile optimizations needed for the email entry flow?
6. **International Compliance**: Do we need specific handling for GDPR, CAN-SPAM, or other email regulations?
7. **ESP Migration**: If a tenant wants to switch ESPs, how should we handle existing access records?

---

*This PRD serves as the foundation for implementing email subscriber content gating. All requirements should be validated against the existing codebase patterns and extended incrementally to maintain system stability.* 