<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPImOpenLinesMessageActivity extends CBPActivity
{
	const ATTACHMENT_TYPE_FILE = 'file';
	const ATTACHMENT_TYPE_DISK = 'disk';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"MessageText" => "",
			"IsSystem" => "",

			'AttachmentType' => static::ATTACHMENT_TYPE_FILE,
			'Attachment' => [],
		];
	}

	public function Execute()
	{
		if (
			!\Bitrix\Main\Loader::includeModule("im")
			|| !\Bitrix\Main\Loader::includeModule("crm")
			|| !\Bitrix\Main\Loader::includeModule("imopenlines")
		)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$moduleId, $documentEntity, $documentId] = $this->GetDocumentId();

		if ($moduleId !== 'crm')
		{
			$this->writeError(GetMessage("IMOL_MA_UNSUPPORTED_DOCUMENT"));

			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		$fromUserId = \CCrmOwnerType::GetResponsibleID($entityTypeId, $entityId, false);

		if(\Bitrix\Im\User::getInstance($fromUserId)->isConnector())
		{
			$fromUserId = 0;
		}

		$sessionCode = $this->getSessionCodeByEntity($entityTypeId, $entityId);

		if (!$sessionCode)
		{
			$this->writeError(GetMessage("IMOL_MA_NO_SESSION_CODE"), $fromUserId);

			return CBPActivityExecutionStatus::Closed;
		}

		$messageText = (string)$this->MessageText;
		if ($messageText && mb_strpos($messageText, '<') !== false)
		{
			$messageText = HTMLToTxt($messageText);
		}


		$isSystem = ($this->IsSystem === 'Y');

		$chat = \Bitrix\Im\Model\ChatTable::getList(array(
			'filter' => array(
				'=ENTITY_TYPE' => 'LINES',
				'=ENTITY_ID' => $sessionCode
			),
			'limit' => 1
		))->fetch();

		if (!$chat)
		{
			$this->writeError(GetMessage("IMOL_MA_NO_CHAT"), $fromUserId);

			return CBPActivityExecutionStatus::Closed;
		}

		if ($messageText)
		{
			$messageFields = array(
				'FROM_USER_ID' => $fromUserId,
				'TO_CHAT_ID' => $chat['ID'],
				'MESSAGE' => $messageText,
				'NO_SESSION_OL' => 'Y',
			);

			if ($isSystem)
			{
				$messageFields['SILENT_CONNECTOR'] = 'Y';
			}
			else
			{
				$messageFields['PARAMS']['CLASS'] = "bx-messenger-content-item-ol-output";
			}

			$messageFields['SKIP_USER_CHECK'] = 'Y';

			$addResult = \Bitrix\ImOpenLines\Im::addMessage($messageFields);

			if ($addResult)
			{
				$this->sendAttachments($chat['ID'], $fromUserId);
			}
			else
			{
				/** @var \CMain $app*/
				$app = $GLOBALS["APPLICATION"];
				/** @var \CApplicationException $exception */
				$exception = $app->GetException();
				$this->writeError($exception->GetString(), $fromUserId);
			}
		}
		else
		{
			$this->sendAttachments($chat['ID'], $fromUserId);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function sendAttachments($chatId, $userId)
	{
		$isDiskAttachments = ($this->AttachmentType === static::ATTACHMENT_TYPE_DISK);

		if ($isDiskAttachments)
		{
			$attachmentFiles = (array)$this->Attachment;
		}
		else
		{
			$attachmentFiles = (array)$this->ParseValue($this->getRawProperty('Attachment'), 'file');
		}

		$attachmentFiles = CBPHelper::MakeArrayFlat($attachmentFiles);
		$attachmentFiles = array_unique(array_filter($attachmentFiles));

		if (!$attachmentFiles)
		{
			return null;
		}

		if ($isDiskAttachments)
		{
			$attachmentFiles = array_map(function ($file)
			{
				return 'disk'.$file;
			}, $attachmentFiles);
		}
		else
		{
			$attachmentFiles = \CIMDisk::UploadFileFromMain($chatId, $attachmentFiles);
			if (!$attachmentFiles)
			{
				return null;
			}
			$attachmentFiles = array_map(function ($file)
			{
				return 'upload'.$file;
			}, $attachmentFiles);
		}

		return (bool)\CIMDisk::UploadFileFromDisk($chatId, $attachmentFiles, '', ['USER_ID' => $userId, 'SKIP_USER_CHECK' => true, 'LINES_SILENT_MODE' => $this->IsSystem === 'Y']);
	}

	public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = "")
	{
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues
		));

		$dialog->setMap([
			'MessageText' => [
				'Name' => GetMessage('IMOL_MA_MESSAGE'),
				'Description' => GetMessage('IMOL_MA_MESSAGE'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true,
			],
			'IsSystem' => [
				'Name' => GetMessage('IMOL_MA_IS_SYSTEM'),
				'Description' => GetMessage('IMOL_MA_IS_SYSTEM_DESCRIPTION'),
				'FieldName' => 'is_system',
				'Type' => 'bool',
				'Default' => 'N',
			],
			'AttachmentType' => [
				'Name' => GetMessage('IMOL_MA_ATTACHMENT_TYPE_1'),
				'FieldName' => 'attachment_type',
				'Type' => 'select',
				'Options' => array(
					static::ATTACHMENT_TYPE_FILE => GetMessage('IMOL_MA_ATTACHMENT_FILE_2'),
					static::ATTACHMENT_TYPE_DISK => GetMessage('IMOL_MA_ATTACHMENT_DISK')
				),
			],
			'Attachment' => [
				'Name' => GetMessage('IMOL_MA_ATTACHMENT_1'),
				'FieldName' => 'attachment',
				'Type' => 'file',
				'Multiple' => true,
			],
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $currentValues, &$errors)
	{
		$errors = [];
		$properties = [
			'MessageText' => (string)$currentValues['message_text'],
			'IsSystem' => $currentValues['is_system'] === 'Y' ? 'Y' : 'N',
			'AttachmentType' => (string)$currentValues["attachment_type"],
			'Attachment' => []
		];

		if ($properties['AttachmentType'] === static::ATTACHMENT_TYPE_DISK)
		{
			foreach ((array)$currentValues["attachment"] as $attachmentId)
			{
				$attachmentId = (int)$attachmentId;
				if ($attachmentId > 0)
				{
					$properties['Attachment'][] = $attachmentId;
				}
			}
		}
		else
		{
			$properties['Attachment'] = isset($currentValues["attachment"])
				? $currentValues["attachment"] : $currentValues["attachment_text"];
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
			return false;

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	private function getSessionCodeByEntity($entityTypeId, $entityId)
	{
		$code = false;
		$lowPriorityCode = false;

		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$clients = $this->getDealClients($entityId);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$clients = $this->getLeadClients($entityId);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			$clients = $this->getOrderClients($entityId);
		}
		elseif ($entityTypeId !== \CCrmOwnerType::Contact && CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			$clients = $this->getItemClients($entityTypeId, $entityId);
		}
		else
		{
			$clients = [[\CCrmOwnerType::ResolveName($entityTypeId), $entityId]];
		}

		foreach ($clients as [$typeName, $id])
		{
			$iterator = \CCrmFieldMulti::GetList(
				array('ID' => 'desc'),
				array('ENTITY_ID' => $typeName, 'ELEMENT_ID' => $id, 'TYPE_ID' => \CCrmFieldMulti::IM)
			);

			while ($row = $iterator->fetch())
			{
				if (mb_strpos($row['VALUE'], 'imol|') === false)
				{
					continue;
				}

				$code = mb_substr($row['VALUE'], 5);

				if (mb_strpos($code, 'livechat') === 0)
				{
					$lowPriorityCode = $code;
					$code = false;
					continue;
				}

				break 2;
			}
		}

		if (!$code && $lowPriorityCode)
		{
			$code = $lowPriorityCode;
		}

		return $code;
	}

	private function getDealClients($dealId)
	{
		$clients = array();
		$deal = \CCrmDeal::GetByID($dealId, false);
		if($deal)
		{
			$dealContactId = isset($deal['CONTACT_ID']) ? intval($deal['CONTACT_ID']) : 0;
			$dealCompanyID = isset($deal['COMPANY_ID']) ? intval($deal['COMPANY_ID']) : 0;

			$dealContactIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($dealId);

			if ($dealContactId > 0)
			{
				$clients[] = [\CCrmOwnerType::ContactName, $dealContactId];
			}
			if ($dealCompanyID > 0)
			{
				$clients[] = [\CCrmOwnerType::CompanyName, $dealCompanyID];
			}

			if ($dealContactIds)
			{
				foreach ($dealContactIds as $id)
				{
					if ($id !== $dealContactId)
					{
						$clients[] = [\CCrmOwnerType::ContactName, $id];
					}
				}
			}
		}
		return $clients;
	}

	private function getOrderClients($id)
	{
		$clients = [];

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
			$clients[] = [CCrmOwnerType::ResolveName($row['ENTITY_TYPE_ID']), $row['ENTITY_ID']];
		}

		return $clients;
	}

	private function getLeadClients($id)
	{
		$clients = [[\CCrmOwnerType::LeadName, $id]];
		$lead = \CCrmLead::GetByID($id, false);
		if($lead)
		{
			$contactId = isset($lead['CONTACT_ID']) ? intval($lead['CONTACT_ID']) : 0;
			$companyId = isset($lead['COMPANY_ID']) ? intval($lead['COMPANY_ID']) : 0;
			if ($contactId > 0)
			{
				$clients[] = [\CCrmOwnerType::ContactName, $contactId];
			}
			if ($companyId > 0)
			{
				$clients[] = [\CCrmOwnerType::CompanyName, $companyId];
			}
		}
		return $clients;
	}

	private function getItemClients($entityTypeId, $entityId): array
	{
		$clients = [];
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return $clients;
		}

		$item = $factory->getItem($entityId);
		if (!$item)
		{
			return $clients;
		}

		$companyId =
			$item->hasField(\Bitrix\Crm\Item::FIELD_NAME_COMPANY_ID)
				? $item->getCompanyId()
				: 0
		;
		$contactIds =
			$item->hasField(\Bitrix\Crm\Item::FIELD_NAME_CONTACT_IDS)
				? $item->getContactIds()
				: []
		;

		if ($companyId > 0)
		{
			$clients[] = [\CCrmOwnerType::CompanyName, $companyId];
		}

		foreach ($contactIds as $contactId)
		{
			$clients[] = [\CCrmOwnerType::ContactName, $contactId];
		}

		return $clients;
	}

	private function writeError($errorText, $userId = 0)
	{
		$this->WriteToTrackingService($errorText, 0, CBPTrackingType::Error);

		$timelineController = \Bitrix\Crm\Timeline\BizprocController::getInstance();

		if (method_exists($timelineController, 'onActivityError'))
		{
			$timelineText = GetMessage('IMOL_MA_TIMELINE_ERROR', ['#ERROR_TEXT#' => $errorText]);
			\Bitrix\Crm\Timeline\BizprocController::getInstance()->onActivityError($this, $userId, $timelineText);
		}
	}
}