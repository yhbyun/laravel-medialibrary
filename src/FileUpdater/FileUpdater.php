<?php

namespace Spatie\MediaLibrary\FileUpdater;

use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\FileAdder\FileAdder;
use Spatie\MediaLibrary\Media;

class FileUpdater extends FileAdder
{
    protected $media;

    /**
     * @param Media $media
     * @return $this
     */
    public function setMedia(Media $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @param string $collectionName
     * @param string $diskName
     *
     * @return \Spatie\MediaLibrary\Media
     *
     * @throws FileCannotBeAdded
     * @throws \Spatie\MediaLibrary\Exceptions\FileCannotBeAdded
     */
    public function toCollectionOnDisk(string $collectionName = 'default', string $diskName = '')
    {
        if (! $this->subject->exists) {
            throw FileCannotBeAdded::modelDoesNotExist($this->subject);
        }

        if (! is_file($this->pathToFile)) {
            throw FileCannotBeAdded::fileDoesNotExist($this->pathToFile);
        }

        if (filesize($this->pathToFile) > config('laravel-medialibrary.max_file_size')) {
            throw FileCannotBeAdded::fileIsTooBig($this->pathToFile);
        }

        $media = $this->media;

        $media->size = filesize($this->pathToFile);

        $media->manipulations = [];

        $media->save();

        $this->filesystem->add($this->subject, $this->pathToFile, $media, $this->fileName);

        if (! $this->preserveOriginal) {
            unlink($this->pathToFile);
        }

        return $media;
    }
}
