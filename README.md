Yii2 Dynamic Image
================================

A toolkit for creating dynamic images during the GET request.

[![License](https://poser.pugx.org/voskobovich/yii2-dynamic-image/license.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Stable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/stable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Latest Unstable Version](https://poser.pugx.org/voskobovich/yii2-dynamic-image/v/unstable.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)
[![Total Downloads](https://poser.pugx.org/voskobovich/yii2-dynamic-image/downloads.svg)](https://packagist.org/packages/voskobovich/yii2-dynamic-image)

See example
------------

Suppose the original image is available at URL.  
```
http://domain.com/uploads/images/post/2/imagesha1andmd5has.png
```  
and you want to get it at a rate of 300x400 pixels.  
Simply add the desired dimensions in the name of the image as shown below.   
```
http://domain.com/uploads/images/post/2/300x400_imagesha1andmd5has.png
```  
Not bad, right?  

But what if one size does not matter and system need to find it?  
In this case, you need to specify a zero instead of the desired value.  
```
http://domain.com/uploads/images/post/2/300x0_imagesha1andmd5has.png
```  
or  
```
http://domain.com/uploads/images/post/2/0x400_imagesha1andmd5has.png
```  

### Placeholder
The system is able to work with placeholders.  
Placeholder can be root and for group objects.  

Originals placed on the URL.  
```
http://domain.com/uploads/images/placeholder.png
http://domain.com/uploads/images/post/placeholder.png
```  
If the system did not find the image, then it will use a placeholder.  
It will be prepared for the required size and returned instead of the expected picture.

For example.  
```
Querying:  
http://domain.com/uploads/images/post/2/300x400_imagesha1andmd5has.png  
Returned:  
http://domain.com/uploads/images/post/300x400_placeholder.png  
or  
http://domain.com/uploads/images/300x400_placeholder.png
```

Support
-------
[GutHub issues](https://github.com/voskobovich/yii2-dynamic-image/issues).


Installation
------------

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


Nginx
-------------
```
location /uploads {  
	# Autogeneration images  
	if (!-f $request_filename) {  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/([0-9]*)x([0-9]*)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/(.*)$ /uploads/$1/placeholder.png redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/placeholder.png$ /uploads/placeholder.png redirect;  
	}  
}  
```


Apache
-------------
```
# Autogeneration images
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/([0-9]+)x([0-9]+)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/(.+)$ /uploads/$1/placeholder.png [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/placeholder.png$ /uploads/placeholder.png [R=302,L]  
```