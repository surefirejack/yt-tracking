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

class SubscriberContentController extends Controller
{
    /**
     * Display a specific piece of subscriber content.
     */
    public function show(Request $request, Tenant $tenant, SubscriberContent $content): View
    {
        // Verify content belongs to this tenant and is published
        if ($content->tenant_id !== $tenant->id) {
            abort(404);
        }

        if (!$content->is_published || ($content->published_at && $content->published_at->isFuture())) {
            abort(404, 'This content is not available yet.');
        }

        // Get the channel information for the header
        $channel = $tenant->ytChannel;

        // Get subscriber user for tracking (if session exists)
        $subscriberUser = null;
        $subscriberUserId = session("subscriber_user_{$tenant->id}");
        if ($subscriberUserId) {
            $subscriberUser = SubscriberUser::find($subscriberUserId);
        }

        return view('subscriber.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channel' => $channel,
            'channelname' => $tenant->getChannelName() ?? 'channel',
            'subscriberUser' => $subscriberUser,
        ]);
    }

    /**
     * Handle secure file downloads for subscribers.
     */
    public function downloadFile(Request $request, Tenant $tenant, SubscriberContent $content, string $filename): BinaryFileResponse
    {
        // Verify content belongs to this tenant and is published
        if ($content->tenant_id !== $tenant->id) {
            abort(404);
        }

        if (!$content->is_published || ($content->published_at && $content->published_at->isFuture())) {
            abort(404, 'This content is not available yet.');
        }

        // Verify the file is associated with this content
        $filePaths = $content->file_paths ?? [];
        $requestedFile = null;

        foreach ($filePaths as $filePath) {
            if (basename($filePath) === $filename) {
                $requestedFile = $filePath;
                break;
            }
        }

        if (!$requestedFile) {
            abort(404, 'File not found.');
        }

        // Check if file exists in storage
        if (!Storage::disk('local')->exists($requestedFile)) {
            abort(404, 'File not found in storage.');
        }

        // Get the full file path
        $fullPath = Storage::disk('local')->path($requestedFile);

        // Track the download if we have a subscriber user
        $subscriberUserId = session("subscriber_user_{$tenant->id}");
        if ($subscriberUserId) {
            $subscriberUser = SubscriberUser::find($subscriberUserId);
            if ($subscriberUser) {
                ContentDownload::create([
                    'subscriber_user_id' => $subscriberUser->id,
                    'subscriber_content_id' => $content->id,
                    'file_name' => $filename,
                    'downloaded_at' => now(),
                ]);
            }
        }

        // Log the download
        \Log::info('File downloaded', [
            'tenant_id' => $tenant->id,
            'content_id' => $content->id,
            'filename' => $filename,
            'subscriber_user_id' => $subscriberUserId,
            'ip' => $request->ip(),
        ]);

        // Return the file download response
        return response()->download($fullPath, $filename, [
            'Content-Type' => $this->getMimeType($filename),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get the MIME type for a file based on its extension.
     */
    private function getMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
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

        return view('subscriber.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channel' => $channel,
            'channelname' => $tenant->getChannelName() ?? 'channel',
            'subscriberUser' => null,
            'isPreview' => true,
        ]);
    }
}
