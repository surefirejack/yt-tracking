# Product Requirements Document: YouTube Subscribers Members Area

## Introduction/Overview

This feature enables tenants to create exclusive members-only areas for their YouTube subscribers. Subscribers can log in with Google/YouTube to verify their subscription status and access exclusive content including downloadable files, videos, and rich text content. The system includes subscription verification, content management, referral tracking, and a clean user experience across login, dashboard, and content pages.

**Problem:** Content creators on YouTube want to provide exclusive value to their subscribers but lack a simple way to verify subscription status and gate content accordingly.

**Goal:** Create a seamless members area where YouTube creators can offer subscriber-only content with automatic subscription verification.

## Goals

1. **Subscription Verification**: Automatically verify YouTube subscription status via Google OAuth and YouTube API v3
2. **Content Gating**: Restrict access to content based on verified subscription status
3. **Easy Content Management**: Allow tenants to easily create, edit, and manage subscriber content
4. **User-Friendly Experience**: Provide intuitive login and content access flows for subscribers
5. **Referral Tracking**: Track and attribute new signups from "Powered by" links
6. **Flexible Routing**: Support clean URLs with channel-based routing structure

## User Stories

**As a YouTube Creator (Tenant):**
- I want to create exclusive content for my subscribers so that I can provide additional value
- I want to verify someone is subscribed to my channel before they access content
- I want to customize login messaging and add my photo to build trust
- I want to upload files and create rich text content easily
- I want to set how long subscription verification is cached
- I want to choose which of my YouTube videos to feature on content pages
- I want to set a custom logout redirect URL

**As a YouTube Subscriber (End User):**
- I want to easily log in with my Google account to verify my subscription
- I want to access exclusive content from creators I'm subscribed to
- I want to download files and view content seamlessly
- I want to see all available content in a dashboard
- I want to stay logged in across sessions

**As a Platform Owner:**
- I want to track referrals from "Powered by" links to measure growth
- I want to convert referral traffic into new tenant signups

## Functional Requirements

### 1. Authentication System
1.1. Users must be able to log in via Google OAuth with YouTube API v3 readonly access
1.2. System must verify subscription status to the tenant's YouTube channel
1.3. Login sessions must persist based on "remember me" functionality
1.4. System must cache subscription verification for tenant-configurable number of days
1.5. Users who lose subscription access outside cache duration must be denied access

### 2. Routing Structure
2.1. Members area must be accessible at `/s/{channelname}` where channelname is automatically derived from tenant's YouTube channel (lowercase)
2.2. Individual content must be accessible at `/s/{channelname}/{slug}` where slug is generated from content title
2.3. `/s/{channelname}/community` route must be reserved (not implemented in prototype)
2.4. System must handle route conflicts and invalid channel names gracefully

### 3. Login Page (`/s/{channelname}/{slug}` when not authenticated)
3.1. Must display the specific content title user was trying to access
3.2. Must show "Login with Google" button
3.3. Must display tenant-customizable text below login button
3.4. Must display tenant's uploaded profile image
3.5. Must show tenant's YouTube channel banner image as header

### 4. Access Denied Page
4.1. Must display when user is not subscribed to tenant's channel
4.2. Must provide link to tenant's YouTube channel (opens in new tab)
4.3. Must include courteous messaging encouraging subscription
4.4. Must provide "try again" functionality after subscription

### 5. Content Management (Tenant Dashboard)
5.1. Tenants must be able to create, read, update, and delete subscriber content
5.2. Content creation must support:
   - Rich text editor for content body
   - File uploads (PDF, JPG, JPEG, PNG, ZIP) with 50MB size limit
   - YouTube video selection from tenant's channel videos
   - Custom page titles (used for slug generation)
5.3. Tenants must be able to set subscription cache duration (in days)
5.4. Tenants must be able to customize login page text and upload profile image
5.5. Tenants must be able to set logout redirect URL

### 6. Subscriber Dashboard (`/s/{channelname}`)
6.1. Must display all available content as cards
6.2. Each card must show content title, description preview, and thumbnail
6.3. Must display tenant's channel banner as header
6.4. Must include logout functionality
6.5. Cards must link to individual content pages

### 7. Content Pages (`/s/{channelname}/{slug}`)
7.1. Must display full rich text content
7.2. Must show embedded YouTube video if selected by tenant
7.3. Must provide download buttons for uploaded files
7.4. Must display tenant's channel banner as header
7.5. Must include navigation back to dashboard

### 8. Data Storage
8.1. System must store user data from YouTube login (email, profile picture, name)
8.2. System must store subscription verification results with cache timestamps
8.3. System must store tenant content with rich text, file paths, and video URLs
8.4. System must store referral tracking data

### 9. Referral System
9.1. All member pages must include "Powered by [App Name]" link in footer
9.2. Referral links must route through controller for click tracking
9.3. System must track total clicks per tenant
9.4. System must set first-party cookies for referral attribution
9.5. Successful signups from referrals must be recorded in `tenant_referrals` table
9.6. Referral source must default to "members only link"

### 10. File Management
10.1. System must handle file uploads with validation
10.2. Supported file types: PDF, JPG, JPEG, PNG, ZIP
10.3. Maximum file size: 50MB per file
10.4. Files must be stored securely and only accessible to verified subscribers
10.5. System must generate secure download URLs

## Non-Goals (Out of Scope)

- Community forum functionality (`/s/{channelname}/community`)
- Advanced analytics for tenants (planned for version 2)
- Premium content tiers beyond subscription verification (planned for version 3)
- Rate limiting on YouTube API calls
- Real-time subscription status checking
- Multiple file uploads per content page in prototype
- Advanced user roles or permissions
- Content scheduling or publishing workflows
- Social sharing features
- Comment systems on content pages

## Design Considerations

- Use tenant's YouTube channel banner (`banner_image_url`) as header image across all member pages
- Login page should feel trustworthy with tenant's profile image and custom messaging
- Content cards should be visually appealing and clearly show available content
- Download buttons should be prominent and clearly labeled
- "Powered by" footer link should be tasteful and not intrusive
- Rich text editor should support basic formatting (bold, italic, links, lists)
- Mobile-responsive design for all member area pages

## Technical Considerations

- Integrate with existing YouTube API service (`YouTubeApiService`) from Settings.php
- Leverage existing Google OAuth implementation
- Use Laravel's file storage system for secure file handling
- Implement proper caching strategy for subscription verification
- Use existing tenant system and database structure
- Generate SEO-friendly slugs from content titles
- Implement proper session management for "remember me" functionality
- Use middleware for subscription verification on protected routes

## Database Schema Requirements

### New Tables:
- `subscriber_content` - stores tenant's member content
- `subscriber_users` - stores subscriber login data and subscription cache
- `tenant_referrals` - tracks referral clicks and conversions
- `content_downloads` - tracks file download activity

### New Columns:
- `tenants.subscription_cache_days` - how long to cache subscription verification
- `tenants.logout_redirect_url` - custom logout redirect
- `tenants.member_login_text` - custom text for login page
- `tenants.member_profile_image` - tenant's profile image for login page

## Success Metrics

- **Primary Success**: Working prototype where tenants can direct subscribers to log in and access gated content
- Successful Google OAuth integration with YouTube subscription verification
- Functional content management system for tenants
- Proper file upload and download functionality
- Working referral tracking system
- Clean, intuitive user experience across all member pages

## Open Questions

1. Should we implement any abuse prevention for repeated login attempts?
2. How should we handle edge cases where YouTube API is temporarily unavailable?
3. Should there be any notification system for tenants when new subscribers access content?
4. What should happen to existing user sessions if a tenant changes their channel name?
5. Should we implement any content versioning or revision history?

---

**Target Audience**: Junior Developer
**Implementation Priority**: High - Core MVP Feature
**Estimated Complexity**: High (involves OAuth, API integration, file management, and multiple user interfaces) 