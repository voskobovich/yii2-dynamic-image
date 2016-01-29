Yii2 Dynamic Image
===

A toolkit to resize and crop images on the fly as specified in a GET request.

[![License](https://poser.pugx.org/voskobovich/yii2-dynamic-image/license.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Stable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/stable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Unstable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/unstable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Total Downloads](https://poser.pugx.org/voskobovich/yii2-dynamic-image/downloads.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)


Support
---
[GutHub issues](https://github.com/voskobovich/yii2-dynamic-image/issues).


See example
---

### Directory structure

This toolkit requires your images to be organized in a specific manner.

A certain directory is the root:

    uploads/images

Images that belong to certain entities (articles, users, etc.) are located in directories that correspond to those entities and their ids:

    uploads/images/article/123
    uploads/images/user/456

Every image should have an alphanumeric name (usually some kind of hash) and a proper extension:

    uploads/images/article/123/acbd18db4cc2f85cedef654fccc4a4d8.jpg
    uploads/images/user/456/37b51d194a7513e45b56f6524f2d51f2.png

### Web Package

#### Getting images

Suppose the original image is available at this URL:
```
http://example.com/uploads/images/article/2/XXXXXXXXXX.png
```
and you need it resized to 300x400 pixels.
Simply add the desired dimensions to the name of the image as shown below:
```
http://example.com/uploads/images/article/2/300x400_XXXXXXXXXX.png
```
The image will be resized proportionately and excess cropped. Not bad, right?

But what if only one of the dimensions is significant, and the other one is not? In this case, you need to set the non-significant dimension to zero:
```
http://example.com/uploads/images/article/2/300x0_XXXXXXXXXX.png
```
or
```
http://example.com/uploads/images/article/2/0x400_XXXXXXXXXX.png
```

##### Placeholder

The system can be configured to work with placeholders to serve when the image is not available. Placeholder file is usually named `placeholder.png`. There are two kinds of placeholder - general and entity-specific. General placeholder is placed into the root directory, entity-specific placeholder is placed into the entity directory:

    http://example.com/uploads/images/placeholder.png
    http://example.com/uploads/images/article/placeholder.png

With this configuration, article images have their own specific placeholder, while user images fall back to the general placeholder. Requesting a non-existent user image will return the general placeholder (`http://domain.com/uploads/images/placeholder.png`), while requesting a non-existent article image will return the entity-specific article placeholder (`http://domain.com/uploads/images/article/placeholder.png`).


#### Uploading images

To upload a image you need to send a **multipart/form-data** POST request:
```
POST: http://example.com/image/upload
Attribute name: file
```
The response will contain
```
{
    "name": "a26b9e822d962f1c7ebf6c255b170820.jpg",
    "url": "http://static.example.com/uploads/images/temp/20150908",
    "width": 300,
    "height": 400
}
```


### API Package


#### Getting images


After configuration the answer API you get 4 new attribute.
```
{
    ...
    "image_small": "namesmallimage.png",
    "image_small__url": "http://static.example.com/uploads/images/article/2",
    "image_big": "namebigimage.png",
    "image_big__url": "http://static.example.com/uploads/images/article/2"
}
```
Now on the client you can do so
```
var bigImageUrl = answer.image_big__url + '/300x400_' + answer.image_big;
```

#### Uploading images


To upload a image you need to send a **multipart/form-data** POST request:
```
POST: http://api.example.com/image/upload
Attribute name: file
```
The response will contain
```
{
    "name": "a26b9e822d962f1c7ebf6c255b170820.jpg",
    "url": "http://static.example.com/uploads/images/temp/20150908",
    "width": 300,
    "height": 400
}
```


Installation
---

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist voskobovich/yii2-dynamic-image "~1.0"
```

or add

```
"voskobovich/yii2-dynamic-image": "~1.0"
```

to the require section of your `composer.json` file.


Usage
---


#### Web Package


Use the package for **voskobovich\image\dynamic\web**.

Create and configure your controller.
```
class ImageController extends Frontend
{
   /**
    * @inheritdoc
    */
   public function behaviors()
   {
       $behaviors = parent::behaviors();
       $behaviors['verbs'] = [
           'class' => VerbFilter::className(),
           'actions' => [
               'index' => ['GET'],
               'upload' => ['POST'],
           ],
       ];
       return $behaviors;
   }

   /**
    * @inheritdoc
    */
   public function actions()
   {
       $actions = parent::actions();

       $actions['index'] = [
           'class' => 'voskobovich\image\dynamic\web\actions\IndexAction',
           'basePath' => Yii::getAlias("@webroot/uploads/images"),
           'baseUrl' => '/uploads/images',
           'placeholder' => 'placeholder.png'
       ];
       $actions['upload'] = [
           'class' => 'voskobovich\image\dynamic\web\actions\UploadAction',
           'basePath' => Yii::getAlias('@webroot/uploads/images'),
           'baseUrl' => '/uploads/images',
       ];

       return $actions;
   }
}
```

#### API Package


Use the package for **voskobovich\image\dynamic\rest**.

Configure your controller as described above.
```
class Post extends ActiveRecord
{
    //...

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['image_small', 'string', 'max' => 255];
        $rules[] = ['image_small', ImageValidator::className()];

        $rules[] = ['image_big', 'string', 'max' => 255];
        $rules[] = ['image_big', ImageValidator::className()];

        return $rules;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors[] = [
            'class' => ImageBehavior::className(),
            'basePath' => '@webroot/uploads/images/article/{id}',
            'baseUrl' => '/uploads/images/article/{id}',
            'fields' => [
                'image_small' => 'image_small_name',
                'image_big' => 'image_big_name',
            ]
        ];

        return $behaviors;
    }

    public function fields()
    {
        return [
            ...
            'image_small',
            'image_small__url',
            'image_big',
            'image_big__url',
        ];
    }

    //...
}
```

Configure ImagePathMap component in your config file.
```
return [
    ...
    'components' => [
        //...
        'imagePathMap' => [
            'class' => 'voskobovich\image\dynamic\components\ImagePathMap'
        ],
    ],
];
```


#### Web server configuration


##### Nginx
```
location /uploads {
    # For https://github.com/voskobovich/yii2-dynamic-image
    if (!-f $request_filename) {
        # uploads/images/(entity)/(id)/(width)x(height)_(original name) -> image server action
        rewrite ^/uploads/images/([a-z0-9-]+)/([0-9]+)/([0-9]+)x([0-9]+)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 redirect;

        # uploads/images/(entity)/(id)/(original name) -> entity-specific placeholder
        rewrite ^/uploads/images/([a-z0-9-]+)/([0-9]+)/(.+)$ /uploads/images/$1/placeholder.png redirect;

        # uploads/images/(entity)/(id)/placeholder.png -> general placeholder
        rewrite ^/uploads/images/([a-z0-9-_]*)/placeholder.png$ /uploads/images/placeholder.png redirect;
    }
}
```

##### Apache
```
# For https://github.com/voskobovich/yii2-dynamic-image

# uploads/images/(entity)/(id)/(width)x(height)_(original name) -> image server action
RewriteRule ^uploads/images/([a-z0-9-]+)/([0-9]+)/([0-9]+)x([0-9]+)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 [R=302,L]

# uploads/images/(entity)/(id)/(original name) -> entity-specific placeholder
RewriteRule ^uploads/images/([a-z0-9-]+)/([0-9]+)/(.+)$ /uploads/images/$1/placeholder.png [R=302,L]

# uploads/images/(entity)/(id)/placeholder.png -> general placeholder
RewriteRule ^uploads/images/([a-z0-9-]+)/placeholder.png$ /uploads/images/placeholder.png [R=302,L]
```