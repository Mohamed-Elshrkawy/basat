<?php

namespace App\Http\Controllers\Api\General\Pages;

use App\Enums\StaticPageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\General\ContactRequest;
use App\Http\Requests\Api\General\ListRequest;
use App\Http\Resources\Api\General\Pages\FaqResource;
use App\Http\Resources\Api\General\Pages\PageResource;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\StaticPage;
use Exception;
use Illuminate\Support\Facades\Log;

class PagesController extends Controller
{
    public function pages(): \Illuminate\Http\JsonResponse
    {
        return json(StaticPageType::getAll());
    }

    public function showPage(ListRequest $request, StaticPageType $page): \Illuminate\Http\JsonResponse
    {
        $contents = StaticPage::query()
            ->where('key', $page->value)
            ->latest()
            ->get();

        return response()->json(PageResource::collection($contents));
    }

    public function contactSubmit(ContactRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();

        $data = [
            'name' => $user?->name ?? $request->name,
            'phone' => $user?->phone ?? $request->phone,
            'email' => $user?->email ?? $request->email,
            'type' => $request->type,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        if (ContactMessage::where($data)->exists()) {

            return json(__('Your message already sent'), status: 'fail', headerStatus: 422);
        }

        try {
             ContactMessage::create($data);

            return json(__('Message has been sent successfully'));
        } catch (Exception $exception) {
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }
    }

    public function faq(): \Illuminate\Http\JsonResponse
    {
        $faqs = Faq::active()->orderBy('order_column')->get();
        return json(FaqResource::collection($faqs));
    }
}
