<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Image::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $filename = $this->faker->uuid . '.jpg';
        return [
            'filename' => $filename,
            'disk' => 'public',
            'original_path' => 'images/original/' . $filename,
            'thumbnails' => [
                'small' => 'images/small/' . $filename,
                'medium' => 'images/medium/' . $filename,
                'large' => 'images/large/' . $filename,
            ],
            'metadata' => [
                'mime_type' => 'image/jpeg',
                'size' => $this->faker->numberBetween(100000, 5000000),
                'exif' => [
                    'make' => $this->faker->company,
                    'model' => $this->faker->word,
                    'exposure_time' => $this->faker->randomFloat(2, 0.001, 1),
                    'aperture' => 'f/' . $this->faker->randomFloat(1, 1.4, 22),
                    'iso' => $this->faker->numberBetween(100, 6400),
                    'taken_at' => $this->faker->dateTimeThisYear->format('Y-m-d H:i:s'),
                ],
            ],
        ];
    }
}
