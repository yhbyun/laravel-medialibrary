<?php

namespace Spatie\MediaLibrary\PathGenerator;

use Spatie\MediaLibrary\Media;

class CustomPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media) : string
    {

        return $media->collection_name.'/'.app('fakeid')->encode($media->id).'/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.
     * @return string
     */
    public function getPathForConversions(Media $media) : string
    {
        return $media->collection_name.'/'.app('fakeid')->encode($media->id).'/c/';
    }
}
