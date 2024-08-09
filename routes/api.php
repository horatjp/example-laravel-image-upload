<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageUploadController;

Route::get('/images', [ImageUploadController::class, 'index']);
Route::post('/images/upload-temp', [ImageUploadController::class, 'uploadTemporary']);
Route::post('/images/confirm', [ImageUploadController::class, 'confirmUpload']);
Route::post('/images/discard', [ImageUploadController::class, 'discardTemporary']);
Route::delete('/images/{id}', [ImageUploadController::class, 'destroy']);
