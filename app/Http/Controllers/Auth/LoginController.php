<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginService;
use App\Validator\LoginValidator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    //    /**
    //     * Where to redirect users after login.
    //     *
    //     * @var string
    //     */
    //    protected $redirectTo = RouteServiceProvider::HOME;

    
    public function __construct(
        private LoginValidator $loginValidator,
        private LoginService $loginService,
    ) {
        $this->middleware('guest')->except('logout');
    }

    public function redirectPath()
    {
        return Redirect::getIntendedUrl() ?? route('home');
    }

    public function showLoginForm()
    {
        if (url()->previous() != route('register') && Redirect::getIntendedUrl() === null) {
            Redirect::setIntendedUrl(url()->previous()); // make sure we redirect back to the page we came from
        }

        return view('auth.login');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->is_blocked) {
            $this->guard()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been blocked. Please contact support.',
            ]);
        }

        if ($user->is_admin) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return redirect(app(\App\Services\UserDashboardService::class)->getUserDashboardUrl($user));
    }

    protected function validateLogin(Request $request)
    {
        $this->loginValidator->validateRequest($request);
    }

    protected function attemptLogin(Request $request)
    {
        return $this->loginService->attempt($this->credentials($request), $request->boolean('remember'));
    }
}
