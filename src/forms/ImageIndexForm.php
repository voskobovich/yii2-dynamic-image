<?php

namespace voskobovich\image\dynamic\forms;

use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
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
     * System path to root images storage in you project
     * @var string
     */
    public $basePath;

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
    private $_rootObjectPath;

    /**
     * Path to images of current model
     * @var string
     */
    private $_modelPath;

    /**
     * Path to file
     * @var string
     */
    private $_filePath;

    /**
     * Path to origin file
     * @var string
     */
    private $_filePathOrigin;

    /**
     * Path to model placeholder file
     * @var string
     */
    private $_placeholderPath;

    /**
     * Path to model origin placeholder file
     * @var string
     */
    private $_placeholderPathOrigin;

    /**
     * Path to common placeholder file
     * @var string
     */
    private $_rootPlaceholderPath;

    /**
     * Path to  common origin placeholder file
     * @var string
     */
    private $_rootPlaceholderPathOrigin;

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

    /**
     * Init variables
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function afterValidate()
    {
        parent::afterValidate();

        $this->_rootObjectPath = $this->basePath . DIRECTORY_SEPARATOR . $this->folder;

        if (!is_dir($this->_rootObjectPath)) {
            HttpError::the404();
        }

        $this->_modelPath = $this->_rootObjectPath . DIRECTORY_SEPARATOR . $this->id;

        if (!is_dir($this->_modelPath) && !mkdir($this->_modelPath)) {
            HttpError::the500('Can not create directory: ' . $this->_modelPath);
        }

        $this->_filePath = $this->_modelPath . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->name;
        $this->_filePathOrigin = $this->_modelPath . DIRECTORY_SEPARATOR . $this->name;

        $this->_placeholderPath = $this->_rootObjectPath . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_placeholderPathOrigin = $this->_rootObjectPath . DIRECTORY_SEPARATOR . $this->placeholder;

        $this->_rootPlaceholderPath = $this->basePath . DIRECTORY_SEPARATOR . $this->width . 'x' . $this->height . '_' . $this->placeholder;
        $this->_rootPlaceholderPathOrigin = $this->basePath . DIRECTORY_SEPARATOR . $this->placeholder;
    }

    /**
     * Save image
     * @return ImageInterface
     */
    public function save()
    {
        $create = false;
        if (file_exists($this->_filePath)) {
            $this->_image = Image::getImagine()->open($this->_filePath);
        } elseif (file_exists($this->_filePathOrigin)) {
            $create = true;
            $this->_image = Image::getImagine()->open($this->_filePathOrigin);
        } elseif (file_exists($this->_placeholderPath)) {
            $this->_image = Image::getImagine()->open($this->_placeholderPath);
            $this->_filePath = $this->_placeholderPath;
        } elseif (file_exists($this->_placeholderPathOrigin)) {
            $create = true;
            $this->_image = Image::getImagine()->open($this->_placeholderPathOrigin);
            $this->_filePath = $this->_placeholderPath;
        } elseif (file_exists($this->_rootPlaceholderPath)) {
            $this->_image = Image::getImagine()->open($this->_rootPlaceholderPath);
            $this->_filePath = $this->_rootPlaceholderPath;
        } elseif (file_exists($this->_rootPlaceholderPathOrigin)) {
            $create = true;
            $this->_image = Image::getImagine()->open($this->_rootPlaceholderPathOrigin);
            $this->_filePath = $this->_rootPlaceholderPath;
        }

        if ($create && $this->_image != null && !empty($this->_filePath)) {
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

            $this->_image->save($this->_filePath);
        }

        return true;
    }

    /**
     * File extension
     * @return mixed
     */
    public function getExtension()
    {
        $imageInfo = pathinfo($this->_filePath);
        return $imageInfo['extension'];
    }

    /**
     * Printing
     * @return string
     */
    public function __toString()
    {
        $this->_image->show($this->getExtension());
        exit;
    }
}