<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\YtVideo;

class VideoDetailsController extends Controller
{
    public function handleDetails(Request $request)
    {

        // Validate the bearer token
        $token = $request->bearerToken();
        $expectedToken = config('services.makedotcom.webhook_token');

        if (!$token || $token !== $expectedToken) {
            Log::warning('Unauthorized webhook attempt from Make.com', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate the payload
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:yt_videos,id',
            'likes' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'views' => 'nullable|integer',
            'length' => 'nullable|integer',
            'channel_id' => 'required|string|max:255',
            'description' => 'required|string',
            'channel_name' => 'required|string|max:255',
            'thumbnail_url' => 'required|url|max:2048',
            'published_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            Log::error('Invalid webhook payload from Make.com', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the validated data
        $data = $validator->validated();

        try {
            // Update the video record with only the specified columns
            $video = YtVideo::findOrFail($data['id']);
            
            // Count URLs in the description
            $urlCount = $this->countUrlsInText($data['description']);
            
            $video->update([
                'title' => $data['title'],
                'published_at' => Carbon::parse($data['published_date']),
                'description' => $data['description'],
                'thumbnail_url' => $data['thumbnail_url'],
                'views' => $data['views'] ?? null,
                'likes' => $data['likes'] ?? null,
                'length' => $data['length'] ?? null,
                'links_found' => $urlCount,
            ]);

            return response()->json([
                'message' => 'Video details updated successfully',
                'video_id' => $video->id,
                'links_found' => $urlCount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update video details', [
                'video_id' => $data['id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update video details'
            ], 500);
        }
    }

    /**
     * Count URLs in the given text
     * 
     * @param string $text The text to search for URLs
     * @return int Number of URLs found
     */
    protected function countUrlsInText($text)
    {
        if (empty($text)) {
            return 0;
        }
        
        // Regular expression to match URLs
        // This matches http://, https://, and www. patterns
        $urlPattern = '/\b(?:https?:\/\/|www\.)[^\s<>"{}|\\^`\[\]]+/i';
        
        // Find all matches
        preg_match_all($urlPattern, $text, $matches);
        
        // Count unique URLs (in case same URL appears multiple times)
        $uniqueUrls = array_unique($matches[0]);
        
        Log::info('URLs found in description', [
            'video_id' => request()->input('id'),
            'url_count' => count($uniqueUrls),
            'urls' => $uniqueUrls
        ]);
        
        return count($uniqueUrls);
    }
}
