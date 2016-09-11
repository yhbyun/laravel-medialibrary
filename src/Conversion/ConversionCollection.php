<?php

namespace Spatie\MediaLibrary\Conversion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Exceptions\InvalidConversion;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Spatie\MediaLibrary\Media;

class ConversionCollection extends Collection
{
    /**
     * @param \Spatie\MediaLibrary\Media $media
     * @param Model $model
     * @return static
     */
    public static function createForMedia(Media $media, Model $model)
    {
        return (new static())->setMedia($media, $model);
    }

    /**
     * @param \Spatie\MediaLibrary\Media $media
     * @param Model $model
     * @return $this
     */
    public function setMedia(Media $media, Model $model)
    {
        $this->items = [];

        $this->addConversionsFromRelatedModel($media, $model);

        $this->addManipulationsFromDb($media);

        return $this;
    }

    /**
     *  Get a conversion by it's name.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws \Spatie\MediaLibrary\Exceptions\InvalidConversion
     */
    public function getByName(string $name)
    {
        foreach ($this->items as $conversion) {
            if ($conversion->getName() === $name) {
                return $conversion;
            }
        }

        throw InvalidConversion::unknownName($name);
    }

    /**
     * Add the conversion that are defined on the related model of
     * the given media.
     *
     * @param \Spatie\MediaLibrary\Media $media
     * @param Model $model
     */
    protected function addConversionsFromRelatedModel(Media $media, Model $model)
    {
        if ($model instanceof HasMediaConversions) {
            $model->registerMediaConversions();
        }

        $this->items = $model->mediaConversions;
    }

    /**
     * Add the extra manipulations that are defined on the given media.
     *
     * @param \Spatie\MediaLibrary\Media $media
     */
    protected function addManipulationsFromDb(Media $media)
    {
        foreach ($media->manipulations as $conversionName => $manipulation) {
            $this->addManipulationToConversion($manipulation, $conversionName);
        }
    }

    /**
     * Get all the conversions in the collection.
     *
     * @param string $collectionName
     *
     * @return $this
     */
    public function getConversions(string $collectionName = '')
    {
        if ($collectionName === '') {
            return $this;
        }

        return $this->filter(function (Conversion $conversion) use ($collectionName) {
            return $conversion->shouldBePerformedOn($collectionName);
        });
    }

    /*
     * Get all the conversions in the collection that should be queued.
     */
    public function getQueuedConversions(string $collectionName = '') : ConversionCollection
    {
        return $this->getConversions($collectionName)->filter(function (Conversion $conversion) {
            return $conversion->shouldBeQueued();
        });
    }

    /*
     * Add the given manipulation to the conversion with the given name.
     */
    protected function addManipulationToConversion(array $manipulation, string $conversionName)
    {
        foreach ($this as $conversion) {
            if ($conversion->getName() === $conversionName) {
                $conversion->addAsFirstManipulation($manipulation);

                return;
            }
        }
    }

    /*
     * Get all the conversions in the collection that should not be queued.
     */
    public function getNonQueuedConversions(string $collectionName = '') : ConversionCollection
    {
        return $this->getConversions($collectionName)->filter(function (Conversion $conversion) {
            return ! $conversion->shouldBeQueued();
        });
    }

    /**
     * Return the list of conversion files.
     */
    public function getConversionsFiles(string $collectionName = '') : ConversionCollection
    {
        return $this->getConversions($collectionName)->map(function (Conversion $conversion) {
            return $conversion->getName().'.'.$conversion->getResultExtension();
        });
    }
}
