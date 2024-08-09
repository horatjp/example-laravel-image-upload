<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Image Uploader and Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">Image Uploader and Gallery</h1>

        <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:bg-gray-50 transition duration-300 mb-6">
            <p class="text-gray-500">Drag and drop an image here, or click to select a file</p>
            <input type="file" id="fileInput" accept="image/*" class="hidden">
        </div>

        <div id="preview" class="mb-4 hidden"></div>

        <div id="buttons" class="mb-4 hidden">
            <button id="confirmButton" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">Confirm Upload</button>
            <button id="cancelButton" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Cancel</button>
        </div>

        <div id="loading" class="text-center hidden">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            <p class="mt-2">Uploading...</p>
        </div>

        <div id="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative hidden mb-4" role="alert"></div>

        <h2 class="text-xl font-semibold mb-4">Image Gallery</h2>
        <div id="imageGallery" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
    </div>

    <script>
        const ImageUploader = {
            init() {
                this.selectedFile = null;
                this.tempUploadData = null;
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                this.setupEventListeners();
                this.fetchImages();
            },

            setupEventListeners() {
                const dropZone = document.getElementById('dropZone');
                const fileInput = document.getElementById('fileInput');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, this.preventDefaults, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.add('bg-gray-100'));
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.remove('bg-gray-100'));
                });

                dropZone.addEventListener('drop', this.handleDrop.bind(this));
                dropZone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', () => this.handleFiles(fileInput.files));

                document.getElementById('confirmButton').addEventListener('click', this.confirmUpload.bind(this));
                document.getElementById('cancelButton').addEventListener('click', this.cancelUpload.bind(this));
            },

            preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            },

            handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                this.handleFiles(files);
            },

            handleFiles(files) {
                this.selectedFile = files[0];
                if (this.selectedFile && this.selectedFile.type.startsWith('image/')) {
                    this.uploadTemporary();
                } else {
                    this.showError('Please select a valid image file.');
                    this.resetUploadState();
                }
            },

            async uploadTemporary() {
                if (!this.selectedFile) return;

                const formData = new FormData();
                formData.append('image', this.selectedFile);

                this.showLoading(true);
                try {
                    const response = await this.sendRequest('/api/images/upload-temp', {
                        method: 'POST',
                        body: formData
                    });

                    this.tempUploadData = response;
                    this.showPreview();
                } catch (error) {
                    console.error('Error uploading temporary image:', error);
                    this.showError('Failed to upload image: ' + error.message);
                    this.resetUploadState();
                } finally {
                    this.showLoading(false);
                }
            },

            showPreview() {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('preview').innerHTML = `<img src="${e.target.result}" alt="Preview" class="max-w-full h-auto rounded-lg shadow-md mb-4">`;
                    document.getElementById('preview').classList.remove('hidden');
                    document.getElementById('buttons').classList.remove('hidden');
                }
                reader.readAsDataURL(this.selectedFile);
            },

            async confirmUpload() {
                if (!this.tempUploadData) return;

                this.showLoading(true);
                try {
                    const response = await this.sendRequest('/api/images/confirm', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.tempUploadData)
                    });

                    this.addImageToGallery(response);
                    this.resetUploadState();
                } catch (error) {
                    console.error('Error confirming upload:', error);
                    this.showError('Failed to confirm upload: ' + error.message);
                } finally {
                    this.showLoading(false);
                }
            },

            async cancelUpload() {
                if (!this.tempUploadData) return;

                this.showLoading(true);
                try {
                    await this.sendRequest('/api/images/discard', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.tempUploadData)
                    });
                    this.resetUploadState();
                } catch (error) {
                    console.error('Error discarding temporary upload:', error);
                    this.showError('Failed to cancel upload: ' + error.message);
                } finally {
                    this.showLoading(false);
                }
            },

            resetUploadState() {
                this.selectedFile = null;
                this.tempUploadData = null;
                document.getElementById('fileInput').value = '';
                document.getElementById('preview').innerHTML = '';
                document.getElementById('preview').classList.add('hidden');
                document.getElementById('buttons').classList.add('hidden');
            },

            addImageToGallery(imageData) {
                const gallery = document.getElementById('imageGallery');
                const imageElement = document.createElement('div');
                imageElement.className = 'relative group';
                imageElement.innerHTML = `
                <img src="${imageData.url}" alt="${imageData.filename}" class="w-full h-48 object-cover rounded-lg shadow-md">
                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <button onclick="ImageUploader.deleteImage(${imageData.id})" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Delete</button>
                </div>
            `;
                gallery.appendChild(imageElement);
            },

            async deleteImage(imageId) {
                this.showLoading(true);
                try {
                    await this.sendRequest(`/api/images/${imageId}`, {
                        method: 'DELETE'
                    });

                    const gallery = document.getElementById('imageGallery');
                    const imageElement = gallery.querySelector(`[onclick*="${imageId}"]`).closest('.relative');
                    gallery.removeChild(imageElement);
                } catch (error) {
                    console.error('Error deleting image:', error);
                    this.showError('Failed to delete image: ' + error.message);
                } finally {
                    this.showLoading(false);
                }
            },

            async fetchImages() {
                try {
                    const response = await this.sendRequest('/api/images');
                    if (Array.isArray(response.data)) {
                        response.data.forEach(this.addImageToGallery.bind(this));
                    } else {
                        throw new Error('Invalid response format');
                    }
                } catch (error) {
                    console.error('Error fetching images:', error);
                    this.showError('Failed to load images. Please refresh the page.');
                }
            },

            async sendRequest(url, options = {}) {
                const defaultOptions = {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                };
                const response = await fetch(url, {
                    ...defaultOptions,
                    ...options
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                }
                return data;
            },

            showLoading(isLoading) {
                document.getElementById('loading').style.display = isLoading ? 'block' : 'none';
            },

            showError(message) {
                const errorDiv = document.getElementById('error');
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
                setTimeout(() => {
                    errorDiv.classList.add('hidden');
                }, 15000);
            }
        };

        document.addEventListener('DOMContentLoaded', () => ImageUploader.init());
    </script>
</body>

</html>
