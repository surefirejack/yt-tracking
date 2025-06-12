<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\SubscriberContent;
use App\Models\SubscriberUser;
use App\Models\ContentDownload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class SubscriberContentController extends Controller
{
    /**
     * Display a specific piece of subscriber content.
     */
    public function show($channelname, $slug)
    {
        try {
            // Handle route model binding - both parameters might be objects
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                $tenant = Tenant::whereHas('ytChannel', function ($query) use ($channelnameStr) {
                    $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelnameStr));
                })->first();

                if (!$tenant) {
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            if ($slug instanceof SubscriberContent) {
                $content = $slug;
            } else {
                $content = SubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->where('is_published', true)
                    ->first();

                if (!$content) {
                    return redirect()->route('subscriber.dashboard', ['channelname' => $channelnameStr])
                        ->with('error', 'Content not found.');
                }
            }

            // Verify content belongs to tenant
            if ($content->tenant_id !== $tenant->id) {
                return redirect()->route('home')->with('error', 'Invalid request.');
            }

            // Get the channel information for the header
            $channel = $tenant->ytChannel;

            // Check if this is a tenant member viewing content
            $isTenantMember = request()->attributes->get('is_tenant_member', false);
            $subscriberUser = null;

            if ($isTenantMember) {
                // For tenant members, create a dummy subscriber representation for UI consistency
                $user = auth()->user();
                $subscriberUser = (object) [
                    'name' => $user?->name ?? 'Tenant Member',
                    'email' => $user?->email ?? 'tenant@example.com',
                    'profile_picture' => null,
                    'is_tenant_member' => true
                ];
            } else {
                // Get subscriber from request attributes (set by middleware)
                $subscriberUser = request()->attributes->get('subscriber');
            }

            // Get related content (other published content from this tenant, excluding current content)
            $relatedContent = SubscriberContent::where('tenant_id', $tenant->id)
                ->where('is_published', true)
                ->where('id', '!=', $content->id)
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get();

            // Get video title if YouTube video is set
            $videoTitle = null;
            if ($content->youtube_video_url) {
                // Extract YouTube video ID from URL
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->youtube_video_url, $matches);
                $videoId = $matches[1] ?? null;
                
                if ($videoId && $channel) {
                    // Find the video in the yt_videos table
                    $video = $channel->ytVideos()->where('video_id', $videoId)->first();
                    $videoTitle = $video?->title;
                }
            }

            return view('subscriber.content', [
                'tenant' => $tenant,
                'content' => $content,
                'channel' => $channel,
                'channelname' => $channelnameStr,
                'subscriber' => $subscriberUser,
                'isTenantMember' => $isTenantMember,
                'relatedContent' => $relatedContent,
                'videoTitle' => $videoTitle,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading subscriber content', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred.');
        }
    }

    /**
     * Handle secure file downloads for subscribers.
     */
    public function download($channelname, $slug, string $filename)
    {
        try {
            // Handle route model binding - both parameters might be objects
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                $tenant = Tenant::whereHas('ytChannel', function ($query) use ($channelnameStr) {
                    $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelnameStr));
                })->first();

                if (!$tenant) {
                    return response()->json(['error' => 'Channel not found'], 404);
                }
            }

            if ($slug instanceof SubscriberContent) {
                $content = $slug;
            } else {
                $content = SubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->where('is_published', true)
                    ->first();

                if (!$content) {
                    return response()->json(['error' => 'Content not found'], 404);
                }
            }

            // Verify content belongs to tenant
            if ($content->tenant_id !== $tenant->id) {
                return response()->json(['error' => 'Invalid request'], 403);
            }

            // Find the file path that matches the filename
            $matchedFilePath = null;
            $displayName = $filename;
            
            if ($content->file_paths && is_array($content->file_paths)) {
                foreach ($content->file_paths as $index => $path) {
                    if (basename($path) === $filename) {
                        $matchedFilePath = $path;
                        
                        // Get human-readable name if available
                        if ($content->file_names && isset($content->file_names[$index])) {
                            $displayName = $content->file_names[$index];
                        } else {
                            // Remove timestamp prefix if present
                            $displayName = preg_replace('/^\d{14}_/', '', $filename);
                        }
                        break;
                    }
                }
            }

            // Check if file path was found and file exists
            if (!$matchedFilePath || !Storage::disk('local')->exists($matchedFilePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            // Get authenticated subscriber for tracking
            $subscriberUserId = Session::get('subscriber_user_id');
            
            // Track download
            if ($subscriberUserId) {
                ContentDownload::create([
                    'subscriber_user_id' => $subscriberUserId,
                    'subscriber_content_id' => $content->id,
                    'tenant_id' => $tenant->id,
                    'filename' => $displayName, // Use human-readable name for tracking
                    'downloaded_at' => now(),
                ]);
            }

            Log::info('File download', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'subscriber_user_id' => $subscriberUserId,
                'filename' => $displayName,
                'actual_file' => $filename
            ]);

            // Get file path and MIME type
            $fullFilePath = Storage::disk('local')->path($matchedFilePath);
            $mimeType = Storage::disk('local')->mimeType($matchedFilePath);

            // Return file download response with human-readable name
            return response()->download($fullFilePath, $displayName, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $displayName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading file', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Download failed'], 500);
        }
    }

    /**
     * Preview content without authentication (used for testing and previews).
     * This should only be used in development or for tenant previews.
     */
    public function preview(Request $request, Tenant $tenant, SubscriberContent $content): View
    {
        // Verify content belongs to this tenant
        if ($content->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Get the channel information for the header
        $channel = $tenant->ytChannel;

        // Get video title if YouTube video is set
        $videoTitle = null;
        if ($content->youtube_video_url) {
            // Extract YouTube video ID from URL
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->youtube_video_url, $matches);
            $videoId = $matches[1] ?? null;
            
            if ($videoId && $channel) {
                // Find the video in the yt_videos table
                $video = $channel->ytVideos()->where('video_id', $videoId)->first();
                $videoTitle = $video?->title;
            }
        }

        return view('subscriber.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channel' => $channel,
            'channelname' => $tenant->getChannelName() ?? 'channel',
            'subscriberUser' => null,
            'isPreview' => true,
            'videoTitle' => $videoTitle,
        ]);
    }
}
