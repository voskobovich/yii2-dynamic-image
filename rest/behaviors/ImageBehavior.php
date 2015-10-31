<?php

namespace voskobovich\image\dynamic\rest\behaviors;

use voskobovich\base\helpers\FileHelper;
use voskobovich\image\dynamic\components\ImagePathMap;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\web\ServerErrorHttpException;


/**
 * Class ImageBehavior
 * @package voskobovich\image\dynamic\rest\behaviors
 */
class ImageBehavior extends Behavior
{
    /**
     * Список атрибутов для загрузки изображений
     * и список полей в которых храняться имена изображений
     * [
     *    'avatar' => 'avatar_name'
     * ]
     * При такой конфигурации будут доступны:
     * 1) avatar - переданное имя tmp-файла
     * 2) avatar__url - ссылка на оригинал
     *
     * @var array
     */
    public $fields = [];

    /**
     * Ссылка для доступа к изображению через сервер
     * @var string
     */
    public $originUrl;

    /**
     * Путь к изображаний модели
     * @var string
     */
    public $originPath;

    /**
     * Путь к временным изображениям
     * @var string
     */
    public $tempPath;

    /**
     * Путь к изображенияю-заглушке
     * @var
     */
    public $placeholderFile;

    /**
     * Значения атрибутов
     * @var array
     */
    private $_values = [];

    /**
     * Данные для геттер-заданий
     * @var array
     */
    private $_gettingTaskValue = [];

    /**
     * Данные для заданий "Перед сохранением"
     * @var array
     */
    private $_beforeSaveTaskValue = [];

    /**
     * Данные для заданий "После сохранениея"
     * @var array
     */
    private $_afterSaveTaskValue = [];

    /**
     * Полный путь к изображениям модели
     * @var
     */
    private $_originPath;

    /**
     * Ссылка к изображению
     * @var
     */
    private $_originUrl;

    /**
     * Полный путь к временным изображениям
     * @var string
     */
    private $_tempPath;

    /**
     * Events list
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'checkConfig',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Init
     * @param $event
     * @throws InvalidConfigException
     */
    public function checkConfig($event)
    {
        if (empty($this->fields)) {
            throw new InvalidConfigException('Property "fields" must be set');
        }

        if (empty($this->originPath)) {
            throw new InvalidConfigException('Property "originPath" must be set');
        }

        if (empty($this->originUrl)) {
            throw new InvalidConfigException('Property "originUrl" must be set');
        }

        if ($this->tempPath === null) {
            $this->tempPath = '@content/web/images/temp';
        }

        $this->_tempPath = Yii::getAlias($this->tempPath);
        if (!file_exists($this->_tempPath)) {
            throw new InvalidConfigException("Temp path: '{$this->_tempPath}' does not exist");
        }

        /** @var \yii\base\Event $event */
        /** @var \yii\db\ActiveRecord $model */
        $model = $event->sender;

        foreach ($this->fields as $field => $attribute) {
            if (empty($field) || !is_string($field)) {
                throw new InvalidConfigException('Invalid name virtual field in "fields" array');
            }
            if (!$model->hasAttribute($attribute)) {
                throw new InvalidConfigException('Unknown property: ' . get_class($model) . '::' . $attribute);
            }
        }
    }

    /**
     * Подготовка путей
     * @param $event
     */
    public function afterFind($event)
    {
        $this->preparePaths($event);
    }

    /**
     * Обработчик события "Перед сохранением"
     * @param $event
     */
    public function beforeSave($event)
    {
        $this->executeBeforeSaveTasks($event);
        $this->setImageAttributes($event);
    }

    /**
     * Обработчик события "После сохранениея"
     * @param $event
     */
    public function afterSave($event)
    {
        $this->preparePaths($event);
        $this->executeAfterSaveTasks($event);
    }

    /**
     * Prepare Paths and Urls
     * @param $event
     */
    private function preparePaths($event)
    {
        if (!$this->_originPath) {
            $originPath = $this->normalizePath($this->originPath);
            $this->_originPath = Yii::getAlias($originPath);
        }

        if (!$this->_originUrl) {
            $this->_originUrl = $this->normalizePath($this->originUrl);
        }
    }

    /**
     * Обработка атрибутов
     * @param $event
     */
    protected function setImageAttributes($event)
    {
        /** @var \yii\base\Event $event */
        /** @var \yii\db\ActiveRecord $model */
        $model = $event->sender;

        foreach ($this->fields as $fieldName => $attribute) {
            if ($this->hasNewValue($fieldName)) {

                $imageName = $this->getNewValue($fieldName);

                $tempPath = $this->getTempPathByImageName($imageName);
                $tempImage = $tempPath . DIRECTORY_SEPARATOR . $imageName;

                if (file_exists($tempImage)) {
                    $oldImageName = $this->getAttributeValueByFieldName($fieldName);
                    if ($oldImageName) {
                        $this->setAfterSaveTaskValue($fieldName, 'deleteImage', $oldImageName);
                    }

                    $model->{$attribute} = $imageName;
                    $this->setAfterSaveTaskValue($fieldName, 'saveImage', $imageName);
                }
            }
        }
    }

    /**
     * Обработка заданий на событии "Перед сохранением"
     * @param $event
     * @return null
     */
    protected function executeBeforeSaveTasks($event)
    {
        /** @var \yii\base\Event $event */
        /** @var \yii\db\ActiveRecord $model */
        $model = $event->sender;

        foreach ($this->_beforeSaveTaskValue as $fieldName => $fieldTasks) {
            foreach ($fieldTasks as $fieldTaskName => $fieldTaskValue) {

                $modelAttribute = $this->getAttributeByFieldName($fieldName);

                switch ($fieldTaskName) {
                    case 'clearAttribute' : {
                        $imageName = $model->{$modelAttribute};
                        if ($imageName) {
                            $this->setAfterSaveTaskValue($fieldName, 'deleteImage', $model->{$modelAttribute});
                        }
                        $model->{$modelAttribute} = null;
                    }
                }

            }
        }
    }

    /**
     * Обработка заданий на событии "После сохранениея"
     * @param $event
     * @return null
     * @throws ServerErrorHttpException
     * @throws \yii\base\Exception
     */
    protected function executeAfterSaveTasks($event)
    {
        foreach ($this->_afterSaveTaskValue as $fieldName => $fieldTasks) {
            foreach ($fieldTasks as $fieldTaskName => $fieldTaskValue) {

                switch ($fieldTaskName) {
                    case 'saveImage': {
                        $imageName = $fieldTaskValue;

                        $tempPath = $this->getTempPathByImageName($imageName);
                        $tempImage = $tempPath . DIRECTORY_SEPARATOR . $imageName;

                        $originImage = $this->_originPath . DIRECTORY_SEPARATOR . $imageName;

                        if (file_exists($tempImage)) {

                            if (!FileHelper::createDirectory($this->_originPath)) {
                                throw new InvalidParamException("Directory specified in '{$this->_originPath}' attribute doesn't exist or cannot be created.");
                            }

                            if (!rename($tempImage, $originImage)) {
                                throw new ServerErrorHttpException('Image not renamed: ' . $tempImage . ' to ' . $originImage);
                            }

                        }
                    }
                        break;
                    case 'deleteImage': {
                        $imageName = $fieldTaskValue;
                        $images = FileHelper::findFiles($this->_originPath, ['only' => ["*{$imageName}*"]]);
                        foreach ($images as $image) {
                            if (!unlink($image)) {
                                throw new ServerErrorHttpException('Image not deleted: ' . $image);
                            }
                        }
                    }
                        break;
                }

            }
        }
    }

    /**
     * Удаление папки с изображениями модели
     */
    public function afterDelete()
    {
        FileHelper::removeDirectory($this->_originPath);
    }

    /**
     * Replaces all placeholders in path variable with corresponding values.
     * @param $path
     *
     * @return mixed
     */
    protected function normalizePath($path)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->owner;

        return preg_replace_callback('/{([^}]+)}/', function ($matches) use ($model) {
            $name = $matches[1];
            $attribute = $model->getAttribute($name);
            if (is_string($attribute) || is_numeric($attribute)) {
                return $attribute;
            } else {
                return $matches[0];
            }
        }, $path);
    }

    /**
     * Позиция разделителя между названием атрибута
     * и названием геттер-задания
     * и командой
     * @param $haystack
     * @return bool|int
     */
    private static function getSeparatorPosition($haystack)
    {
        return strpos($haystack, '__');
    }

    /**
     * Проверка наличия виртаульного атрибута
     * в конфигурации поведения
     * @param $name
     * @return bool|int
     * @internal param $haystack
     */
    private function isProperty($name)
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * Название виртуального атрибута
     * @param $haystack
     * @return bool|int
     */
    private function getFieldName($haystack)
    {
        if (($pos = self::getSeparatorPosition($haystack)) !== false) {
            return substr($haystack, 0, $pos);
        }
        return $haystack;
    }

    /**
     * Получение названия геттер-задания из имени атрибута
     * @param $haystack
     * @return bool|int
     */
    private function getGettingTaskName($haystack)
    {
        if (($pos = self::getSeparatorPosition($haystack)) !== false) {
            $taskName = substr($haystack, ($pos + 2), strlen($haystack));
            if (!empty($taskName)) {
                return $taskName;
            }
        }
        return false;
    }

    /**
     * Проверяем атрибут на геттер-задание
     * @param $name
     * @return bool
     */
    private function isGettingProperty($name)
    {
        if (($pos = self::getSeparatorPosition($name)) !== false) {
            $field = self::getFieldName($name);
            if ($this->isProperty($field)) {
                $taskName = self::getGettingTaskName($name);

                if ($taskName == 'url') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Название атрибута по имени свойства
     * @param $name
     * @return mixed
     */
    private function getAttributeByFieldName($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    /**
     * Название атрибута по имени свойства
     * @param $name
     * @return mixed
     */
    private function getAttributeValueByFieldName($name)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->owner;

        $attributeName = $this->getAttributeByFieldName($name);
        return $model->{$attributeName};
    }

    /**
     * Путь к времменой директори в которой хранится изображение
     * @param $imageName
     * @return string
     * @throws InvalidConfigException
     */
    private function getTempPathByImageName($imageName)
    {
        /** @var ImagePathMap $imagePathMap */
        $imagePathMap = Yii::$app->get('imagePathMap');
        $tempPathID = $imagePathMap->get($imageName);

        return $this->_tempPath . DIRECTORY_SEPARATOR . $tempPathID;
    }

    /**
     * Выполнение геттер-задания
     * @param $fieldName
     * @param $taskName
     * @return null|string
     */
    protected function executeGettingTasks($fieldName, $taskName)
    {
        $imageName = $this->getAttributeValueByFieldName($fieldName);

        if ($imageName) {
            switch ($taskName) {
                case 'url' : {
                    return $this->_originUrl;
                }
            }
        }

        return null;
    }

    /**
     * Установка данных для геттер-задания
     * @param $fieldName
     * @param $taskName
     * @param $value
     */
    private function setGettingTaskValue($fieldName, $taskName, $value)
    {
        $this->_gettingTaskValue[$fieldName][$taskName] = $value;
    }

    /**
     * Установка данных для заданий "Перед сохранением"
     * @param $fieldName
     * @param $taskName
     * @param $value
     */
    private function setBeforeSaveTaskValue($fieldName, $taskName, $value)
    {
        $this->_beforeSaveTaskValue[$fieldName][$taskName] = $value;
    }

    /**
     * Установка данных для заданий "После сохранениея"
     * @param $fieldName
     * @param $taskName
     * @param $value
     */
    private function setAfterSaveTaskValue($fieldName, $taskName, $value)
    {
        $this->_afterSaveTaskValue[$fieldName][$taskName] = $value;
    }

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param $fieldName
     * @return null
     */
    private function hasNewValue($fieldName)
    {
        return isset($this->_values[$fieldName]);
    }

    /**
     * Get value of a dirty attribute by name
     * @param $fieldName
     * @return null
     */
    private function getNewValue($fieldName)
    {
        return $this->_values[$fieldName];
    }

    /**
     * Returns a value indicating whether a property can be read.
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     * TODO: Make it honor $checkVars ??
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->fields) || $this->isGettingProperty($name) ?
            true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * Returns a value indicating whether a property can be set.
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     * TODO: Make it honor $checkVars and $checkBehaviors ??
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return bool whether the property can be written
     * @internal param bool $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->fields) ?
            true : parent::canSetProperty($name, $checkVars);
    }

    /**
     * Returns the value of an object property.
     * Get it from our local temporary variable if we have it,
     * get if from DB otherwise.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @throws InvalidCallException
     * @throws UnknownPropertyException
     * @see __set()
     */
    public function __get($name)
    {
        $taskName = $this->getGettingTaskName($name);
        if ($taskName) {
            $fieldName = $this->getFieldName($name);
            return $this->executeGettingTasks($fieldName, $taskName);
        }

        $value = $this->getAttributeValueByFieldName($name);
        if ($this->hasNewValue($name)) {
            $value = $this->getNewValue($name);
        }

        return $value;
    }

    /**
     * Sets the value of a component property. The data is passed
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException
     * @see __get()
     */
    public function __set($name, $value)
    {
        if (empty($value)) {
            $fieldName = $this->getFieldName($name);
            $this->setBeforeSaveTaskValue($name, 'clearAttribute', $fieldName);
            return;
        }

        $taskName = $this->getGettingTaskName($name);
        if ($taskName) {
            $fieldName = $this->getFieldName($name);
            $this->setGettingTaskValue($fieldName, $taskName, $value);
            return;
        }

        $this->_values[$name] = $value;
    }
}