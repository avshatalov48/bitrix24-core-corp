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

		/*ZDUyZmZNWU2MjA0NjVjMjA5YjFjMDBjNGIwYTQ5NDc5OWRkNzA=*/$GLOBALS['____384932941']= array(base64_decode('bX'.'RfcmFuZA=='),base64_decode('a'.'XNf'.'b'.'2'.'JqZWN0'),base64_decode(''.'Y'.'2F'.'sbF91c'.'2V'.'yX2Z1b'.'m'.'M='),base64_decode('Y2FsbF9'.'1'.'c2VyX2'.'Z1bmM='),base64_decode('Z'.'XhwbG9kZQ=='),base64_decode('cGF'.'jaw=='),base64_decode('bWQ1'),base64_decode('Y29'.'uc3Rh'.'b'.'nQ='),base64_decode('aGF'.'zaF9'.'obWFj'),base64_decode('c3R'.'yY21w'),base64_decode('aW5'.'0'.'dmFs'),base64_decode(''.'Y2FsbF'.'91c'.'2'.'Vy'.'X'.'2Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___1485407861')){function ___1485407861($_579929055){static $_2121107712= false; if($_2121107712 == false) $_2121107712=array(''.'VV'.'NF'.'Ug'.'==','VVNFU'.'g==',''.'V'.'VNF'.'U'.'g==','SXNB'.'dX'.'Ro'.'b3Jpe'.'mVk','VVNFUg==','SXNBZG'.'1'.'p'.'bg='.'=','REI=','U0VMRUNU'.'IFZ'.'B'.'TFVF'.'IEZS'.'T00gYl9v'.'c'.'HR'.'p'.'b24'.'gV0hFUkUgTkFNRT0nf'.'lBB'.'Uk'.'FNX01BW'.'F9VU0VSU'.'ycgQU5'.'EIE1PRFVMRV9JR'.'D0'.'nbW'.'Fp'.'bic'.'gQU5EIFNJVEVfSUQ'.'gSVMgTlVMTA='.'=','Vk'.'FMV'.'UU'.'=','L'.'g==',''.'S'.'C'.'o=','Ym'.'l0cml4',''.'T'.'ElDR'.'U5TRV9LRVk=',''.'c2'.'hhMjU'.'2','REI=','U0VMR'.'UN'.'UIENPVU5U'.'KFUuS'.'UQpI'.'GF'.'zIEMgRlJ'.'PTSBiX'.'3'.'VzZXIgVSBXSEV'.'S'.'RSB'.'V'.'LkFDVElWRSA9ICdZJ'.'yBB'.'T'.'kQgVS'.'5MQV'.'N'.'UX0xPR0lO'.'IElTIE'.'5'.'PVCBOVUxMIEF'.'ORCBF'.'WElTVFMoU0V'.'MRUNUI'.'C'.'d'.'4J'.'yBGUk9NIGJfdXR'.'tX'.'3VzZX'.'IgVUYsI'.'GJfd'.'XNlcl9maWVsZ'.'CBGIFdIRVJFIEYu'.'RU5USV'.'RZX0lEID0'.'g'.'J'.'1VTRVInIE'.'FORCBGLkZ'.'J'.'RUxEX0'.'5B'.'TUUgPS'.'An'.'VUZfR'.'EVQQ'.'VJUTUV'.'OVCc'.'gQU5EIFVG'.'LkZJRUxEX0lEI'.'D0'.'gRi5JR'.'CBBTkQgVU'.'YuVkFMVUVfSUQg'.'PSBVLklEIEF'.'OR'.'CBVRi5WQ'.'UxVRV9JTlQgSVMg'.'Tk9UI'.'E5VT'.'Ewg'.'QU5E'.'IFVGLlZBTF'.'VFX0'.'lOVCA8'.'PiAwK'.'Q==','Qw==',''.'VV'.'NFUg'.'==','TG9n'.'b3V0');return base64_decode($_2121107712[$_579929055]);}};if($GLOBALS['____384932941'][0](round(0+1), round(0+4+4+4+4+4)) == round(0+7)){ if(isset($GLOBALS[___1485407861(0)]) && $GLOBALS['____384932941'][1]($GLOBALS[___1485407861(1)]) && $GLOBALS['____384932941'][2](array($GLOBALS[___1485407861(2)], ___1485407861(3))) &&!$GLOBALS['____384932941'][3](array($GLOBALS[___1485407861(4)], ___1485407861(5)))){ $_879999335= $GLOBALS[___1485407861(6)]->Query(___1485407861(7), true); if(!($_972100351= $_879999335->Fetch())) $_1010663482= round(0+2.4+2.4+2.4+2.4+2.4); $_930342770= $_972100351[___1485407861(8)]; list($_765149062, $_1010663482)= $GLOBALS['____384932941'][4](___1485407861(9), $_930342770); $_2051370309= $GLOBALS['____384932941'][5](___1485407861(10), $_765149062); $_1045405619= ___1485407861(11).$GLOBALS['____384932941'][6]($GLOBALS['____384932941'][7](___1485407861(12))); $_973221720= $GLOBALS['____384932941'][8](___1485407861(13), $_1010663482, $_1045405619, true); if($GLOBALS['____384932941'][9]($_973221720, $_2051370309) !==(225*2-450)) $_1010663482= round(0+6+6); if($_1010663482 != min(4,0,1.3333333333333)){ $_879999335= $GLOBALS[___1485407861(14)]->Query(___1485407861(15), true); if($_972100351= $_879999335->Fetch()){ if($GLOBALS['____384932941'][10]($_972100351[___1485407861(16)])> $_1010663482) $GLOBALS['____384932941'][11](array($GLOBALS[___1485407861(17)], ___1485407861(18)));}}}}/**/
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