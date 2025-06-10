# YouTube Attribution Tracker

A powerful Laravel SaaS application built with the TALL stack to help online marketers and entrepreneurs get accurate attribution data for their YouTube marketing campaigns.

## 🎯 What We Do

This platform solves a critical problem for online marketers: **tracking the true performance of their YouTube videos**. By providing unique shortlinks for each video, marketers can finally see the complete customer journey from YouTube click to final conversion.

## 👥 Target Audience

**Online Marketers & Entrepreneurs** who:
- Run YouTube marketing campaigns
- Need accurate ROI data from their video content
- Want to optimize their video marketing strategy with real data
- Struggle with YouTube's limited attribution tracking

## 🚀 Key Features

### Smart Shortlink Generation
- Create unique, branded shortlinks for each YouTube video
- Easy-to-remember URLs that maintain your brand identity
- Automatic link management and organization

### Comprehensive Tracking
- **Click Tracking**: Monitor every visitor from your YouTube videos
- **Lead Conversion Tracking**: See which videos generate the most leads
- **Sale Conversion Tracking**: Track revenue directly back to specific videos
- **Attribution Analysis**: Understand the complete customer journey

### Performance Analytics
- Real-time conversion data
- Video-by-video performance breakdown
- ROI calculations and insights
- Conversion funnel analysis

## 🛠️ Built With The TALL Stack

- **[Tailwind CSS](https://tailwindcss.com/)** - Utility-first CSS framework for rapid UI development
- **[Alpine.js](https://alpinejs.dev/)** - Lightweight JavaScript framework for interactive components  
- **[Laravel](https://laravel.com/)** - Robust PHP framework for the backend API and business logic
- **[Livewire](https://laravel-livewire.com/)** - Full-stack framework for dynamic interfaces without complex JavaScript

## 🎬 How It Works

1. **Create Your Campaign**: Set up tracking for your YouTube video
2. **Get Your Shortlink**: Receive a unique, trackable URL
3. **Use in Video Description**: Add the shortlink to your YouTube video
4. **Track Performance**: Monitor clicks, leads, and sales in real-time
5. **Optimize**: Use data insights to improve your video marketing ROI

## 💼 Business Value

- **Accurate Attribution**: Know exactly which videos drive your best customers
- **ROI Optimization**: Focus your efforts on highest-performing content
- **Data-Driven Decisions**: Make marketing choices based on real conversion data
- **Competitive Advantage**: Outperform competitors who guess at video performance

## 🎥 Perfect For

- **Course Creators** tracking student sign-ups from YouTube tutorials
- **SaaS Companies** measuring trial conversions from product demos  
- **E-commerce Brands** attributing sales to specific product videos
- **Coaches & Consultants** tracking booking conversions from educational content
- **Affiliate Marketers** optimizing video campaigns for maximum commissions

## 🛠️ Development

For developers working on this codebase:
- See [Filament Development Guide](docs/FILAMENT_DEVELOPMENT_GUIDE.md) for important patterns and gotchas
- Built with Laravel 11+ using the latest features and best practices
- Multi-tenant architecture with Filament dashboard

### 🧪 Analytics Test Mode

For development and testing, the application includes a test mode for analytics data that generates realistic sample data instead of making API calls to Dub.co.

**Quick Setup:**
```bash
# Enable test mode
php artisan dub:test-mode enable

# Check current status
php artisan dub:test-mode status

# Disable test mode (use real API)
php artisan dub:test-mode disable
```

**Environment Configuration:**
```env
# Add to your .env file
DUB_TEST_MODE=true    # Enable test mode
DUB_TEST_MODE=false   # Use real Dub.co API
```

**Features:**
- 📊 Generates realistic analytics data for 30-day periods
- 🎯 Different performance metrics based on video IDs and UTM parameters
- 📈 Includes weekend traffic patterns and realistic conversion rates
- 🔄 Consistent data generation (same parameters = same data)
- 🚀 No API rate limits or external dependencies during development

**Test Data Includes:**
- Click, lead, and sales metrics
- Revenue tracking with realistic amounts
- Device and browser distribution
- Geographic data (countries)
- Referrer information
- UTM parameter variations

This allows you to:
- Test the analytics dashboard without real traffic
- Develop UI components with realistic data
- Demo the application with convincing metrics
- Avoid API rate limits during development

---

*Transform your YouTube marketing from guesswork into a data-driven growth engine.*
