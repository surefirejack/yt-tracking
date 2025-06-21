# Async ESP Tag Synchronization - Setup Guide

## Overview

The email subscriber content gating system now uses **background jobs** for ESP (Email Service Provider) tag synchronization, providing a much better user experience.

## What Changed

### Before (Synchronous)
- User clicks content link
- Browser loading spinner for 5-20 seconds
- ESP API calls block the web request
- User sees nothing until all API calls complete
- Poor mobile experience, high abandonment rate

### After (Asynchronous)
- User clicks content link
- Immediate loading page with progress indicators
- ESP API calls happen in background
- Real-time status updates via JavaScript
- Seamless redirect when complete

## Setup Requirements

### 1. Database Migration
The migration has already been run, adding these fields to `subscriber_access_records`:

```sql
access_check_status         -- 'pending', 'processing', 'completed', 'failed'
has_required_access         -- boolean result
required_tag_id            -- tag being checked
access_check_started_at    -- job start time
access_check_completed_at  -- job completion time
access_check_error         -- error message if failed
```

### 2. Queue Worker
**IMPORTANT**: You must run queue workers for this to work:

```bash
# Start the queue worker
php artisan queue:work --queue=esp-sync,default --sleep=3 --tries=3

# Or use the provided script
./start-queue-worker.sh
```

### 3. Queue Configuration
Make sure your `.env` has:

```env
QUEUE_CONNECTION=database
```

## How It Works

### User Flow
1. **User visits content URL** → Immediate response (200ms)
2. **Loading page displays** → Shows progress and content preview
3. **Background job starts** → ESP API calls happen asynchronously
4. **JavaScript polling** → Checks status every second
5. **Automatic redirect** → To content or access form when complete

### Background Job (`CheckUserAccessJob`)
- Runs in `esp-sync` queue (dedicated to ESP operations)
- 2-minute timeout with 3 retry attempts
- Updates database with results
- Comprehensive error handling and logging

### API Endpoint
```
GET /api/check-access-status/{accessRecordId}
```

Returns:
```json
{
  "status": "completed",
  "hasAccess": true,
  "contentUrl": "/p/channel/content-slug",
  "accessFormUrl": "/p/channel/content-slug?access_denied=1",
  "processingTime": 3
}
```

## Files Added/Modified

### New Files
- `app/Jobs/CheckUserAccessJob.php` - Background job for ESP sync
- `resources/views/diamonds/email-gated-content/checking-access.blade.php` - Loading page
- `database/migrations/..._add_access_check_status_to_subscriber_access_records_table.php`
- `start-queue-worker.sh` - Helper script to start queue worker

### Modified Files
- `app/Http/Controllers/EmailGatedContentController.php` - Async logic
- `app/Models/SubscriberAccessRecord.php` - New status methods
- `routes/web.php` - API endpoint for status checking
- `docs/EMAIL_SUBSCRIBER_CONTENT_GATING.md` - Updated documentation

## Testing

### Manual Test
1. **Start queue worker**: `./start-queue-worker.sh`
2. **Visit content URL** with valid cookie but mismatched tags
3. **Should see**: Loading page immediately
4. **Should redirect**: To content or access form within 5 seconds

### Monitoring
```bash
# Watch job processing
tail -f storage/logs/laravel.log | grep "CheckUserAccessJob"

# Watch ESP API calls
tail -f storage/logs/laravel.log | grep "ESP"

# Check queue status
php artisan queue:monitor
```

## Performance Benefits

- **User Experience**: No more 5-20 second waits
- **Server Performance**: Web requests no longer blocked by ESP calls
- **Scalability**: Background workers handle ESP processing
- **Reliability**: Retry logic and proper error handling
- **Mobile Friendly**: Immediate response on all devices

## Troubleshooting

### Queue Worker Not Running
**Symptom**: Loading page never completes
**Solution**: Start queue worker with `./start-queue-worker.sh`

### ESP API Failures
**Symptom**: Jobs failing repeatedly
**Check**: ESP credentials and connectivity
**Logs**: `storage/logs/laravel.log`

### JavaScript Errors
**Symptom**: Loading page doesn't poll for updates
**Check**: Browser console for JavaScript errors
**Verify**: API endpoint is accessible

---

**Next Steps**: Monitor user experience and ESP API performance to optimize further if needed. 