# Laravel Image Upload Example

This project demonstrates image upload functionality using the Laravel framework. It utilizes a Docker environment for easy setup and execution.

## Key Features

1. **Image Upload and Storage**
   - Temporary image upload
   - Permanent storage after confirmation
   - Storage using local filesystem or S3-compatible storage (Minio)

2. **Image Processing**
   - Generation of multiple thumbnail sizes (small, medium, large)
   - Image manipulation using the Intervention Image library

3. **Temporary File Management**
   - Automatic cleanup of temporary images
   - Background job for deleting old temporary files

4. **API and Web Interface**
   - RESTful API endpoints
   - Simple web interface for image upload demonstration

## Installation

1. Clone the repository and move to the project directory:
   ```bash
   git clone https://github.com/horatjp/example-laravel-image-upload
   cd example-laravel-image-upload
   ```

2. Copy the environment configuration file:
   ```bash
   cp .env.example .env
   ```

3. Start Docker containers:
   ```bash
   docker-compose up -d
   ```

4. Install dependencies:
   ```bash
   docker-compose exec app composer install
   ```

5. Generate application key:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. Run database migrations:
   ```bash
   touch database/database.sqlite
   docker-compose exec app php artisan migrate:fresh
   ```

7. Create storage link:
   ```bash
   ln -s ../storage/app/public public/storage
   ```

8. Add the following entry to your hosts file:
   ```
   127.0.0.1 laravel.test
   ```

## Usage

1. Access the application in your browser:
   https://laravel.test/

2. Upload images and check the processing results

3. To clean up temporary images:
   ```bash
   docker-compose exec app php artisan app:cleanup-temp
   ```

4. Start the queue worker:
   ```bash
   docker-compose exec app php artisan queue:work
   ```

## Minio Configuration (Optional)

To use Minio for S3-compatible storage:

1. Access Minio management console:
   http://laravel.test:8900 (username: minio, password: minio_password)

2. Set `disk` to `s3` in `config/image_upload.php`

3. Add to your hosts file:
   ```
   127.0.0.1 laravel.test minio.laravel.test
   ```

## Conclusion

You have now set up the Laravel image upload example project and are ready to use it. For detailed configurations and additional features, please refer to the configuration files and source code.
