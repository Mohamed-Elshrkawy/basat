<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get language from various headers

        $locale = auth()->user()->locale
        ??$request->header('Accept-Language')
                 ?? $request->header('X-Localization')
                 ?? $request->header('X-App-Locale')
                 ?? config('app.locale', 'en')
                 ??Session::get('locale', config('app.locale'));

        // Validate and set locale
        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
