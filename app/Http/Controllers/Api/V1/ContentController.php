<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Api\V1\FaqResource;
use App\Http\Resources\Api\V1\StaticPageResource;
use App\Http\Requests\Api\V1\StoreContactMessageRequest;

class ContentController extends Controller
{
    /**
     * Get a specific static page by its key.
     * Keys: about-us, privacy-policy, terms-and-conditions, cancellation-policy, contact-us
     */
    public function show(string $key): JsonResponse
    {
        $page = StaticPage::where('key', $key)->firstOrFail();
        
        return response()->json([
            'status' => true, 
            'data' => new StaticPageResource($page)
        ]);
    }

    /**
     * Get the list of Frequently Asked Questions.
     */
    public function getFaqs(): JsonResponse
    {
        $faqs = Faq::where('is_active', true)->orderBy('order_column')->get();
        
        return response()->json([
            'status' => true, 
            'data' => FaqResource::collection($faqs)
        ]);
    }

    public function getAboutUs(): JsonResponse
    {
        $about = 'KSA Bus is a leading platform for bus booking services in the Kingdom of Saudi Arabia...';
        return response()->json(['status' => true, 'data' => ['content' => $about]]);
    }

    public function getPrivacyPolicy(): JsonResponse
    {
        $policy = 'We are committed to protecting your privacy and personal data...';
        return response()->json(['status' => true, 'data' => ['content' => $policy]]);
    }

    public function getTermsAndConditions(): JsonResponse
    {
        $terms = 'By using the KSA Bus application, you agree to the following terms and conditions...';
        return response()->json(['status' => true, 'data' => ['content' => $terms]]);
    }

    /**
     * Store a new contact message from the contact-us form.
     * @param StoreContactMessageRequest $request
     * @return JsonResponse
     */
    public function storeContactMessage(StoreContactMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        \App\Models\ContactMessage::create([
            'user_id' => auth()->check() ? auth()->id() : null,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        return response()->json([
            'status' => true,
            'message' => __('messages.contact_form_submitted')
        ], 201);
    }
} 