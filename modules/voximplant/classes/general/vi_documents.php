<?
IncludeModuleLangFile(__FILE__);

use Bitrix\VoxImplant as VI;
use Bitrix\Main\Localization\Loc;

class CVoxImplantDocuments
{
	const STATUS_REQUIRED = 'REQUIRED';
	const STATUS_IN_PROGRESS = 'IN_PROGRESS';
	const STATUS_VERIFIED = 'VERIFIED';

	private $error = null;

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function GetUploadData()
	{
		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetDocumentAccess();
		if (!$result)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		return Array(
			'ACCOUNT_ID' => $result->ACCOUNT_ID,
			'SESSION_ID' => $result->SESSION_ID,
			'ADMIN_NAME' => $result->ADMIN_NAME,
		);
	}

	/**
	 * Returns URL of the iframe for the document upload.
	 * @param string $country_code 2-letter country code.
	 * @param string $address_type Type of the address (i.e. LOCAL), returned by CVox
	 * @param string $phone_category
	 * @param string $phone_region_code
	 * @param string $phone_region_id
	 * @return string
	 */
	public function  GetUploadUrl($country_code = 'RU', $address_type = null, $phone_category = null, $phone_region_code = null, $phone_region_id = null)
	{
		if($country_code == 'RU')
		{
			$params = $this->GetUploadData();
			return 'https://verify.voximplant.com/verification_ru?account_id='.$params['ACCOUNT_ID'].'&admin_user_name='.$params['ADMIN_NAME'].'&verification_name='.$country_code.'&session_id='.$params['SESSION_ID'].'&vendor=bitrix';
		}
		else
		{
			$accessData = $this->GetUploadData();
			if($accessData === false)
				return false;

			$params['account_id'] = $accessData['ACCOUNT_ID'];
			$params['admin_user_name'] = $accessData['ADMIN_NAME'];
			$params['session_id'] = $accessData['SESSION_ID'];
			$params['country_code'] = $country_code;
			if($address_type)
				$params['address_type'] = $address_type;

			if($phone_category)
				$params['phone_category'] = $phone_category;

			if($phone_region_code)
				$params['phone_region_code'] = $phone_region_code;

			if($phone_region_id)
				$params['phone_region_id'] = $phone_region_id;

			$language = Bitrix\Main\Context::getCurrent()->getLanguage();
			if(in_array($language, array('ru', 'de', 'en')))
				$params['_lang'] = $language;
			else
				$params['_lang'] = 'en';
			
			$query = http_build_query($params);

			if(defined('VOXIMPLANT_DEVAPI') && VOXIMPLANT_DEVAPI === true)
				$result = 'http://devapi.voximplant.com:8093/?'.$query;
			else
				$result = 'https://verify.voximplant.com/additional/?'.$query;

			return $result;
		}
	}

	public function GetAdditionalUploadUrl($verificationId)
	{
		$accessData = $this->GetUploadData();
		if($accessData === false)
			return false;

		$params['account_id'] = $accessData['ACCOUNT_ID'];
		$params['admin_user_name'] = $accessData['ADMIN_NAME'];
		$params['regulation_address_id'] = $verificationId;
		$params['session_id'] = $accessData['SESSION_ID'];

		$language = Bitrix\Main\Context::getCurrent()->getLanguage();
		if (in_array($language, ['ru', 'de', 'en'], true))
		{
			$params['_lang'] = $language;
		}
		else
		{
			$params['_lang'] = 'en';
		}

		$query = http_build_query($params);
		return 'https://verify.voximplant.com/?'.$query;
	}

	public function GetStatus()
	{
		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetDocumentStatus();
		if (!$result)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		$verifications = Array();
		foreach ($result as $key => $verification)
		{
			$regionName = GetMessage('VI_DOCS_COUNTRY_'.$verification->REGION);
			$regionName = $regionName <> ''? $regionName: $verification->REGION;

			$verifications[$key]['REGION'] = $verification->REGION;
			$verifications[$key]['REGION_NAME'] = $regionName;
			$verifications[$key]['STATUS'] = $verification->STATUS;
			$verifications[$key]['STATUS_NAME'] = static::GetStatusName($verification->STATUS);

			if ($verification->STATUS != 'VERIFIED' && $verification->UNVERIFIED_HOLD_UNTIL)
			{
				$data = new Bitrix\Main\Type\DateTime($verification->UNVERIFIED_HOLD_UNTIL, 'Y-m-d H:i:s', new DateTimeZone('UTC'));
				$verifications[$key]['UNVERIFIED_HOLD_UNTIL'] = $data->toString();
			}
			else
			{
				$verifications[$key]['UNVERIFIED_HOLD_UNTIL'] = '';
			}

			if (isset($verification->DOCUMENTS))
			{
				foreach ($verification->DOCUMENTS as $document)
				{
					$data = new Bitrix\Main\Type\DateTime($document->UPLOADED, 'Y-m-d H:i:s', new DateTimeZone('UTC'));

					$verifications[$key]['DOCUMENTS'][] = array(
						'UPLOADED' => $data->toString(),
						'DOCUMENT_ID' => $document->DOCUMENT_ID,
						'DOCUMENT_STATUS' => $document->DOCUMENT_STATUS,
						'DOCUMENT_STATUS_NAME' => GetMessage('VI_DOCS_DOCUMENT_STATUS_'.$document->DOCUMENT_STATUS) ?: $document->DOCUMENT_STATUS,
						'IS_INDIVIDUAL' => $document->IS_INDIVIDUAL,
						'IS_INDIVIDUAL_NAME' => GetMessage('VI_DOCS_IS_INDIVIDUAL_'.$document->IS_INDIVIDUAL),
						'REVIEWER_COMMENT' => $document->REVIEWER_COMMENT,
					);
				}
			}
		}

		return $verifications;
	}

	public function SetVerifyResult($params)
	{
		if ($params['STATUS'] == 'VERIFIED')
		{
			$phoneVerified = Array();
			$orm = VI\ConfigTable::getList(Array(
				'filter'=>Array(
					'=PHONE_COUNTRY_CODE' => $params['REGION']
				)
			));
			while($config = $orm->fetch())
			{
				VI\ConfigTable::update($config['ID'], Array('PHONE_VERIFIED' => 'Y'));
				$phoneVerified[] = $config['PHONE_NAME'];
			}

			if (!empty($phoneVerified))
			{
				CVoxImplantHistory::WriteToLog($phoneVerified, 'VERIFY PHONES');
			}
		}

		return true;
	}

	/**
	 * Notifies user, that sent documents, about the finishing of the verification process.
	 * @param array $params Array of parameters of the callback.
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function notifyUserWithVerifyResult(array $params)
	{
		if(!\Bitrix\Main\Loader::includeModule('im'))
			return;

		$userId = $this->getFilledByUser();
		if($userId === false)
			return;

		if(!isset($params['STATUS']) || !($params['STATUS'] === 'VERIFIED' || $params['STATUS'] === 'REQUIRED'))
			return;

		$phoneManageUrl = \CVoxImplantHttp::GetServerAddress().\CVoxImplantMain::GetPublicFolder().'lines.php';

		$attach = new \CIMMessageParamAttach(null, "#95c255");
		$attach->AddGrid(array(
			array(
				"NAME" => Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY_HEAD_'.$params['STATUS']),
				"VALUE" => Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY_BODY_'.$params['STATUS'], array('#REJECT_REASON#' => $params['REVIEWER_COMMENT'])),
			)
		));
		$attach->AddLink(array(
			"NAME" => Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY_LINK_'.$params['STATUS']),
			"LINK" => $phoneManageUrl
		));

		$messageFields = array(
			"TO_USER_ID" => $userId,
			"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
			"MESSAGE" => Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY'),
			"MESSAGE_OUT" => Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY_HEAD_'.$params['STATUS'])." ".Loc::getMessage('DOCUMENTS_VERIFICATION_NOTIFY_BODY_'.$params['STATUS']).": ".$phoneManageUrl,
			"ATTACH" => Array($attach)
		);

		$mess = \CIMNotify::Add($messageFields);
	}

	/**
	 * Stores ID of the user, who was the last to fill documents.
	 * @param int $userId Id of the user.
	 * @return void
	 */
	public function setFilledByUser($userId)
	{
		$userId = (int)$userId;
		if($userId === 0)
			return;

		\Bitrix\Main\Config\Option::set('voximplant', 'documents_filled_by', $userId);
	}

	/**
	 * Returns ID of the user, who was the last to fill documents.
	 * @return int|false User ID or false if not set.
	 */
	public function getFilledByUser()
	{
		$lastFilledBy = (int)\Bitrix\Main\Config\Option::get('voximplant', 'documents_filled_by');
		return ($lastFilledBy > 0 ? $lastFilledBy : false);
	}


	public static function GetStatusName($status)
	{
		return GetMessage('VI_DOCS_STATUS_'.$status) ?: $status;
	}

	public function GetError()
	{
		return $this->error;
	}
}
?>
