<?

use Bitrix\Voximplant\Result;
use Bitrix\Main\Error;

class CVoxImplantHttp
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';
	const VERSION = 20;

	const CONTROLLER_RU = 'https://telephony-ru.bitrix.info/telephony/portal.php';
	const CONTROLLER_OTHER = 'https://telephony.bitrix.info/telephony/portal.php';

	private $licenceCode = '';
	private $domain = '';
	private $type = '';
	private $error = null;

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');

		if(defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		else if(defined('VOXIMPLANT_HOST_NAME'))
		{
			$this->licenceCode = VOXIMPLANT_HOST_NAME;
		}
		else
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
			$this->licenceCode = md5("BITRIX".CUpdateClient::GetLicenseKey()."LICENCE");
		}
		$this->type = self::GetPortalType();
		$this->domain = self::GetServerAddress();

		return true;
	}

	public static function GetControllerUrl()
	{
		if (defined('VOXIMPLANT_CONTROLLER_URL'))
		{
			return VOXIMPLANT_CONTROLLER_URL;
		}

		$account = new CVoxImplantAccount();
		$accountLang = $account->GetAccountLang(false);
		if ($accountLang === "kz" || $accountLang === "ru")
		{
			return static::CONTROLLER_RU;
		}
		return static::CONTROLLER_OTHER;
	}

	public static function GetPortalType()
	{
		$type = '';
		if(defined('BX24_HOST_NAME') || defined('VOXIMPLANT_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}
		return $type;
	}

	public function GetAccountInfo(array $params = [])
	{
		$query = $this->Query('GetAccountInfo', $params);
		if (isset($query->error))
		{
			$error = (array)$query->error;
			$this->error = new CVoxImplantError(__METHOD__, $error['code'] ?? '', $error['msg'] ?? '');

			return false;
		}

		return $query;
	}

	public function GetPhoneNumberCategories($countryCode = '')
	{
		$query = $this->Query(
			'GetPhoneNumberCategories',
			Array('COUNTRY_CODE' => $countryCode)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumberCountryStates($phoneCategoryName, $countryCode, $countryState = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY_STATE' => $countryState,
		);

		$query = $this->Query(
			'GetPhoneNumberCountryStates',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumberRegions($phoneCategoryName, $countryCode, $countryState = '', $phoneRegionName = '', $phoneRegionCode = '',  $phoneRegionId = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY_STATE' => $countryState,
			'PHONE_REGION_NAME' => $phoneRegionName,
			'PHONE_REGION_CODE' => $phoneRegionCode,
			'PHONE_REGION_ID' => $phoneRegionId,
		);

		$query = $this->Query(
			'GetPhoneNumberRegions',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	/**
	 * @param array $parameters
	 * <li> LIMIT max count of numbers to return
	 *
	 * @return bool|object
	 */
	public function GetPhoneNumbers(array $parameters = [])
	{
		$query = $this->Query(
			'GetPhoneNumbers',
			$parameters
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function ClearConfigCache()
	{
		$query = $this->Query('ClearConfigCache', [], [
			'returnArray' => true,
		]);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error['code'], $query->error['msg']);

			return false;
		}

		return $query;
	}

	public function StartOutgoingCall($userId, $phoneNumber, array $additionalParams = array())
	{
		if(\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		$params = array(
			'TYPE' => 'phone',
			'USER_ID' => intval($userId),
			'NUMBER' => $phoneNumber,
			'IP' => Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRemoteAddress()
		);
		$params = $params + $additionalParams;
		$query = $this->Query('StartOutgoingCall', $params);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function StartCallBack($callbackFromLine, $callbackNumber, $textToPronounce, $voice)
	{
		if(\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		$query = $this->Query(
			'StartCallBack',
			Array(
				'CALLBACK_NUMBER' => $callbackNumber,
				'CALLBACK_FROM_LINE' => $callbackFromLine,
				'TEXT' => $textToPronounce,
				'VOICE' => $voice
			)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function StartInfoCall($number, $text, array $options, $lineConfig)
	{
		if(\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		$query = $this->Query(
			'StartInfoCall',
			Array(
				'LINE_CONFIG' => $lineConfig,
				'NUMBER' => $number,
				'TEXT' => $text,
				'OPTIONS' => $options,
			)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetNewPhoneNumbers($phoneCategoryName, $countryCode, $phoneRegionId, $offset = 0, $count = 20, $countryState = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'PHONE_REGION_ID' => $phoneRegionId,
			'OFFSET' => intval($offset),
			'COUNT' => intval($count),
			'COUNTRY_STATE' => $countryState,
		);

		$query = $this->Query(
			'GetNewPhoneNumbers',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	/**
	 * @param array $params
	 *  <li> string categoryName Name of the number category.
	 *  <li> string countryCode 2-letter country code.
	 *  <li> integer regionId Region id.
	 *  <li> array|string number Phone number(s) to attach (if numbers could be selected for the current region and category).
	 *  <li> integer count Count of phone numbers to attach (if numbers could not be select for the current region and category).
	 *  <li> bool singleSubscription
	 *  <li> countryState (Optional)
	 *  <li> addressVerification
	 * @return bool|object
	 */
	public function AttachPhoneNumber(array $params)
	{
		$request = Array(
			'PHONE_CATEGORY_NAME' => $params['categoryName'],
			'COUNTRY_CODE' => $params['countryCode'],
			'PHONE_REGION_ID' => $params['regionId'],
			'PHONE_NUMBER' => $params['number'],
			'PHONE_COUNT' => $params['count'],
			'SINGLE_SUBSCRIPTION' => $params['singleSubscription'] ? 'Y' : 'N',
			'COUNTRY_STATE' => $params['countryState'],
			'ADDRESS_VERIFICATION' => $params['addressVerification']
		);
		$query = $this->Query(
			'AttachPhoneNumber',
			$request,
			[
				"streamTimeout" => 60
			]
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DeactivateSubscription($subscriptionId)
	{
		$query = $this->Query(
			'DeactivateSubscription',
			Array('SUBSCRIPTION_ID' => $subscriptionId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function CancelDeactivateSubscription($subscriptionId)
	{
		$query = $this->Query(
			'CancelDeactivateSubscription',
			Array('SUBSCRIPTION_ID' => $subscriptionId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DeactivatePhoneNumber($phoneNumber)
	{
		$query = $this->Query(
			'DeactivatePhoneNumber',
			Array('PHONE_NUMBER' => $phoneNumber)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function CancelDeactivatePhoneNumber($phoneNumber)
	{
		$query = $this->Query(
			'CancelDeactivatePhoneNumber',
			Array('PHONE_NUMBER' => $phoneNumber)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneOrderStatus()
	{
		$query = $this->Query(
			'GetPhoneOrderStatus'
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddPhoneOrder($params)
	{
		$query = $this->Query(
			'AddPhoneOrder',
			Array('FORM_DATA' => Bitrix\Main\Web\Json::encode($params))
		);

		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddServiceOrder($params)
	{
		$query = $this->Query(
			'AddServiceOrder',
			Array('FORM_DATA' => Bitrix\Main\Web\Json::encode($params))
		);

		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetUser($userId, $getPhoneAccess = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$query = $this->Query(
			'GetUser',
			Array('USER_ID' => $userId, 'GET_PHONE_ACCESS' => $getPhoneAccess? 'Y': 'N')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetUsers($userId, $multiply = true)
	{
		if (!is_array($userId))
			$userId = Array($userId);

		foreach ($userId as $key => $value)
		{
			$userId[$key] = intval($value);
		}

		$query = $this->Query(
			'GetUsers',
			Array('USER_ID' => implode('|', $userId), 'MULTIPLY' => $multiply? 'Y': 'N')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function UpdateUserPassword($userId, $mode, $password = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$query = $this->Query(
			'UpdateUserPassword',
			Array('USER_ID' => $userId, 'MODE' => $mode, 'PASSWORD' => $password? $password: '')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetSipInfo()
	{
		$query = $this->Query(
			'GetSipInfo',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}
		return $query;
	}

	public function GetSipParams($configId)
	{
		$configId = intval($configId);

		$query = $this->Query(
			'GetSipParams',
			Array('CONFIG_ID' => $configId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}
		return $query;
	}

	public function GetOnlineUsers()
	{
		$query = $this->Query(
			'GetOnlineUsers',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetCallHistory($filter = Array(), $limit = 20, $page = 1)
	{
		$arFilter = Array('LIMIT' => intval($limit), 'PAGE' => intval($page));
		if (isset($filter['LAST_ID']))
			$arFilter['LAST_ID'] = intval($filter['LAST_ID']);

		$query = $this->Query(
			'GetCallHistory',
			$arFilter
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function CreateSipRegistration($server, $login, $password = '', $authUser = '', $outboundProxy = '')
	{
		if (mb_strlen($server) <= 3)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'SERVER_INCORRECT', 'Server is not correct');
			return false;
		}
		if ($login == '')
		{
			$this->error = new CVoxImplantError(__METHOD__, 'LOGIN_INCORRECT', 'Login is not correct');
			return false;
		}

		$query = $this->Query(
			'CreateSipRegistration',
			Array('SERVER' => $server, 'LOGIN' => $login, 'PASSWORD' => $password, 'AUTH_USER' => $authUser, 'OUTBOUND_PROXY' => $outboundProxy)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function UpdateSipRegistration($regId, $server, $login, $password = '', $authUser = '', $outboundProxy = '')
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}
		if (mb_strlen($server) <= 3)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'SERVER_INCORRECT', 'Server is not correct');
			return false;
		}
		if ($login == '')
		{
			$this->error = new CVoxImplantError(__METHOD__, 'LOGIN_INCORRECT', 'Login is not correct');
			return false;
		}

		$query = $this->Query(
			'UpdateSipRegistration',
			Array('REG_ID' => $regId, 'SERVER' => $server, 'LOGIN' => $login, 'PASSWORD' => $password, 'AUTH_USER' => $authUser, 'OUTBOUND_PROXY' => $outboundProxy)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DeleteSipRegistration($regId)
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}

		$query = $this->Query(
			'DeleteSipRegistration',
			Array('REG_ID' => $regId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetSipRegistrations($regId)
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}

		$query = $this->Query(
			'GetSipRegistrations',
			Array('REG_ID' => $regId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddCallerID($number)
	{
		if (mb_strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'AddCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DelCallerID($number)
	{
		if (mb_strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'DelCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetCallerIDs($number = '')
	{
		if ($number > 0 && mb_strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'GetCallerIDs',
			$number > 0? Array('NUMBER' => $number): Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function VerifyCallerID($number)
	{
		if (mb_strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'VerifyCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function ActivateCallerID($number, $code)
	{
		if (mb_strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}
		if ($code == '')
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CODE_INCORRECT', 'Code for activation is not correct');
			return false;
		}

		$query = $this->Query(
			'ActivateCallerID',
			Array('NUMBER' => $number, 'CODE' => $code)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetDocumentAccess()
	{
		$query = $this->Query(
			'GetDocumentAccess',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetAvailableVerifications($countryCode, $categoryName, $regionCode = '')
	{
		$parameters = array(
			'COUNTRY_CODE' => $countryCode,
			'CATEGORY_NAME' => $categoryName,
			'REGION_CODE' => $regionCode
		);

		$query = $this->Query(
				'GetAvailableVerifications',
				$parameters
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetVerifications($countryCode = '', $phoneCategoryName = '', $phoneRegionCode = '', $verified = null, $inProgress = null)
	{
		$parameters = array(
			'COUNTRY_CODE' => $countryCode,
			'CATEGORY_NAME' => $phoneCategoryName,
			'REGION_CODE' => $phoneRegionCode,
			'VERIFIED' => $verified,
			'IN_PROGRESS' => $inProgress,
		);

		$query = $this->Query(
			'GetVerifications',
			$parameters,
			['returnArray' => true]
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;

	}

	public function GetDocumentStatus()
	{
		$query = $this->Query(
			'GetDocumentStatus',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		foreach ($query as $key => $verification)
		{
			if (isset($verification->DOCUMENTS))
			{
				foreach ($verification->DOCUMENTS as $id => $document)
				{
					$query[$key]->DOCUMENTS[$id]->REVIEWER_COMMENT = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($document->REVIEWER_COMMENT);
				}
			}
		}

		return $query;
	}

	public function enqueueCallback(array $parameters, $executeAt)
	{
		return $this->Query('EnqueueCallback', array(
			'EXEC_AT' => $executeAt,
			'PARAMETERS' => $parameters
		));
	}

	public function setCommonBackupNumber($number, $redirectConfig)
	{
		return $this->Query('setBackupNumber', array(
			'TYPE' => CVoxImplantConfig::BACKUP_NUMBER_COMMON,
			'BACKUP_NUMBER' => $number,
			'REDIRECT_CONFIG' => $redirectConfig
		));
	}

	public function setLineBackupNumber($lineId, $number, $redirectConfig)
	{
		return $this->Query('setBackupNumber', array(
			'TYPE' => CVoxImplantConfig::BACKUP_NUMBER_SPECIFIC,
			'LINE_ID' => $lineId,
			'BACKUP_NUMBER' => $number,
			'REDIRECT_CONFIG' => $redirectConfig
		));
	}

	public function deleteBackupNumber($type)
	{
		return $this->Query('deleteBackupNumber', array(
			'TYPE' => $type,
		));
	}

	public function sendClosingDocumentsRequest($period, $addressIndex, $address, $email)
	{
		$query = $this->Query('sendClosingDocumentsRequest', array(
			'PERIOD' => $period,
			'ADDRESS_INDEX' => $addressIndex,
			'ADDRESS' => $address,
			'EMAIL' => $email
		));

		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function listInvoices(array $filter = [])
	{
		return $this->Query("listInvoices", $filter, ['returnArray' => true]);
	}

	public function generateInvoice($invoiceNumber)
	{
		return $this->Query("generateInvoice", ["INVOICE_NUMBER" => $invoiceNumber], ['returnRaw' => true]);
	}

	public function getBillingUrl($userId = 0)
	{
		global $USER;
		$userId = (int)$userId;
		if (!$userId && $USER)
		{
			$userId = (int)$USER->getId();
		}

		return $this->Query("getBillingUrl", ['USER_ID' => $userId], ['returnArray' => true]);
	}

	public function getDocumentUploadUrl(string $countryCode)
	{
		return $this->Query("getDocumentUploadUrl", ['COUNTRY_CODE' => $countryCode], ['returnArray' => true]);
	}

	public function saveTOSConsent($ipAddress, $userAgent, $userId = 0)
	{
		global $USER;
		$userId = (int)$userId;
		if (!$userId && $USER)
		{
			$userId = (int)$USER->getId();
		}

		$account = new CVoxImplantAccount();
		$accountLang = $account->GetAccountLang(false);

		return $this->Query(
			"saveConsent",
			[
				"USER_ID" => $userId,
				"IP_ADDRESS" => $ipAddress,
				"USER_AGENT" => $userAgent,
				"CONSENT_TYPE" => $accountLang === 'ru' ? "TOS_RU" : "TOS_EN"
			],
			['returnArray' => true]
		);
	}

	public function GetError()
	{
		return $this->error;
	}

	private function Query($command, $params = array(), $options = array())
	{
		if ($command == '' || !is_array($params))
		{
			return false;
		}

		$returnArray = isset($options['returnArray']) && $options['returnArray'] === true;
		$returnRaw = isset($options['returnRaw']) && $options['returnRaw'] === true;

		$params['BX_COMMAND'] = $command;
		$params['BX_LICENCE'] = $this->licenceCode;
		$params['BX_DOMAIN'] = $this->domain;
		$params['BX_TYPE'] = $this->type;
		$params['BX_VERSION'] = self::VERSION;

		foreach ($params as $k => $v)
		{
			if(is_null($params[$k]))
				$params[$k] = '';
		}

		$params["BX_HASH"] = self::RequestSign($this->type, md5(implode("|", $params)));

		$httpClient = \Bitrix\Voximplant\HttpClientFactory::create([
			'socketTimeout' => (int)($options['socketTimeout'] ?? 10),
			'streamTimeout' => (int)($options['streamTimeout'] ?? 30),
			'disableSslVerification' => true,
		]);
		$httpClient->setHeader('User-Agent', 'Bitrix Telephony');
		$httpClient->setCharset(\Bitrix\Main\Context::getCurrent()->getCulture()->getCharset());
		$result = $httpClient->query('POST', static::GetControllerUrl(), $params);

		if (!$result)
		{
			CVoxImplantHistory::WriteToLog($result, 'ERROR QUERY EXECUTE');
			return (object)array('error' => array('code' => 'CONNECT_ERROR', 'msg' => 'Parse error or connect error from server'));
		}

		if ($httpClient->getStatus() !== 200)
		{
			CVoxImplantHistory::WriteToLog($result, 'ERROR QUERYING CONTROLLER, RESPONSE STATUS ' . $httpClient->getStatus());
			return (object)array('error' => array('code' => 'CONNECT_ERROR', 'msg' => 'Parse error or connect error from server'));
		}

		$response = $httpClient->getResult();
		if($returnRaw)
		{
			// check for errors

			try
			{
				if($response != "" && $response[0] == "{")
				{
					$decodedResponse = \Bitrix\Main\Web\Json::decode($response);
					if(isset($decodedResponse['error']))
					{
						$this->error = new CVoxImplantError(__METHOD__, $decodedResponse['error']['code'], $decodedResponse['error']['msg']);
						return false;
					}
				}
			}
			catch (\Bitrix\Main\ArgumentException $e)
			{
			}

			return $response;
		}
		else if($returnArray)
		{
			try
			{
				$decodedResponse = \Bitrix\Main\Web\Json::decode($response);
			}
			catch (\Bitrix\Main\ArgumentException $e)
			{
				CVoxImplantHistory::WriteToLog($response, 'ERROR QUERY EXECUTE');
				return array('error' => array('code' => 'CONNECT_ERROR', 'msg' => $e->getMessage()));
			}
		}
		else
		{
			$decodedResponse = json_decode($response);
			if (!$decodedResponse)
			{
				CVoxImplantHistory::WriteToLog($response, 'ERROR QUERY EXECUTE');
				return (object)array('error' => array('code' => 'CONNECT_ERROR', 'msg' => 'Parse error or connect error from server'));
			}
		}

		return $decodedResponse;
	}

	public static function RequestSign($type, $str)
	{
		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			$LICENSE_KEY = "";
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			return md5($str.md5($LICENSE_KEY));
		}
	}

	public static function CheckDirectRequest()
	{
		$rawRequest = \Bitrix\Main\Context::getCurrent()->getRequest()->getInput();

		return md5($rawRequest . "|" . self::GetPortalSign());
	}
	
	public static function GetPortalSign()
	{
		if( (defined('BX24_HOST_NAME') && function_exists('bx_sign')) || defined('VOXIMPLANT_HOST_NAME') )
		{
			return self::RequestSign(self::TYPE_BITRIX24, defined('BX24_HOST_NAME')? md5(BX24_HOST_NAME): md5(VOXIMPLANT_HOST_NAME));
		}
		else
		{
			return self::RequestSign(self::TYPE_CP, 'DIRECT CONNECT SIGN');
		}
	}

	public static function GetPortalUrl()
	{
		return CVoxImplantHttp::GetServerAddress().'/bitrix/tools/voximplant/receiver.php?b24_direct=y';
	}

	public static function GetServerAddress()
	{
		$publicUrl = COption::GetOptionString("voximplant", "portal_url", '');

		if ($publicUrl != '')
			return $publicUrl;
		else
			return (CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
	}

	public function GetSipRegistrationList(array $params = [])
	{
		$query = $this->Query('GetSipRegistrationList', $params);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function checkPortalVisibility(): Result
	{
		$result = new Result();
		$result->setData(['isVisible' => false]);
		if ($this->checkIsDomainLocal())
		{
			$result->addError(new Error('', 'VI_PORTAL_URL_IS_LOCAL'));

			return $result;
		}

		if ($this->checkIsDomainWithProtocol() === false)
		{
			$result->addError(new Error('', 'VI_PORTAL_URL_WITHOUT_PROTOCOL'));

			return $result;
		}

		$query = $this->Query('checkPortalVisibility', [], [
			'returnArray' => true,
		]);
		if (isset($query->error))
		{
			$result->addError(new Error($query->error['msg'], $query->error['code']));

			return $result;
		}

		$result->setData(['isVisible' => true]);

		return $result;
	}

	private function checkIsDomainLocal()
	{
		$parsedUrl = parse_url($this->domain);
		$host = $parsedUrl['host'] ?? '';

		$isLocalhost = strtolower($host) === 'localhost';
		$isDefaultIP = $host === '0.0.0.0';
		$isLocalIP = (
			preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)
			&& preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
		);

		return $isLocalhost || $isDefaultIP || $isLocalIP;
	}

	private function checkIsDomainWithProtocol(): bool
	{
		return (
			mb_strpos($this->domain, 'http://') !== false
			|| mb_strpos($this->domain, 'https://') !== false
		);
	}
}
?>
