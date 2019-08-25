<?php

class __AntiAdBlock_2784141
{
    private $token = 'c4b7e9262a0347db3109e8376423e01388ab3cf2';
    private $zoneId = '2784141';
    ///// do not change anything below this point /////
    private $requestDomainName = 'go.transferzenad.com';
    private $requestTimeout = 1000;
    private $requestUserAgent = 'AntiAdBlock API Client';
    private $requestIsSSL = false;
    private $cacheTtl = 30; // minutes
    private $version = '1';
    private $routeGetTag = '/v3/getTag';
    private $selfSourceContent;

    private function getTimeout()
    {
        $value = ceil($this->requestTimeout / 1000);

        return $value == 0 ? 1 : $value;
    }

    private function getTimeoutMS()
    {
        return $this->requestTimeout;
    }

    private function ignoreCache()
    {
        $key = md5('PMy6vsrjIf-' . $this->zoneId);

        return array_key_exists($key, $_GET);
    }

    private function getCurl($url)
    {
        if ((!extension_loaded('curl')) || (!function_exists('curl_version'))) {
            return false;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT => $this->requestUserAgent . ' (curl)',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->getTimeout(),
            CURLOPT_TIMEOUT_MS => $this->getTimeoutMS(),
            CURLOPT_CONNECTTIMEOUT => $this->getTimeout(),
            CURLOPT_CONNECTTIMEOUT_MS => $this->getTimeoutMS(),
        ));
        $version = curl_version();
        $scheme = ($this->requestIsSSL && ($version['features'] & CURL_VERSION_SSL)) ? 'https' : 'http';
        curl_setopt($curl, CURLOPT_URL, $scheme . '://' . $this->requestDomainName . $url);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    private function getFileGetContents($url)
    {
        if (!function_exists('file_get_contents') || !ini_get('allow_url_fopen') ||
            ((function_exists('stream_get_wrappers')) && (!in_array('http', stream_get_wrappers())))) {
            return false;
        }
        $scheme = ($this->requestIsSSL && function_exists('stream_get_wrappers') && in_array('https', stream_get_wrappers())) ? 'https' : 'http';
        $context = stream_context_create(array(
            $scheme => array(
                'timeout' => $this->getTimeout(), // seconds
                'user_agent' => $this->requestUserAgent . ' (fgc)',
            ),
        ));

        return file_get_contents($scheme . '://' . $this->requestDomainName . $url, false, $context);
    }

    private function getFsockopen($url)
    {
        $fp = null;
        if (function_exists('stream_get_wrappers') && in_array('https', stream_get_wrappers())) {
            $fp = fsockopen('ssl://' . $this->requestDomainName, 443, $enum, $estr, $this->getTimeout());
        }
        if ((!$fp) && (!($fp = fsockopen('tcp://' . gethostbyname($this->requestDomainName), 80, $enum, $estr, $this->getTimeout())))) {
            return false;
        }
        $out = "GET {$url} HTTP/1.1\r\n";
        $out .= "Host: {$this->requestDomainName}\r\n";
        $out .= "User-Agent: {$this->requestUserAgent} (socket)\r\n";
        $out .= "Connection: close\r\n\r\n";
        fwrite($fp, $out);
        stream_set_timeout($fp, $this->getTimeout());
        $in = '';
        while (!feof($fp)) {
            $in .= fgets($fp, 2048);
        }
        fclose($fp);

        $parts = explode("\r\n\r\n", trim($in));

        return isset($parts[1]) ? $parts[1] : '';
    }

    private function getCacheFilePath($url, $suffix = '.js')
    {
        return sprintf('%s/pa-code-v%s-%s%s', $this->findTmpDir(), $this->version, md5($url), $suffix);
    }

    private function findTmpDir()
    {
        $dir = null;
        if (function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
        } elseif (!empty($_ENV['TMP'])) {
            $dir = realpath($_ENV['TMP']);
        } elseif (!empty($_ENV['TMPDIR'])) {
            $dir = realpath($_ENV['TMPDIR']);
        } elseif (!empty($_ENV['TEMP'])) {
            $dir = realpath($_ENV['TEMP']);
        } else {
            $filename = tempnam(dirname(__FILE__), '');
            if (file_exists($filename)) {
                unlink($filename);
                $dir = realpath(dirname($filename));
            }
        }

        return $dir;
    }

    private function isActualCache($file)
    {
        if ($this->ignoreCache()) {
            return false;
        }

        return file_exists($file) && (time() - filemtime($file) < $this->cacheTtl * 60);
    }

    private function getCode($url)
    {
        $code = false;
        if (!$code) {
            $code = $this->getCurl($url);
        }
        if (!$code) {
            $code = $this->getFileGetContents($url);
        }
        if (!$code) {
            $code = $this->getFsockopen($url);
        }

        return $code;
    }

    private function getPHPVersion($major = true)
    {
        $version = explode('.', phpversion());
        if ($major) {
            return (int)$version[0];
        }
        return $version;
    }

    private function parseRaw($code)
    {
        $hash = substr($code, 0, 32);
        $dataRaw = substr($code, 32);
        if (md5($dataRaw) !== strtolower($hash)) {
            return null;
        }

        if ($this->getPHPVersion() >= 7) {
            $data = @unserialize($dataRaw, array(
                'allowed_classes' => false,
            ));
        } else {
            $data = @unserialize($dataRaw);
        }

        if ($data === false || !is_array($data)) {
            return null;
        }

        return $data;
    }

    private function getTag($code)
    {
        $data = $this->parseRaw($code);
        if ($data === null) {
            return '';
        }

        if (array_key_exists('code', $data)) {
            $this->selfUpdate($data['code']);
        }

        if (array_key_exists('tag', $data)) {
            return (string)$data['tag'];
        }

        return '';
    }

    public function get()
    {
        $e = error_reporting(0);
        $url = $this->routeGetTag . '?' . http_build_query(array(
                'token' => $this->token,
                'zoneId' => $this->zoneId,
                'version' => $this->version,
            ));
        $file = $this->getCacheFilePath($url);
        if ($this->isActualCache($file)) {
            error_reporting($e);

            return $this->getTag(file_get_contents($file));
        }
        if (!file_exists($file)) {
            @touch($file);
        }
        $code = '';
        if ($this->ignoreCache()) {
            $fp = fopen($file, "r+");
            if (flock($fp, LOCK_EX)) {
                $code = $this->getCode($url);
                ftruncate($fp, 0);
                fwrite($fp, $code);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        } else {
            $fp = fopen($file, 'r+');
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                if (file_exists($file)) {
                    $code = file_get_contents($file);
                } else {
                    $code = "<!-- cache not found / file locked  -->";
                }
            } else {
                $code = $this->getCode($url);
                ftruncate($fp, 0);
                fwrite($fp, $code);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        error_reporting($e);

        return $this->getTag($code);
    }

    private function getSelfBackupFilename()
    {
        return $this->getCacheFilePath($this->version, '');
    }

    private function selfBackup()
    {
        $this->selfSourceContent = file_get_contents(__FILE__);
        if ($this->selfSourceContent !== false && is_writable($this->findTmpDir())) {
            $fp = fopen($this->getSelfBackupFilename(), 'cb');
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);

                return false;
            }
            ftruncate($fp, 0);
            fwrite($fp, $this->selfSourceContent);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            return true;
        }

        return false;
    }

    private function selfRestore()
    {
        if (file_exists($this->getSelfBackupFilename())) {
            return rename($this->getSelfBackupFilename(), __FILE__);
        }

        return false;
    }

    private function selfUpdate($newCode)
    {
        if(is_writable(__FILE__)) {
            $hasBackup = $this->selfBackup();

            if ($hasBackup) {
                try {
                    $fp = fopen(__FILE__, 'cb');
                    if (!flock($fp, LOCK_EX)) {
                        fclose($fp);
                        throw new Exception();
                    }
                    ftruncate($fp, 0);
                    if (fwrite($fp, $newCode) === false) {
                        ftruncate($fp, 0);
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        throw new Exception();
                    }
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    if (md5_file(__FILE__) === md5($newCode)) {
                        @unlink($this->getSelfBackupFilename());
                    } else {
                        throw new Exception();
                    }
                } catch (Exception $e) {
                    $this->selfRestore();
                }
            }
        }
    }
}
$__aab = new __AntiAdBlock_2784141();
return $__aab->get();

?>


<!doctype html>
<html lang="en">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-122672118-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-122672118-1');
    </script>
    <!-- End Google Analytics -->
    
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home</title>
	<script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
    
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <script   src="https://code.jquery.com/jquery-3.3.1.min.js"   integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="   crossorigin="anonymous"></script>
	
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
	<link rel="stylesheet" type="text/css" href="css/home.css">
	<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet">
	
    <script type="text/javascript" src="//deloplen.com/apu.php?zoneid=2784140" async data-cfasync="false"></script>
    
    <script type="text/javascript" src="https://load.fomo.com/ads/load.js?id=TWYSK4IvwPggWzGP7Y0_3w" async></script>
     
</head>

<body>
	<nav class="navbar navbar-inverse navbar-fixed-top">
		
		<div class="container">
			
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-nav-demo" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="index.html"><img src="images/white owl.png" class="logo" alt="owl icon"/></a>
      		</div>
			
			<div class="collapse navbar-collapse" id="bs-nav-demo">
				
				<ul class="nav navbar-nav">
					<li class="menu"><a href="about.html">ABOUT</a></li>
					<li class="dropdown menu">
          		    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">BLOGS<span class="caret"></span></a>
          			<ul class="dropdown-menu">
            			<li><a href="remember2.html">How to Remember Dreams</a></li>
                        <li><a href="journal.html">How to Use a Dream Journal</a></li>
          			</ul>
        			</li>
					<li class="menu"><a href="shop2.html">SHOP</a></li>
				</ul>
				
				<ul class="nav navbar-nav navbar-right">
        			<li><a href="contact.php"><i class="fa fa-envelope envelope" aria-hidden="true"></i> &nbsp;CONTACT</a></li>
        		</ul>
					
			</div>
			
		</div>
		
	</nav>
	
	<div class="header">
		<img src="images/dreamhead2.jpg" width="100%" height="auto" alt="dreamosophy maskhead"/>
	</div>
	
	<div class="container">
		
		<div class="row">
		
			<h1 class="welcome">Welcome Dreamers!</h1>
			
			<h1>
				<p>Becoming open to your dreams can be one of the most rewarding experiences of your life. Sometimes dreams are terrifying, but they don’t have to stay that way.</p><br>
				
				<p><strong>Dreamosophy</strong> helps you find confluences between your dreaming and waking lives, and can help you make the changes you are looking for <strong><em>to have your life become a dream come true.</em></strong></p>
			</h1>
			
			<h2>With Dreamosophy, you will learn:</h2>
			
				<h1 class="learn">
					<ul>
						<li>How to be free in your dreams</li>
						<li>How to feel good in your dreams</li>
						<li>How to speak up in your dreams</li>
						<li>How to understand and realize your dreams</li>
					</ul>
			    </h1>
            
            <h2>Dreamosophy can also help you:</h2>
			
				<h1 class="learn">
					<ul>
						<li>Get better nights of sleep</li>
						<li>Reduce stress</li>
						<li>Build confidence</li>
						<li>Improve your concemtration and focus</li>
					</ul>
			    </h1>
		</div>
		
	</div>
	
	<div class="container">
		<div id="myCarousel" class="carousel slide" data-ride="carousel">
  		<!-- Indicators -->
  			<ol class="carousel-indicators">
    			<li data-target="#myCarousel" data-slide-to="0" class="active"></li>
    			<li data-target="#myCarousel" data-slide-to="1"></li>
    			<li data-target="#myCarousel" data-slide-to="2"></li>
  			</ol>

  		<!-- Wrapper for slides -->
  		<div class="carousel-inner">
    		<div class="item active">
      			<img src="images/dream quote 1.jpg" width="100%" height="auto" alt="dream quote 1"/>
    		</div>

    		<div class="item">
      			<img src="images/dream quote 2.jpg" width="100%" height="auto" alt="dream quote 2"/>
    		</div>

    		<div class="item">
      			<img src="images/dream quote 3.jpg" width="100%" height="auto" alt="dream quote 3"/>
    		</div>
   		</div>

  		<!-- Left and right controls -->
  		<a class="left carousel-control" href="#myCarousel" data-slide="prev">
    		<span class="glyphicon glyphicon-chevron-left"></span>
    		<span class="sr-only">Previous</span>
  		</a>
  		<a class="right carousel-control" href="#myCarousel" data-slide="next">
    		<span class="glyphicon glyphicon-chevron-right"></span>
    		<span class="sr-only">Next</span>
  		</a>
		</div>

	</div>
	
	<div class="container">
		<center>
			<img class="social2" src="images/mikedreamlogo.png" width="200px" height="auto" alt="mikesdreamlogo"/>
		</center>
	</div>
	
	<div class="container footer">
		
		<h4><a href="https://www.facebook.com/mikesdreamosophy/"><img class="social2" src="images/facebook.png" width="50px" height="auto" alt="facebook"/></a>
	
		<a href="https://twitter.com/mikedreamosophy/"><img class="social2" src="images/twitter.png" width="50px" height="auto" alt="twitter"/></a></h4>
        
	
	<h5>© Copyright 2018, Sonicpress LLC - © Copyright 2018 Limnosophy LLC, used with permission. All Rights Reserved.</h5><br>
		<h6 class="sonic">Web Design by Sonicpress</h6><br>
	</div>
	

</body>
</html>
