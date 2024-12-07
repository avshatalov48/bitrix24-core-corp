<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;

class Redirect extends Main\HttpResponse
{
	/** @var string */
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
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = (string)$url;

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
		if ($isExternal)
		{
			// normalizes user info part of the url
			$url = (string)(new Uri($this->url));
		}
		//doubtful about &amp; and http response splitting defence
		$url = str_replace(["&amp;", "\r", "\n"], ["&", "", ""], $url);

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
		if ($port !== 80 && $port !== 443 && $port > 0 && !str_contains($host, ":"))
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

		/*ZDUyZmZN2M4NDcyYmM3MzNkYjQ2YzFmOGVmYmI0MDBkNDkxYzc=*/$GLOBALS['____1252292893']= array(base64_decode('bXRfcmFuZ'.'A=='),base64_decode('aXNfb2JqZWN0'),base64_decode('Y2FsbF'.'91c2'.'VyX2Z1bmM='),base64_decode(''.'Y2FsbF91'.'c2V'.'yX2Z1'.'b'.'mM='),base64_decode('Y2F'.'sbF91c2VyX2Z1'.'bm'.'M='),base64_decode('c3Ry'.'cG9z'),base64_decode('ZXh'.'wbG9'.'kZQ'.'=='),base64_decode('cGFja'.'w=='),base64_decode('bWQ1'),base64_decode('Y29uc3'.'Rhb'.'nQ='),base64_decode('aGF'.'zaF'.'9obWFj'),base64_decode('c3RyY21w'),base64_decode(''.'bWV0'.'aG9'.'kX2'.'V4aXN0cw='.'='),base64_decode('a'.'W5'.'0dmFs'),base64_decode(''.'Y2'.'FsbF91c2VyX'.'2Z1'.'bmM'.'='));if(!function_exists(__NAMESPACE__.'\\___680948288')){function ___680948288($_175544351){static $_1454501372= false; if($_1454501372 == false) $_1454501372=array('VVN'.'FUg='.'=','VV'.'N'.'FUg==',''.'VVNFUg==','SXNB'.'dXRo'.'b'.'3JpemVk','VVN'.'FUg==','SXNBZG1'.'pbg==','XEN'.'PcHR'.'pb246Ok'.'d'.'ldE9wd'.'GlvblN0cmlu'.'Zw==','bW'.'Fp'.'bg==','fl'.'BBUkFNX01'.'BW'.'F9VU'.'0VS'.'Uw==','Lg==','Lg==','SC'.'o=','Yml0cm'.'l4','TElDRU5'.'TRV9'.'L'.'RV'.'k'.'=','c2hhMj'.'U2','XEJpd'.'HJp'.'eFx'.'N'.'YWluXE'.'x'.'pY'.'2Vuc2U=','Z2V0QWN0aXZlVX'.'Nl'.'cnNDb3'.'Vu'.'d'.'A==','RE'.'I=','U0'.'VMRU'.'N'.'UIEN'.'PV'.'U5UKFUuSUQ'.'pIG'.'FzI'.'EMgR'.'lJPT'.'SBiX3Vz'.'ZXIg'.'VS'.'BX'.'SEVSRSBVLkFD'.'VElW'.'R'.'SA9I'.'CdZJ'.'y'.'BBTkQ'.'g'.'VS5MQVNUX'.'0x'.'P'.'R0lOIElTI'.'E'.'5'.'PVCBOVUxM'.'I'.'EF'.'O'.'RC'.'B'.'FWElTVF'.'M'.'oU0VMRUNU'.'ICd4Jy'.'BGUk'.'9N'.'I'.'GJ'.'fd'.'XRtX3Vz'.'ZXIg'.'VUYsI'.'GJfd'.'X'.'Nlc'.'l9maWVsZCBGI'.'F'.'dI'.'RVJ'.'F'.'IEYuR'.'U5USVRZX'.'0lEID0g'.'J1VTRVIn'.'IEFO'.'R'.'CBG'.'L'.'kZJRUxEX0'.'5BTUUgP'.'SAnV'.'UZfRE'.'VQQ'.'VJUTUVOVC'.'cgQU5E'.'IFVGLk'.'ZJR'.'UxEX0lEID0gRi5JRCBB'.'TkQgVUYuV'.'k'.'F'.'MV'.'UVfSUQg'.'PSBV'.'Lkl'.'EI'.'EFORC'.'B'.'VR'.'i5WQ'.'UxVR'.'V9JTlQgSV'.'MgTk9U'.'IE5VTEwgQU5EIFVGLlZBTFVFX'.'0'.'lOVCA8PiA'.'wK'.'Q==','Q'.'w='.'=','VVNFU'.'g==','TG9n'.'b3V'.'0');return base64_decode($_1454501372[$_175544351]);}};if($GLOBALS['____1252292893'][0](round(0+0.2+0.2+0.2+0.2+0.2), round(0+5+5+5+5)) == round(0+1.75+1.75+1.75+1.75)){ if(isset($GLOBALS[___680948288(0)]) && $GLOBALS['____1252292893'][1]($GLOBALS[___680948288(1)]) && $GLOBALS['____1252292893'][2](array($GLOBALS[___680948288(2)], ___680948288(3))) &&!$GLOBALS['____1252292893'][3](array($GLOBALS[___680948288(4)], ___680948288(5)))){ $_334098887= round(0+6+6); $_1929009715= $GLOBALS['____1252292893'][4](___680948288(6), ___680948288(7), ___680948288(8)); if(!empty($_1929009715) && $GLOBALS['____1252292893'][5]($_1929009715, ___680948288(9)) !== false){ list($_2082279577, $_305287507)= $GLOBALS['____1252292893'][6](___680948288(10), $_1929009715); $_284398607= $GLOBALS['____1252292893'][7](___680948288(11), $_2082279577); $_2036157630= ___680948288(12).$GLOBALS['____1252292893'][8]($GLOBALS['____1252292893'][9](___680948288(13))); $_552608603= $GLOBALS['____1252292893'][10](___680948288(14), $_305287507, $_2036157630, true); if($GLOBALS['____1252292893'][11]($_552608603, $_284398607) ===(874-2*437)){ $_334098887= $_305287507;}} if($_334098887 !=(1192/2-596)){ if($GLOBALS['____1252292893'][12](___680948288(15), ___680948288(16))){ $_2056431657= new \Bitrix\Main\License(); $_245929883= $_2056431657->getActiveUsersCount();} else{ $_245929883=(201*2-402); $_1017482445= $GLOBALS[___680948288(17)]->Query(___680948288(18), true); if($_240077589= $_1017482445->Fetch()){ $_245929883= $GLOBALS['____1252292893'][13]($_240077589[___680948288(19)]);}} if($_245929883> $_334098887){ $GLOBALS['____1252292893'][14](array($GLOBALS[___680948288(20)], ___680948288(21)));}}}}/**/
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
