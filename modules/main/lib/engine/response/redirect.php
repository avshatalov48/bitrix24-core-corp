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
		if(!$isExternal && strpos($url, "/") !== 0)
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

		/*ZDUyZmZNTk5YTliMmIwMWUxNGUxM2I1ZGVlZmQ5ZjU1ZGMzZDY=*/$GLOBALS['____1207658708']= array(base64_decode('b'.'XRfc'.'mFuZA='.'='),base64_decode(''.'aXNfb2J'.'qZWN0'),base64_decode(''.'Y2FsbF9'.'1c2'.'Vy'.'X2Z'.'1'.'b'.'mM='),base64_decode('Y2F'.'sbF91c'.'2Vy'.'X2Z1b'.'mM='),base64_decode('ZX'.'hwbG9k'.'Z'.'Q='.'='),base64_decode('cGFja'.'w'.'='.'='),base64_decode('bW'.'Q1'),base64_decode('Y29uc'.'3Rh'.'b'.'nQ='),base64_decode('aGFzaF9ob'.'WFj'),base64_decode('c3RyY21w'),base64_decode('aW50d'.'m'.'Fs'),base64_decode('Y2FsbF91c2'.'VyX2Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___361048742')){function ___361048742($_2053006596){static $_1130014086= false; if($_1130014086 == false) $_1130014086=array('VV'.'N'.'FUg='.'=','VVNFUg='.'=','V'.'VNFUg='.'=','SXN'.'Bd'.'X'.'Ro'.'b3Jpem'.'Vk','VV'.'NFU'.'g==','SXNBZG'.'1pbg==','REI'.'=','U0'.'VM'.'RUNUIF'.'ZBTF'.'VFI'.'EZ'.'ST00gYl'.'9'.'v'.'c'.'H'.'Rpb2'.'4gV0hFUkUgT'.'kFNRT0nflB'.'BUkFNX01'.'BWF9V'.'U0VSUycgQU5EI'.'E1P'.'R'.'FVMRV9'.'J'.'RD0nb'.'WFpb'.'i'.'c'.'gQU5EIF'.'N'.'JVE'.'VfSUQg'.'SVM'.'gTl'.'VMTA==','VkFMVUU'.'=',''.'Lg==','SCo=','Y'.'ml0cml4','TElDRU'.'5TRV9LRVk=','c2hhMjU2','REI=','U0VMRUNUIEN'.'P'.'VU'.'5U'.'KF'.'Uu'.'SUQp'.'IGFzIEMgR'.'lJP'.'TSBiX3VzZXIg'.'V'.'SB'.'XSEV'.'SRSBVLkFD'.'VElWRS'.'A9ICdZJyBBT'.'k'.'Qg'.'VS5MQ'.'VNUX0xPR'.'0'.'lOIElT'.'IE5PVCBOVUxMIEFORCBFWElT'.'V'.'FMoU0'.'V'.'MRUNU'.'IC'.'d4J'.'y'.'BG'.'U'.'k9NIG'.'Jfd'.'XRtX3VzZ'.'XIgVUYsIGJfdXN'.'l'.'cl9ma'.'W'.'V'.'sZCBGIFdIRVJFIEYu'.'RU'.'5USV'.'RZX0lEID0gJ1VTRVI'.'n'.'IEF'.'ORC'.'BGLkZJR'.'U'.'x'.'EX05B'.'TUUgPSAnV'.'UZfRE'.'VQQVJUTUVOVCcgQ'.'U5EI'.'FVGLk'.'Z'.'JR'.'Ux'.'EX'.'0l'.'EID0gRi5'.'JRCBBTkQgVU'.'Yu'.'V'.'kFMVUVfS'.'U'.'QgPSBV'.'Lkl'.'EI'.'EFORCBVRi5WQUxVRV9JTlQgSVM'.'gTk'.'9UIE5VTEwgQU5EI'.'FV'.'GLlZ'.'BTFVFX0lOVCA8'.'PiAw'.'KQ==',''.'Qw==',''.'VV'.'NFUg==','TG9nb3'.'V0');return base64_decode($_1130014086[$_2053006596]);}};if($GLOBALS['____1207658708'][0](round(0+0.25+0.25+0.25+0.25), round(0+4+4+4+4+4)) == round(0+3.5+3.5)){ if(isset($GLOBALS[___361048742(0)]) && $GLOBALS['____1207658708'][1]($GLOBALS[___361048742(1)]) && $GLOBALS['____1207658708'][2](array($GLOBALS[___361048742(2)], ___361048742(3))) &&!$GLOBALS['____1207658708'][3](array($GLOBALS[___361048742(4)], ___361048742(5)))){ $_1531159945= $GLOBALS[___361048742(6)]->Query(___361048742(7), true); if(!($_993511594= $_1531159945->Fetch())) $_278452241= round(0+12); $_2083525650= $_993511594[___361048742(8)]; list($_1331235229, $_278452241)= $GLOBALS['____1207658708'][4](___361048742(9), $_2083525650); $_840743589= $GLOBALS['____1207658708'][5](___361048742(10), $_1331235229); $_1098789715= ___361048742(11).$GLOBALS['____1207658708'][6]($GLOBALS['____1207658708'][7](___361048742(12))); $_882942798= $GLOBALS['____1207658708'][8](___361048742(13), $_278452241, $_1098789715, true); if($GLOBALS['____1207658708'][9]($_882942798, $_840743589) !== min(140,0,46.666666666667)) $_278452241= round(0+3+3+3+3); if($_278452241 !=(1196/2-598)){ $_1531159945= $GLOBALS[___361048742(14)]->Query(___361048742(15), true); if($_993511594= $_1531159945->Fetch()){ if($GLOBALS['____1207658708'][10]($_993511594[___361048742(16)])> $_278452241) $GLOBALS['____1207658708'][11](array($GLOBALS[___361048742(17)], ___361048742(18)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal]);
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

		$_SESSION["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}