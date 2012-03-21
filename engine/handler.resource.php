<?php/* WWW - PHP micro-frameworkIndex gateway resource handlerResource handler is loaded for various resource files. This data is cached, compressed and in some cases minified before being sent to the client. The file extensions sent to Index gateway in this handler are *.css, *.js, *.txt, *.csv, *.xml, *.html and *.htm. Resource handler also allows to unify CSS and JS files as well as support overwriting the base functionality in case developer uses multiple installations and wants to have tweaks per-install.Author and support: Kristo Vaher - kristo@waher.net*/// Currently known location of the file$resource=str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$_SERVER['DOCUMENT_ROOT'].$_SERVER['REDIRECT_URL']);// Getting information about current resource$fileInfo=pathinfo($resource);// Assigning file information$file=$fileInfo['basename'];// File extension in requested file$extension=$fileInfo['extension'];// Solving the folder that client is loading resource from$folder=$fileInfo['dirname'].DIRECTORY_SEPARATOR;// System returns proper content type based on file extensionswitch($extension){	case 'css':		header('Content-Type: text/css;charset=utf-8;');		break;	case 'js':		header('Content-Type: application/javascript;charset=utf-8;');		break;	case 'txt':		header('Content-Type: text/plain;charset=utf-8;');		break;	case 'csv':		header('Content-Type: text/csv;charset=utf-8;');		break;	case 'xml':		header('Content-Type: text/xml;charset=utf-8;');		break;	case 'html':		header('Content-Type: text/html;charset=utf-8;');		break;	case 'htm':		header('Content-Type: text/html;charset=utf-8;');		break;	case 'ini':		header('Content-Type: text/plain;charset=utf-8;');		break;	case 'rss':		header('Content-Type: application/rss+xml;charset=utf-8;');		break;	case 'vcard':		header('Content-Type: text/vcard;charset=utf-8;');		break;		}// This stores currently used compression mode$compression='';// If output compression is turned on then the content is compressedif((isset($config['output-compression']) || $config['output-compression']!=false) && extension_loaded('Zlib')){	// Different compression options can be used	switch($this->state->data['output-compression']){		case 'deflate':			$compression='deflate';			break;		case 'gzip':			$compression='gzip';			break;	}	} else if(extension_loaded('Zlib')){	// Client accepted methods are checked when compression is not set in configuration itself	if(in_array('deflate',explode(',',$_SERVER['HTTP_ACCEPT_ENCODING']))){		$compression='deflate';	} else if(in_array('gzip',explode(',',$_SERVER['HTTP_ACCEPT_ENCODING']))){		$compression='gzip';	}	}// This tells proxies to store both compressed and uncompressed versionif($compression!=''){	header('Vary: Accept-Encoding');}// Resulting data (like script or stylesheet content) is stored to this variable$data='';// If cache is not defined in configuration file then pre-set is usedif(!isset($config['resource-cache-timeout'])){	$config['resource-cache-timeout']=31536000; // A year}// If robots setting is not defined in cache, then it is turned offif(!isset($config['robots'])){	header('X-Robots-Tag: noindex,nocache,nofollow,noarchive,noimageindex,nosnippet', true);} else {	header('X-Robots-Tag: '.$config['robots'], true);}// Pragma header removed should the server happen to set it automaticallyheader_remove('Pragma');// Dynamic resource loading can be turned off in configurationif(!isset($config['dynamic-resource-loading']) || $config['dynamic-resource-loading']==true){	// Comma separated filenames will mean that the result will be unified	$parameters=array_unique(explode('&',$file));} else {	// If dynamic resource loading was turned off, then the entire 'first parameter' is considered to be the full string for parsing purposes	$parameters=array();	$parameters[0]=$parameters;}// Storing last modified time here$lastModified=false;// If minification is used for CSS and JS$minify=false;// Web root is the subfolder on public site$webRoot=str_replace('index.php','',$_SERVER['PHP_SELF']);$overridesFolder=false;if(preg_match('/^'.str_replace('/','\/',$webRoot).'resources/',$_SERVER['REDIRECT_URL'])){	// Solving possible overrides folder	$overridesFolder=str_replace($webRoot.'resources'.DIRECTORY_SEPARATOR,$webRoot.'overrides'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR,$folder);}	// Newest last-modified file is considered for the last modified timeforeach($parameters as $key=>$file){	// Cache can be turned off and minification can be turned on with parameters.	// Full stop including parameters are also entirely ignored for parameter considerations	if($file!='nocache' && $file!='minify' && strpos($file,'.')!==false){			// Making sure that parent folders are not requested		$parameters[$key]=str_replace('..','',$file);			// Overrides can be used if file with the same name is stored in same folder under /overrides/ folder		if($overridesFolder && file_exists($overridesFolder.$file)){					// File was found and the filename will be replaced by file location for later processing			$parameters[$key]=$overridesFolder.$file;			// Last modified time of the file stored in overrides folder			$thisLastModified=filemtime($overridesFolder.$file);			// Only the newest last modified time will be used for output headers			if($lastModified==false || $lastModified<$thisLastModified){				$lastModified=$thisLastModified;			}				} else if(file_exists($folder.$file)){					// File was found and the filename will be replaced by file location for later processing			$parameters[$key]=$folder.$file;			// Last modified time of the file stored in overrides folder			$thisLastModified=filemtime($folder.$file);			// Only the newest last modified time will be used for output headers			if($lastModified==false || $lastModified<$thisLastModified){				$lastModified=$thisLastModified;			}				} else {					// Returning 404 data				require(__DIR__.DIRECTORY_SEPARATOR.'handler.404.php');			die();					}	} else if($file=='minify'){			// This will use minify for CSS and JS files		$minify=true;				// Unsetting the parameter as it will not be used later		unset($parameters[$key]);			} else if($file=='nocache'){			// Unsetting the parameter as it will not be used later		unset($parameters[$key]);			} else {			// Returning 404 data			require(__DIR__.DIRECTORY_SEPARATOR.'handler.404.php');		die();			}	}// Solving cache folders and directory$cacheFilename=md5($lastModified.$_SERVER['REDIRECT_URL']).(($compression!='')?'_'.$compression:'').'.tmp';$cacheDirectory=__ROOT__.DIRECTORY_SEPARATOR.'filesystem'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.substr($cacheFilename,0,2).DIRECTORY_SEPARATOR;// If cache file exists then cache modified is considered that timeif(file_exists($cacheDirectory.$cacheFilename)){	$lastModified=filemtime($cacheDirectory.$cacheFilename);} else {	// Otherwise it is server request time	$lastModified=$_SERVER['REQUEST_TIME'];}// If resource cannot be found from cache, it is generatedif(in_array('nocache',$parameters) || ($lastModified==$_SERVER['REQUEST_TIME'] || $lastModified<($_SERVER['REQUEST_TIME']-$config['resource-cache-timeout']))){	// Resource data is stored as a string	$data='';		// All requested files are appended	foreach($parameters as $file){		// Loading data into string.		$data.=file_get_contents($file)."\n";				}					// Minification of data for smaller filesize and less clutter.	if($minify){				// Including minification class		require(__ROOT__.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'class.www-minifier.php');				// Minification is based on the type of class		switch($extension){					case 'js':				$data=WWW_Minifier::minifyJS($data);				break;			case 'css':				$data=WWW_Minifier::minifyCSS($data);				break;			case 'htm':				$data=WWW_Minifier::minifyHTML($data);				break;			case 'html':				$data=WWW_Minifier::minifyHTML($data);				break;			case 'xml':				$data=WWW_Minifier::minifyXML($data);				break;			case 'rss':				$data=WWW_Minifier::minifyXML($data);				break;						}	}	// Data is compressed based on current compression settings	switch($compression){			case 'deflate':			$data=gzdeflate($data,9);			break;		case 'gzip':			$data=gzencode($data,9);			break;				}		// Resource cache is cached in subdirectories, if directory does not exist then it is created	if(!is_dir($cacheDirectory)){		if(!mkdir($cacheDirectory,0777)){			trigger_error('Cannot create cache folder',E_USER_ERROR);		}	}		// Data is written to cache file	if(!file_put_contents($cacheDirectory.$cacheFilename,$data)){		trigger_error('Cannot create resource cache',E_USER_ERROR);	}		// Unsetting the variable due to memory reasons	unset($data);} else {	// Logger is notified that cache was used	if(isset($logger)){		$logger->cacheUsed=true;	}}// If cache is used, then proper headers will be sentif(!in_array('nocache',$parameters)){		// Client is told to cache these results for set duration	header('Cache-Control: public,max-age='.$config['resource-cache-timeout'].',must-revalidate');	header('Expires: '.gmdate('D, d M Y H:i:s',($lastModified+$config['resource-cache-timeout'])).' GMT');	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');} else {	// Client is told to cache these results for set duration	header('Cache-Control: public,max-age=0,must-revalidate');	header('Expires: '.gmdate('D, d M Y H:i:s',$_SERVER['REQUEST_TIME']).' GMT');	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');	}// Proper compression headerif($compression!=''){	header('Content-Encoding: '.$compression);}// Content length is defined that can speed up website requests, letting client to determine file sizeheader('Content-Length: '.filesize($cacheDirectory.$cacheFilename));  // Returning the file contents to clientreadfile($cacheDirectory.$cacheFilename);// File is deleted if cache was requested to be offif(in_array('nocache',$parameters)){	unlink($cacheDirectory.$cacheFilename);}?>