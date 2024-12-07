<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Automation\ClientCommunications\ClientCommunications;
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
			'RecipientUser' => CBPHelper::UsersStringToArray(($arCurrentValues["recipient_user"] ?? ''), $documentType,
				$errors),
			'PhoneType' => (string)$arCurrentValues["phone_type"],
		];

		if (
			isset($arCurrentValues['provider_id'])
			&& $arCurrentValues['provider_id'] === ''
			&& static::isExpression($arCurrentValues['provider_id_text'])
		)
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
		$communications = [];
		if ($typeId > 0 && $id > 0)
		{
			$clientCommunications = new ClientCommunications((int)$typeId, (int)$id, CCrmFieldMulti::PHONE);
			$communications = $clientCommunications->getFirstFilled(is_string($phoneType) ? $phoneType : null);
		}

		$communications = array_slice($communications, 0, 1);

		return $communications ? $communications[0] : null;
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

		$queryItem = \Bitrix\Rest\Sqs::queryItem(
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
			],
		);

		if (is_callable([\Bitrix\Rest\Event\Sender::class, 'queueEvent']))
		{
			\Bitrix\Rest\Event\Sender::queueEvent($queryItem);
		}
		else
		{
			\Bitrix\Rest\OAuthService::getEngine()->getClient()->sendEvent([$queryItem]);
		}

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
		if (!$this->workflow->isDebug())
		{
			return;
		}

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
