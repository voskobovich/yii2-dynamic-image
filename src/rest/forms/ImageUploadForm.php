<?php

namespace voskobovich\image\dynamic\rest\forms;


/**
 * Class ImageUploadForm
 * @package voskobovich\image\dynamic\rest\forms
 */
class ImageUploadForm extends \voskobovich\image\dynamic\forms\ImageUploadForm
{
    /**
     * @return array
     */
    public function fields()
    {
        return [
            'name',
            'url',
            'width',
            'height'
        ];
    }
}