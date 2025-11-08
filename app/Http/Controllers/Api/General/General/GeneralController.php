<?php

namespace App\Http\Controllers\Api\General\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\General\Helpers\CityResource;
use App\Models\City;
use App\Models\Setting;


class GeneralController extends Controller
{
    public function cities(): \Illuminate\Http\JsonResponse
    {
        $cities = City::where('status', 1)->get();
        return json(CityResource::collection($cities));
    }

    public function settings(): \Illuminate\Http\JsonResponse
    {
        $contact = Setting::getByGroup('contact');
        $app_info = Setting::getByGroup('app_info');
        return json([
            'contact' => $contact,
            'app_info' => $app_info,
        ]);
    }


}
