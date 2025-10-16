<?php

namespace App\Models\Traits;
use Illuminate\Support\Facades\Storage;

trait HandlesImages
{
    public static function bootHandlesImages()
    {
        static::saved(function ($model) {
            $prefix = strtolower(class_basename($model)); // Hotel / Room → hotel / room
            $inputName = $prefix . '_images';
            // Удаление
            if (request()->filled('deleted_images')) {
                $ids = json_decode(request('deleted_images'), true);
                if (!empty($ids)) {
                    $images = $model->images()->whereIn('id', $ids)->get();
                    foreach ($images as $image) {
                        Storage::disk(config('voyager.storage.disk'))->delete($image->path);
                        $image->delete();
                    }
                }
            }
            // Добавление
            if (request()->hasFile($inputName)) {
                foreach (request()->file($inputName) as $file) {
                    $path = $file->store($prefix . 's', config('voyager.storage.disk'));
                    $model->images()->create([
                        'path' => $path,
                        'is_main' => false,
                    ]);
                }
            }
        });
    }
}