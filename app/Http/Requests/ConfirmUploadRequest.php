<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmUploadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'temp_id' => 'required|uuid',
            'filename' => 'required|string',
            'temp_path' => 'required|string',
            'thumbnails' => 'required|array',
            'metadata' => 'required|array',
        ];
    }
}
