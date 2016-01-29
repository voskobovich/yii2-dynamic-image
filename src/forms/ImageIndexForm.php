<?php

namespace voskobovich\image\dynamic\forms;

use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
use voskobovich\base\helpers\FileHelper;
use voskobovich\base\helpers\HttpError;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\imagine\Image;


/**
 * Class ImageIndexForm
 * @package voskobovich\image\dynamic\forms
 */
class ImageIndexForm extends Model
{
    /**
     * Origin filename
     * @var string
     */
    public $folder;

    /**
     * Identity of model
     * @var string
     */
    public $id;

    /**
     * Origin filename
     * @var string
     */
    public $name;

    /**
     * Desired width
     * @var integer
     */
    public $width;

    /**
     * Desired height
     * @var integer
     */
    public $height;

    /**
     * System path to images storage
     * @var string
     */
    public $basePath;

    /**
     * Base url to images storage
     * @var string
     */
    public $baseUrl;

    /**
     * Filename of placeholder image
     * @var integer
     */
    public $placeholder;

    /**
     * @var ImageInterface
     */
    private $_image;

    /**
     * Root path to images of model
     * @var string
     */
    private $_pathRootObject;

    /**
     * Path to images of current model
     * @var string
     */
    private $_pathObject;

    /**
     * Path to file
     * @var string
     */
    private $_pathObjectFile;

    /**
     * Path to origin file
     * @var string
     */
    private $_pathObjectFileOrigin;

    /**
     * Path to model placeholder file
     * @var string
     */
    private $_pathObjectPlaceholder;

    /**
     * Path to model origin placeholder file
     * @var string
     */
    private $_pathObjectPlaceholderOrigin;

    /**
     * Path to common placeholder file
     * @var string
     */
    private $_pathRootPlaceholder;

    /**
     * Path to  common origin placeholder file
     * @var string
     */
    private $_pathRootPlaceholderOrigin;

    /**
     * Init
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->basePath)) {
            throw new InvalidConfigException('Property "basePath" must be filled');
        }

        if (empty($this->placeholder)) {
            $this->placeholder = 'placeholder.png';
        }

        // Fix error "PHP GD Allowed memory size exhausted".
        ini_set('memory_limit', '512M');
    }

    /**
     * Validation rules
     * @return array
     */
    public function rules()
    {
        return [
            [['width', 'height', 'name', 'folder', 'id'], 'required'],
            [
                ['width', 'height'],
                'integer',
                'min' => 1,
                'max' => 1024,
                'when' => function ($model, $attribute) {
                    switch ($attribute) {
                        case 'width' :
                            return $model->height == 0;
                            break;
                        case 'height' :
                            return $model->width == 0;
                            break;
                    }
                    return true;
                }
            ],
            [['id'], 'integer'],
            [['name', 'folder'], 'string'],

        ];
    }

    private $_url;
    private $_urlRootObject;
    private $_urlObject;
    private $_urlObjectFile;
    private $_urlObjectPlaceholder;
    private $_urlRootPlaceholder;

    /**
     * Init variables
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function afterValidate()
    {
        parent::afterValidate();

        $this->_pathRootObject = $this->basePath . DIRECTORY_SEPARATOR . $this->folder;
        $this->_urlRootObject = $this->baseUrl . DIRECTORY_SEPARATOR . $this->folder;

        if (!is_dir($this->_pathRootObject)) {
            HttpError::the404();
        }

        $this->_pathObject = $this->_pathRootObject . DIRECTORY_SEPARATOR . $this->id;
        $this->_urlObject = $this->_urlRootObject . DIRECTORY_SEPARATOR . $this->id;

        if (!is_dir($this->_pathObject) && !FileHelper::createDirectory($this->_pathObject)) {
            HttpError::the500('Can not create directory: ' . $this->_pathObject);
        }

        $this->_pathObjectFile = $this->_pathObject . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->name;
        $this->_urlObjectFile = $this->_urlObject . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->name;
        $this->_pathObjectFileOrigin = $this->_pathObject . DIRECTORY_SEPARATOR . $this->name;

        $this->_pathObjectPlaceholder = $this->_pathRootObject . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_urlObjectPlaceholder = $this->_urlRootObject . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_pathObjectPlaceholderOrigin = $this->_pathRootObject . DIRECTORY_SEPARATOR . $this->placeholder;

        $this->_pathRootPlaceholder = $this->basePath . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_urlRootPlaceholder = $this->baseUrl . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_pathRootPlaceholderOrigin = $this->basePath . DIRECTORY_SEPARATOR . $this->placeholder;
    }

    /**
     * Save image
     * @return ImageInterface
     */
    public function save()
    {
        if (file_exists($this->_pathObjectFile)) {
            $this->_url = $this->_urlObjectFile;
            return true;
        } elseif (file_exists($this->_pathObjectFileOrigin)) {
            $this->_image = Image::getImagine()->open($this->_pathObjectFileOrigin);
        } elseif (file_exists($this->_pathObjectPlaceholder)) {
            $this->_url = $this->_urlObjectPlaceholder;
            return true;
        } elseif (file_exists($this->_pathObjectPlaceholderOrigin)) {
            $this->_image = Image::getImagine()->open($this->_pathObjectPlaceholderOrigin);
            $this->_pathObjectFile = $this->_pathObjectPlaceholder;
            $this->_urlObjectFile = $this->_urlObjectPlaceholder;
        } elseif (file_exists($this->_pathRootPlaceholder)) {
            $this->_url = $this->_urlRootPlaceholder;
            return true;
        } elseif (file_exists($this->_pathRootPlaceholderOrigin)) {
            $this->_image = Image::getImagine()->open($this->_pathRootPlaceholderOrigin);
            $this->_pathObjectFile = $this->_pathRootPlaceholder;
            $this->_urlObjectFile = $this->_urlRootPlaceholder;
        }

        if ($this->_image) {
            if (!$this->width || !$this->height) {
                $ratio = $this->_image->getSize()->getWidth() / $this->_image->getSize()->getHeight();
                if ($this->width) {
                    $this->height = ceil($this->width / $ratio);
                } else {
                    $this->width = ceil($this->height * $ratio);
                }
                $box = new Box($this->width, $this->height);
                $this->_image->resize($box);
                $this->_image->crop(new Point(0, 0), $box);
            } else {
                $box = new Box($this->width, $this->height);
                $size = $this->_image->getSize();
                if (($size->getWidth() <= $box->getWidth() && $size->getHeight() <= $box->getHeight()) || (!$box->getWidth() && !$box->getHeight())) {

                } else {
                    $this->_image = $this->_image->thumbnail($box, ManipulatorInterface::THUMBNAIL_OUTBOUND);

                    // calculate points
                    $size = $this->_image->getSize();

                    $startX = 0;
                    $startY = 0;
                    if ($size->getWidth() < $this->width) {
                        $startX = ceil($this->width - $size->getWidth()) / 2;
                    }
                    if ($size->getHeight() < $this->height) {
                        $startY = ceil($this->height - $size->getHeight()) / 2;
                    }

                    // create empty image to preserve aspect ratio of thumbnail
                    $thumb = Image::getImagine()->create($box, new Color('FFF', 100));
                    $thumb->paste($this->_image, new Point($startX, $startY));

                    $this->_image = $thumb;
                }
            }

            $this->_image->save($this->_pathObjectFile);
            $this->_url = $this->_urlObjectFile;
            return true;
        }

        return false;
    }

    /**
     * Result image url
     * @return mixed
     */
    public function getUrl()
    {
        return $this->_url;
    }
}