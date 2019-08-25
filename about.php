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
    <title>What is Dreamosophy?</title>
	<script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
    
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <script   src="https://code.jquery.com/jquery-3.3.1.min.js"   integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="   crossorigin="anonymous"></script>
	
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
	<link rel="stylesheet" type="text/css" href="css/about.css">
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
				<a class="navbar-brand" href="index.html"><img src="images/white owl.png" class="logo" alt=""/></a>
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

	
	<div class="container header">
		<center>
			<img src="images/dcatcherhead.png" width="100%" height="auto" alt="dream catchers"/>
		</center>
	</div><br><br><br>
	
	<div class="container">
            
            <h2>What is Dreamosophy?</h2>
        
			<h1>
				<p><strong>Dreamosophy</strong> – the wisdom of dreams, grows from aspects of wisdom, wholehearted engagement, and metaphor. Dreamosophy is more than just a concept. It’s a solid exploration of one of the most precious and revitalizing dimensions of the human experience – dreaming! It is the confluence of these underlying aspects with opportunities, initiations, and inquiry. Put simply, <strong>Dreamosophy</strong> is a unique way of approaching your dreams that can help you have a better, more effective dream life.</p><br>
				
				<p>You can learn to experience your dreams and yourself in your dreams without any constraints or restrictions at all. Just total freedom of choice and wonderment in the dream world.  When you do this, interesting things start to happen. This is when people can start to get profound insights and spiritual experiences from their dreams.</p><br>
				
				<p>For example, in a dream, Elias Howe saw the breakthrough insight for how to perfect the lock stitch sewing machine. He dreamt of tribespeople carrying spears that had holes in the spearheads. It was a breakthrough for him on how to make a sewing machine needle with the thread on the leading point of the needle, rather than the back end. Francis Crick dreamt of angels walking up Jacob’s ladder, and the spiral staircase gave him insight into how the DNA molecule was shaped. This insight opened his thinking to consider Rosalind Franklin’s suggestion of the double helix. The worldwide children’s book, <em>Goodnight Moon</em>, came from a dream Margaret Wise Brown had as a child. The waking world has been shaped by dreams in countless ways, told and untold. And <strong>Dreamosophy</strong> came from a series of dream insights and explorations by the founders.</p><br>
				
				<p><strong>Dreamosophy</strong> can be thought of as the space in which the wisdom and knowledge of dreams flow together, through shared metaphor and personal discovery. The activities and exercises found in the practicing <strong>Dreamosophy</strong> are geared towards promoting wonder, creativity, and curiosity as you allow yourself to engage with your dreams in a deeper, fuller way than ever before. As you engage with Dreamosophy, you can begin having dreams that work <em>for you</em> rather than <em>against you</em>. Your dreams can become like a cherished friend whom you get to see or experience on a nightly basis. As you learn to experience dreaming in new ways, your dreams can become far more than just a distraction or jumble of “day residue.”</p><br>
				
			</h1>
			
			<h2>What can Dreamosophy do for me?</h2>
			
			<h1>
				<p><strong>Dreamosophy</strong> is a highly individualized approach to dreaming, because everyone’s dream life is different! This is <em>your dream life</em>. Your goals for your dreams may be different than someone else’s. There is no one ‘right’ way or ‘right’ outcome – what is right for you is yours, and may be different from what is right for another. With that in mind, there are three foundational goals of the Dreamosophy program that apply to most people. These are:</p><br>
				
				<p><strong>GOAL 1:</strong></p>
				
				<p><strong>Have a deeper, more profound, and more meaningful relationship with your dream life.</strong></p><br>
				
				<p>This might mean something different for each of you, because dreams are personal.  Your dream life is personal. This is ultimately about your experience. A deeper, more profound relationship with your own dream life might look different from one person to another.  That’s the way it’s designed! But at a very basic level, <strong>Goal 1</strong> is for Dreamosophy to help you to:</p>
				
				<p>
					<ul>
						<li>Have dreams that work for you, not against you</li>
						<li>Experience more enjoyment from your dream life</li>
						<li>Get a better night’s sleep</li>
					</ul>
				</p><br><br>
			
				<p><strong>GOAL 2:</strong></p>
				
				<p><strong>Begin to transform your relationship with your dreams in new and beneficial ways.</strong></p><br>
			
				<p>Through <strong>Dreamosophy</strong> you can begin to make connections between your waking life and your dream life and consider how your waking life and dream life affect each other.</p><br><br>
			
				<p><strong>GOAL 3:</strong></p>
				
				<p><strong>Learn about your Dream Opportunities</strong> beginning with <em>How to Be Free in Your Dreams</em>, as presented in the book,<strong><a href="https://www.amazon.com/Wisdom-Dreaming-Guide-Effective-Dream/dp/0692912606/ref=sr_1_1?ie=UTF8&qid=1528659211&sr=8-1&keywords=wisdom+of+dreaming"> Wisdom of Dreaming: A Guide to an Effective Dream Life</a></strong>. Through <strong>Dreamosophy</strong>, you will be able to experience transformation in your dream life. You can begin to identify the dreams that you have for yourself and your life, consider how those dreams reflect who you are, and make choices that might help you realize your dreams in your waking life as well.</p><br>
			
				<p>The real effect of <strong>Dreamosophy</strong> happens with <strong>YOU, IN YOUR DREAMS</strong>, when you do the exercises and activities in the book, <strong><a href="https://www.amazon.com/Wisdom-Dreaming-Guide-Effective-Dream/dp/0692912606/ref=sr_1_1?ie=UTF8&qid=1528659211&sr=8-1&keywords=wisdom+of+dreaming">Wisdom of Dreaming</a></strong>.</p><br>
			
				<p><strong>DREAM ON!</strong></p>
			</h1>
		
	        <div>
	            <center>
					<img class="social2" src="images/mikedreamlogo.png" width="200px" height="auto" alt="mikesdreamlogo"/>
	            </center>
	        </div>
		
	</div>
	
	
	<div class="container footer">
		
		<h4><a href="https://www.facebook.com/mikesdreamosophy/"><img class="social2" src="images/facebook.png" width="50px" height="auto" alt="facebook"/></a>
	
		<a href="https://twitter.com/mikedreamosophy/"><img class="social2" src="images/twitter.png" width="50px" height="auto" alt="twitter"/></a>
        </h4>
	
	<h5>© Copyright 2018, Sonicpress LLC - © Copyright 2018 Limnosophy LLC, used with permission. All Rights Reserved.</h5><br>
		<h6 class="sonic">Web Design by Sonicpress</h6><br>
	</div>
	
	
	
	
</body>
</html>
