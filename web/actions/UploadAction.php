<?php

namespace voskobovich\image\dynamic\web\actions;

use voskobovich\base\helpers\HttpError;
use voskobovich\image\dynamic\web\forms\ImageUploadForm;
use Yii;
use yii\base\Action;
use yii\base\InvalidParamException;
use yii\web\ServerErrorHttpException;


/**
 * Class UploadAction
 * @package voskobovich\image\dynamic\web\actions
 */
class UploadAction extends Action
{
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
     * @return string
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function run()
    {
        $params = Yii::$app->request->getBodyParams();

        $form = new ImageUploadForm([
            'basePath' => $this->basePath,
            'baseUrl' => $this->baseUrl
        ]);
        $form->setAttributes($params);

        if (!$form->validate()) {
            HttpError::the400();
        }

        if (!$form->save() && !$form->hasErrors()) {
            HttpError::the500('Failed to upload file for unknown reason.');
        }

        $response = Yii::$app->getResponse();
        $response->setStatusCode(201);
        $response->getHeaders()->set('Location', $form->getLink());

        return $form;
    }
}