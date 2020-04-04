<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPImOpenLinesMessageActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"MessageText" => "",
			"IsSystem" => "",
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("im") || !CModule::IncludeModule("crm") || !CModule::IncludeModule("imopenlines"))
			return CBPActivityExecutionStatus::Closed;

		list($moduleId, $documentEntity, $documentId) = $this->GetDocumentId();

		if ($moduleId !== 'crm')
		{
			$this->writeError(GetMessage("IMOL_MA_UNSUPPORTED_DOCUMENT"));
			return CBPActivityExecutionStatus::Closed;
		}

		list($entityTypeName, $entityId) = explode('_', $documentId);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

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
		if ($messageText && strpos($messageText, '<') !== false)
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

		$messageFields = array(
			"FROM_USER_ID" => $fromUserId,
			"TO_CHAT_ID" => $chat['ID'],
			"MESSAGE" => $messageText,
		);

		if ($isSystem)
		{
			$messageFields['SYSTEM'] = 'Y';
		}
		else
		{
			$messageFields['SKIP_USER_CHECK'] = 'Y';
			$messageFields['PARAMS']['CLASS'] = "bx-messenger-content-item-ol-output";
		}

		$addResult = \Bitrix\ImOpenLines\Im::addMessage($messageFields);

		if (!$addResult)
		{
			/** @var \CMain $app*/
			$app = $GLOBALS["APPLICATION"];
			/** @var \CApplicationException $exception */
			$exception = $app->GetException();
			$this->writeError($exception->GetString(), $fromUserId);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists("MessageText", $arTestProperties) || strlen($arTestProperties["MessageText"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("IMOL_MA_EMPTY_MESSAGE"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
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

		$dialog->setMap(array(
			'MessageText' => array(
				'Name' => GetMessage('IMOL_MA_MESSAGE'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			),
			'IsSystem' => array(
				'Name' => GetMessage('IMOL_MA_IS_SYSTEM'),
				'Description' => GetMessage('IMOL_MA_IS_SYSTEM_DESCRIPTION'),
				'FieldName' => 'is_system',
				'Type' => 'bool',
				'Default' => 'N'
			)
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $currentValues, &$errors)
	{
		$errors = array();
		$properties = array(
			'MessageText' => (string)$currentValues['message_text'],
			'IsSystem' => $currentValues['is_system'] === 'Y' ? 'Y' : 'N'
		);

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

		if ($entityTypeId == \CCrmOwnerType::Deal)
		{
			$clients = $this->getDealClients($entityId);
		}
		elseif ($entityTypeId == \CCrmOwnerType::Lead)
		{
			$clients = $this->getLeadClients($entityId);
		}
		else
		{
			$clients = array(\CCrmOwnerType::ResolveName($entityTypeId) => $entityId);
		}

		foreach ($clients as $typeName => $id)
		{
			$iterator = \CCrmFieldMulti::GetList(
				array('ID' => 'desc'),
				array('ENTITY_ID' => $typeName, 'ELEMENT_ID' => $id, 'TYPE_ID' => \CCrmFieldMulti::IM)
			);

			while ($row = $iterator->fetch())
			{
				if (strpos($row['VALUE'], 'imol|') === false)
				{
					continue;
				}

				$code = substr($row['VALUE'], 5);

				if (strpos($code, 'livechat') === 0)
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
			if ($dealContactId > 0)
			{
				$clients[\CCrmOwnerType::ContactName] = $dealContactId;
			}
			if ($dealCompanyID > 0)
			{
				$clients[\CCrmOwnerType::CompanyName] = $dealCompanyID;
			}
		}
		return $clients;
	}

	private function getLeadClients($id)
	{
		$clients = array(\CCrmOwnerType::LeadName => $id);
		$lead = \CCrmLead::GetByID($id, false);
		if($lead)
		{
			$contactId = isset($lead['CONTACT_ID']) ? intval($lead['CONTACT_ID']) : 0;
			$companyId = isset($lead['COMPANY_ID']) ? intval($lead['COMPANY_ID']) : 0;
			if ($contactId > 0)
			{
				$clients[\CCrmOwnerType::ContactName] = $contactId;
			}
			if ($companyId > 0)
			{
				$clients[\CCrmOwnerType::CompanyName] = $companyId;
			}
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