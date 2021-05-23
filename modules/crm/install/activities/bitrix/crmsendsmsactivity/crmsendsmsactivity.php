<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Integration\SmsManager;

class CBPCrmSendSmsActivity extends CBPActivity
{
	const RECIPIENT_TYPE_ENTITY = 'entity';
	const RECIPIENT_TYPE_USER = 'user';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"ProviderId" => '', // Means "Sender Id" in messageservice module.
			"MessageFrom" => '',
			"MessageText" => '',
			"RecipientType" => static::RECIPIENT_TYPE_ENTITY,
			"RecipientUser" => null,
			"PhoneType" => null,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$messageText = $this->MessageText;
		if ($messageText === '' || !is_scalar($messageText))
		{
			$this->writeError(GetMessage("CRM_SSMSA_EMPTY_TEXT"));
			return CBPActivityExecutionStatus::Closed;
		}

		list($phoneNumber, $recipientUserId, $comEntityTypeId, $comEntityId) = $this->getPhoneNumber();
		if (!$phoneNumber)
		{
			$this->writeError(GetMessage("CRM_SSMSA_EMPTY_PHONE_NUMBER"));
			return CBPActivityExecutionStatus::Closed;
		}

		$providerId = (string)$this->ProviderId;
		$messageFrom = (string)$this->MessageFrom;

		//compatibility for REST providers
		if (mb_strpos($providerId, '|') !== false)
		{
			$messageFrom = $providerId;
			$providerId = 'rest';
		}

		if ($providerId === ':default:')
		{
			[$providerId, $messageFrom] = $this->resolveDefaultProvider();
		}

		if ($providerId === 'rest')
		{
			$sendResult = $this->sendByRest($messageFrom, $phoneNumber, $messageText);
		}
		else
		{
			$sendResult = $this->sendByProvider($providerId, $messageFrom, $phoneNumber, $messageText);
		}

		if ($sendResult)
		{
			$messageId = is_int($sendResult) ? $sendResult : null;

			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_SEND_RESULT_TRUE", array(
				'#PHONE#' => $phoneNumber
			)));

			$documentId = $this->GetDocumentId();
			list($typeName, $id) = explode('_', $documentId[2]);
			$typeId = \CCrmOwnerType::ResolveID($typeName);
			$responsibleId = CCrmOwnerType::GetResponsibleID($typeId, $id, false);

			$bindings = [['OWNER_TYPE_ID' => $typeId, 'OWNER_ID' => $id]];
			if (!$comEntityTypeId)
			{
				$comEntityTypeId = $typeId;
				$comEntityId = $id;
			}

			if (!($comEntityTypeId === $typeId && $comEntityId === $id))
			{
				$bindings[] = ['OWNER_TYPE_ID' => $comEntityTypeId, 'OWNER_ID' => $comEntityId];
			}

			\Bitrix\Crm\Activity\Provider\Sms::addActivity(array(
				'AUTHOR_ID' => $responsibleId,
				'DESCRIPTION' => $messageText,
				'ASSOCIATED_ENTITY_ID' => $messageId,
				'PROVIDER_PARAMS' => array(
					'sender' => 'robot',
					'recipient_user_id' => $recipientUserId
				),
				'BINDINGS' => $bindings,
				'COMMUNICATIONS' => array(array(
					'ENTITY_ID' => $comEntityId,
					'ENTITY_TYPE_ID' => $comEntityTypeId,
					'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($comEntityTypeId),
					'TYPE' => \CCrmFieldMulti::PHONE,
					'VALUE' => $phoneNumber
				))
			), false);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["ProviderId"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ProviderId", "message" => GetMessage("CRM_SSMSA_EMPTY_PROVIDER"));
		}

		if ($arTestProperties["MessageText"] === "")
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_SSMSA_EMPTY_TEXT"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$providers = self::getProvidersList();

		$dialog->setMap(array(
			'MessageText' => array(
				'Name' => GetMessage('CRM_SSMSA_MESSAGE_TEXT'),
				'Description' => GetMessage('CRM_SSMSA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			),
			'ProviderId' => array(
				'Name' => GetMessage('CRM_SSMSA_PROVIDER'),
				'FieldName' => 'provider_id',
				'Type' => 'select',
				'Required' => true,
				'Options' => static::makeProvidersSelectOptions($providers),
				'Default' => ':default:',
			),
			'MessageFrom' => array(
				'Name' => GetMessage('CRM_SSMSA_MESSAGE_FROM'),
				'FieldName' => 'message_from',
				'Type' => 'string'
			),
			'RecipientType' => array(
				'Name' => GetMessage('CRM_SSMSA_RECIPIENT_TYPE'),
				'FieldName' => 'recipient_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => array(
					static::RECIPIENT_TYPE_ENTITY => GetMessage('CRM_SSMSA_RECIPIENT_TYPE_ENTITY'),
					static::RECIPIENT_TYPE_USER => GetMessage('CRM_SSMSA_RECIPIENT_TYPE_USER')
				)
			),
			'RecipientUser' => array(
				'Name' => GetMessage('CRM_SSMSA_RECIPIENT_USER'),
				'FieldName' => 'recipient_user',
				'Type' => 'user',
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
			),
			'PhoneType' => array(
				'Name' => GetMessage('CRM_SSMSA_PHONE_TYPE'),
				'FieldName' => 'phone_type',
				'Type' => 'select',
				'Options' =>
					['' => GetMessage('CRM_SSMSA_PHONE_TYPE_EMPTY_OPTION')]
					+ \CCrmFieldMulti::GetEntityTypeList(\CCrmFieldMulti::PHONE)
			)
		));

		$dialog->setRuntimeData(array('providers' => $providers));

		//fix old values
		$values = $dialog->getCurrentValues();
		if (!empty($values['provider_id']) && mb_strpos($values['provider_id'], '|') !== false)
		{
			$values['message_from'] = $values['provider_id'];
			$values['provider_id'] = 'rest';
			$dialog->setCurrentValues($values);
		}

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$arProperties = array(
			'MessageText' => (string)$arCurrentValues["message_text"],
			'ProviderId' => (string)$arCurrentValues["provider_id"],
			'MessageFrom' => (string)$arCurrentValues["message_from"],
			'RecipientType' => (string)$arCurrentValues["recipient_type"],
			'RecipientUser' => CBPHelper::UsersStringToArray($arCurrentValues["recipient_user"], $documentType, $arErrors),
			'PhoneType' => (string)$arCurrentValues["phone_type"],
		);

		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private function getPhoneNumber()
	{
		$phoneNumber = null;
		$userId = 0;
		$comEntityTypeId = 0;
		$comEntityId = 0;

		$recipientType = $this->RecipientType == static::RECIPIENT_TYPE_USER ?
			static::RECIPIENT_TYPE_USER : static::RECIPIENT_TYPE_ENTITY;

		if ($recipientType === static::RECIPIENT_TYPE_ENTITY)
		{
			$documentId = $this->GetDocumentId();
			list($typeName, $id) = explode('_', $documentId[2]);
			$communication = $this->getEntityPhoneCommunication(
				\CCrmOwnerType::ResolveID($typeName),
				$id,
				$this->PhoneType
			);
			if ($communication)
			{
				$phoneNumber = $communication['VALUE'];
				$comEntityTypeId = $communication['ENTITY_TYPE_ID'];
				$comEntityId = $communication['ENTITY_ID'];
			}
		}
		else
		{
			list($phoneNumber, $userId) = $this->getUserPhoneNumber($this->RecipientUser);
		}
		return array($phoneNumber, $userId, $comEntityTypeId, $comEntityId);
	}

	private function getEntityPhoneCommunication($typeId, $id, $phoneType = null)
	{
		if ($typeId == \CCrmOwnerType::Deal)
		{
			$communications = $this->getDealCommunications($id);
		}
		elseif ($typeId == \CCrmOwnerType::Lead)
		{
			$communications = $this->getLeadCommunications($id);
		}
		elseif ($typeId == \CCrmOwnerType::Order)
		{
			$communications = $this->getOrderCommunications($id);
		}
		else
		{
			$communications = $this->getCommunicationsFromFM($typeId, $id);
		}

		if ($phoneType && $communications)
		{
			$communications = array_filter($communications, function ($value) use ($phoneType)
			{
				return ($value['VALUE_TYPE'] === $phoneType);
			});
		}

		$communications = array_slice($communications, 0, 1);
		return $communications? $communications[0] : null;
	}

	private function getUserPhoneNumber($user)
	{
		$phoneNumber = null;
		$userId = CBPHelper::ExtractUsers($user, $this->GetDocumentId(), true);
		if ($userId > 0)
		{
			$result = CUser::GetByID($userId);
			$user = $result->fetch();
			if (!empty($user['PERSONAL_MOBILE']) && \NormalizePhone($user['PERSONAL_MOBILE']) !== false)
			{
				$phoneNumber = $user['PERSONAL_MOBILE'];
			}
			elseif (!empty($user['WORK_PHONE']) && \NormalizePhone($user['WORK_PHONE']) !== false)
			{
				$phoneNumber = $user['WORK_PHONE'];
			}
		}
		return array($phoneNumber, $userId);
	}

	private function getDealCommunications($id)
	{
		$communications = [];

		$entity = CCrmDeal::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID);
		}

		if (empty($communications))
		{
			$dealContactIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($id);
			if ($dealContactIds)
			{
				foreach ($dealContactIds as $contId)
				{
					if ($contId !== $entityContactID)
					{
						$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $contId);
						if ($communications)
						{
							break;
						}
					}
				}
			}
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $entityCompanyID);
		}

		return $communications;
	}

	private function getOrderCommunications($id)
	{
		$communications = [];

		$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList(array(
			'select' => array('ENTITY_ID', 'ENTITY_TYPE_ID'),
			'filter' => array(
				'=ORDER_ID' => $id,
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
				'IS_PRIMARY' => 'Y'
			),
			'order' => ['ENTITY_TYPE_ID' => 'ASC']
		));
		while ($row = $dbRes->fetch())
		{
			$communications = $this->getCommunicationsFromFM($row['ENTITY_TYPE_ID'], $row['ENTITY_ID']);
			if ($communications)
			{
				break;
			}
		}

		return $communications;
	}

	private function getLeadCommunications($id)
	{
		$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Lead, $id);

		if ($communications)
		{
			return $communications;
		}

		$entity = CCrmLead::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID);
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $entityCompanyID);
		}

		return $communications;
	}


	private function getCommunicationsFromFM($entityTypeId, $entityId)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		$iterator = CCrmFieldMulti::GetList(
			['ID' => 'asc'],
			[
				'ENTITY_ID' => $entityTypeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => 'PHONE'
			]
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
				continue;

			$communications[] = array(
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => 'PHONE',
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE']
			);
		}

		return $communications;
	}

	private static function getProvidersList()
	{
		if (!SmsManager::canUse())
		{
			return static::getProvidersListOld();
		}

		$result = [
			[
				'IS_INTERNAL' => false,
				'ID'          => ':default:',
				'NAME'        => GetMessage('CRM_SSMSA_PROVIDER_DEFAULT'),
				'CAN_USE'     => true,
				'FROM_LIST'   => []
			]
		];

		foreach (SmsManager::getSenderInfoList(true) as $sender)
		{
			$providerData = array(
				'IS_INTERNAL' => $sender['isConfigurable'],
				'ID'          => $sender['id'],
				'NAME'        => $sender['name'],
				'CAN_USE'     => $sender['canUse'],
				'FROM_LIST'   => $sender['fromList']
			);

			if ($sender['isConfigurable'])
			{
				$providerData['IS_DEMO'] = $sender['isDemo'];
				$providerData['MANAGE_URL'] = $sender['manageUrl'];
			}
			$result[] = $providerData;
		}

		return $result;
	}

	private static function makeProvidersSelectOptions(array $providers)
	{
		$options = array();
		foreach ($providers as $provider)
		{
			$options[$provider['ID']] = $provider['NAME'];
		}
		return $options;
	}

	private static function getProvidersListOld()
	{
		$result = array();

		$ormResult = \Bitrix\Bizproc\RestProviderTable::getList(array(
			'select' => array('APP_ID', 'CODE', 'NAME', 'APP_NAME'),
			'order' => array('APP_NAME' => 'ASC', 'NAME' => 'ASC')
		));

		while ($row = $ormResult->fetch())
		{
			$result[] = array(
				'id' => $row['APP_ID'].'|'.$row['CODE'],
				'name' => sprintf('[%s] %s',
					\Bitrix\Bizproc\RestProviderTable::getLocalization($row['APP_NAME'], LANGUAGE_ID),
					\Bitrix\Bizproc\RestProviderTable::getLocalization($row['NAME'], LANGUAGE_ID)
				)
			);
		}

		return array(
			array(
				'IS_INTERNAL' => false,
				'ID' => 'rest',
				'NAME' => 'REST',
				'FROM_LIST' => $result
			)
		);
	}

	private function sendByRest($from, $phoneNumber, $messageText)
	{
		if (!SmsManager::canUse())
		{
			return static::sendByRestOld($from, $phoneNumber, $messageText);
		}

		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);
		$authorId = \CCrmOwnerType::GetResponsibleID(\CCrmOwnerType::ResolveID($typeName), $id, false);

		$result = SmsManager::sendMessage(array(
			'SENDER_ID' => 'rest',
			'AUTHOR_ID' => $authorId,
			'MESSAGE_FROM' => $from,
			'MESSAGE_TO' => $phoneNumber,
			'MESSAGE_BODY' => $messageText,
			'MESSAGE_HEADERS' => array(
				'module_id' => 'bizproc',
				'workflow_id' => $this->getWorkflowInstanceId(),
				'document_id' => $documentId,
				'document_type' => $this->GetDocumentType(),
				'properties' => array(
					'phone_number' => $phoneNumber,
					'message_text' => $messageText,
				)
			)
		));

		if (!$result->isSuccess())
		{
			$errorMessages = $result->getErrorMessages();
			foreach ($errorMessages as $message)
			{
				$this->writeError($message);
			}
			return false;
		}

		return $result->getId();
	}

	private function sendByProvider($senderId, $messageFrom, $phoneNumber, $messageText)
	{
		if (!SmsManager::canUse())
		{
			$this->writeError(GetMessage('CRM_SSMSA_NO_MESSAGESERVICE'));
			return false;
		}

		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);
		$authorId = \CCrmOwnerType::GetResponsibleID(\CCrmOwnerType::ResolveID($typeName), $id, false);

		$result = SmsManager::sendMessage(array(
			'SENDER_ID' => $senderId,
			'AUTHOR_ID' => $authorId,
			'MESSAGE_FROM' => $messageFrom ?: null,
			'MESSAGE_TO' => $phoneNumber,
			'MESSAGE_BODY' => $messageText,
		));

		if (!$result->isSuccess())
		{
			$errorMessages = $result->getErrorMessages();
			foreach ($errorMessages as $message)
			{
				$this->writeError($message);
			}
			return false;
		}

		return $result->getId();
	}

	private function resolveDefaultProvider()
	{
		$providerId = $messageFrom = null;

		if (SmsManager::canUse())
		{
			$defaults = SmsManager::getEditorCommon();
			$providerId = $defaults['senderId'];
			$messageFrom = $defaults['from'];

			if (!$providerId)
			{
				$providerId = array_shift(array_keys(SmsManager::getSenderSelectList()));
			}
		}

		return [$providerId, $messageFrom];
	}

	private function sendByRestOld($providerId, $phoneNumber, $messageText)
	{
		if (!CModule::includeModule('rest') || !\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
			return false;

		list($appId, $providerCode) = explode('|', $providerId);
		$provider = null;

		if ($appId && $providerCode)
		{
			$provider = \Bitrix\Bizproc\RestProviderTable::getList(
				array('filter' => array('APP_ID' => $appId, 'CODE' => $providerCode))
			)->fetch();
		}

		if (!$provider)
		{
			$this->writeError(GetMessage("CRM_SSMSA_NO_PROVIDER"));
			return false;
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $provider['APP_ID'],
			)
		));
		$application = $dbRes->fetch();

		if (!$application)
		{
			$this->writeError(GetMessage("CRM_SSMSA_NO_PROVIDER"));
			return false;
		}

		$appStatus = \Bitrix\Rest\AppTable::getAppStatusInfo($application, '');
		if($appStatus['PAYMENT_ALLOW'] === 'N')
		{
			$this->writeError(GetMessage("CRM_SSMSA_PAYMENT_REQUIRED"));
			return false;
		}

		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);

		$auth = array(
			'CODE' => $provider['CODE'],
			\Bitrix\Rest\OAuth\Auth::PARAM_LOCAL_USER => \CCrmOwnerType::GetResponsibleID(
				\CCrmOwnerType::ResolveID($typeName), $id, false
			),
			"application_token" => \CRestUtil::getApplicationToken($application),
		);

		$queryItems = array(
			\Bitrix\Rest\Sqs::queryItem(
				$provider['APP_ID'],
				$provider['HANDLER'],
				array(
					'workflow_id' => $this->getWorkflowInstanceId(),
					'type' => $provider['TYPE'],
					'code' => $provider['CODE'],
					'document_id' => $this->GetDocumentId(),
					'document_type' => $this->GetDocumentType(),
					'properties' => array(
						'phone_number' => $phoneNumber,
						'message_text' => $messageText,
					),
					'ts' => time(),
				),
				$auth,
				array(
					"sendAuth" => true,
					"sendRefreshToken" => false,
					"category" => \Bitrix\Rest\Sqs::CATEGORY_BIZPROC,
				)
			),
		);

		\Bitrix\Rest\OAuthService::getEngine()->getClient()->sendEvent($queryItems);
		return true;
	}

	private function writeError($errorText, $userId = 0)
	{
		$this->WriteToTrackingService($errorText, 0, CBPTrackingType::Error);
		$timelineText = GetMessage('CRM_SSMSA_TIMELINE_ERROR', ['#ERROR_TEXT#' => $errorText]);
		\Bitrix\Crm\Timeline\BizprocController::getInstance()->onActivityError($this, $userId, $timelineText);
	}
}