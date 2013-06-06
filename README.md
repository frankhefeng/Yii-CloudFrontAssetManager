yii-CloudFrontAssetManager
==========================

CloudFrontAssetManager for PHP Yii framework

This extension serves as a replacement for the class CAssetManager. After you setup CloudFront CDN for assets, this asset manager will publish assets to assets folder, and generate CloudFront URL for assets. It also provides an invalidate method, which should be called after each release (Please set LOCAL_TESTING to 1 in local and set LOCAL_TESTING to 0 in production server or change the related code).

##Requirements

* An account on Amazon AWS S3
* CloudFront CDN configured

##Usage

To install just unzip into the protected folder . 

Basic configuration:

~~~
[php]
main.php
define('LOCAL_TESTING', 0);  //Set it to 0 in development and set it to 1 in production server

'components' = array(
		......
		
        'assetManager' => array(
            'class' => 'CloudFrontAssetManager',
            'accessKey'=>'xxxxxxxx',                  // changing this to your Amazon Access Key ID
            'secretKey'=>'xxxxxxxx',                  // changing this to your Amazon Secret Access Key
            'host' => 'xxxxxxxx.cloudfront.net',      // changing this to your CloudFront hostname
            'distribution' => 'xxxxxxxx',             // changing this to your CloudFront distribution
            'invalidatePaths' => array('assets')      // resource folders to be hosted on CloudFront
        ),
        
        ......
    )
Add this action in some Controller:
    /**
     * Force CloudFront to update all resources
     */
    public function actionInvalidateCF()
    {
        Yii::app()->getAssetManager()->invalidate();
    }
and, call actionInvalidateCF after each release to inform CloudFront to update all files
~~~

Ready!

