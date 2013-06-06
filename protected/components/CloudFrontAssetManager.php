<?php
class CloudFrontAssetManager extends CAssetManager
{
    public $accessKey;
    public $secretKey;
    public $host;
    public $distribution;
    public $invalidatePaths;
    private $_baseCloudFrontUrl;
    private $_published;

    public function getCloudFrontUrl($path)
    {
        if (LOCAL_TESTING)
        {
            return $path;
        }
        else
        {
            if ($this->_baseCloudFrontUrl === null)
            {
                if(Yii::app()->getRequest()->isSecureConnection)
                    $this->_baseCloudFrontUrl = 'https://'.$this->host;
                else
                    $this->_baseCloudFrontUrl = 'http://'.$this->host;
            }
            return $this->_baseCloudFrontUrl. $path;
        }
    }

    public function publish($path, $hashByName=false, $level=-1, $forceCopy=false)
    {
        return $this->getCloudFrontUrl(parent::publish($path, $hashByName, $level, $forceCopy));
    }

    protected function generatePath($file,$hashByName=false)
    {
        //always hash by name
        $file_rel = substr($file, strlen(Yii::getPathOfAlias('webroot')));
        return $this->hash($file_rel);
    }

    public function invalidate()
    {
        /**
         * Super-simple AWS CloudFront Invalidation Script
         * 
         * Steps:
         * 1. Set your AWS access_key
         * 2. Set your AWS secret_key
         * 3. Set your CloudFront Distribution ID
         * 4. Define the batch of paths to invalidate
         * 5. Run it on the command-line with: php cf-invalidate.php
         * 
         * The author disclaims copyright to this source code.
         *
         * Details on what's happening here are in the CloudFront docs:
         * http://docs.amazonwebservices.com/AmazonCloudFront/latest/DeveloperGuide/Invalidation.html
         * 
         * From: https://gist.github.com/claylo/1009169
         */

        $distribution = $this->distribution;
        $epoch = date('U');
         
        $paths = '';
        foreach ($this->invalidatePaths as $path)
        {
            $files=CFileHelper::findFiles(realpath($path));
            foreach($files as $file)
            {
                $file_rel = substr($file, strlen(Yii::getPathOfAlias('webroot')));
                $paths .= "    <Path>".$file_rel."</Path>\n";
            }
        }

        $xml = <<<EOT
<InvalidationBatch>
$paths    <CallerReference>{$distribution}{$epoch}</CallerReference>
</InvalidationBatch>
EOT;

        /**
         * You probably don't need to change anything below here.
         */
        $len = strlen($xml);
        $date = gmdate('D, d M Y G:i:s T');
        $sig = base64_encode(
            hash_hmac('sha1', $date, $this->secretKey, true)
        );
         
        $msg = "POST /2010-11-01/distribution/{$distribution}/invalidation HTTP/1.0\r\n";
        $msg .= "Host: cloudfront.amazonaws.com\r\n";
        $msg .= "Date: {$date}\r\n";
        $msg .= "Content-Type: text/xml; charset=UTF-8\r\n";
        $msg .= "Authorization: AWS {$this->accessKey}:{$sig}\r\n";
        $msg .= "Content-Length: {$len}\r\n\r\n";
        $msg .= $xml;
         
        $fp = fsockopen('ssl://cloudfront.amazonaws.com', 443, 
            $errno, $errstr, 30
        );
        if (!$fp) {
            die("Connection failed: {$errno} {$errstr}\n");
        }
        fwrite($fp, $msg);
        $resp = '';
        while(! feof($fp)) {
            $resp .= fgets($fp, 1024);
        }
        fclose($fp);
        echo $resp;
    }
}