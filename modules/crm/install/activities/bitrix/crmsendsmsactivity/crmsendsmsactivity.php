<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\SmsManager;

class CBPCrmSendSmsActivity extends CBPActivity
{
	const RECIPIENT_TYPE_ENTITY = 'entity';
	const RECIPIENT_TYPE_USER = 'user';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"ProviderId" => '', // Means "Sender Id" in messageservice module.
			"MessageFrom" => '', //Deprecated
			"MessageText" => '',
			"RecipientType" => static::RECIPIENT_TYPE_ENTITY,
			"RecipientUser" => null,
			"PhoneType" => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->logDebug();

		$messageText = $this->MessageText;
		if ($messageText === '' || !is_scalar($messageText))
		{
			$this->writeError(GetMessage("CRM_SSMSA_EMPTY_TEXT"));

			return CBPActivityExecutionStatus::Closed;
		}

		[$phoneNumber, $recipientUserId, $comEntityTypeId, $comEntityId] = $this->getPhoneNumber();
		if (!$phoneNumber)
		{
			$this->writeError(GetMessage("CRM_SSMSA_EMPTY_PHONE_NUMBER"));

			return CBPActivityExecutionStatus::Closed;
		}

		$providerId = (string)$this->ProviderId;
		$messageFrom = (string)$this->MessageFrom;

		if (mb_strpos($providerId, '@') !== false)
		{
			[$messageFrom, $providerId] = explode('@', $providerId);
		}
		//compatibility for REST providers
		elseif (mb_strpos($providerId, '|') !== false)
		{
			$messageFrom = $providerId;
			$providerId = 'rest';
		}

		if ($providerId === 'rest')
		{
			$sendResult = $this->sendByRest($messageFrom, $phoneNumber, $messageText);
		}
		else
		{
			if ($providerId === ':default:')
			{
				[$providerId, $messageFrom] = $this->resolveDefaultProvider();
			}

			$sendResult = $this->sendByProvider($providerId, $messageFrom, $phoneNumber, $messageText);
		}

		if ($sendResult)
		{
			$messageId = is_int($sendResult) ? $sendResult : null;

			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_SEND_RESULT_TRUE", [
				'#PHONE#' => $phoneNumber,
			]));

			$documentId = $this->GetDocumentId();
			[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($documentId);
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

			\Bitrix\Crm\Activity\Provider\Sms::addActivity([
				'AUTHOR_ID' => $responsibleId,
				'DESCRIPTION' => $messageText,
				'ASSOCIATED_ENTITY_ID' => $messageId,
				'PROVIDER_PARAMS' => [
					'sender' => 'robot',
					'recipient_user_id' => $recipientUserId,
				],
				'BINDINGS' => $bindings,
				'COMMUNICATIONS' => [
					[
						'ENTITY_ID' => $comEntityId,
						'ENTITY_TYPE_ID' => $comEntityTypeId,
						'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($comEntityTypeId),
						'TYPE' => \CCrmFieldMulti::PHONE,
						'VALUE' => $phoneNumber,
					],
				],
			], false);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties["ProviderId"]))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => "ProviderId",
				"message" => GetMessage("CRM_SSMSA_EMPTY_PROVIDER"),
			];
		}

		if ($arTestProperties["MessageText"] === "")
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => "MessageText",
				"message" => GetMessage("CRM_SSMSA_EMPTY_TEXT"),
			];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters,
		$arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = [
			'MessageText' => [
				'Name' => GetMessage('CRM_SSMSA_MESSAGE_TEXT'),
				'Description' => GetMessage('CRM_SSMSA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true,
			],
			'RecipientType' => [
				'Name' => GetMessage('CRM_SSMSA_RECIPIENT_TYPE'),
				'FieldName' => 'recipient_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => [
					static::RECIPIENT_TYPE_ENTITY => GetMessage('CRM_SSMSA_RECIPIENT_TYPE_ENTITY'),
					static::RECIPIENT_TYPE_USER => GetMessage('CRM_SSMSA_RECIPIENT_TYPE_USER'),
				],
			],
			'RecipientUser' => [
				'Name' => GetMessage('CRM_SSMSA_RECIPIENT_USER'),
				'FieldName' => 'recipient_user',
				'Type' => 'user',
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType),
			],
			'PhoneType' => [
				'Name' => GetMessage('CRM_SSMSA_PHONE_TYPE'),
				'FieldName' => 'phone_type',
				'Type' => 'select',
				'Options' =>
					['' => GetMessage('CRM_SSMSA_PHONE_TYPE_EMPTY_OPTION')]
					+ \CCrmFieldMulti::GetEntityTypeList(\CCrmFieldMulti::PHONE),
			],
		];

		if (!SmsManager::canUse())
		{
			$map['ProviderId'] = [
				'Name' => GetMessage('CRM_SSMSA_PROVIDER'),
				'FieldName' => 'provider_id',
				'Type' => 'select',
				'Required' => true,
				'Options' => static::getProvidersListOld(),
			];
		}
		else
		{
			$map['ProviderId'] = [
				'Name' => GetMessage('CRM_SSMSA_PROVIDER'),
				'FieldName' => 'provider_id',
				'Type' => 'sms_sender',
				'Required' => true,
				'Default' => ':default:',
			];
		}

		return $map;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate,
		&$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = [
			'MessageText' => (string)$arCurrentValues["message_text"],
			'ProviderId' => (string)$arCurrentValues["provider_id"],
			'RecipientType' => (string)$arCurrentValues["recipient_type"],
			'RecipientUser' => CBPHelper::UsersStringToArray($arCurrentValues["recipient_user"], $documentType,
				$errors),
			'PhoneType' => (string)$arCurrentValues["phone_type"],
		];

		if ($arCurrentValues['provider_id'] === '' && static::isExpression($arCurrentValues['provider_id_text']))
		{
			$properties['ProviderId'] = $arCurrentValues['provider_id_text'];
		}

		if ($errors)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

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
			[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($documentId);
			$communication = $this->getEntityPhoneCommunication($typeId, $id, $this->PhoneType);
			if ($communication)
			{
				$phoneNumber = $communication['VALUE'];
				$comEntityTypeId = $communication['ENTITY_TYPE_ID'];
				$comEntityId = $communication['ENTITY_ID'];
			}
		}
		else
		{
			[$phoneNumber, $userId] = $this->getUserPhoneNumber($this->RecipientUser);
		}

		return [$phoneNumber, $userId, $comEntityTypeId, $comEntityId];
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
		elseif ($typeId == \CCrmOwnerType::Contact || $typeId == \CCrmOwnerType::Company)
		{
			$communications = $this->getCommunicationsFromFM($typeId, $id);
		}
		else
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeId);
			if ($factory)
			{
				$item = $factory->getItem((int)$id);
				if ($item)
				{
					$communications = $this->getCommunicationsFromItem($item);
				}
			}
		}

		if ($phoneType && $communications)
		{
			$communications = array_filter($communications, function ($value) use ($phoneType) {
				return ($value['VALUE_TYPE'] === $phoneType);
			});
		}

		$communications = array_slice($communications, 0, 1);

		return $communications ? $communications[0] : null;
	}

	private function getCommunicationsFromItem(\Bitrix\Crm\Item $item): array
	{
		$contactBindings = $item->getContactBindings();
		$communications = [];
		foreach ($contactBindings as $binding)
		{
			$contactId = (int)($binding['CONTACT_ID'] ?? 0);
			if ($contactId > 0)
			{
				$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $contactId);
				if (!empty($communications))
				{
					break;
				}
			}
		}

		if (empty($communications) && $item->getCompanyId() > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $item->getCompanyId());
		}

		return $communications;
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

		return [$phoneNumber, $userId];
	}

	private function getDealCommunications($id)
	{
		$communications = [];

		$entity = CCrmDeal::GetByID($id, false);
		if (!$entity)
		{
			return [];
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

		$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList([
			'select' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'filter' => [
				'=ORDER_ID' => $id,
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
				'IS_PRIMARY' => 'Y',
			],
			'order' => ['ENTITY_TYPE_ID' => 'ASC'],
		]);
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
		if (!$entity)
		{
			return [];
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
		$communications = [];

		$iterator = CCrmFieldMulti::GetList(
			['ID' => 'asc'],
			[
				'ENTITY_ID' => $entityTypeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => 'PHONE',
			]
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
			{
				continue;
			}

			$communications[] = [
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => 'PHONE',
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE'],
			];
		}

		return $communications;
	}

	private static function getProvidersListOld()
	{
		$result = [];

		$ormResult = \Bitrix\Bizproc\RestProviderTable::getList([
			'select' => ['APP_ID', 'CODE', 'NAME', 'APP_NAME'],
			'order' => ['APP_NAME' => 'ASC', 'NAME' => 'ASC'],
		]);

		while ($row = $ormResult->fetch())
		{
			$result[$row['APP_ID'] . '|' . $row['CODE']] = sprintf(
				'[%s] %s',
				\Bitrix\Bizproc\RestProviderTable::getLocalization($row['APP_NAME'], LANGUAGE_ID),
				\Bitrix\Bizproc\RestProviderTable::getLocalization($row['NAME'], LANGUAGE_ID)
			);
		}

		return $result;
	}

	private function sendByRest($from, $phoneNumber, $messageText)
	{
		if (!SmsManager::canUse())
		{
			return static::sendByRestOld($from, $phoneNumber, $messageText);
		}

		$documentId = $this->GetDocumentId();

		[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($documentId);
		$authorId = \CCrmOwnerType::loadResponsibleId($typeId, $id, false);

		$result = SmsManager::sendMessage([
			'SENDER_ID' => 'rest',
			'AUTHOR_ID' => $authorId,
			'MESSAGE_FROM' => $from,
			'MESSAGE_TO' => $phoneNumber,
			'MESSAGE_BODY' => $messageText,
			'MESSAGE_HEADERS' => [
				'module_id' => 'bizproc',
				'workflow_id' => $this->getWorkflowInstanceId(),
				'document_id' => $documentId,
				'document_type' => $this->GetDocumentType(),
			],
		]);

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
		[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($documentId);

		if ($typeId === \CCrmOwnerType::Undefined || !$id)
		{
			return false;
		}

		$authorId = \CCrmOwnerType::loadResponsibleId($typeId, $id, false);

		$result = SmsManager::sendMessage([
			'SENDER_ID' => $senderId,
			'AUTHOR_ID' => $authorId,
			'MESSAGE_FROM' => $messageFrom ?: null,
			'MESSAGE_TO' => $phoneNumber,
			'MESSAGE_BODY' => $messageText,
		]);

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

			$firstSuitableProviderId = null;
			$providers = SmsManager::getSenderSelectList();
			foreach ($providers as $provider)
			{
				if ($providerId === $provider['id'] && $provider['isTemplatesBased'])
				{
					$providerId = null; // can't use templates based provider
				}
				if (!$firstSuitableProviderId && !$provider['isTemplatesBased'] && $provider['canUse'])
				{
					$firstSuitableProviderId = $provider['id'];
				}
			}

			if (!$providerId)
			{
				$providerId = $firstSuitableProviderId;
			}
		}

		return [$providerId, $messageFrom];
	}

	private function sendByRestOld($providerId, $phoneNumber, $messageText)
	{
		if (!CModule::includeModule('rest') || !\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
		{
			return false;
		}

		[$appId, $providerCode] = explode('|', $providerId);
		$provider = null;

		if ($appId && $providerCode)
		{
			$provider = \Bitrix\Bizproc\RestProviderTable::getList([
				'filter' => [
					'=APP_ID' => $appId,
					'=CODE' => $providerCode,
				],
			])->fetch();
		}

		if (!$provider)
		{
			$this->writeError(GetMessage("CRM_SSMSA_NO_PROVIDER"));

			return false;
		}

		$dbRes = \Bitrix\Rest\AppTable::getList([
			'filter' => [
				'=CLIENT_ID' => $provider['APP_ID'],
			],
		]);
		$application = $dbRes->fetch();

		if (!$application)
		{
			$this->writeError(GetMessage("CRM_SSMSA_NO_PROVIDER"));

			return false;
		}

		$appStatus = \Bitrix\Rest\AppTable::getAppStatusInfo($application, '');
		if ($appStatus['PAYMENT_ALLOW'] === 'N')
		{
			$this->writeError(GetMessage("CRM_SSMSA_PAYMENT_REQUIRED"));

			return false;
		}

		$documentId = $this->GetDocumentId();
		[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($documentId);

		$auth = [
			'CODE' => $provider['CODE'],
			\Bitrix\Rest\OAuth\Auth::PARAM_LOCAL_USER => \CCrmOwnerType::GetResponsibleID(
				$typeId, $id, false
			),
			'application_token' => \CRestUtil::getApplicationToken($application),
		];

		$queryItems = [
			\Bitrix\Rest\Sqs::queryItem(
				$provider['APP_ID'],
				$provider['HANDLER'],
				[
					'workflow_id' => $this->getWorkflowInstanceId(),
					'type' => $provider['TYPE'],
					'code' => $provider['CODE'],
					'document_id' => $this->GetDocumentId(),
					'document_type' => $this->GetDocumentType(),
					'properties' => [
						'phone_number' => $phoneNumber,
						'message_text' => $messageText,
					],
					'ts' => time(),
				],
				$auth,
				[
					"sendAuth" => true,
					"sendRefreshToken" => false,
					"category" => \Bitrix\Rest\Sqs::CATEGORY_BIZPROC,
				]
			),
		];

		\Bitrix\Rest\OAuthService::getEngine()->getClient()->sendEvent($queryItems);

		return true;
	}

	private function writeError($errorText, $userId = 0)
	{
		$this->WriteToTrackingService($errorText, 0, CBPTrackingType::Error);
		$timelineText = GetMessage('CRM_SSMSA_TIMELINE_ERROR', ['#ERROR_TEXT#' => $errorText]);
		\Bitrix\Crm\Timeline\BizprocController::getInstance()->onActivityError($this, $userId, $timelineText);
	}

	private function logDebug()
	{
		$debugInfo = $this->getDebugInfo();

		if ($debugInfo['RecipientType']['TrackValue'] !== static::RECIPIENT_TYPE_USER)
		{
			unset($debugInfo['RecipientUser']);
		}
		else
		{
			unset($debugInfo['PhoneType']);
		}
		unset($debugInfo['RecipientType']);

		$this->writeDebugInfo($debugInfo);
	}
}
