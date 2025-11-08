<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function switch($locale)
    {
        if (!in_array($locale, ['ar', 'en'])) {
            abort(400);
        }

        Session::put('locale', $locale);
        app()->setLocale($locale);

        $user = auth()->user();
        if($user){
            $user->locale = $locale;
            $user->save();
        }
        return redirect()->back();
    }
}
