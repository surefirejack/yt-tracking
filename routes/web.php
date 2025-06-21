<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentProviders\PaddleController;
use App\Services\SessionService;
use App\Services\TenantCreationService;
use App\Services\UserDashboardService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YouTubeIntegrationController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\ProductCheckoutController;
use App\Http\Controllers\EmailGatedContentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
| If you want the URL to be added to the sitemap, add a "sitemapped" middleware to the route (it has to GET route)
|
*/
if ( env('APP_ENV') == 'staging' || env('APP_ENV') == 'production') {
    Route::get('/', function () {
        return view('coming-soon.vertical');
    })->name('home')->middleware('sitemapped');

    Route::get('/previewpage', function () {
        return view('home');
    })->name('home')->middleware('sitemapped');
} else {
    Route::get('/', function () {
        return view('home');
    })->name('home')->middleware('sitemapped');
}

// Route::get('/', function () {
//     return view('home');
// })->name('home')->middleware('sitemapped');

Route::get('/about', function () {
    return view('about');
})->name('about')->middleware('sitemapped');

Route::get('/dashboard', function (UserDashboardService $dashboardService) {
    return redirect($dashboardService->getUserDashboardUrl(Auth::user()));
})->name('dashboard')->middleware(['auth', 'verified']);

Auth::routes();

Route::get('/plan/start', function (
    TenantCreationService $tenantCreationService,
    SessionService $sessionService
) {
    if (! auth()->check()) {
        $sessionService->setCreateTenantForFreePlanUser(true);
    } else {
        $tenantCreationService->createTenantForFreePlanUser(auth()->user());
    }

    return redirect()->route('register');
})->name('plan.start');

Route::get('/email/verify', function () {
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    $user = $request->user();
    if ($user->hasVerifiedEmail()) {
        return redirect()->route('registration.thank-you');
    }

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/phone/verify', function () {
    return view('verify.sms-verification');
})->name('user.phone-verify')
    ->middleware('auth');

Route::get('/phone/verified', function () {
    return view('verify.sms-verification-success');
})->name('user.phone-verified')
    ->middleware('auth');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/registration/thank-you', function () {
    return view('auth.thank-you');
})->middleware('auth')->name('registration.thank-you');

Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])
    ->where('provider', 'google|github|facebook|twitter-oauth-2|linkedin-openid|bitbucket|gitlab')
    ->name('auth.oauth.redirect');

Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])
    ->where('provider', 'google|github|facebook|twitter-oauth-2|linkedin-openid|bitbucket|gitlab')
    ->name('auth.oauth.callback');

// YouTube Integration Routes
Route::get('/integrations/youtube/redirect', [YouTubeIntegrationController::class, 'redirect'])
    ->name('integrations.youtube.redirect');

Route::get('/integrations/youtube/callback', [YouTubeIntegrationController::class, 'callback'])
    ->name('integrations.youtube.callback');

Route::post('/integrations/youtube/disconnect/{tenant?}', [YouTubeIntegrationController::class, 'disconnect'])
    ->name('integrations.youtube.disconnect')
    ->middleware('auth');

Route::get('/checkout/plan/{planSlug}', [
    App\Http\Controllers\SubscriptionCheckoutController::class,
    'subscriptionCheckout',
])->name('checkout.subscription');

Route::get('/checkout/convert-subscription/{subscriptionUuid}', [
    App\Http\Controllers\SubscriptionCheckoutController::class,
    'convertLocalSubscriptionCheckout',
])->name('checkout.convert-local-subscription');

Route::get('/already-subscribed', function () {
    return view('checkout.already-subscribed');
})->name('checkout.subscription.already-subscribed');

Route::get('/checkout/subscription/success', [
    App\Http\Controllers\SubscriptionCheckoutController::class,
    'subscriptionCheckoutSuccess',
])->name('checkout.subscription.success')->middleware('auth');

Route::get('/checkout/convert-subscription-success', [
    App\Http\Controllers\SubscriptionCheckoutController::class,
    'convertLocalSubscriptionCheckoutSuccess',
])->name('checkout.convert-local-subscription.success')->middleware('auth');

Route::get('/payment-provider/paddle/payment-link', [
    PaddleController::class,
    'paymentLink',
])->name('payment-link.paddle');

Route::get('/subscription/{subscriptionUuid}/change-plan/{planSlug}/tenant/{tenantUuid}', [
    App\Http\Controllers\SubscriptionController::class,
    'changePlan',
])->name('subscription.change-plan')->middleware('auth');

Route::post('/subscription/{subscriptionUuid}/change-plan/{planSlug}/tenant/{tenantUuid}', [
    App\Http\Controllers\SubscriptionController::class,
    'changePlan',
])->name('subscription.change-plan.post')->middleware('auth');

Route::get('/subscription/change-plan-thank-you', [
    App\Http\Controllers\SubscriptionController::class,
    'success',
])->name('subscription.change-plan.thank-you')->middleware('auth');

// blog
Route::controller(BlogController::class)
    ->prefix('/blog')
    ->group(function () {
        Route::get('/', 'all')->name('blog')->middleware('sitemapped');
        Route::get('/category/{slug}', 'category')->name('blog.category');
        Route::get('/{slug}', 'view')->name('blog.view');
    });

Route::get('/terms-of-service', function () {
    return view('pages.terms-of-service');
})->name('terms-of-service')->middleware('sitemapped');

Route::get('/privacy-policy', function () {
    return view('pages.privacy-policy');
})->name('privacy-policy')->middleware('sitemapped');

Route::get('/terms', function () {
    $markdownPath = resource_path('views/markdown/terms.md');
    
    if (!file_exists($markdownPath)) {
        abort(404, 'Terms file not found');
    }
    
    $markdown = file_get_contents($markdownPath);
    
    $converter = new \League\CommonMark\CommonMarkConverter([
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ]);
    
    $html = $converter->convert($markdown);
    
    return view('terms', ['terms' => $html]);
})->name('terms');

// Product checkout routes

Route::get('/buy/product/{productSlug}/{quantity?}', [
    App\Http\Controllers\ProductCheckoutController::class,
    'addToCart',
])->name('buy.product');

Route::get('/cart/clear', [
    App\Http\Controllers\ProductCheckoutController::class,
    'clearCart',
])->name('cart.clear');

Route::get('/checkout/product', [
    App\Http\Controllers\ProductCheckoutController::class,
    'productCheckout',
])->name('checkout.product');

Route::get('/checkout/product/success', [
    App\Http\Controllers\ProductCheckoutController::class,
    'productCheckoutSuccess',
])->name('checkout.product.success')->middleware('auth');

// roadmap

Route::controller(App\Http\Controllers\RoadmapController::class)
    ->prefix('/roadmap')
    ->group(function () {
        Route::get('/', 'index')->name('roadmap');
        Route::get('/i/{itemSlug}', 'viewItem')->name('roadmap.viewItem');
        Route::get('/suggest', 'suggest')->name('roadmap.suggest')->middleware('auth');
    });

// Invitations

Route::get('/invitations', [
    App\Http\Controllers\InvitationController::class,
    'index',
])->name('invitations')->middleware('auth');

// Invoice

Route::controller(App\Http\Controllers\InvoiceController::class)
    ->prefix('/invoice')
    ->group(function () {
        Route::get('/generate/{transactionUuid}', 'generate')->name('invoice.generate');
        Route::get('/preview', 'preview')->name('invoice.preview');
    });

/*
|--------------------------------------------------------------------------
| Subscriber Routes - YouTube Members Area
|--------------------------------------------------------------------------
|
| Routes for the subscriber-only members area. All routes use the /s/{channelname}
| pattern where channelname is the tenant's YouTube channel name (lowercase).
|
*/

// Referral tracking route (must come before other /s routes to avoid conflicts)
Route::get('/referral/{tenant}', [App\Http\Controllers\ReferralController::class, 'trackReferral'])
    ->name('referral.track')
    ->where('tenant', '[0-9a-f-]+'); // UUID pattern

// Fixed subscriber OAuth callback (for Google OAuth compatibility)
Route::get('/subscriber/auth/callback', [App\Http\Controllers\SubscriberAuthController::class, 'handleGoogleCallback'])
    ->name('subscriber.auth.callback.fixed');

// OAuth routes for subscriber authentication
Route::prefix('s/{channelname}')->group(function () {
    // Login page (shows when not authenticated)
    Route::get('/login/{slug?}', [App\Http\Controllers\SubscriberAuthController::class, 'showLogin'])
        ->name('subscriber.login')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');

    // Google OAuth redirect
    Route::get('/auth/google/{slug?}', [App\Http\Controllers\SubscriberAuthController::class, 'redirectToGoogle'])
        ->name('subscriber.auth.google')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');

    // Google OAuth callback
    Route::get('/auth/callback', [App\Http\Controllers\SubscriberAuthController::class, 'handleGoogleCallback'])
        ->name('subscriber.auth.callback')
        ->where('channelname', '[a-z0-9_-]+');

    // Logout
    Route::post('/logout', [App\Http\Controllers\SubscriberDashboardController::class, 'logout'])
        ->name('subscriber.logout')
        ->where('channelname', '[a-z0-9_-]+');

    // Try again (force re-verify subscription)
    Route::get('/try-again/{slug?}', [App\Http\Controllers\SubscriberAuthController::class, 'tryAgain'])
        ->name('subscriber.try-again')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');
});

// Protected subscriber routes (require subscription verification)
Route::prefix('s/{channelname}')->middleware('verify.subscription')->group(function () {
    // Dashboard (shows all content)
    Route::get('/', [App\Http\Controllers\SubscriberDashboardController::class, 'index'])
        ->name('subscriber.dashboard')
        ->where('channelname', '[a-z0-9_-]+');

    // Individual content pages
    Route::get('/{slug}', [App\Http\Controllers\SubscriberContentController::class, 'show'])
        ->name('subscriber.content')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');

    // File downloads (secure)
    Route::get('/{slug}/download/{filename}', [App\Http\Controllers\SubscriberContentController::class, 'download'])
        ->name('subscriber.download')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+')
        ->where('filename', '[^/]+'); // Allow various file name patterns
});

/*
|--------------------------------------------------------------------------
| Email-Gated Content Routes
|--------------------------------------------------------------------------
|
| Routes for email subscriber content gating. Uses the /p/{channelname}/{slug}
| pattern where channelname is the tenant's YouTube channel name (lowercase).
|
*/

// Email verification route (global, not scoped to channelname)
Route::get('/email-verification/{tenantId}/{token}', [EmailGatedContentController::class, 'verifyEmail'])
    ->name('email-verification.verify')
    ->where('token', '[a-zA-Z0-9]+');

// Email-gated content routes
Route::prefix('p/{channelname}')->group(function () {
    // Show email access form or content if verified
    Route::get('/{slug}', [EmailGatedContentController::class, 'show'])
        ->name('email-gated-content.show')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');

    // Handle email submission for verification
    Route::post('/{slug}/submit-email', [EmailGatedContentController::class, 'submitEmail'])
        ->name('email-gated-content.submit-email')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+');

    // File downloads (secure, requires valid access)
    Route::get('/{slug}/download/{filename}', [EmailGatedContentController::class, 'download'])
        ->name('email-gated-content.download')
        ->where('channelname', '[a-z0-9_-]+')
        ->where('slug', '[a-z0-9_-]+')
        ->where('filename', '[^/]+'); // Allow various file name patterns
});

// API routes for async access checking
Route::prefix('api')->group(function () {
    Route::get('/check-access-status/{accessRecordId}', [EmailGatedContentController::class, 'checkAccessStatus'])
        ->name('api.check-access-status');
});

/*
|--------------------------------------------------------------------------
| Route Model Binding for Subscriber Routes
|--------------------------------------------------------------------------
|
| Define custom route model binding to resolve tenants by channelname
| and content by slug within tenant scope.
|
*/

// Bind channelname to Tenant model
Route::bind('channelname', function ($value) {
    $tenant = \App\Models\Tenant::whereHas('ytChannel', function ($query) use ($value) {
        $query->whereRaw('LOWER(REPLACE(handle, "@", "")) = ?', [strtolower($value)]);
    })->first();

    // Return the tenant if found, or return the string value to let middleware handle it
    return $tenant ?: $value;
});

// Bind slug to content within tenant scope (handles both subscriber and email-gated content)
Route::bind('slug', function ($value, $route) {
    // Get the tenant from the channelname parameter
    $channelname = $route->parameter('channelname');
    
    if ($channelname instanceof \App\Models\Tenant) {
        $tenant = $channelname;
    } else {
        // If channelname is still a string, try to find the tenant
        $tenant = \App\Models\Tenant::whereHas('ytChannel', function ($query) use ($channelname) {
            $query->whereRaw('LOWER(REPLACE(handle, "@", "")) = ?', [strtolower($channelname)]);
        })->first();
    }

    if (!$tenant) {
        // Return the string value to let middleware handle tenant not found
        return $value;
    }

    // Check if this is an email-gated content route (p/ prefix)
    if (str_starts_with($route->uri(), 'p/')) {
        // Find email-gated content by slug within this tenant
        $content = $tenant->emailSubscriberContents()->where('slug', $value)->first();
        
        // Return the content if found, or return the string value to let middleware handle it
        return $content ?: $value;
    }
    
    // For subscriber routes (s/ prefix), find subscriber content
    $content = $tenant->subscriberContent()->where('slug', $value)->first();
    
    // Return the content if found, or return the string value to let middleware handle it
    return $content ?: $value;
});
