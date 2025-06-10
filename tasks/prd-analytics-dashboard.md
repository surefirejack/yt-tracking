# Product Requirements Document: Analytics Dashboard

## Introduction/Overview

The Analytics Dashboard feature will provide YouTube Attribution Tracker users with comprehensive analytics capabilities to analyze their link performance data. This feature addresses the critical need for users to understand which videos are driving the most valuable traffic and which destination URLs are performing best across their YouTube marketing campaigns.

The dashboard will leverage data from [Dub.co's Analytics API](https://dub.co/docs/api-reference/endpoint/retrieve-analytics) to provide actionable insights that help users optimize their video marketing strategy and improve ROI.

**Problem Statement**: Users currently cannot easily analyze their link performance data to answer key questions like "Which video is generating the most leads?" or "Which destination URL receives the most traffic from my videos?"

**Solution Goal**: Create an analytics dashboard that enables users to make data-driven decisions about their YouTube marketing campaigns by providing clear, actionable performance insights.

## Goals

1. **Enable Video Performance Analysis**: Users can select any of their YouTube videos and view comprehensive analytics for all associated links
2. **Enable Destination URL Analysis**: Users can analyze traffic patterns for specific destination URLs across all their links
3. **Provide Time-Based Filtering**: Users can filter analytics data using predefined time intervals to understand performance trends
4. **Display Actionable Metrics**: Present both raw metrics (clicks, leads, sales) and calculated metrics (conversion rates, revenue per click) in an intuitive format
5. **Support Decision-Making**: Provide insights that directly inform users' next video creation and marketing strategies

## User Stories

### Primary User Stories

1. **As a course creator**, I want to select one of my YouTube videos and see how many clicks, leads, and sales all its associated links generated, so that I can understand which videos are most effective at driving conversions.

2. **As an affiliate marketer**, I want to choose a specific destination URL (like my landing page) and see which of my videos are sending the most traffic to it, so that I can create more content similar to my top-performing videos.

3. **As a SaaS founder**, I want to filter my analytics by different time periods (last 7 days, 30 days, etc.) so that I can track performance trends and measure the impact of recent campaigns.

4. **As an online entrepreneur**, I want to see conversion rates and revenue per click for my links, not just raw numbers, so that I can identify my most profitable traffic sources.

5. **As a YouTube marketer**, I want to filter my analytics by UTM parameters so that I can understand which campaign strategies are working best.

### Secondary User Stories

6. **As a data-driven marketer**, I want to refresh my analytics data manually so that I can see the most current performance without waiting for automatic updates.

7. **As a content strategist**, I want to view analytics in both chart and table formats so that I can identify trends and drill into specific performance details.

## Functional Requirements

### Core Analytics Views

1. **Video Performance View**
   - Users can select a YouTube video from their `yt_videos` table (filtered by tenant)
   - System displays aggregated analytics for all links associated with that video using the `yt-video-{id}` tag pattern
   - Metrics shown: clicks, leads, sales, sale amount, plus calculated conversion rates and revenue per click

2. **Destination URL Performance View**
   - Users can select a destination URL from their `links` table (based on `original_url` field)
   - System displays analytics for all links pointing to that destination URL
   - Shows which videos are associated with those links (or "None" if no video association)
   - Sortable table format prioritizing links by traffic volume

### Data Integration

3. **Dub.co Analytics API Integration**
   - Retrieve analytics using existing `externalId` and `tenantId` parameters
   - Use `top_urls` groupBy parameter for destination URL analysis
   - Use tag-based filtering (`yt-video-{id}`) for video-specific analytics
   - Support for composite event types (clicks, leads, sales)

4. **Time-Based Filtering**
   - Support Dub.co's predefined intervals: `24h`, `7d`, `30d`, `90d`, `1y`, `mtd`, `qtd`, `ytd`, `all`
   - Use [Filament's DateTimePicker component](https://filamentphp.com/docs/3.x/forms/fields/date-time-picker) for date range selection
   - Default to 30-day view

5. **UTM Parameter Filtering**
   - Filter analytics by utm_source, utm_medium, utm_campaign, utm_term, utm_content
   - Use existing UTM data stored in the `links` table

### User Interface Components

6. **Dashboard Layout**
   - Implement within existing `AnalyticsResource.php` Filament resource
   - Primary navigation item in dashboard sidebar
   - Tabbed interface: "Video Performance" and "URL Performance"

7. **Visualization Components**
   - Line charts for time-series data (default view)
   - Sortable data tables for detailed link performance
   - Key metrics cards showing totals and calculated percentages
   - Interactive filters panel

8. **Data Management**
   - Laravel caching for API responses to avoid rate limits
   - Manual refresh button to update cached data
   - Tenant-based data isolation using existing multi-tenancy system

## Non-Goals (Out of Scope)

1. **Export/Download Functionality**: Users cannot export analytics data in this version
2. **Real-time Updates**: Dashboard shows cached data, not live/real-time analytics
3. **Geographic Analytics**: No country/city-based analytics in this version
4. **Device/Browser Analytics**: No device or browser-based filtering
5. **Drill-down Navigation**: Users cannot drill from video-level to individual link analytics
6. **Custom Date Ranges**: Only predefined intervals, no custom date picker in v1
7. **Advanced Visualizations**: No pie charts, heat maps, or complex chart types
8. **Email Reports**: No automated or scheduled reporting features
9. **Comparison Views**: No side-by-side video or time period comparisons
10. **External Analytics Integration**: No Google Analytics or other third-party integrations

## Design Considerations

### Filament Implementation
- Utilize Filament's existing component system for consistent UI/UX
- Implement as custom pages within the `AnalyticsResource`
- Use Filament's table builder for sortable data displays
- Leverage Filament's form components for filters

### Chart Library
- Use Chart.js or similar JavaScript charting library compatible with Filament
- Ensure responsive design for mobile/tablet viewing
- Maintain consistent color scheme with existing dashboard

### Performance Considerations
- Cache Dub.co API responses for 15-30 minutes to balance freshness with rate limits
- Implement loading states for API calls
- Optimize database queries for video and link lookups

## Technical Considerations

### API Integration
- Build upon existing Dub.co API implementation patterns found in codebase
- Use existing authentication and rate limiting strategies
- Handle API errors gracefully with user-friendly messages

### Database Structure
- Leverage existing relationships: `links` → `yt_videos` → `tenants`
- Use existing tag system (`yt-video-{id}`) for video association
- No new database migrations required

### Multi-tenancy
- Ensure all analytics data is properly filtered by `tenant_id`
- Use existing `Filament::getTenant()` for context
- Maintain data isolation between tenants

### Caching Strategy
- Use Laravel's cache system with tenant-specific keys
- Cache analytics data by combination of: tenant_id, view_type, filters, date_range
- Implement cache invalidation on manual refresh

## Success Metrics

### User Engagement Metrics
1. **Feature Adoption**: 80% of active users access Analytics dashboard within first month
2. **Session Duration**: Average session time in Analytics section exceeds 3 minutes
3. **Return Usage**: 60% of users return to Analytics dashboard within one week

### Business Impact Metrics
4. **Decision Making**: Users can identify their top-performing video within 2 clicks
5. **Content Strategy**: Users can determine which destination URLs receive most traffic within 3 clicks
6. **Performance Insights**: Users can calculate conversion rates for their links without external tools

### Technical Performance Metrics
7. **Load Time**: Analytics views load within 2 seconds
8. **API Efficiency**: Less than 100 Dub.co API calls per user per day
9. **Error Rate**: Less than 1% of analytics requests result in errors

## Open Questions

1. **API Rate Limits**: Should we implement queue-based API calls for users with high link volumes?
2. **Data Retention**: How long should we cache analytics data before requiring refresh?
3. **Notification System**: Should users be notified when their analytics data is refreshed?
4. **Mobile Experience**: What level of mobile optimization is required for the analytics dashboard?
5. **Tenant Limitations**: Should there be any limits on the number of videos or URLs a tenant can analyze?
6. **Default Views**: What should be the default landing view when users first access Analytics?
7. **Error Handling**: How should we handle cases where Dub.co API is unavailable or returns errors?
8. **Performance Thresholds**: At what point should we warn users about potential slow loading due to large datasets?

---

**Target Implementation Timeline**: 2-3 weeks for MVP
**Primary Developer Audience**: Junior Laravel/Filament developer
**Dependencies**: Existing Dub.co API integration, Filament UI framework, Laravel caching system 