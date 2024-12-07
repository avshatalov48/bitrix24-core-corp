<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;
use Bitrix\Im as IM;

class CVoxImplantMain
{
	const CALL_OUTGOING = 1;
	const CALL_INCOMING = 2;
	const CALL_INCOMING_REDIRECT = 3;
	const CALL_CALLBACK = 4;
	const CALL_INFO = 5;

	private $userId = 0;
	private $error = null;

	function __construct($userId)
	{
		$this->userId = intval($userId);
		if ($this->userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID', 'USER ID is not correct');
		}
		else
		{
			$this->error = new CVoxImplantError(null, '', '');
		}
	}

	public static function Enable($number = '')
	{
		$enable = !IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser();
		if ($enable && $number <> '')
		{
			if (!CVoxImplantPhone::Normalize($number))
				$enable = false;
		}

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if($enable && !$userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM)
		)
		{
			$enable = false;
		}

		return $enable;
	}

	public function ClearUserInfo()
	{
		$ViUser = new CVoxImplantUser();
		$ViUser->ClearUserInfo($this->userId);
	}

	public function ClearAccountInfo()
	{
		$ViAccount = new CVoxImplantAccount();
		$ViAccount->ClearAccountInfo();
	}

	public function	GetDialogInfo($phone, $chatTitle = '', $getPhoto = true)
	{
		$phoneNormalize = CVoxImplantPhone::Normalize($phone);
		if (!$phoneNormalize)
		{
			$phoneNormalize = preg_replace("/[^0-9\#\*]/i", "", $phone);
		}
		$phone = $phoneNormalize;

		$hrPhoto = [];

		$openChat = true;
		$result = VI\PhoneTable::getList([
			'select' => ['USER_ID', 'PHONE_MNEMONIC'],
			'filter' => [
				'=PHONE_NUMBER' => $phone,
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_REAL_USER' => 'Y'
			]
		]);

		$userId = false;
		while ($row = $result->fetch())
		{
			if (!$userId && $row['PHONE_MNEMONIC'] != 'WORK_PHONE' )
			{
				$userId = $row['USER_ID'];
				$openChat = false;
			}
			else if (!$userId && $row['PHONE_MNEMONIC'] == 'WORK_PHONE' )
			{
				$openChat = true;
			}
		}

		if ($userId == $this->userId)
		{
			$openChat = true;
		}

		$dialogId = 0;
		$isUnified = false;
		if (!CModule::IncludeModule('im'))
		{
			return false;
		}

		if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_NONE)
		{
		}
		else if ($openChat)
		{
			$entityId = $phone;
			if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_APPEND)
			{
				$entityId = 'UNIFY_CALL_CHAT';
				$chatTitle = GetMessage('VI_CALL_CHAT_UNIFY');
				$isUnified = true;
			}
			$result = IM\Model\ChatTable::getList(Array(
				'select' => Array('ID', 'AVATAR'),
				'filter' => Array('=ENTITY_TYPE' => 'CALL', '=ENTITY_ID' => $entityId, '=AUTHOR_ID' => $this->userId)
			));

			if ($row = $result->fetch())
			{
				$dialogId = 'chat'.$row['ID'];
				$avatarId = $row['AVATAR'];
			}
			else
			{
				$CIMChat = new CIMChat($this->userId);
				$chatId = $CIMChat->Add(Array(
					'TITLE' => $chatTitle != ''? $chatTitle: \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($phone)->format(),
					'USERS' => false,
					'CALL_NUMBER' => $entityId == 'UNIFY_CALL_CHAT'? '': $entityId,
					'ENTITY_TYPE' => 'CALL',
					'ENTITY_ID' => $entityId,
				));
				if ($chatId)
				{
					$dialogId = 'chat'.$chatId;
					$avatarId = $CIMChat->lastAvatarId;
				}
			}
			if ($getPhoto && intval($avatarId) > 0)
			{
				$arPhotoHrTmp = CFile::ResizeImageGet(
					$avatarId,
					array('width' => 200, 'height' => 200),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				$hrPhoto[$dialogId] = empty($arPhotoHrTmp['src'])? '/bitrix/js/im/images/hidef-avatar-v2.png': $arPhotoHrTmp['src'];
			}
		}
		else if ($userId)
		{
			if ($getPhoto)
			{
				$userData = CIMContactList::GetUserData(Array('ID' => $userId, 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));
				$hrPhoto = $userData['hrphoto'];
			}
			$dialogId = $userId;
		}


		if (!$dialogId)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'ERROR_NEW_CHAT', GetMessage('VI_ERROR_NEW_CHAT'));
			return false;
		}

		//foreach(GetModuleEvents("voximplant", "OnGetDialogInfo", true) as $arEvent)
		//	ExecuteModuleEventEx($arEvent, array('USER_ID' => $this->userId, 'DIALOG_ID' => $dialogId));

		return Array(
			'DIALOG_ID' => $dialogId,
			'HR_PHOTO' => $hrPhoto,
			'UNIFIED' => $isUnified,
		);
	}

	public function	SendChatMessage($dialogId, $incomingType, $messageText, $attach = null)
	{
		if ($dialogId == '' || ($messageText == '' && is_null($attach)))
			return false;

		if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_NONE)
			return false;

		if (!CModule::IncludeModule('im'))
			return false;

		// TODO CHECK NULL USER BEFORE SEND

		$chatId = 0;
		if (mb_substr($dialogId, 0, 4) == 'chat')
		{
			$chatId = intval(mb_substr($dialogId, 4));

			$message = Array(
				"FROM_USER_ID" => ($incomingType == CVoxImplantMain::CALL_OUTGOING ? $this->userId : 0),
				"TO_CHAT_ID" => $chatId,
				"SYSTEM" => 'Y',
				"ATTACH" => $attach,
			);

			if($messageText != '')
				$message['MESSAGE'] =  $messageText;

			if(!is_null($attach))
				$message['ATTACH'] = $attach;

			CIMChat::AddMessage($message);
		}
		else if (intval($dialogId) > 0)
		{
			$message = Array(
				"FROM_USER_ID" => ($incomingType == CVoxImplantMain::CALL_OUTGOING ? $this->userId : intval($dialogId)),
				"TO_USER_ID" => ($incomingType == CVoxImplantMain::CALL_OUTGOING ? intval($dialogId) : $this->userId),
				"SYSTEM" => 'Y',
			);

			if($messageText != '')
				$message['MESSAGE'] =  $messageText;

			if(!is_null($attach))
				$message['ATTACH'] = $attach;

			CIMMessage::Add($message);
		}

		return true;
	}

	public static function UpdateChatInfo($dialogId, array $additionalData)
	{
		if(!CModule::IncludeModule('im'))
			return false;

		if (mb_substr($dialogId, 0, 4) == 'chat')
		{
			$chatId = intval(mb_substr($dialogId, 4));
			$fieldValue = $additionalData['CRM'].'|'.$additionalData['CRM_ENTITY_TYPE'].'|'.$additionalData['CRM_ENTITY_ID'];

			$chatFields = array(
				'ENTITY_DATA_1' => $fieldValue
			);

			if($additionalData['CRM'] == 'Y' && $additionalData['CRM_ENTITY_TYPE'] != '' && $additionalData['CRM_ENTITY_ID'] > 0)
			{
				$entityFields = CVoxImplantCrmHelper::getEntityFields($additionalData['CRM_ENTITY_TYPE'], $additionalData['CRM_ENTITY_ID']);
				if($entityFields)
				{
					$chatFields['TITLE'] = $entityFields['NAME'];
					$chatFields['AVATAR'] = (int)$entityFields['PHOTO'];
				}
			}

			$updateResult = IM\Model\ChatTable::update($chatId, $chatFields)->isSuccess();

			if($updateResult)
			{
				$relationCursor = \Bitrix\Im\Model\RelationTable::getList(array(
					"select" => array("ID", "USER_ID", "EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID"),
					"filter" => array(
						"=CHAT_ID" => $chatId
					),
				));
				while ($relation = $relationCursor->fetch())
				{
					if (
						\Bitrix\Im\User::getInstance($relation['USER_ID'])->isBot() ||
						\Bitrix\Im\User::getInstance($relation['USER_ID'])->isNetwork() ||
						\Bitrix\Im\User::getInstance($relation['USER_ID'])->isConnector()
					)
					{
						continue;
					}
					\CIMContactList::CleanChatCache($relation['USER_ID']);
				}
			}

			return $updateResult;
		}
		return false;
	}

	public function GetAuthorizeInfo($updateInfo = false)
	{
		if(!VI\Integration\Bitrix24::isEmailConfirmed())
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIRMATION_ERROR', GetMessage('VI_ERROR_EMAIL_NOT_CONFIRMED'));
			return false;
		}

		$ViAccount = new CVoxImplantAccount();
		if ($updateInfo)
			$ViAccount->UpdateAccountInfo();

		$ViUser = new CVoxImplantUser();
		$userInfo = $ViUser->GetUserInfo($this->userId);
		if (!$userInfo)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViUser->GetError()->code, GetMessage('VI_GET_USER_INFO', Array('#CODE#' => $ViUser->GetError()->code)));
			return false;
		}

		$userData = CIMContactList::GetUserData(Array('ID' => $this->userId, 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));

		return Array(
			'SERVER' => str_replace('voximplant.com', 'bitrixphone.com', $userInfo['call_server']),
			'LOGIN' => $userInfo['user_login'],
			'HASH' => 	defined('BX_MOBILE')? $userInfo['user_password']: md5(time().randString()),
			'CALLERID' => $userInfo['user_backphone'],
			'HR_PHOTO' => $userData['hrphoto']
		);
	}

	public function GetOneTimeKey($key)
	{
		$ViAccount = new CVoxImplantAccount();
		$accountName = $ViAccount->GetAccountName();
		if (!$accountName)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViAccount->GetError()->code, GetMessage('VI_GET_ACCOUNT_INFO', Array('#CODE#' => $ViAccount->GetError()->code)));
			return false;
		}

		$ViUser = new CVoxImplantUser();
		$userInfo = $ViUser->GetUserInfo($this->userId);
		if (!$userInfo)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViUser->GetError()->code, GetMessage('VI_GET_USER_INFO', Array('#CODE#' => $ViUser->GetError()->code)));
			return false;
		}

		return md5($key."|".md5($userInfo['user_login'].":voximplant.com:".$userInfo['user_password']));
	}

	public static function SendPullEvent($params)
	{
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		if ($params['COMMAND'] == 'start')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"CRM" => $params['CRM']? $params['CRM']: false,
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if($params['COMMAND'] == 'answer_phone')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if($params['COMMAND'] == 'showExternalCall')
		{
			$config = Array(
				'callId' => $params['CALL_ID'],
				'phoneNumber' => $params['PHONE_NUMBER'],
				'lineNumber' => $params['LINE_NUMBER'],
				'companyPhoneNumber' => $params['COMPANY_PHONE_NUMBER'],
				'isCallback' => $params['INCOMING'] == CVoxImplantMain::CALL_CALLBACK,
				'showCrmCard' => $params['SHOW_CRM_CARD'],
				'crmEntityType' => $params['CRM_ENTITY_TYPE'],
				'crmEntityId' => $params['CRM_ENTITY_ID'],
				'crmBindings' => $params['CRM_BINDINGS'],
				'crmActivityId' => $params['CRM_ACTIVITY_ID'] ?? null,
				'crmActivityEditUrl' => $params['CRM_ACTIVITY_EDIT_URL'] ?? null,
				'config' => $params['CONFIG'],
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"CRM" => $params["CRM"] ?: array()
			);
			if($params['INCOMING'] == self::CALL_INCOMING)
				$config['toUserId'] = $params['USER_ID'];
			else if($params['INCOMING'] == self::CALL_OUTGOING)
				$config['fromUserId'] = $params['USER_ID'];
		}
		else if($params['COMMAND'] == 'hideExternalCall')
		{
			$config = Array(
				'callId' => $params['CALL_ID']
			);
		}

		if (isset($params['MARK']))
		{
			$config['mark'] = $params['MARK'];
		}
		$userId = is_array($params['USER_ID']) ? $params['USER_ID'] : array($params['USER_ID']);
		\Bitrix\Pull\Event::add($userId,
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config,
				'push' => $push
			)
		);

		return true;
	}


	/**
	 * Unsupported old-fashioned permission check.
	 * @return bool
	 * @deprecated Use Bitrix\Voximplant\Security\Permissions instead.
	 */
	public static function CheckAccess()
	{
		global $USER;

		$result = false;
		if (IsModuleInstalled('bitrix24'))
		{
			if (is_object($USER) && intval($USER->GetID()) && $USER->CanDoOperation('bitrix24_config'))
			{
				$result = true;
			}
		}
		else
		{
			if (is_object($USER) && intval($USER->GetID()) && $USER->IsAdmin())
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @deprecated Select data from b_voximplant_statistic instead
	 * @return array
	 */
	public static function GetTelephonyStatistic()
	{
		$arMonthlyStat = COption::GetOptionString("voximplant", "telephony_statistic", "");
		if ($arMonthlyStat)
		{
			$arMonthlyStat = unserialize($arMonthlyStat, ['allowed_classes' => false]);
		}

		if(!$arMonthlyStat)
		{
			$arMonthlyStat = array();
		}

		$lastUncountedMonth = COption::GetOptionString("voximplant", "telephony_statistic_last_month", "");  //last month which wasn't counted
		if ($lastUncountedMonth)
		{
			$lastUncountedMonth = unserialize($lastUncountedMonth, ['allowed_classes' => false]);
		}
		else
		{
			$lastUncountedMonth = Array();
		}

		$curLastMonth = array();
		$curLastMonth["MM"] = date("m");
		$curLastMonth["YYYY"] = date("Y");

		if (date("m") != $lastUncountedMonth["MM"] || date("Y") != $lastUncountedMonth["YYYY"])  //current month is not last month which wasn't counted
		{
			$firstDayCurMonth = ConvertTimeStamp(MakeTimeStamp("01.".date("m").".".date("Y"), "DD.MM.YYYY"));

			if (!empty($lastUncountedMonth))
			{
				$firstUncountedDay = ConvertTimeStamp(MakeTimeStamp("01.".$lastUncountedMonth["MM"].".".$lastUncountedMonth["YYYY"], "DD.MM.YYYY"));
				$arFilter = array(
					array(
						'LOGIC' => 'AND',
						'>CALL_START_DATE' => $firstUncountedDay,
						'<CALL_START_DATE' => $firstDayCurMonth
					)
				);
			}
			else
			{
				$arFilter = array(
					array(
						'LOGIC' => 'AND',
						'>CALL_START_DATE' => ConvertTimeStamp(MakeTimeStamp("04.02.2014", "DD.MM.YYYY")), // correct start date for counting statistics
						'<CALL_START_DATE' => $firstDayCurMonth
					)
				);
			}

			$arFilter['CALL_CATEGORY'] = 'external';

			$parameters = array(
				'order' => array('CALL_START_DATE'=>'DESC'),
				'filter' => $arFilter,
				'select' => array('COST', 'COST_CURRENCY', 'CALL_DURATION', 'CALL_START_DATE'),
			);
			$dbStat = VI\StatisticTable::getList($parameters);

			$curPortalCurrency = "";

			while($arData = $dbStat->fetch())
			{
				$arData["COST_CURRENCY"] = ($arData["COST_CURRENCY"] == "RUR" ? "RUB" : $arData["COST_CURRENCY"]);

				if (!$curPortalCurrency)
					$curPortalCurrency = $arData["COST_CURRENCY"];

				$arDateParse = ParseDateTime($arData["CALL_START_DATE"]);
				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["CALL_DURATION"] += $arData["CALL_DURATION"];

				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"] += $arData["COST"];
		//		$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"] = number_format($arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"], 4);
				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST_CURRENCY"] = $curPortalCurrency;
			}

			if (!empty($arMonthlyStat))
			{
				krsort ($arMonthlyStat);
				foreach($arMonthlyStat as $year => $arYear)
				{
					krsort ($arYear);
					$arMonthlyStat[$year] = $arYear;
				}

				COption::SetOptionString("voximplant", "telephony_statistic", serialize($arMonthlyStat));
				COption::SetOptionString("voximplant", "telephony_statistic_last_month", serialize($curLastMonth));
			}
		}

		return $arMonthlyStat;
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public static function CountTelephonyStatisticAgent()
	{
		//$arStat = self::GetTelephonyStatistic();

		return "";
	}

	public static function GetTrialText($popupId = 'main')
	{
		switch ($popupId)
		{
			case 'main':
				return static::GetTrialTextMain();
			case 'security':
				return static::GetSecurityTrialText();
			case 'groups':
				return static::GetGroupsTrialText();
			case 'ivr':
				return static::GetIvrTrialText();
			case 'numbers':
				return static::GetNumbersTrialText();
			case 'call-intercept':
				return static::GetCallInterceptTrialText();
			case 'line-selection':
				return static::GetLineSelectionTrialText();
		}
		throw new \Bitrix\Main\ArgumentException('Unknown trial popup id', 'popupId');
	}

	public static function GetTrialTextMain()
	{
		if(\Bitrix\Main\Loader::includeModule('bitrix24'))
			$transcriptionLimited = !in_array(CBitrix24::getLicensePrefix(), VI\Transcript::getAllowedRegions());
		else
			$transcriptionLimited = false;

		$title = GetMessage('VI_TRIAL_TITLE');
		$text = GetMessage('VI_TRIAL_TEXT_TITLE').'
			<ul class="hide-features-list">
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_1').'</li>
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_2').'</li>
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_3').'</li>
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_4').'</li>
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_5').' <sup class="hide-features-soon">'.GetMessage('VI_TRIAL_SOON').'</sup></li>
				<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_6_3').'</li>';
		if($transcriptionLimited)
		{
			$text .= '<li class="hide-features-list-item">'.GetMessage('VI_TRIAL_FEATURES_7').'</li>';
		}
		$text .= '
			</ul>
			<a href="'.GetMessage('VI_TRIAL_LINK').'" target="_blank" class="hide-features-more">'.GetMessage('VI_TRIAL_LINK_TEXT').'</a>
			<strong>
				'.GetMessage('VI_TRIAL_TARIFF').'
			</strong>';

		return Array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetSecurityTrialText()
	{
		$title = GetMessage("VI_TRIAL_S_TITLE");
		$text = '<p>'.GetMessage("VI_TRIAL_S_P1").'</p><p>'.GetMessage("VI_TRIAL_S_P2").'</p> 
			 <ul class="hide-features-list">
			 	<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_S_F1").'</li>
				<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_S_F2").'</li>
				<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_S_F3").'</li> 
				<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_S_F4").'</li> 
			<p>'.GetMessage("VI_TRIAL_S_P3").'</p>';

		return array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetGroupsTrialText()
	{
		$title = GetMessage('VI_TRIAL_TITLE');
		$text = '<p>'.GetMessage('VI_TRIAL_G_P1').'</p>
			<ul class="hide-features-list">
			 	<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_G_F1").'</li>
				<li class="hide-features-list-item">'.GetMessage("VI_TRIAL_G_F2").'</li>
			</ul>
			<a href="'.GetMessage('VI_TRIAL_LINK').'" target="_blank" class="hide-features-more">'.GetMessage('VI_TRIAL_LINK_TEXT').'</a>
			<strong>
				'.GetMessage('VI_TRIAL_G_P2').'
			</strong>';
		return array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetIvrTrialText()
	{
		return array(
			'TITLE' => VI\Ivr\Helper::getLicensePopupHeader(),
			'TEXT' => VI\Ivr\Helper::getLicensePopupContent(),
		);
	}

	public static function GetNumbersTrialText()
	{
		$title = GetMessage('VI_TRIAL_TITLE');
		$text = '<p>'.GetMessage('VI_TRIAL_N_P1').'</p>';
		$text .= '<p>'.GetMessage('VI_TRIAL_N_P2').'</p>';

		return array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetCallInterceptTrialText()
	{
		$title = GetMessage('VI_TRIAL_CALL_INTERCEPT_TITLE');
		$text = '<p>'.GetMessage('VI_TRIAL_CALL_INTERCEPT_TEXT').'</p>';

		return array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetLineSelectionTrialText()
	{
		$title = GetMessage('VI_TRIAL_LINE_SELECT_TITLE');
		$text = '<p>'.GetMessage('VI_TRIAL_LINE_SELECT_TEXT').'</p>';

		return array('TITLE' => $title, 'TEXT' => $text);
	}

	public static function GetTOS()
	{
		$account = new CVoxImplantAccount();
		$accountLang = $account->GetAccountLang(false);

		$sanitizer = new \CBXSanitizer();
		$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_HIGH);
		$sanitizer->AddTags([
			"a" => ["href", "target"]
		]);

		$text =
			$accountLang === 'ru'
				? \Bitrix\Main\Localization\Loc::getMessage("VI_TOS_RU")
				: \Bitrix\Main\Localization\Loc::getMessage("VI_TOS_EN_2", [
					"#LINK1START#" => '<a target="_blank" href="https://cdn.voximplant.com/data-processing-addendum-new.pdf">',
					"#LINK1END#" => '</a>',
					"#LINK2START#" => '<a target="_blank" href="https://voximplant.com/legal/privacy">',
					"#LINK2END#" => '</a>',
				]
			)
		;

		return $sanitizer->SanitizeHtml($text);
	}

	public static function GetDemoTopUpWarning()
	{
		return GetMessage("VI_DEMO_TOPUP_WARNING", [
			"#LINK#" => \Bitrix\Main\Loader::includeModule("ui") ? \Bitrix\UI\Util::getArticleUrlByCode("5435221") : ""
		]);
	}
	public static function GetDemoTopUpWarningTitle()
	{
		return GetMessage("VI_DEMO_TOPUP_WARNING_TITLE");
	}

	public static function GetPublicFolder()
	{
		return '/telephony/';
	}

	/**
	 * @return string
	 */
	public static function GetRedirectToBuyLink()
	{
		return "/bitrix/tools/voximplant/redirect_billing.php";
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public static function GetBuyLink()
	{
		if (LANGUAGE_ID == "kz")
		{
			$accountLang = "kz";
		}
		else
		{
			$account = new CVoxImplantAccount();
			$accountLang = $account->GetAccountLang();
		}

		if (IsModuleInstalled('bitrix24'))
		{
			if ($accountLang == "ua")
			{
				return 'https://www.bitrix24.ua/prices/telephony.php';
			}
			else if ($accountLang != "kz")
			{
				return '/settings/license_phone.php';
			}
		}
		else
		{
			if ($accountLang == 'ru')
			{
				return 'https://www.1c-bitrix.ru/buy/intranet.php#tab-call-link';
			}
			else if ($accountLang == 'ua')
			{
				return 'https://www.bitrix24.ua/prices/telephony.php';
			}
			else if ($accountLang == 'kz')
			{
			}
			else if ($accountLang == 'de')
			{
				return 'https://www.bitrix24.de/prices/self-hosted-telephony.php';
			}
			else
			{
				return 'https://www.bitrix24.com/prices/self-hosted-telephony.php';
			}
		}
		return '';
	}

	public static function GetProLink()
	{
		if(LANGUAGE_ID == "ru" || LANGUAGE_ID == "kz" || LANGUAGE_ID == "by")
			return "https://www.bitrix24.ru/pro/call.php";
		else if(LANGUAGE_ID == "de")
			return "https://www.bitrix24.de/pro/call.php";
		else if(LANGUAGE_ID == "ua")
			return "https://www.bitrix24.ua/pro/call.php";
		else
			return "https://www.bitrix24.com/pro/call.php";
	}

	public static function getPricesUrl()
	{
		if(LANGUAGE_ID == "ru" || LANGUAGE_ID == "kz" || LANGUAGE_ID == "by")
			return "https://www.bitrix24.ru/prices/calls.php";
		else if(LANGUAGE_ID == "de")
			return "https://www.bitrix24.de/prices/self-hosted-telephony.php";
		else if(LANGUAGE_ID == "ua")
			return "https://www.bitrix24.ua/prices/calls.php";
		else
			return "https://www.bitrix24.com/prices/self-hosted-telephony.php";
	}

	public static function getTariffsUrl()
	{
		$account = new CVoxImplantAccount();
		$language = $account->GetAccountLang(false);

		switch ($language)
		{
			case "ru":
				return "https://www.bitrix24.ru/prices/tariffs.php";
			case "ua":
				return "https://www.bitrix24.ua/prices/tariffs.php";
			case "de":
				return "https://www.bitrix24.de/prices/calls.php";
			default:
				return "https://www.bitrix24.com/prices/calls.php";
		}
	}

	public function GetError()
	{
		return $this->error;
	}

	/**
	 * Returns true if portal has at least one call
	 * @return bool
	 */
	public static function hasCalls()
	{
		return (bool)VI\StatisticTable::getList(array('limit' => 1))->fetch();
	}

	/**
	 * Returns id of the default group
	 * @return int
	 */
	public static function getDefaultGroupId()
	{
		return (int)\Bitrix\Main\Config\Option::get('voximplant', 'default_group_id');
	}

	/**
	 * Returns true if current DB type is MySQL
	 * @return bool
	 */
	public static function isDbMySql()
	{
		global $DB;
		return $DB->type == 'MYSQL';
	}

	public static function getCallTypes()
	{
		return array(
			static::CALL_OUTGOING,
			static::CALL_INCOMING,
			static::CALL_INCOMING_REDIRECT,
			static::CALL_CALLBACK,
			static::CALL_INFO
		);
	}

	public static function sendCallStartEvent(array $callFields)
	{
		foreach(GetModuleEvents("voximplant", "onCallStart", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $callFields['CALL_ID'],
				'USER_ID' => $callFields['USER_ID'],
			)));
		}
	}

	public static function formatMoney($amount)
	{
		$account = new CVoxImplantAccount();
		$currency = $account->GetAccountCurrency();
		if($currency == 'RUR')
		{
			$currency = 'RUB';
		}

		if(!$currency)
			return (string)$amount;

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			//TODO: temporary fix
			$result = CCurrencyLang::CurrencyFormat($amount, $currency);
			if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			{
				$result = htmlspecialcharsEx($result);
			}
			return $result;
		}
		else
		{
			return $amount . ' ' . $currency;
		}
	}

	public static function getSdkUrl(): string
	{
		if (defined('VOXIMPLANT_SDK_URL'))
		{
			return VOXIMPLANT_SDK_URL;
		}

		return '/bitrix/js/voximplant/voximplant.min.js';
	}

	/**
	 * Returns designated media server address (if found in portal config)
	 *
	 * @return string
	 */
	public static function getMediaServer(): string
	{
		if (defined('VOXIMPLANT_MEDIA_SERVER'))
		{
			return VOXIMPLANT_MEDIA_SERVER;
		}

		return \Bitrix\Main\Config\Option::get('voximplant', 'media_server', '');
	}
}
