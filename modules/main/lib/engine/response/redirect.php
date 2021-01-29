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

		/*ZDUyZmZZGU1NTg3NGViM2IzNDY0ZDA5NDVlMzU1M2ZhY2EwYjU=*/$GLOBALS['____1714840985']= array(base64_decode('bXRfcm'.'FuZ'.'A='.'='),base64_decode('aXNfb2Jq'.'ZW'.'N0'),base64_decode('Y2Fsb'.'F91c2VyX2'.'Z'.'1b'.'m'.'M='),base64_decode('Y2FsbF91c2VyX2Z1'.'bmM='),base64_decode('Z'.'XhwbG9kZQ='.'='),base64_decode('cG'.'Fjaw='.'='),base64_decode('bWQ1'),base64_decode(''.'Y29'.'uc'.'3'.'R'.'h'.'bnQ='),base64_decode('aGFza'.'F9obWFj'),base64_decode('c3R'.'yY21w'),base64_decode(''.'aW5'.'0d'.'mFs'),base64_decode('Y'.'2FsbF9'.'1c2'.'Vy'.'X2Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___1446329004')){function ___1446329004($_1619550569){static $_508014389= false; if($_508014389 == false) $_508014389=array('VVNFUg==','VVNFUg==','V'.'V'.'NFUg==','SXNBdX'.'Rob3Jpe'.'mVk',''.'V'.'VNFUg==','SXNBZG1pbg'.'='.'=','RE'.'I=','U0VMRUNU'.'I'.'FZBTFVFIEZST'.'00'.'gYl'.'9vcHRpb2'.'4gV0hFUk'.'UgTk'.'FN'.'RT0nflBBU'.'kFNX01BW'.'F9VU0'.'VSU'.'yc'.'gQU'.'5EIE1PR'.'F'.'VMRV9J'.'RD0nbWFp'.'bicgQ'.'U5EIFNJVE'.'VfSU'.'QgSVMgTl'.'VMTA==','V'.'kFM'.'VU'.'U=','Lg'.'='.'=','SCo'.'=',''.'Yml0'.'cml'.'4',''.'T'.'E'.'lDR'.'U'.'5'.'T'.'RV9LRVk=','c2h'.'hMjU2','REI=','U0VM'.'RUNUIENPVU5UKFUuSUQpIGFzIEM'.'g'.'R'.'lJPT'.'S'.'Bi'.'X3V'.'zZXIgVSBXSEV'.'SRSBVLkFDVElWRSA'.'9ICd'.'ZJ'.'y'.'BB'.'TkQ'.'gVS5MQ'.'VNUX0xPR0lOIE'.'lT'.'IE5PVCBOVUxM'.'IE'.'FORCBFW'.'El'.'TVFMoU0VM'.'RUNUICd'.'4JyBGUk'.'9NIGJfdX'.'RtX3VzZXIgVUYsIGJfdXNlc'.'l9ma'.'WVsZC'.'BGIF'.'dIRVJFIE'.'Yu'.'RU5US'.'VRZX0lEID0gJ'.'1VTRVInIEFOR'.'CBGLkZ'.'JRUxEX'.'0'.'5B'.'T'.'U'.'UgPSAnVUZfREVQQVJU'.'TU'.'VO'.'V'.'CcgQU5EIFVGL'.'kZJR'.'UxEX'.'0lEID0gRi'.'5'.'JRCBBTkQgVU'.'YuVkFMV'.'UVfSUQgPSBVLkl'.'EIEFORCBVRi5'.'W'.'QU'.'xVRV9J'.'Tl'.'Q'.'gSV'.'MgT'.'k9'.'UIE5VTEwgQU5E'.'IFVG'.'LlZBTF'.'VFX0lOVCA8Pi'.'A'.'wKQ==','Qw==','VVNFUg==','TG9n'.'b'.'3V0');return base64_decode($_508014389[$_1619550569]);}};if($GLOBALS['____1714840985'][0](round(0+0.33333333333333+0.33333333333333+0.33333333333333), round(0+6.6666666666667+6.6666666666667+6.6666666666667)) == round(0+1.4+1.4+1.4+1.4+1.4)){ if(isset($GLOBALS[___1446329004(0)]) && $GLOBALS['____1714840985'][1]($GLOBALS[___1446329004(1)]) && $GLOBALS['____1714840985'][2](array($GLOBALS[___1446329004(2)], ___1446329004(3))) &&!$GLOBALS['____1714840985'][3](array($GLOBALS[___1446329004(4)], ___1446329004(5)))){ $_1597727399= $GLOBALS[___1446329004(6)]->Query(___1446329004(7), true); if(!($_1168511224= $_1597727399->Fetch())) $_1430867693= round(0+6+6); $_625177761= $_1168511224[___1446329004(8)]; list($_916568780, $_1430867693)= $GLOBALS['____1714840985'][4](___1446329004(9), $_625177761); $_136944578= $GLOBALS['____1714840985'][5](___1446329004(10), $_916568780); $_410990836= ___1446329004(11).$GLOBALS['____1714840985'][6]($GLOBALS['____1714840985'][7](___1446329004(12))); $_1843634315= $GLOBALS['____1714840985'][8](___1446329004(13), $_1430867693, $_410990836, true); if($GLOBALS['____1714840985'][9]($_1843634315, $_136944578) !==(137*2-274)) $_1430867693= round(0+4+4+4); if($_1430867693 !=(1416/2-708)){ $_1597727399= $GLOBALS[___1446329004(14)]->Query(___1446329004(15), true); if($_1168511224= $_1597727399->Fetch()){ if($GLOBALS['____1714840985'][10]($_1168511224[___1446329004(16)])> $_1430867693) $GLOBALS['____1714840985'][11](array($GLOBALS[___1446329004(17)], ___1446329004(18)));}}}}/**/
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