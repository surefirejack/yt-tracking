# YouTube Video Link Conversion Process

This document explains the process of converting URLs from YouTube video descriptions into trackable shortlinks.

## Overview

The link conversion process allows users to select URLs from a video's description and convert them into trackable shortlinks. This process includes automatic filtering of social media links and duplicate prevention.

## Process Flow

### 1. Opening the Conversion Interface
- User clicks the "Convert Links" button in the video list
- A slideover panel opens with two main sections:
  - Choose Links to Update
  - Excluded Links

### 2. Link Categorization
The system automatically categorizes URLs from the video description into:
- **Allowed Links**: URLs that can be converted to tracking links
- **Excluded Links**: URLs from restricted domains:
  - youtu.be
  - youtube.com
  - facebook.com
  - instagram.com
  - twitter.com
  - linkedin.com

### 3. User Interface Elements
#### Choose Links to Update Section
- Checkbox list of allowed URLs
- Existing links marked with "✓ (Already created)"
- Pre-checked boxes for already created links

#### Excluded Links Section
- List of excluded URLs
- Red "×" symbol for each excluded link
- Clear indication of why links are excluded

### 4. Link Creation Process
When user submits the form:
1. System processes selected URLs
2. For each new URL:
   - Creates a new Link record with:
     - Tenant ID
     - Original URL
     - YouTube video ID
     - Title (prefixed with "From " + video title)
     - Status: "pending"
   - Dispatches CreateLinkJob for processing
3. Shows appropriate notification:
   - Success: "X new links have been queued for processing"
   - Info: "All selected links have already been created"

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
- `YtVideo`: Handles video data and URL extraction
- `Link`: Manages tracking links and their relationships
- `CreateLinkJob`: Processes new links asynchronously

### URL Processing
```php
// URL extraction pattern
$urlPattern = '/\b(?:https?:\/\/|www\.)[^\s<>\ 