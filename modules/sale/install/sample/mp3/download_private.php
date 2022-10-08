<?
function initialize_params($url)
{
	if (mb_strpos($url, "?") > 0)
	{
		$par = mb_substr($url, mb_strpos($url, "?") + 1, mb_strlen($url));
		$arr = explode("#",$par);
		$par = $arr[0];
		$arr1 = explode("&",$par);
		foreach ($arr1 as $pair)
		{
			$arr2 = explode("=",$pair);
			global ${$arr2[0]};
			${$arr2[0]} = $arr2[1];
		}
	}
}

$arImageExts = array("gif", "jpg", "jpeg", "png", "bmp");

$DIR = dirname($_SERVER["REQUEST_URI"]);

$sapi = (mb_stristr(php_sapi_name(), "cgi") !== false? "cgi":"");
set_time_limit(0);
$arr1 = explode("?", $_SERVER["REQUEST_URI"]); 
$arr2 = explode("#", $arr1[0]);
$URI = $arr2[0];
$file = mb_substr($URI, mb_strlen($DIR) + 1);
$file = str_replace("..", "", $file);
$filename = urldecode($_SERVER["DOCUMENT_ROOT"].$DIR."/files/".$file);

$bRealyImage = False;
$arFilePathInfo = pathinfo($filename);
if (in_array($arFilePathInfo["extension"], $arImageExts))
	$bRealyImage = True;

if(file_exists($filename))
{
	include_once(__DIR__."/init_vars.php");

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	if (CModule::IncludeModule("sale"))
	{
		$bCanAccess = False;
		if ($USER->IsAuthorized())
		{
			$FILE_PERM = $APPLICATION->GetFileAccessPermission($DIR."/files/".$file, $USER->GetUserGroupArray());
			$FILE_PERM = (($FILE_PERM <> '') ? $FILE_PERM : "D");
			if ($FILE_PERM >= "R")
				if (CSaleAuxiliary::CheckAccess($USER->GetID(), $mp3AuxiliaryPrefix.$file, $mp3AccessTimeLength, $mp3AccessTimeType))
					$bCanAccess = True;
		}

		if (!$bCanAccess)
		{
			LocalRedirect($mp3Url2Folder."auth.php?fname=".urlencode($file)."&DIR=".urlencode($DIR));
		}
		else
		{
			$filesize = filesize($filename);
			$f = fopen($filename, "rb");
			$cur_pos = 0;
			$size = $filesize-1;

			if ($bRealyImage)
			{
				$imageParams = CFile::GetImageSize($filename);
			}

			if ($_SERVER["REQUEST_METHOD"]=="HEAD")
			{
				if($sapi == "cgi") 
					header("Status: 200 OK"); 
				else 
					header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
				header("Accept-Ranges: bytes");
				header("Content-Length: ".$filesize);
				if ($bRealyImage)
					header("Content-Type: ".$imageParams["mime"]."; name=\"".$file."\"");
				else
					header("Content-Type: application/force-download; name=\"".$file."\"");
				header("Last-Modified: ".date("r",filemtime($filename)));
			}
			else
			{
				$p = mb_strpos($_SERVER["HTTP_RANGE"], "=");
				if(intval($p)>0)
				{
					$bytes = mb_substr($_SERVER["HTTP_RANGE"], $p + 1);
					$p = mb_strpos($bytes, "-");
					if($p!==false)
					{
						$cur_pos = intval(mb_substr($bytes, 0, $p));
						$size = intval(mb_substr($bytes, $p + 1));
						if($size<=0)
							$size = $filesize - 1;
						if($cur_pos>$size)
						{
							$cur_pos = 0;
							$size = $filesize - 1;
						}
						fseek($f, $cur_pos);
					}
				}

				if(intval($cur_pos)>0 && $_SERVER["SERVER_PROTOCOL"] == "HTTP/1.1")
				{
					if($sapi=="cgi") 
						header("Status: 206 Partial Content"); 
					else 
						header("HTTP/1.1 206 Partial Content");
				}
				else
				{
					session_cache_limiter('');
					session_start();
					if (CModule::IncludeModule("statistic"))
					{
						initialize_params($_SERVER["REQUEST_URI"]);
						if ($event1 == '' && $event2 == '')
						{
							$event1 = "download";
							$event2 = "private";
							$event3 = $file;
						}
						$e = $event1."/".$event2."/".$event3;
						if (!in_array($e, $_SESSION["DOWNLOAD_EVENTS"]))
						{
							$w = CStatEvent::GetByEvents($event1, $event2);
							$wr = $w->Fetch();
							$z = CStatEvent::GetEventsByGuest($_SESSION["SESS_GUEST_ID"], $wr["EVENT_ID"], $event3, 21600);
							if (!($zr=$z->Fetch()))
							{
								CStatistic::Set_Event($event1, $event2, $event3);
								$_SESSION["DOWNLOAD_EVENTS"][] = $e;
							}
						}
					}
					ob_end_clean();
					session_write_close();
					if($sapi=="cgi") 
						header("Status: 200 OK"); 
					else 
						header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
				}

				if ($bRealyImage)
					header("Content-Type: ".$imageParams["mime"]."; name=\"".$file."\"");
				else
					header("Content-Type: application/force-download; name=\"".$file."\"");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".($size-$cur_pos+1));
				header("Accept-Ranges: bytes");
				header("Content-Range: bytes ".$cur_pos."-".$size."/".$filesize);
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
				header("Expires: 0"); 
				header("Pragma: public"); 

				$str = "";
				while($cur_pos<=$size)
				{
					$bufsize = 32768;
					if($bufsize+$cur_pos>$size)
						$bufsize = $size - $cur_pos + 1;
					$cur_pos += $bufsize;
					$p = fread($f, $bufsize);
					echo $p;
					flush();
				}
				fclose ($f);
				die();
			}
		}
	}
	else
	{
		include($_SERVER["DOCUMENT_ROOT"]."/404.php");
	}
}
else
{
	include($_SERVER["DOCUMENT_ROOT"]."/404.php");
}
?>