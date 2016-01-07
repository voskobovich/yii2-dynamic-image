<?php

namespace voskobovich\image\dynamic\actions;

use voskobovich\base\helpers\HttpError;
use voskobovich\image\dynamic\forms\ImageIndexForm;
use Yii;
use yii\base\Action;
use yii\base\InvalidParamException;


/**
 * Class IndexAction
 * @package voskobovich\image\dynamic\actions
 */
abstract class IndexAction extends Action
{
    /**
     * System path to root images storage in you project
     * @var string
     */
    public $basePath;

    /**
     * Placeholder filename
     * @var string
     */
    public $placeholder;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->basePath)) {
            throw new InvalidParamException('Param "basePath" can not be empty.');
        }
    }

    /**
     * @param $folder
     * @param $id
     * @param $name
     * @param $width
     * @param $height
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($folder, $id, $name, $width, $height)
    {
        $form = new ImageIndexForm([
            'placeholder' => $this->placeholder,
            'basePath' => $this->basePath
        ]);

        $form->setAttributes([
            'id' => $id,
            'folder' => $folder,
            'name' => $name,
            'width' => $width,
            'height' => $height,
        ]);

        if (!$form->validate() || !$form->save()) {
            HttpError::the404();
        }

        return $form;
    }
}