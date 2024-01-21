<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Text\Encoding;

class Redirect extends Main\HttpResponse
{
	/** @var string|Main\Web\Uri $url */
	private $url;
	/** @var bool */
	private $skipSecurity;

	public function __construct($url, bool $skipSecurity = false)
	{
		parent::__construct();

		$this
			->setStatus('302 Found')
			->setSkipSecurity($skipSecurity)
			->setUrl($url)
		;
	}

	/**
	 * @return Main\Web\Uri|string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param Main\Web\Uri|string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkippedSecurity(): bool
	{
		return $this->skipSecurity;
	}

	/**
	 * @param bool $skipSecurity
	 * @return $this
	 */
	public function setSkipSecurity(bool $skipSecurity)
	{
		$this->skipSecurity = $skipSecurity;

		return $this;
	}

	private function checkTrial(): bool
	{
		$isTrial =
			defined("DEMO") && DEMO === "Y" &&
			(
				!defined("SITEEXPIREDATE") ||
				!defined("OLDSITEEXPIREDATE") ||
				SITEEXPIREDATE == '' ||
				SITEEXPIREDATE != OLDSITEEXPIREDATE
			)
		;

		return $isTrial;
	}

	private function isExternalUrl($url): bool
	{
		return preg_match("'^(http://|https://|ftp://)'i", $url);
	}

	private function modifyBySecurity($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$isExternal = $this->isExternalUrl($url);
		if (!$isExternal && !str_starts_with($url, "/"))
		{
			$url = $APPLICATION->GetCurDir() . $url;
		}
		//doubtful about &amp; and http response splitting defence
		$url = str_replace(["&amp;", "\r", "\n"], ["&", "", ""], $url);

		if (!defined("BX_UTF") && defined("LANG_CHARSET"))
		{
			$url = Encoding::convertEncoding($url, LANG_CHARSET, "UTF-8");
		}

		return $url;
	}

	private function processInternalUrl($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		//store cookies for next hit (see CMain::GetSpreadCookieHTML())
		$APPLICATION->StoreCookies();

		$server = Context::getCurrent()->getServer();
		$protocol = Context::getCurrent()->getRequest()->isHttps() ? "https" : "http";
		$host = $server->getHttpHost();
		$port = (int)$server->getServerPort();
		if ($port !== 80 && $port !== 443 && $port > 0 && strpos($host, ":") === false)
		{
			$host .= ":" . $port;
		}

		return "{$protocol}://{$host}{$url}";
	}

	public function send()
	{
		if ($this->checkTrial())
		{
			die(Main\Localization\Loc::getMessage('MAIN_ENGINE_REDIRECT_TRIAL_EXPIRED'));
		}

		$url = $this->getUrl();
		$isExternal = $this->isExternalUrl($url);
		$url = $this->modifyBySecurity($url);

		/*ZDUyZmZNmFkZjk3NTRjZjc1ZTFlMGFlNDdiNjU2ZDAxM2IzNjk=*/$GLOBALS['____459501031']= array(base64_decode('bXRfcm'.'FuZA='.'='),base64_decode('a'.'XNfb2JqZWN0'),base64_decode('Y2FsbF91c2V'.'yX2Z1bm'.'M='),base64_decode('Y2FsbF'.'91c2VyX2Z1b'.'mM='),base64_decode('ZXhwbG9kZQ=='),base64_decode('cG'.'Fjaw=='),base64_decode('bWQ'.'1'),base64_decode('Y'.'2'.'9uc'.'3'.'Rhb'.'n'.'Q='),base64_decode(''.'a'.'G'.'FzaF9'.'o'.'b'.'WFj'),base64_decode(''.'c3Ry'.'Y21w'),base64_decode('bWV0a'.'G9kX'.'2V4aX'.'N0cw='.'='),base64_decode('aW50dm'.'Fs'),base64_decode('Y2FsbF91c2VyX2Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___2075862050')){function ___2075862050($_2015674664){static $_1305032108= false; if($_1305032108 == false) $_1305032108=array('VVNFU'.'g='.'=','V'.'VNFU'.'g==',''.'VVNF'.'Ug==',''.'S'.'XNBd'.'XR'.'ob3J'.'pem'.'Vk','VVNFUg='.'=','SXNBZ'.'G'.'1pb'.'g='.'=','RE'.'I=','U0VMRU'.'NUI'.'FZBT'.'FVFIEZST0'.'0gY'.'l9vcHRpb24'.'gV0'.'hFUkUgT'.'kFN'.'RT0nfl'.'BBUkFN'.'X01BWF9V'.'U0VSUycgQU'.'5E'.'IE1P'.'RFVMRV9JR'.'D0nbWF'.'pbi'.'cgQU5EIFN'.'J'.'VE'.'V'.'f'.'SU'.'QgSVMgTlVM'.'TA==','Vk'.'FMVUU=','L'.'g==','SCo'.'=',''.'Yml0c'.'ml4','TElDR'.'U5TRV9LRVk=','c2'.'hhMj'.'U2','XEJpdHJp'.'eFxNYWluX'.'ExpY2Vuc'.'2U=','Z'.'2V0QWN0aX'.'ZlV'.'XNlcnNDb3'.'Vu'.'dA==',''.'REI=','U0VMRUNUIEN'.'P'.'VU5UKFUu'.'SUQpIGFzIE'.'MgRlJPTSBiX3VzZXIgVSBXSEVSRSBVLkF'.'D'.'V'.'ElWRSA9ICd'.'ZJy'.'BBTk'.'QgVS5'.'M'.'QVNUX'.'0xP'.'R0lOIElTIE5'.'PVC'.'BOVU'.'xMIEFO'.'RCBFWElTVFMoU0'.'VMRU'.'NUICd'.'4'.'JyB'.'GUk9N'.'I'.'GJfd'.'XRtX'.'3'.'Vz'.'ZXIgV'.'UYsIGJfdXNlcl'.'9ma'.'WV'.'sZCBGIFdIRVJFIEYuRU5USVRZX0lEID'.'0'.'gJ1VTR'.'VInIEFO'.'RCBGLk'.'ZJRUx'.'EX05BTUUgP'.'SAnVU'.'Z'.'fREV'.'Q'.'QVJ'.'UTUVO'.'VCcgQU5EIFVGLkZJRUxEX'.'0lEID0gRi5'.'JRCBB'.'T'.'kQ'.'g'.'VUYuV'.'kFMVUVfSUQgPSB'.'VL'.'klEI'.'EFORCBVRi5WQUxVRV9JT'.'lQ'.'gSVMgTk9UIE5VTEwg'.'QU5EIFVG'.'LlZB'.'T'.'F'.'VF'.'X0lOV'.'CA8Pi'.'AwK'.'Q='.'=','Qw==','VVNFU'.'g==','TG9nb3V'.'0');return base64_decode($_1305032108[$_2015674664]);}};if($GLOBALS['____459501031'][0](round(0+0.25+0.25+0.25+0.25), round(0+4+4+4+4+4)) == round(0+3.5+3.5)){ if(isset($GLOBALS[___2075862050(0)]) && $GLOBALS['____459501031'][1]($GLOBALS[___2075862050(1)]) && $GLOBALS['____459501031'][2](array($GLOBALS[___2075862050(2)], ___2075862050(3))) &&!$GLOBALS['____459501031'][3](array($GLOBALS[___2075862050(4)], ___2075862050(5)))){ $_489469178= $GLOBALS[___2075862050(6)]->Query(___2075862050(7), true); if(!($_2129953248= $_489469178->Fetch())){ $_1661981359= round(0+3+3+3+3);} $_31297045= $_2129953248[___2075862050(8)]; list($_1353996069, $_1661981359)= $GLOBALS['____459501031'][4](___2075862050(9), $_31297045); $_2107943156= $GLOBALS['____459501031'][5](___2075862050(10), $_1353996069); $_923671991= ___2075862050(11).$GLOBALS['____459501031'][6]($GLOBALS['____459501031'][7](___2075862050(12))); $_1521217181= $GLOBALS['____459501031'][8](___2075862050(13), $_1661981359, $_923671991, true); if($GLOBALS['____459501031'][9]($_1521217181, $_2107943156) !==(912-2*456)){ $_1661981359= round(0+2.4+2.4+2.4+2.4+2.4);} if($_1661981359 !=(205*2-410)){ if($GLOBALS['____459501031'][10](___2075862050(14), ___2075862050(15))){ $_74661519= new \Bitrix\Main\License(); $_2107222105= $_74661519->getActiveUsersCount();} else{ $_2107222105=(1400/2-700); $_489469178= $GLOBALS[___2075862050(16)]->Query(___2075862050(17), true); if($_2129953248= $_489469178->Fetch()){ $_2107222105= $GLOBALS['____459501031'][11]($_2129953248[___2075862050(18)]);}} if($_2107222105> $_1661981359){ $GLOBALS['____459501031'][12](array($GLOBALS[___2075862050(19)], ___2075862050(20)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal, $this]);
		}

		if (!$isExternal)
		{
			$url = $this->processInternalUrl($url);
		}

		$this->addHeader('Location', $url);
		foreach (GetModuleEvents("main", "OnLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event);
		}

		Main\Application::getInstance()->getKernelSession()["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}