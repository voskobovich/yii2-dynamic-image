Yii2 Dynamic Image
================================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist voskobovich/yii2-dynamic-image "*"
```

or add

```
"voskobovich/yii2-dynamic-image": "*"
```

to the require section of your `composer.json` file.


Nginx
-------------
location /uploads {  
	# Autogeneration images  
	if (!-f $request_filename) {  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/([0-9]*)x([0-9]*)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/([0-9]*)/(.*)$ /uploads/$1/placeholder.png redirect;  
	    rewrite ^/uploads/([a-z0-9-_]*)/placeholder.png$ /uploads/placeholder.png redirect;  
	}  
}  


Apache
-------------
# Autogeneration images
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/([0-9]+)x([0-9]+)_(.*)$ /image?folder=$1&id=$2&width=$3&height=$4&name=$5 [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/([0-9]+)/(.+)$ /uploads/$1/placeholder.png [R=302,L]  
RewriteRule ^uploads/([a-z0-9-]+)/placeholder.png$ /uploads/placeholder.png [R=302,L]  