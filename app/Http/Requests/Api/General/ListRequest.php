<?php

namespace App\Http\Requests\Api\General;

use App\Http\Requests\Api\ApiMasterRequest;

class ListRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        return [
            'keyword' => 'sometimes|nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword' => __('Keyword'),
        ];
    }

    public function authorize()
    {
        return true;
    }
}
