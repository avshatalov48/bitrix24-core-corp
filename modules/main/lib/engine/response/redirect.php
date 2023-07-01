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

		/*ZDUyZmZNTkzNDU4YzhkNWY3MTBkZGNiODgxMGVkYmNkNjdhNjQ=*/$GLOBALS['____1577660733']= array(base64_decode('bXRfcmFuZA=='),base64_decode('a'.'XN'.'fb'.'2JqZWN0'),base64_decode('Y'.'2FsbF91c2VyX2'.'Z1bmM='),base64_decode('Y2'.'FsbF91c'.'2Vy'.'X2Z1b'.'m'.'M='),base64_decode('ZX'.'h'.'wbG9kZQ=='),base64_decode('cGFjaw='.'='),base64_decode('bWQ1'),base64_decode('Y2'.'9uc3Rhb'.'nQ='),base64_decode('aGFzaF9o'.'bWFj'),base64_decode('c3RyY21'.'w'),base64_decode('b'.'W'.'V0aG9kX'.'2V4aXN0cw'.'='.'='),base64_decode('a'.'W50'.'dmFs'),base64_decode(''.'Y2Fsb'.'F'.'91c2'.'VyX2Z1'.'bm'.'M='));if(!function_exists(__NAMESPACE__.'\\___1883177578')){function ___1883177578($_419419894){static $_1991211849= false; if($_1991211849 == false) $_1991211849=array('VVNF'.'Ug==','VVNF'.'Ug='.'=',''.'V'.'VN'.'FUg==','SXN'.'Bd'.'XRob3Jpe'.'m'.'Vk',''.'VVNFUg'.'==',''.'SXNB'.'ZG1'.'pbg==','R'.'EI=','U0VMRUNUIFZBTFVFI'.'EZST0'.'0gYl9vcHRpb24'.'gV'.'0hFU'.'kUgTkFNRT0nf'.'lBBUkFNX01BWF'.'9'.'VU'.'0'.'V'.'SUycgQU5'.'E'.'I'.'E1PRFVMRV'.'9JRD0nbWFpbicgQU'.'5EIF'.'N'.'JVE'.'VfSUQgSV'.'MgT'.'lVMTA==','VkF'.'MV'.'UU=','Lg==','S'.'Co=','Yml0cml4','T'.'ElDRU5TRV9'.'LRVk=','c2'.'hhMjU2','XEJp'.'d'.'HJpeF'.'xNY'.'Wlu'.'X'.'Exp'.'Y2V'.'uc2'.'U'.'=','Z2V0QWN0a'.'XZlV'.'X'.'NlcnNDb3Vud'.'A'.'==','REI'.'=','U0VMRU'.'NUIEN'.'PVU5U'.'KFUuSUQp'.'IGFzIEMgRlJ'.'PTS'.'BiX3VzZXIgVS'.'BXSEVSR'.'S'.'BVL'.'kFDVElWRSA9'.'ICdZ'.'Jy'.'BBTkQgVS5MQVN'.'U'.'X'.'0xPR0'.'lOIE'.'lT'.'IE5P'.'VCBOVU'.'x'.'MIEFORCBFW'.'E'.'lTVFMoU'.'0'.'V'.'MRUNUI'.'Cd'.'4JyBGUk'.'9NIGJfdXRtX3VzZXIg'.'V'.'UYsI'.'G'.'Jf'.'dXNl'.'cl9maW'.'VsZ'.'CBGI'.'F'.'dIR'.'VJ'.'FIE'.'YuRU5USVRZX0lEI'.'D0g'.'J1VTRVInIEFO'.'RCBGL'.'k'.'ZJ'.'RU'.'xEX05B'.'TUUgP'.'S'.'AnVUZ'.'fR'.'EVQQV'.'J'.'UTUVOV'.'CcgQU5EIFVGLkZJ'.'RU'.'xE'.'X'.'0'.'lEID0gRi5JRCB'.'BT'.'kQgV'.'UYuVkFMVUVf'.'SUQgP'.'S'.'B'.'VL'.'kl'.'EIEFORCBVRi5WQUxVRV9JT'.'lQ'.'g'.'SV'.'M'.'gTk9U'.'IE5VT'.'Ew'.'gQU5'.'E'.'I'.'FV'.'GLlZ'.'B'.'TF'.'VFX0lOV'.'CA8PiAwKQ='.'=','Qw==','VVNFUg==','TG9nb3'.'V0');return base64_decode($_1991211849[$_419419894]);}};if($GLOBALS['____1577660733'][0](round(0+0.2+0.2+0.2+0.2+0.2), round(0+5+5+5+5)) == round(0+7)){ if(isset($GLOBALS[___1883177578(0)]) && $GLOBALS['____1577660733'][1]($GLOBALS[___1883177578(1)]) && $GLOBALS['____1577660733'][2](array($GLOBALS[___1883177578(2)], ___1883177578(3))) &&!$GLOBALS['____1577660733'][3](array($GLOBALS[___1883177578(4)], ___1883177578(5)))){ $_1000791746= $GLOBALS[___1883177578(6)]->Query(___1883177578(7), true); if(!($_1849456837= $_1000791746->Fetch())){ $_720529644= round(0+4+4+4);} $_2078137234= $_1849456837[___1883177578(8)]; list($_1928387662, $_720529644)= $GLOBALS['____1577660733'][4](___1883177578(9), $_2078137234); $_417147853= $GLOBALS['____1577660733'][5](___1883177578(10), $_1928387662); $_81532991= ___1883177578(11).$GLOBALS['____1577660733'][6]($GLOBALS['____1577660733'][7](___1883177578(12))); $_997472001= $GLOBALS['____1577660733'][8](___1883177578(13), $_720529644, $_81532991, true); if($GLOBALS['____1577660733'][9]($_997472001, $_417147853) !== min(78,0,26)){ $_720529644= round(0+4+4+4);} if($_720529644 !=(127*2-254)){ if($GLOBALS['____1577660733'][10](___1883177578(14), ___1883177578(15))){ $_232731449= new \Bitrix\Main\License(); $_1131110582= $_232731449->getActiveUsersCount();} else{ $_1131110582=(182*2-364); $_1000791746= $GLOBALS[___1883177578(16)]->Query(___1883177578(17), true); if($_1849456837= $_1000791746->Fetch()){ $_1131110582= $GLOBALS['____1577660733'][11]($_1849456837[___1883177578(18)]);}} if($_1131110582> $_720529644){ $GLOBALS['____1577660733'][12](array($GLOBALS[___1883177578(19)], ___1883177578(20)));}}}}/**/
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