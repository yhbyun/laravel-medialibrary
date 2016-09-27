<?php

namespace Spatie\MediaLibrary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Image;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Pdf;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Svg;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Video;
use Spatie\MediaLibrary\ImageGenerators\ImageGenerator;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\Conversion\ConversionCollection;
use Spatie\MediaLibrary\Helpers\File;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;

class Media extends Model
{
    const TYPE_OTHER = 'other';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_SVG = 'svg';
    const TYPE_PDF = 'pdf';
    const TYPE_WORD = 'word';
    const TYPE_EXCEL = 'excel';
    const TYPE_PPT = 'powerpoint';

    protected $guarded = ['id', 'disk', 'file_name', 'size', 'model_type', 'model_id'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
    ];

    /**
     * Create the polymorphic relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Get the original Url to a media-file.
     *
     * @param string $conversionName
     * @param Model $model model that this media is attached to
     * @return string
     */
    public function getUrl(string $conversionName = '', Model $model = null) : string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this);

        if ($conversionName !== '') {
            $urlGenerator->setConversion(ConversionCollection::createForMedia($this, $model)->getByName($conversionName));
        }

        return $urlGenerator->getUrl();
    }

    /**
     * Get the original path to a media-file.
     *
     * @param string $conversionName
     * @param Model $model model that this media is attached to
     * @return string
     */
    public function getPath(string $conversionName = '', Model $model = null) : string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this);

        if ($conversionName != '') {
            $urlGenerator->setConversion(ConversionCollection::createForMedia($this, $model)->getByName($conversionName));
        }

        return $urlGenerator->getPath();
    }

    /**
     * Collection of all ImageGenerator drivers.
     */
    public function getImageGenerators() : Collection
    {
        return collect([
            Image::class,
            Pdf::class,
            Svg::class,
            Video::class,
        ]);
    }

    /**
     * Determine the type of a file.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        $type = $this->type_from_extension;
        if ($type !== self::TYPE_OTHER) {
            return $type;
        }

        return $this->type_from_mime;
    }

    /**
     * Determine type icon
     *
     * @return string
     */
    public function getTypeIconAttribute()
    {
        $type = $this->type !== self::TYPE_OTHER ? 'file-'.$this->type : 'file';

        return 'fa fa-' . $type . '-o';
    }

    /**
     * Determine the type of a file from its file extension.
     *
     * @return string
     */
    public function getTypeFromExtensionAttribute()
    {
        $extension = strtolower($this->extension);

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
            return static::TYPE_IMAGE;
        }

        if (in_array($extension, ['webm', 'mov', 'mp4'])) {
            return static::TYPE_VIDEO;
        }

        if ($extension == 'pdf') {
            return static::TYPE_PDF;
        }

        if ($extension == 'svg') {
            return static::TYPE_SVG;
        }

        if ($extension == 'doc') {
            return static::TYPE_WORD;
        }

        if ($extension == 'ppt') {
            return static::TYPE_PPT;
        }

        if (in_array($extension, ['xls', 'xlsx', 'csv'])) {
            return static::TYPE_EXCEL;
        }

        return static::TYPE_OTHER;
    }

    /*
     * Determine the type of a file from its mime type
     */
    public function getTypeFromMimeAttribute() : string
    {
        if (!($mime = $this->mime_type)) {
            if ($this->getDiskDriverName() !== 'local') {
                return static::TYPE_OTHER;
            }

            $mime = $this->getMimeAttribute();
        }

        if (in_array($mime, ['image/jpeg', 'image/gif', 'image/png'])) {
            return static::TYPE_IMAGE;
        }

        if (in_array($mime, ['video/webm', 'video/mpeg', 'video/mp4', 'video/quicktime'])) {
            return static::TYPE_VIDEO;
        }

        if ($mime === 'application/pdf') {
            return static::TYPE_PDF;
        }

        return static::TYPE_OTHER;
    }

    public function getMimeAttribute() : string
    {
        return File::getMimetype($this->getPath());
    }

    public function getExtensionAttribute() : string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getHumanReadableSizeAttribute() : string
    {
        return File::getHumanReadableSize($this->size);
    }

    public function getDiskDriverName() : string
    {
        return config("filesystems.disks.{$this->disk}.driver");
    }

    /*
     * Determine if the media item has a custom property with the given name.
     */
    public function hasCustomProperty(string $propertyName) : bool
    {
        return array_key_exists($propertyName, $this->custom_properties);
    }

    /**
     * Get if the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty(string $propertyName, $default = null)
    {
        return $this->custom_properties[$propertyName] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setCustomProperty(string $name, $value)
    {
        $this->custom_properties = array_merge($this->custom_properties, [$name => $value]);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeCustomProperty(string $name)
    {
        if ($this->hasCustomProperty($name)) {
            $customProperties = $this->custom_properties;

            unset($customProperties[$name]);

            $this->custom_properties = $customProperties;
        }

        return $this;
    }

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames() : array
    {
        $conversions = ConversionCollection::createForMedia($this);

        return $conversions->map(function (Conversion $conversion) {
            return $conversion->getName();
        })->toArray();
    }
}
