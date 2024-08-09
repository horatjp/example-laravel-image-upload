<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscardTemporaryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'temp_id' => 'required|uuid',
            'temp_path' => 'required|string',
            'thumbnails' => 'required|array',
        ];
    }
}
