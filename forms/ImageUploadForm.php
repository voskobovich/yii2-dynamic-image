<?php

namespace voskobovich\image\dynamic\forms;

use voskobovich\base\helpers\FileHelper;
use voskobovich\image\dynamic\components\ImagePathMap;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\web\UploadedFile;


/**
 * Class ImageUploadForm
 * @package voskobovich\image\dynamic\forms
 */
abstract class ImageUploadForm extends Model
{
    /**
     * Параметр объекта
     * @var UploadedFile
     */
    public $file;

    /**
     * System path to root images storage in you project
     * @var string
     */
    public $basePath;

    /**
     * Web path to root images storage in you project
     * @var string
     */
    public $baseUrl;

    /**
     * Allowed extensions to upload
     * @var string
     */
    public $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    /**
     * Имя созданного изображения
     * @var
     */
    private $_name;

    /**
     * Имя папки в которую будет
     * сохранен файл во временной папке
     * @var
     */
    private $_path;

    /**
     * Идентификатор временной папки
     * @var
     */
    private $_pathID;

    /**
     * Путь по которому будет достапно изображение
     * после загрузки
     * @var
     */
    private $_url;

    /**
     * Ширина изображения
     * @var
     */
    private $_width;

    /**
     * Высота изображения
     * @var
     */
    private $_height;

    /**
     * Инициализация
     */
    public function init()
    {
        if (empty($this->basePath)) {
            throw new InvalidConfigException('Property "basePath" must be filled');
        }

        if (empty($this->baseUrl)) {
            throw new InvalidConfigException('Property "baseUrl" must be filled');
        }

        if (!file_exists($this->basePath)) {
            throw new InvalidConfigException('Path not found: ' . $this->basePath);
        }

        $this->_pathID = date('Ymd');
        $this->_path = $this->basePath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $this->_pathID;
        $this->_url = $this->baseUrl . '/temp/' . $this->_pathID;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['file', 'required'],
            ['file', 'image', 'extensions' => $this->allowedExtensions],
        ];
    }

    /**
     * Перед валидацией получаем
     * экземпляр объекта файла
     *
     * @return bool
     */
    public function beforeValidate()
    {
        $this->file = UploadedFile::getInstanceByName('file');

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            'file',
        ];
    }

    /**
     * Сохранение файла на сервере
     */
    public function save()
    {
        // Если это действительно экземпляр объекта файла
        if ($this->file instanceof UploadedFile) {

            if (!FileHelper::createDirectory($this->_path)) {
                throw new InvalidParamException("Directory specified in '{$this->_path}' attribute doesn't exist or cannot be created.");
            }

            $name = FileHelper::getRandomFileName($this->_path, $this->file->getExtension());
            $filePath = $this->_path . DIRECTORY_SEPARATOR . $name;

            // Если оригинальная картинка сохранилась
            if ($this->file->saveAs($filePath)) {

                $imageInfo = getimagesize($filePath);
                list($width, $height) = $imageInfo;

                $this->_width = $width;
                $this->_height = $height;

                $this->_name = $name;

                /** @var ImagePathMap $imagePathMap */
                $imagePathMap = Yii::$app->get('imagePathMap', false);
                if ($imagePathMap != null) {
                    $imagePathMap->add($this->_name, $this->_pathID);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Название изображения
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Высота загруженного изображения
     * @return string
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * Высота загруженного изображения
     * @return string
     */
    public function getHeight()
    {
        return $this->_height;
    }

    /**
     * Ссылка на директорию изображения
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Ссылка на директорию с изображением
     * @return string
     */
    public function getLink()
    {
        return $this->_url . '/' . $this->_name;
    }
}