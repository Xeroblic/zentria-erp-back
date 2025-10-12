<?php
return [
    'disk_name' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),
    'media_model' => App\Models\Media\Media::class,                 // tu modelo extendido (con branch_id)
    'path_generator' => App\Media\BranchPathGenerator::class,      // path por branch
    'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,
    'image_driver' => 'imagick',
    'queue_connection_name' => env('QUEUE_CONNECTION', null),
    'max_file_size' => 32 * 1024 * 1024, // 32 MB
    'temporary_directory_path' => storage_path('medialibrary/temp'),
];
