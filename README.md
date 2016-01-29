Yii2 Dynamic Image
===

A toolkit for creating dynamic images during the GET request.

[![License](https://poser.pugx.org/voskobovich/yii2-dynamic-image/license.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Stable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/stable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Unstable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/unstable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Total Downloads](https://poser.pugx.org/voskobovich/yii2-dynamic-image/downloads.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)


Support
---
[GutHub issues](https://github.com/voskobovich/yii2-dynamic-image/issues).


See example
---

### Web Package


#### Getting image


Suppose the original image is available at URL.  
```
http://domain.com/uploads/images/post/2/imagesha1andmd5hash.png
```  
and you want to get it at a rate of 300x400 pixels.  
Simply add the desired dimensions in the name of the image as shown below.   
```
http://domain.com/uploads/images/post/2/300x400_imagesha1andmd5hash.png
```  
Not bad, right?  

But what if one size does not matter and system need to find it?  
In this case, you need to specify a zero instead of the desired value.  
```
http://domain.com/uploads/images/post/2/300x0_imagesha1andmd5hash.png
```  
or  
```
http://domain.com/uploads/images/post/2/0x400_imagesha1andmd5hash.png
```  


##### Placeholder


The system is able to work with placeholders.  
Placeholder can be root and for group objects.  

Originals placed on the URL.  
```
http://domain.com/uploads/images/placeholder.png
http://domain.com/uploads/images/post/placeholder.png
```  
If the system did not find the image, then it will use a placeholder.  
It will be prepared for the required size and returned instead of the expected picture.

For example  
```
Querying:  
http://domain.com/uploads/images/post/2/300x400_imagesha1andmd5hash.png  
Returned:  
http://domain.com/uploads/images/post/300x400_placeholder.png  
or  
http://domain.com/uploads/images/300x400_placeholder.png
```


#### Uploading image


To upload a image you need to send a **multipart/form-data** POST request:  
```
POST: http://domain.com/image/upload
Attribute name: file
```  
The answer will contain  
```
{
    "name": "a26b9e822d962f1c7ebf6c255b170820.jpeg",
    "url": "http://static.domain.com/uploads/images/temp/20150908",
    "width": 300,
    "height": 400
}
```


### Rest Package


#### Getting image


After configuration the answer API you get 4 new attribute.  
```
{
    ...
    "image_small": "namesmallimage.png",
    "image_small__url": "http://static.domain.com/uploads/images/post/2",
    "image_big": "namebigimage.png",
    "image_big__url": "http://static.domain.com/uploads/images/post/2"
}
```  
Now on the client you can do so  
```
var bigImageUrl = answer.image_big__url + '/300x400_' + answer.image_big;
```

#### Uploading image


To upload a image you need to send a **multipart/form-data** POST request:  
```
POST: http://api.domain.com/image/upload
Attribute name: file
```  
The answer will contain  
```
{
    "name": "a26b9e822d962f1c7ebf6c255b170820.jpeg",
    "url": "http://static.domain.com/uploads/images/temp/20150908",
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
           'basePath' => Yii::getAlias("@webroot/uploads"),
           'baseUrl' => '/uploads/images',
           'placeholder' => 'placeholder.png'
       ];
       $actions['upload'] = [
           'class' => 'voskobovich\image\dynamic\web\actions\UploadAction',
           'basePath' => Yii::getAlias('@webroot/uploads'),
           'baseUrl' => '/uploads/images',
       ];

       return $actions;
   }
}
```


#### Rest Package


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
            'basePath' => '@webroot/uploads/images/posts/{id}',
            'baseUrl' => '/uploads/images/posts/{id}',
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


#### Configuration web server


##### Nginx
```
location /uploads {  
	# For https://github.com/voskobovich/yii2-dynamic-image  
	if (!-f $request_filename) {  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/([0-9]*)x([0-9]*)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/(.*)$ /uploads/$1/placeholder.png redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/placeholder.png$ /uploads/placeholder.png redirect;  
	}  
}  
```


##### Apache
```
# For https://github.com/voskobovich/yii2-dynamic-image
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/([0-9]+)x([0-9]+)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/(.+)$ /uploads/$1/placeholder.png [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/placeholder.png$ /uploads/placeholder.png [R=302,L]  
```