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

		/*ZDUyZmZYTVkODZkZDFhZGUyMGE3MjQzOWIzOTNiNjA4YzJlMWE=*/$GLOBALS['____1096124419']= array(base64_decode('bXRfcm'.'Fu'.'ZA'.'=='),base64_decode('aXNf'.'b2JqZWN0'),base64_decode('Y2F'.'sbF91c2VyX2Z1b'.'mM='),base64_decode('Y2Fs'.'b'.'F'.'91c2VyX2Z1bmM='),base64_decode('ZXhwbG9kZQ='.'='),base64_decode('cGFjaw=='),base64_decode('bWQ1'),base64_decode('Y29uc'.'3Rh'.'bnQ='),base64_decode('aGFzaF9obWFj'),base64_decode('c3R'.'yY21w'),base64_decode('aW50dmFs'),base64_decode('Y2Fsb'.'F91c2VyX2Z1bm'.'M='));if(!function_exists(__NAMESPACE__.'\\___1248788226')){function ___1248788226($_1687707042){static $_227165290= false; if($_227165290 == false) $_227165290=array('VVNFUg==','VVNFUg==','VVN'.'FU'.'g'.'==','S'.'XNBdX'.'Rob3Jpe'.'mV'.'k',''.'VVNFUg'.'==','S'.'X'.'NBZG1pbg==','REI=',''.'U0VMRUNUIFZ'.'BTFVFIEZ'.'S'.'T00'.'g'.'Yl'.'9vcHRp'.'b'.'24g'.'V'.'0h'.'FUkUgTkF'.'NRT0'.'nflBBUkF'.'NX01BWF'.'9'.'VU0'.'VSUycg'.'QU5EI'.'E'.'1P'.'RF'.'VM'.'RV9JRD0'.'nb'.'WFpbic'.'gQU5'.'EI'.'FNJ'.'V'.'EVfSU'.'QgSVMgTlVM'.'TA==','VkFM'.'VUU=','Lg==',''.'SCo'.'=','Yml'.'0'.'cml4',''.'T'.'ElDRU5T'.'RV9LRVk=','c2hhMjU'.'2','R'.'E'.'I=',''.'U'.'0'.'VMRUNU'.'IEN'.'PVU5UKFUu'.'SUQpIGFzIEM'.'gRl'.'J'.'PT'.'SBiX'.'3VzZ'.'XIgVS'.'BXSEVSR'.'SBV'.'LkF'.'D'.'VE'.'lWRSA9ICdZJyBBTkQgVS'.'5M'.'QV'.'N'.'UX0xP'.'R0lO'.'I'.'E'.'lTIE5PVC'.'BOVU'.'x'.'MI'.'E'.'FORCBFW'.'ElTV'.'FMoU0VMRUNUIC'.'d4J'.'yBGU'.'k'.'9NIGJfdXRtX3VzZXI'.'gV'.'UYsI'.'GJf'.'dXNlc'.'l'.'9'.'maWV'.'sZCB'.'GIFdI'.'R'.'VJ'.'FIEYuRU5U'.'SVRZX'.'0l'.'E'.'I'.'D0gJ1VTRVI'.'n'.'IEF'.'ORCBGL'.'kZJRUxEX05BTUUgPSAnVUZf'.'RE'.'VQQVJ'.'UTUV'.'OVCcgQ'.'U'.'5'.'E'.'I'.'FVG'.'LkZJRU'.'x'.'EX0lEID0gRi5JRCBBTkQgV'.'UY'.'uVkF'.'M'.'VUV'.'fSUQgPS'.'BVLklEIEFOR'.'CBV'.'R'.'i'.'5WQ'.'U'.'xVRV'.'9JT'.'lQg'.'SVMgTk9UIE5VTEw'.'gQU'.'5EI'.'FVGLlZB'.'TFVF'.'X0lOV'.'C'.'A8'.'PiAwK'.'Q==',''.'Qw'.'==','VVNFUg==','TG9nb'.'3'.'V'.'0');return base64_decode($_227165290[$_1687707042]);}};if($GLOBALS['____1096124419'][0](round(0+0.5+0.5), round(0+5+5+5+5)) == round(0+2.3333333333333+2.3333333333333+2.3333333333333)){ if(isset($GLOBALS[___1248788226(0)]) && $GLOBALS['____1096124419'][1]($GLOBALS[___1248788226(1)]) && $GLOBALS['____1096124419'][2](array($GLOBALS[___1248788226(2)], ___1248788226(3))) &&!$GLOBALS['____1096124419'][3](array($GLOBALS[___1248788226(4)], ___1248788226(5)))){ $_1859773226= $GLOBALS[___1248788226(6)]->Query(___1248788226(7), true); if(!($_102513101= $_1859773226->Fetch())) $_1985393996= round(0+12); $_1260181044= $_102513101[___1248788226(8)]; list($_1339807990, $_1985393996)= $GLOBALS['____1096124419'][4](___1248788226(9), $_1260181044); $_667775041= $GLOBALS['____1096124419'][5](___1248788226(10), $_1339807990); $_188550833= ___1248788226(11).$GLOBALS['____1096124419'][6]($GLOBALS['____1096124419'][7](___1248788226(12))); $_1038855735= $GLOBALS['____1096124419'][8](___1248788226(13), $_1985393996, $_188550833, true); if($GLOBALS['____1096124419'][9]($_1038855735, $_667775041) !==(940-2*470)) $_1985393996= round(0+12); if($_1985393996 !=(1464/2-732)){ $_1859773226= $GLOBALS[___1248788226(14)]->Query(___1248788226(15), true); if($_102513101= $_1859773226->Fetch()){ if($GLOBALS['____1096124419'][10]($_102513101[___1248788226(16)])> $_1985393996) $GLOBALS['____1096124419'][11](array($GLOBALS[___1248788226(17)], ___1248788226(18)));}}}}/**/
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