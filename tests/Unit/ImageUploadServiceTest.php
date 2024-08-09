<?php

namespace Tests\Unit;

use App\Services\ImageUploadService;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $imageUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->imageUploadService = new ImageUploadService();
    }

    public function testGetAllImages()
    {
        Image::factory()->count(3)->create();

        $images = $this->imageUploadService->getAllImages();

        $this->assertCount(3, $images);
        $this->assertInstanceOf(Image::class, $images->first());
    }

    public function testUploadTemporary()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->imageUploadService->uploadTemporary($file);

        $this->assertArrayHasKey('temp_id', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('temp_path', $result);
        $this->assertArrayHasKey('thumbnails', $result);
        $this->assertArrayHasKey('metadata', $result);

        Storage::disk('public')->assertExists($result['temp_path']);
        foreach ($result['thumbnails'] as $thumbnail) {
            Storage::disk('public')->assertExists($thumbnail);
        }
    }

    public function testConfirmUpload()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tempData = $this->imageUploadService->uploadTemporary($file);

        $image = $this->imageUploadService->confirmUpload($tempData);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals($tempData['filename'], $image->filename);
        $this->assertArrayHasKey('metadata', $image->getAttributes());

        Storage::disk('public')->assertExists($image->original_path);
        foreach ($image->thumbnails as $thumbnail) {
            Storage::disk('public')->assertExists($thumbnail);
        }

        Storage::disk('public')->assertMissing($tempData['temp_path']);
    }

    public function testDiscardTemporary()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tempData = $this->imageUploadService->uploadTemporary($file);

        $this->imageUploadService->discardTemporary($tempData);

        Storage::disk('public')->assertMissing($tempData['temp_path']);
        foreach ($tempData['thumbnails'] as $thumbnail) {
            Storage::disk('public')->assertMissing($thumbnail);
        }
    }

    public function testDelete()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tempData = $this->imageUploadService->uploadTemporary($file);
        $image = $this->imageUploadService->confirmUpload($tempData);

        $this->imageUploadService->delete($image->id);

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->original_path);
        foreach ($image->thumbnails as $thumbnail) {
            Storage::disk('public')->assertMissing($thumbnail);
        }
    }
}
