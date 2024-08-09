<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'url' => $this->getUrl(),
            'thumbnails' => $this->getThumbnailUrls(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
