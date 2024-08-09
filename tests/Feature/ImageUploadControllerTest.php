<?php

namespace Tests\Feature;

use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function testIndex()
    {
        $image1 = Image::factory()->create();
        $image2 = Image::factory()->create();

        $response = $this->getJson('/api/images');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'filename', 'url', 'thumbnails', 'metadata', 'created_at']
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    public function testUploadTemporary()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/images/upload-temp', [
            'image' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'temp_id',
                'filename',
                'temp_path',
                'thumbnails',
                'metadata',
            ]);

        $data = $response->json();
        Storage::disk('public')->assertExists($data['temp_path']);
        foreach ($data['thumbnails'] as $thumbnail) {
            Storage::disk('public')->assertExists($thumbnail);
        }
    }

    public function testConfirmUpload()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tempResponse = $this->postJson('/api/images/upload-temp', ['image' => $file]);
        $tempData = $tempResponse->json();

        $response = $this->postJson('/api/images/confirm', $tempData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'filename',
                'url',
                'thumbnails',
                'metadata',
                'created_at'
            ]);

        $data = $response->json();
        $this->assertDatabaseHas('images', ['id' => $data['id']]);

        $storagePath = str_replace('/storage/', '', parse_url($data['url'], PHP_URL_PATH));
        Storage::disk('public')->assertExists($storagePath);

        foreach ($data['thumbnails'] as $thumbnail) {
            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnail, PHP_URL_PATH));
            Storage::disk('public')->assertExists($thumbnailPath);
        }
    }

    public function testDiscardTemporary()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tempResponse = $this->postJson('/api/images/upload-temp', ['image' => $file]);
        $tempData = $tempResponse->json();

        $response = $this->postJson('/api/images/discard', $tempData);

        $response->assertStatus(200)
            ->assertJson([]);

        Storage::disk('public')->assertMissing($tempData['temp_path']);
        foreach ($tempData['thumbnails'] as $thumbnail) {
            Storage::disk('public')->assertMissing($thumbnail);
        }
    }

    public function testDestroy()
    {
        $image = Image::factory()->create();

        $response = $this->deleteJson("/api/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJson([]);

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
    }

    public function testUploadTemporaryWithInvalidImage()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->postJson('/api/images/upload-temp', [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
}
