<?php

namespace voskobovich\image\dynamic\rest\validators;

use voskobovich\image\dynamic\components\ImagePathMap;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;


/**
 * Class ImageValidator
 * @package voskobovich\image\dynamic\rest\validators
 */
class ImageValidator extends Validator
{
    /**
     * Path to temp file
     * @var string
     */
    public $tempPath;

    /**
     * @var string the error message used when the uploaded file is not an image.
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     */
    public $notImage;

    /**
     * @var integer the minimum width in pixels.
     * Defaults to null, meaning no limit.
     * @see underWidth for the customized message used when image width is too small.
     */
    public $minWidth;

    /**
     * @var integer the maximum width in pixels.
     * Defaults to null, meaning no limit.
     * @see overWidth for the customized message used when image width is too big.
     */
    public $maxWidth;

    /**
     * @var integer the minimum height in pixels.
     * Defaults to null, meaning no limit.
     * @see underHeight for the customized message used when image height is too small.
     */
    public $minHeight;

    /**
     * @var integer the maximum width in pixels.
     * Defaults to null, meaning no limit.
     * @see overWidth for the customized message used when image height is too big.
     */
    public $maxHeight;

    /**
     * @var string the error message used when the image is under [[minWidth]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     * - {limit}: the value of [[minWidth]]
     */
    public $underWidth;

    /**
     * @var string the error message used when the image is over [[maxWidth]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     * - {limit}: the value of [[maxWidth]]
     */
    public $overWidth;

    /**
     * @var string the error message used when the image is under [[minHeight]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     * - {limit}: the value of [[minHeight]]
     */
    public $underHeight;

    /**
     * @var string the error message used when the image is over [[maxHeight]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     * - {limit}: the value of [[maxHeight]]
     */
    public $overHeight;

    /**
     * Full path to temp folder
     * @var string
     */
    private $_tempPath;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', 'File not found.');
        }
        if ($this->notImage === null) {
            $this->notImage = Yii::t('yii', 'The file is not an image.');
        }
        if ($this->underWidth === null) {
            $this->underWidth = Yii::t('yii',
                'The image is too small. The width cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
        }
        if ($this->underHeight === null) {
            $this->underHeight = Yii::t('yii',
                'The image is too small. The height cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
        }
        if ($this->overWidth === null) {
            $this->overWidth = Yii::t('yii',
                'The image is too large. The width cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
        }
        if ($this->overHeight === null) {
            $this->overHeight = Yii::t('yii',
                'The image is too large. The height cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
        }

        if ($this->tempPath === null) {
            $this->tempPath = '@content/web/images/temp';
        }
        $this->_tempPath = Yii::getAlias($this->tempPath);
        if (!file_exists($this->_tempPath)) {
            throw new InvalidConfigException("Temp path: '{$this->_tempPath}' does not exist");
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($imageName)
    {
        /** @var ImagePathMap $imagePathMap */
        $imagePathMap = Yii::$app->get('imagePathMap');
        $tempPathID = $imagePathMap->get($imageName);

        $tempPath = $this->_tempPath . DIRECTORY_SEPARATOR . $tempPathID;
        $imagePath = $tempPath . DIRECTORY_SEPARATOR . $imageName;

        if (file_exists($imagePath)) {
            return $this->validateImage($imagePath);
        }

        return [$this->message, []];
    }

    /**
     * Validates an image file.
     * @param string $imagePath
     * @return array|null the error message and the parameters to be inserted into the error message.
     * Null should be returned if the data is valid.
     */
    protected function validateImage($imagePath)
    {
        if (false === ($imageInfo = getimagesize($imagePath))) {
            return [$this->notImage];
        }
        list($width, $height) = $imageInfo;
        if ($width == 0 || $height == 0) {
            return [$this->notImage];
        }
        if ($this->minWidth !== null && $width < $this->minWidth) {
            return [$this->underWidth, ['limit' => $this->minWidth]];
        }
        if ($this->minHeight !== null && $height < $this->minHeight) {
            return [$this->underHeight, ['limit' => $this->minHeight]];
        }
        if ($this->maxWidth !== null && $width > $this->maxWidth) {
            return [$this->overWidth, ['limit' => $this->maxWidth]];
        }
        if ($this->maxHeight !== null && $height > $this->maxHeight) {
            return [$this->overHeight, ['limit' => $this->maxHeight]];
        }
        return null;
    }
}