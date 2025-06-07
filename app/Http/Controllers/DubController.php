<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DubController extends Controller
{
    /**
     * Track a conversion event by forwarding it to Dub.co API
     */
    public function trackConversion(Request $request)
    {
        // Handle CORS preflight request
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization')
                ->header('Access-Control-Max-Age', '86400');
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'clickId' => 'required|string',
            'eventName' => 'required|string|max:255',
            'eventQuantity' => 'nullable|integer|min:1',
            'externalId' => 'nullable|string|max:255',
            'customerName' => 'nullable|string|max:255',
            'customerEmail' => 'nullable|email|max:255',
            'customerAvatar' => 'nullable|url|max:500',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
        }

        $apiKey = config('services.dub.api_key');
        
        if (!$apiKey) {
            Log::error('Dub API key not configured');
            return response()->json([
                'success' => false,
                'message' => 'Service configuration error'
            ], 500)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
        }

        // Prepare data for Dub.co API
        $dubData = [
            'clickId' => $request->input('clickId'),
            'eventName' => $request->input('eventName'),
            'eventQuantity' => $request->input('eventQuantity', 1),
            'externalId' => $request->input('externalId'),
            'customerName' => $request->input('customerName'),
            'customerEmail' => $request->input('customerEmail'),
            'customerAvatar' => $request->input('customerAvatar'),
            'mode' => 'async',
            'metadata' => $request->input('metadata')
        ];

        // Remove null values
        $dubData = array_filter($dubData, function ($value) {
            return $value !== null && $value !== '';
        });

        try {
            // Make the request to Dub.co API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.dub.co/track/lead', $dubData);

            if ($response->successful()) {
                Log::info('Dub conversion tracked successfully', [
                    'click_id' => $request->input('clickId'),
                    'event_name' => $request->input('eventName')
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Conversion tracked successfully'
                ])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
            } else {
                Log::error('Dub API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $dubData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to track conversion'
                ], $response->status())
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
            }
        } catch (\Exception $e) {
            Log::error('Exception while tracking Dub conversion', [
                'message' => $e->getMessage(),
                'data' => $dubData
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service temporarily unavailable'
            ], 500)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
        }
    }
} 