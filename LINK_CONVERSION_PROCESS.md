# YouTube Video Link Conversion Process

This document explains the process of converting URLs from YouTube video descriptions into trackable shortlinks.

## Overview

The link conversion process allows users to select URLs from a video's description and convert them into trackable shortlinks. This process includes automatic filtering of social media links, duplicate prevention, and now supports updating the video description with tracking links.

## Data Model Enhancements

- **description_new**: A new column in the `yt_videos` table. If not null, this is used for extracting and updating links. It contains the most recent version of the YouTube video description as synced from the client. The original `description` column is preserved for possible reversion.
- **converted_links**: A new integer column in the `yt_videos` table. It tracks the number of links that have been replaced with tracking links in the description.

## Process Flow

### 1. Opening the Conversion Interface
- User clicks the "Convert Links" button in the video list
- A slideover panel opens with two main sections:
  - Choose Links to Update
  - Excluded Links

### 2. Link Extraction and Deduplication
- The system extracts URLs from `description_new` if it is not null, otherwise from `description`.
- Only unique links are shown in the checkbox options, even if a link appears multiple times in the description.
- Excluded domains (e.g., YouTube, social media) are filtered out as before.

### 3. User Interface Elements
#### Choose Links to Update Section
- Checkbox list of unique allowed URLs
- Existing links marked with "✓ (Already created)"
- Pre-checked boxes for already created links

#### Excluded Links Section
- List of excluded URLs
- Red "×" symbol for each excluded link
- Clear indication of why links are excluded

### 4. Link Conversion and Description Update
When the user submits the form:
1. The system processes selected URLs.
2. For each new URL:
   - Creates a new Link record with:
     - Tenant ID
     - Original URL
     - YouTube video ID
     - Title (prefixed with "From " + video title)
     - Status: "pending"
   - Dispatches CreateLinkJob for processing
3. For each existing link:
   - Associates the link with the current video (many-to-many)
   - Ensures the video-specific tag exists and is attached
   - Updates Dub.co with the new tag
4. **All instances** of each selected link in the description (`description_new` if present, otherwise `description`) are replaced with the corresponding tracking link (if available).
5. The `converted_links` column is updated to reflect the number of replacements made.
6. The original description is preserved in the `description` column for possible reversion.

### 5. Link Processing
The CreateLinkJob handles:
- Creation of video-specific tag (format: "yt-video-{video_id}")
- Tag association with the link
- Setup of tracking parameters
- UTM parameter configuration

### 6. Database Structure
- Many-to-many relationship between `Link` and `YtVideo`
- Pivot table: `link_yt_video`
- Enables tracking of link-to-video associations
- Supports analytics and reporting features

## Technical Implementation

### Key Models
- `YtVideo`: Handles video data and URL extraction, and now manages which description field is used for link processing and replacement.
- `Link`: Manages tracking links and their relationships
- `CreateLinkJob` / `UpdateLinkJob`: Process new and existing links asynchronously, update tags, and trigger Dub.co updates

### URL Processing
```php
// URL extraction pattern
$urlPattern = '/\b(?:https?:\/\/|www\.)[^\s<>"]+/i';
```
- Extraction uses `description_new` if present, otherwise `description`.
- Only unique links are shown for conversion.
- All instances of a chosen link are replaced in the description when converted.

## Best Practices
- Always check for existing links before creation
- Process links asynchronously to maintain UI responsiveness
- Provide clear feedback to users about the conversion status
- Maintain proper relationships between videos and links
- Use appropriate error handling and logging
- Preserve the original description for possible reversion

## Monitoring
- Job queue monitoring
- Link creation and update success rates
- Description update and replacement counts
- Error rate tracking