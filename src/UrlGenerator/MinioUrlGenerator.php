<?php

namespace Spatie\MediaLibrary\UrlGenerator;

class MinioUrlGenerator extends BaseUrlGenerator
{
    /**
     * Get the url for the profile of a media item.
     *
     * @return string
     */
    public function getUrl() : string
    {
        if (config('laravel-medialibrary.minio.multiple_disk')) {
            return config('laravel-medialibrary.minio.domain').'/'.$this->media->disk.'/'.$this->getPathRelativeToRoot();
        }

        return config('laravel-medialibrary.minio.domain').'/'.$this->getPathRelativeToRoot();
    }

    /*
     * Get the path for the profile of a media item.
     */
    public function getPath() : string
    {
        return $this->getPathRelativeToRoot();
    }
}
