<?php

namespace App\Http\Controllers;

use App\Services\ImageUploadService;
use App\Http\Requests\UploadTemporaryImageRequest;
use App\Http\Requests\ConfirmUploadRequest;
use App\Http\Requests\DiscardTemporaryRequest;
use App\Http\Resources\ImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImageUploadController extends Controller
{
    private $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function index(): AnonymousResourceCollection
    {
        $images = $this->imageUploadService->getAllImages();
        return ImageResource::collection($images);
    }

    public function uploadTemporary(UploadTemporaryImageRequest $request): JsonResponse
    {
        $result = $this->imageUploadService->uploadTemporary($request->file('image'));
        return response()->json($result);
    }

    public function confirmUpload(ConfirmUploadRequest $request): JsonResponse
    {
        $image = $this->imageUploadService->confirmUpload($request->validated());
        return response()->json(new ImageResource($image));
    }

    public function discardTemporary(DiscardTemporaryRequest $request): JsonResponse
    {
        $this->imageUploadService->discardTemporary($request->validated());
        return response()->json(null);
    }

    public function destroy($id): JsonResponse
    {
        $this->imageUploadService->delete($id);
        return response()->json(null);
    }
}
