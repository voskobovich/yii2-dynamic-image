<?php

namespace voskobovich\image\dynamic\web\forms;

use yii\helpers\Json;


/**
 * Class ImageUploadForm
 * @package voskobovich\image\dynamic\web\forms
 */
class ImageUploadForm extends \voskobovich\image\dynamic\forms\ImageUploadForm
{
    /**
     * @return string
     */
    public function __toString()
    {
        return Json::encode([
            'name' => $this->getName(),
            'url' => $this->getUrl(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
        ]);
    }
}