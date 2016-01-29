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

        if (empty($this->baseUrl)) {
            throw new InvalidParamException('Param "baseUrl" can not be empty.');
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
            'basePath' => $this->basePath,
            'baseUrl' => $this->baseUrl,
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

        return Yii::$app->response->redirect($form->getUrl());
    }
}