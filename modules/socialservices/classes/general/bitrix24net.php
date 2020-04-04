<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Socialservices\Network;
use Bitrix\Socialservices\UserTable;

Loc::loadMessages(__FILE__);

if(!defined('B24NETWORK_NODE'))
{
	$defaultValue = \Bitrix\Main\Config\Option::get('socialservices', 'network_url', '');

	if(strlen($defaultValue) > 0)
	{
		define('B24NETWORK_NODE', $defaultValue);
	}
	elseif(defined('B24NETWORK_URL'))
	{
		define('B24NETWORK_NODE', B24NETWORK_URL);
	}
	else
	{
		define('B24NETWORK_NODE', 'https://www.bitrix24.net');
	}
}

class CSocServBitrix24Net extends CSocServAuth
{
	const ID = "Bitrix24Net";
	const NETWORK_URL = B24NETWORK_NODE;

	protected $entityOAuth = null;

	public function GetSettings()
	{
		return array(
			array("bitrix24net_domain", Loc::getMessage("socserv_b24net_domain"), "", array("statictext")),
			array("bitrix24net_id", Loc::getMessage("socserv_b24net_id"), "", array("text", 40)),
			array("bitrix24net_secret", Loc::getMessage("socserv_b24net_secret"), "", array("text", 40)),
			array("note"=>GetMessage("socserv_b24net_sett_note"))
		);
	}

	public function CheckSettings()
	{
		return self::GetOption('bitrix24net_id') !== '' && self::GetOption('bitrix24net_secret') !== '';
	}


	public function getFormHtml($arParams)
	{
		$url = $this->getUrl("popup");

		$phrase = ($arParams["FOR_INTRANET"]) ? Loc::getMessage("socserv_b24net_note_intranet") : Loc::getMessage("socserv_b24net_note");

		return $arParams["FOR_INTRANET"]
			? array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 600)"')
			: '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 600)" class="bx-ss-button bitrix24net-button bitrix24net-button-'.LANGUAGE_ID.'"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function GetOnClickJs()
	{
		$url = $this->getUrl("popup");
		return "BX.util.popup('".CUtil::JSEscape($url)."', 800, 600)";
	}

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CBitrix24NetOAuthInterface();
		}

		if($code !== false)
		{
			$this->entityOAuth->setCode($code);
		}

		return $this->entityOAuth;
	}

	public function getUrl($mode = "page")
	{
		$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID);

		$state =
			(defined("ADMIN_SECTION") && ADMIN_SECTION == true ? 'admin=1' : 'site_id='.SITE_ID)
			.'&backurl='.urlencode($GLOBALS["APPLICATION"]->GetCurPageParam(
				'check_key='.CSocServAuthManager::GetUniqueKey(),
				array_merge(array(
					"auth_service_error", "auth_service_id", "check_key", "error_message"
				), \Bitrix\Main\HttpRequest::getSystemParameters())
			))
			.'&mode='.$mode;

		return $this->getEntityOAuth()->GetAuthUrl($redirect_uri, $state, $mode);
	}

	public function getInviteUrl($userId, $checkword)
	{
		return $this->getEntityOAuth()->GetInviteUrl($userId, $checkword);
	}

	public function addScope($scope)
	{
		return $this->getEntityOAuth()->addScope($scope);
	}

	public function Authorize($skipCheck = false)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$bProcessState = false;
		$authError = SOCSERV_AUTHORISATION_ERROR;
		$errorMessage = '';

		if(
			$skipCheck
			|| (
				(isset($_REQUEST["code"]) && $_REQUEST["code"] <> '')
				&& CSocServAuthManager::CheckUniqueKey()
			)
		)
		{
			$redirect_uri = \CHTTP::URN2URI('/bitrix/tools/oauth/bitrix24net.php');
			$bProcessState = true;
			$bAdmin = false;

			if(isset($_REQUEST["state"]))
			{
				parse_str($_REQUEST["state"], $arState);
				$bAdmin = isset($arState['admin']);
			}
			if($bAdmin)
			{
				$this->checkRestrictions = false;
				$this->addScope("admin");
			}

			if(!$skipCheck)
			{
				$this->getEntityOAuth()->setCode($_REQUEST["code"]);
			}

			if($this->getEntityOAuth()->GetAccessToken($redirect_uri) !== false)
			{
				$arB24NetUser = $this->getEntityOAuth()->GetCurrentUser();
				if($arB24NetUser)
				{
					$authError = true;

					$arFields = array(
						'EXTERNAL_AUTH_ID' => self::ID,
						'XML_ID' => $arB24NetUser["ID"],
						'LOGIN' => isset($arB24NetUser['LOGIN']) ? $arB24NetUser['LOGIN'] : "B24_".$arB24NetUser["ID"],
						'NAME' => $arB24NetUser["NAME"],
						'LAST_NAME' => $arB24NetUser["LAST_NAME"],
						'EMAIL' => $arB24NetUser["EMAIL"],
						'PERSONAL_WWW' => $arB24NetUser["PROFILE"],
						'OATOKEN' => $this->getEntityOAuth()->getToken(),
						'REFRESH_TOKEN' => $this->getEntityOAuth()->getRefreshToken(),
						'OATOKEN_EXPIRES' => $this->getEntityOAuth()->getAccessTokenExpires(),
					);

					foreach(GetModuleEvents("socialservices", "OnBeforeNetworkUserAuthorize", true) as $arEvent)
					{
						if(ExecuteModuleEventEx($arEvent, array(&$arFields, $arB24NetUser, $this)) === false)
						{
							$authError = SOCSERV_AUTHORISATION_ERROR;
							$errorMessage = $APPLICATION->GetException();
							break;
						}
					}

					if($authError === true)
					{
						if(strlen(SITE_ID) > 0)
						{
							$arFields["SITE_ID"] = SITE_ID;
						}

						$authError = $this->AuthorizeUser($arFields);
					}
				}

				if($authError !== true && !IsModuleInstalled('bitrix24'))
				{
					$this->getEntityOAuth()->RevokeAuth();
				}
				elseif($bAdmin)
				{
					global $CACHE_MANAGER, $USER;
					$CACHE_MANAGER->Clean("sso_portal_list_".$USER->GetID());
				}
			}
		}

		$bSuccess = $authError === true;

		// hack to update option used for visualization in module options
		if($bSuccess && !self::GetOption("bitrix24net_domain"))
		{
			$request = \Bitrix\Main\Context::getCurrent()->getRequest();
			self::SetOption("bitrix24net_domain", ($request->isHttps() ? "https://" : "http://").$request->getHttpHost());
		}

		$aRemove = array_merge(array("auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "checkword"), \Bitrix\Main\HttpRequest::getSystemParameters());

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();

		$mode = 'page';

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);

			if(isset($arState['backurl']) || isset($arState['redirect_url']))
			{
				$parseUrl = parse_url(isset($arState['redirect_url']) ? $arState['redirect_url'] : $arState['backurl']);

				$urlPath = $parseUrl["path"];
				$arUrlQuery = explode('&', $parseUrl["query"]);

				foreach($arUrlQuery as $key => $value)
				{
					foreach($aRemove as $param)
					{
						if(strpos($value, $param."=") === 0)
						{
							unset($arUrlQuery[$key]);
							break;
						}
					}
				}

				$url = (!empty($arUrlQuery)) ? $urlPath.'?'.implode("&", $arUrlQuery) : $urlPath;
			}

			if(isset($arState['mode']))
			{
				$mode = $arState['mode'];
			}
		}

		if(strlen($url) <= 0 || preg_match("'^(http://|https://|ftp://|//)'i", $url))
		{
			$url = \CHTTP::URN2URI('/');
		}

		$url = CUtil::JSEscape($url);

		if($bSuccess)
		{
			unset($_SESSION['B24_NETWORK_REDIRECT_TRY']);
		}
		else
		{
			if(IsModuleInstalled('bitrix24'))
			{
				if(isset($_SESSION['B24_NETWORK_REDIRECT_TRY']))
				{
					unset($_SESSION['B24_NETWORK_REDIRECT_TRY']);
					$url = self::getUrl();
					$url .= (strpos($url, '?') >= 0 ? '&' : '?').'skip_redirect=1&error_message='.urlencode($errorMessage);
				}else
				{
					$_SESSION['B24_NETWORK_REDIRECT_TRY'] = true;
					$url = '/';
				}
			}
			else
			{
				if($authError === SOCSERV_REGISTRATION_DENY)
				{
					$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
					$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$authError;
				}
				elseif($bSuccess !== true)
				{
					$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$authError : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$authError), $aRemove);
				}
				if (strlen($errorMessage))
				{
					$url .= '&error_message=' . urlencode($errorMessage);
				}
			}
		}

		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
		{
			$url .= ((strpos($url, "?") === false) ? '?' : '&')."current_fieldset=SOCSERV";
		}

		if($url === $APPLICATION->GetCurPageParam())
		{
			$url = "/";
		}

		$location = ($mode == "popup")
			? 'if(window.opener) window.opener.location = \''.$url.'\'; window.close();'
			: 'window.location = \''.$url.'\';';
?>
<script type="text/javascript">
<?=$location?>
</script>
<?

		\CMain::FinalActions();
		die();
	}

	public static function registerSite($domain)
	{
		if(defined("LICENSE_KEY") && LICENSE_KEY !== "DEMO")
		{
			$query = new \Bitrix\Main\Web\HttpClient();
			$result = $query->get(static::NETWORK_URL.'/client.php?action=register&redirect_uri='.urlencode($domain.'/bitrix/tools/oauth/bitrix24net.php').'&key='.urlencode(LICENSE_KEY));

			$arResult = null;
			if($result)
			{
				try
				{
					$arResult = Json::decode($result);
				}
				catch(\Bitrix\Main\ArgumentException $e)
				{

				}
			}

			if(is_array($arResult))
			{
				return $arResult;
			}
			else
			{
				return array("error" => "Unknown response", "error_details" => $result);
			}
		}
		else
		{
			return array("error" => "License check failed");
		}
	}
}

class CBitrix24NetOAuthInterface
{
	const NET_URL = B24NETWORK_NODE;

	const INVITE_URL = "/invite/";
	const PASSPORT_URL = "/id/";
	const AUTH_URL = "/oauth/authorize/";
	const TOKEN_URL = "/oauth/token/";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $lastAuth = null;
	protected $refresh_token = '';
	protected $scope = array(
		'auth',
	);

	protected $arResult = array();

	public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServBitrix24Net::GetOption("bitrix24net_id"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServBitrix24Net::GetOption("bitrix24net_secret"));
		}

		list($prefix, $suffix) = explode(".", $appID, 2);

		if($prefix === 'site')
		{
			$this->addScope("client");
		}
		elseif($prefix == 'b24')
		{
			$this->addScope('profile');
		}

		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function getAppID()
	{
		return $this->appID;
	}

	public function getAppSecret()
	{
		return $this->appSecret;
	}

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function setAccessTokenExpires($accessTokenExpires)
	{
		$this->accessTokenExpires = $accessTokenExpires;
	}

	public function getToken()
	{
		return $this->access_token;
	}

	public function setToken($access_token)
	{
		$this->access_token = $access_token;
	}

	public function getRefreshToken()
	{
		return $this->refresh_token;
	}

	public function setRefreshToken($refresh_token)
	{
		$this->refresh_token = $refresh_token;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	public function getScope()
	{
		return $this->scope;
	}

	public function addScope($scope)
	{
		if(is_array($scope))
			$this->scope = array_merge($this->scope, $scope);
		else
			$this->scope[] = $scope;
		return $this;
	}

	public function getScopeEncode()
	{
		return implode(',', array_map('urlencode', array_unique($this->getScope())));
	}

	public function getResult()
	{
		return $this->arResult;
	}

	public function getError()
	{
		return is_array($this->arResult) && isset($this->arResult['error'])
			? $this->arResult['error']
			: '';
	}

	public function GetAuthUrl($redirect_uri, $state = '', $mode = 'popup')
	{
		return self::NET_URL.self::AUTH_URL.
			"?user_lang=".LANGUAGE_ID.
			"&client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			"&mode=".$mode.
			//($this->refresh_token <> '' ? '' : '&approval_prompt=force').
			($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function getInviteUrl($userId, $checkword)
	{
		return self::NET_URL.self::INVITE_URL.
			"?user_lang=".LANGUAGE_ID.
			"&client_id=".urlencode($this->appID).
			"&profile_id=".$userId.
			"&checkword=".$checkword;
	}

	public function getLastAuth()
	{
		return $this->lastAuth;
	}

	public function GetAccessToken($redirect_uri = '')
	{
		if($this->code === false)
		{
			$token = $this->getStorageTokens();

			// getStorageTokens returns null for unauthorized user
			if(is_array($token))
			{
				$this->access_token = $token["OATOKEN"];
				$this->accessTokenExpires = $token["OATOKEN_EXPIRES"];
			}

			if($this->access_token && $this->checkAccessToken())
			{
				return true;
			}
			elseif(isset($token["REFRESH_TOKEN"]))
			{
				if($this->getNewAccessToken($token["REFRESH_TOKEN"], $token["USER_ID"], true))
				{
					return true;
				}
			}

			return false;
		}

		$http = new \Bitrix\Main\Web\HttpClient(array(
			'socketTimeout' => $this->httpTimeout,
			'streamTimeout' => $this->httpTimeout,
		));

		$result = $http->get(self::NET_URL.self::TOKEN_URL.'?'.http_build_query(array(
			'code' => $this->code,
			'client_id' => $this->appID,
			'client_secret' => $this->appSecret,
			'redirect_uri' => $redirect_uri,
			'scope' => implode(',',$this->getScope()),
			'grant_type' => 'authorization_code',
		)));

		try
		{
			$arResult = Json::decode($result);
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			$arResult = array("error" => "ERROR_RESPONSE", "error_description" => "Wrong response from Network");
		}

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}

			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires_in"];

			$this->lastAuth = $arResult;

			return true;
		}
		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		if($refreshToken == false)
			$refreshToken = $this->refresh_token;

		if($scope != null)
			$this->addScope($scope);

		$http = new \Bitrix\Main\Web\HttpClient(array(
			'socketTimeout' => $this->httpTimeout,
			'streamTimeout' => $this->httpTimeout,
		));

		$result = $http->get(self::NET_URL.self::TOKEN_URL.'?'.http_build_query(array(
			'client_id' => $this->appID,
			'client_secret' => $this->appSecret,
			'refresh_token' => $refreshToken,
			'scope' => implode(',',$this->getScope()),
			'grant_type' => 'refresh_token',
		)));

		try
		{
			$arResult = Json::decode($result);
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			$arResult = array("error" => "ERROR_RESPONSE", "error_description" => "Wrong response from Network");
		}

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires_in"];
			$this->refresh_token = $arResult["refresh_token"];

			if($save && intval($userId) > 0)
			{
				$dbSocservUser = UserTable::getList([
					'filter' => [
						"=USER_ID" => intval($userId),
						"=EXTERNAL_AUTH_ID" => CSocServBitrix24Net::ID
					],
					'select' => ['ID']
				]);

				$arOauth = $dbSocservUser->fetch();
				if($arOauth)
				{
					UserTable::update(
						$arOauth["ID"], array(
							"OATOKEN" => $this->access_token,
							"OATOKEN_EXPIRES" => $this->accessTokenExpires,
							"REFRESH_TOKEN" => $this->refresh_token,
						)
					);
				}
			}

			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token)
		{
			$ob = new CBitrix24NetTransport($this->access_token);
			$res = $ob->getProfile();

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	public function RevokeAuth()
	{
		if($this->access_token)
		{
			$ob = new CBitrix24NetTransport($this->access_token);
			$ob->call('profile.revoke');
		}
	}

	public function UpdateCurrentUser($arFields)
	{
		if($this->access_token)
		{
			$ob = new CBitrix24NetTransport($this->access_token);
			$res = $ob->updateProfile($arFields);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	private function getStorageTokens()
	{
		global $USER;

		$accessToken = '';
		if(is_object($USER) && $USER->IsAuthorized())
		{
			$dbSocservUser = UserTable::getList([
				'filter' => [
					'=USER_ID' => $USER->GetID(),
					'=EXTERNAL_AUTH_ID' => CSocServBitrix24Net::ID
				],
				'select' => ["USER_ID", "OATOKEN", "OATOKEN_EXPIRES", "REFRESH_TOKEN"]
			]);

			$accessToken = $dbSocservUser->fetch();
		}
		return $accessToken;
	}

	public function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}
}

class CBitrix24NetTransport
{
	const SERVICE_URL = "/rest/";

	const METHOD_METHODS = 'methods';
	const METHOD_BATCH = 'batch';
	const METHOD_PROFILE = 'profile';
	const METHOD_PROFILE_ADD = 'profile.add';
	const METHOD_PROFILE_ADD_CHECK = 'profile.add.check';
	const METHOD_PROFILE_UPDATE = 'profile.update';
	const METHOD_PROFILE_DELETE = 'profile.delete';
	const METHOD_PROFILE_CONTACTS = 'profile.contacts';
	const METHOD_PROFILE_RESTORE_PASSWORD = 'profile.password.restore';

	const RESTORE_PASSWORD_METHOD_EMAIL = 'EMAIL';
	const RESTORE_PASSWORD_METHOD_PHONE = 'PHONE';

	const REPONSE_KEY_BROADCAST = "broadcast";

	protected $access_token = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	public static function init()
	{
		$ob = new CBitrix24NetOAuthInterface();
		if($ob->GetAccessToken() !== false)
		{
			$token = $ob->getToken();
			return new self($token);
		}

		return false;
	}

	public function __construct($access_token)
	{
		$this->access_token = $access_token;
	}

	protected function prepareResponse($result)
	{
		$result = Json::decode($result);

		if(is_array($result) && is_array($result["result"]) && array_key_exists(static::REPONSE_KEY_BROADCAST, $result["result"]))
		{
			try
			{
				Network::processBroadcastData($result["result"][static::REPONSE_KEY_BROADCAST]);
			}
			catch(Exception $e)
			{
				AddMessage2Log(array($e->getMessage(), $e->getFile(), $e->getLine()));
			}
			unset($result["result"][static::REPONSE_KEY_BROADCAST]);
		}

		return $result;
	}

	protected function prepareRequest(array $request)
	{
		$request["broadcast_last_check"] = Network::getLastBroadcastCheck();
		$request["user_lang"] = LANGUAGE_ID;
		$request["auth"] = $this->access_token;

		return $this->convertRequest($request);
	}

	protected function convertRequest(array $request)
	{
		global $APPLICATION;

		return $APPLICATION->ConvertCharsetArray($request, LANG_CHARSET, 'utf-8');
	}

	public function call($methodName, $additionalParams = null)
	{
		if(!is_array($additionalParams))
		{
			$additionalParams = array();
		}

		$request = $this->prepareRequest($additionalParams);

		$http = new \Bitrix\Main\Web\HttpClient(array(
			'socketTimeout' => $this->httpTimeout,
			'streamTimeout' => $this->httpTimeout,
		));
		$result = $http->post(
			CBitrix24NetOAuthInterface::NET_URL.self::SERVICE_URL.$methodName,
			$request
		);

		try
		{
			$res = $this->prepareResponse($result);
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			$res = false;
		}

		if(!$res)
		{
			AddMessage2Log('Strange answer from Network! '.$http->getStatus().' '.$result);
		}

		return $res;
	}

	public function batch($actions)
	{
		$arBatch = array();

		if(is_array($actions))
		{
			foreach($actions as $query_key => $arCmd)
			{
				list($cmd, $arParams) = array_values($arCmd);
				$arBatch['cmd'][$query_key] = $cmd.(is_array($arParams) ? '?'.http_build_query($arParams) : '');
			}
		}

		return $this->call(self::METHOD_BATCH, $arBatch);
	}

	public function getMethods()
	{
		return $this->call(self::METHOD_METHODS);
	}

	public function getProfile()
	{
		return $this->call(self::METHOD_PROFILE);
	}

	public function addProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_ADD, $arFields);
	}

	public function checkProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_ADD_CHECK, $arFields);
	}

	public function updateProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_UPDATE, $arFields);
	}

	public function deleteProfile($ID)
	{
		return $this->call(self::METHOD_PROFILE_DELETE, array("ID" => $ID));
	}

	public function getProfileContacts($userId)
	{
		return $this->call(self::METHOD_PROFILE_CONTACTS, array("USER_ID" => $userId));
	}

	/**
	 * Restore user profile password
	 * @param int $userId User id whom password should be restored.
	 * @param string $restoreMethod Restore method (via email or via phone).
	 * @return mixed
	 */
	public function restoreProfilePassword($userId, $restoreMethod)
	{
		return $this->call(self::METHOD_PROFILE_RESTORE_PASSWORD, array("USER_ID" => $userId, 'RESTORE_METHOD' => $restoreMethod, 'LANGUAGE_ID' => LANGUAGE_ID));
	}
}

class CBitrix24NetPortalTransport extends CBitrix24NetTransport
{
	protected $clientId = null;
	protected $clientSecret = null;

	public static function init()
	{
		$result = parent::init();

		if(!$result)
		{
			$interface = new CBitrix24NetOAuthInterface();
			if($interface->getAppID())
			{
				$result = new self($interface->getAppID(), $interface->getAppSecret());
			}
		}

		return $result;
	}

	public function __construct($clientId, $clientSecret)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;

		return parent::__construct('');
	}

	protected function prepareRequest(array $request)
	{
		$request["client_id"] = $this->clientId;
		$request["client_secret"] = $this->clientSecret;

		return $this->convertRequest($request);
	}

}
