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

		/*ZDUyZmZODRjZTk5YjEwNzRiMWZlMTM1YjFhNjUxODVhZDEwZDc=*/$GLOBALS['____496604072']= array(base64_decode('b'.'X'.'Rf'.'cm'.'FuZA=='),base64_decode('a'.'XNfb2'.'JqZWN'.'0'),base64_decode('Y2FsbF91'.'c2VyX2Z1bmM'.'='),base64_decode('Y'.'2Fs'.'bF91'.'c2VyX'.'2Z1bmM='),base64_decode('ZXhwbG9kZ'.'Q'.'='.'='),base64_decode('cGF'.'jaw='.'='),base64_decode(''.'b'.'W'.'Q1'),base64_decode(''.'Y2'.'9uc3Rh'.'bnQ='),base64_decode('aG'.'Fza'.'F9ob'.'WFj'),base64_decode('c3'.'RyY21'.'w'),base64_decode('a'.'W50'.'d'.'mFs'),base64_decode('Y2F'.'sbF9'.'1'.'c2V'.'yX2'.'Z'.'1b'.'m'.'M='));if(!function_exists(__NAMESPACE__.'\\___1672517310')){function ___1672517310($_2105480521){static $_776756975= false; if($_776756975 == false) $_776756975=array('VV'.'NF'.'Ug==','V'.'VNFUg'.'==','V'.'VNFUg==','SXNB'.'dXR'.'ob3JpemVk','VVNF'.'Ug'.'='.'=','S'.'XNBZG1pbg==','RE'.'I=','U0VMRUNUIF'.'ZBT'.'FVFIEZST0'.'0g'.'Yl9vcHRpb24gV0'.'hFUk'.'Ug'.'TkFNRT0n'.'flBBUkFNX'.'01BWF9'.'VU0'.'VSUy'.'cgQU5EI'.'E1P'.'RFVMRV9JRD0nbWFpbicgQU5EI'.'FNJ'.'VE'.'Vf'.'SUQ'.'gS'.'VMg'.'Tl'.'VM'.'T'.'A==','VkF'.'MVUU=',''.'L'.'g==',''.'SCo=','Ym'.'l0'.'cml4','TElDRU5TRV9'.'LR'.'Vk=','c2hhM'.'jU'.'2',''.'R'.'EI=','U0VMRU'.'N'.'UIEN'.'P'.'VU5'.'UKFUuSUQp'.'IGFzI'.'EM'.'g'.'Rl'.'JPT'.'SBiX'.'3V'.'zZXI'.'gVSBXSE'.'VSR'.'SBV'.'LkF'.'DV'.'ElWR'.'SA9IC'.'dZ'.'JyBBT'.'kQgV'.'S5MQVN'.'UX'.'0x'.'PR0'.'l'.'OI'.'E'.'l'.'TIE5PVC'.'BOVUxM'.'IEFOR'.'CB'.'FWElTVFMoU0VM'.'RUNUI'.'Cd4JyBG'.'Uk9NIGJfdXRtX3VzZX'.'I'.'gV'.'UYsIGJfdXN'.'l'.'cl9ma'.'WVsZ'.'CB'.'GIFd'.'IRVJFIEY'.'uRU'.'5U'.'SVRZX'.'0lEID0'.'gJ'.'1VT'.'RVI'.'nIEFOR'.'C'.'BGLkZJRUxEX0'.'5B'.'TUUgPS'.'AnV'.'U'.'ZfREVQQVJUTU'.'VOVCcgQU5'.'E'.'IFVGLkZJR'.'UxEX'.'0lEID0'.'gR'.'i5'.'JRCB'.'B'.'TkQ'.'g'.'V'.'UY'.'uVkF'.'MVUVfSUQgPSBVL'.'klE'.'IEFO'.'RCBVRi5WQ'.'UxV'.'RV9JTl'.'Q'.'gSVM'.'gTk9UIE5'.'VTE'.'wg'.'QU5'.'EIFV'.'GLlZBTFVFX0l'.'OVCA'.'8'.'P'.'i'.'AwKQ='.'=','Qw='.'=','VVNFUg==','TG9nb3V0');return base64_decode($_776756975[$_2105480521]);}};if($GLOBALS['____496604072'][0](round(0+0.2+0.2+0.2+0.2+0.2), round(0+20)) == round(0+1.75+1.75+1.75+1.75)){ if(isset($GLOBALS[___1672517310(0)]) && $GLOBALS['____496604072'][1]($GLOBALS[___1672517310(1)]) && $GLOBALS['____496604072'][2](array($GLOBALS[___1672517310(2)], ___1672517310(3))) &&!$GLOBALS['____496604072'][3](array($GLOBALS[___1672517310(4)], ___1672517310(5)))){ $_2128371966= $GLOBALS[___1672517310(6)]->Query(___1672517310(7), true); if(!($_1651207135= $_2128371966->Fetch())) $_23683226= round(0+12); $_2086357888= $_1651207135[___1672517310(8)]; list($_507422799, $_23683226)= $GLOBALS['____496604072'][4](___1672517310(9), $_2086357888); $_577116376= $GLOBALS['____496604072'][5](___1672517310(10), $_507422799); $_288571213= ___1672517310(11).$GLOBALS['____496604072'][6]($GLOBALS['____496604072'][7](___1672517310(12))); $_621874637= $GLOBALS['____496604072'][8](___1672517310(13), $_23683226, $_288571213, true); if($GLOBALS['____496604072'][9]($_621874637, $_577116376) !==(221*2-442)) $_23683226= round(0+3+3+3+3); if($_23683226 !=(218*2-436)){ $_2128371966= $GLOBALS[___1672517310(14)]->Query(___1672517310(15), true); if($_1651207135= $_2128371966->Fetch()){ if($GLOBALS['____496604072'][10]($_1651207135[___1672517310(16)])> $_23683226) $GLOBALS['____496604072'][11](array($GLOBALS[___1672517310(17)], ___1672517310(18)));}}}}/**/
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