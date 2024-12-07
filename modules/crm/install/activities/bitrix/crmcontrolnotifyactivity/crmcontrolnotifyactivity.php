<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmControlNotifyActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"MessageText" => "",
			"ToHead" => "Y",
			"ToUsers" => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("im") || !CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$fromUserId = $this->getResponsibleId();
		$toUserIds = CBPHelper::ExtractUsers($this->ToUsers, $this->GetDocumentId());

		if ($this->ToHead !== 'N')
		{
			$toUserIds = array_merge($toUserIds, $this->getUserHead($fromUserId));
		}

		$this->logDebug($toUserIds);

		if (!$toUserIds)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CTRNA_EMPTY_TO_USERS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$this->sendImMessage($fromUserId, $toUserIds);

		return CBPActivityExecutionStatus::Closed;
	}

	private function getResponsibleId()
	{
		$documentId = $this->GetDocumentId();
		[$ownerTypeID, $ownerID] = CCrmBizProcHelper::resolveEntityId($documentId);

		return CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
	}

	private function getUserHead($userId)
	{
		$userId = (int)$userId;
		$userService = $this->workflow->getRuntime()->getUserService();

		$skipAbsent = CModule::IncludeModule('intranet');
		$userDepartments = $userService->getUserDepartmentChains($userId);

		$heads = [];
		$absentHeads = [];
		$maxLevel = 1;
		foreach ($userDepartments as $arV)
		{
			foreach ($arV as $level => $deptId)
			{
				if ($maxLevel > 0 && $level + 1 > $maxLevel)
				{
					break;
				}

				$departmentHead = $userService->getDepartmentHead($deptId);

				if (!$departmentHead || $departmentHead === $userId)
				{
					$maxLevel++;
					continue;
				}

				if ($skipAbsent && CIntranetUtils::IsUserAbsent($departmentHead))
				{
					if (!isset($absentHeads[$level]))
					{
						$absentHeads[$level] = [];
					}

					$absentHeads[$level][] = $departmentHead;
					$maxLevel++;
					continue;
				}
				if (!in_array($departmentHead, $heads, true))
				{
					$heads[] = $departmentHead;
				}
			}
		}

		if (!$heads && $absentHeads)
		{
			reset($absentHeads);
			$heads = current($absentHeads);
		}

		return $heads;
	}

	private function sendImMessage($from, $to)
	{
		$runtime = CBPRuntime::GetRuntime();
		$documentId = $this->GetDocumentId();
		/** @var CBPDocumentService $documentService */
		$documentService = $runtime->GetService('DocumentService');

		$messageText = $this->getMessageText();

		$attach = new CIMMessageParamAttach(1, '#468EE5');
		$attach->AddUser([
			'NAME' => GetMessage('CRM_CTRNA_FORMAT_ROBOT'),
			'AVATAR' => '/bitrix/images/bizproc/message_robot.png',
		]);
		$attach->AddDelimiter();
		$attach->AddGrid([
			[
				"NAME" => $documentService->getDocumentTypeName($this->GetDocumentType()) . ':',
				"VALUE" => $documentService->getDocumentName($documentId),
				"LINK" => $documentService->GetDocumentAdminPage($documentId),
				'DISPLAY' => 'BLOCK',
			],
		]);
		$attach->AddDelimiter();
		$attach->AddHtml($messageText);

		$message = [
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"MESSAGE_OUT" => CBPHelper::convertBBtoText($messageText),
			"ATTACH" => $attach,
			'NOTIFY_TAG' => 'ROBOT|' . implode('|', array_map('mb_strtoupper', $documentId)),
			'NOTIFY_MODULE' => 'bizproc',
			'NOTIFY_EVENT' => 'activity',
			'PUSH_MESSAGE' => $this->getPushText($messageText),
		];

		if ($from)
		{
			$message['FROM_USER_ID'] = $from;
		}

		$to = array_unique($to);
		foreach ($to as $userTo)
		{
			$message["TO_USER_ID"] = $userTo;
			CIMNotify::Add($message);
		}
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (!array_key_exists("MessageText", $arTestProperties) || $arTestProperties["MessageText"] == '')
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "MessageText",
				"message" => GetMessage("CRM_CTRNA_EMPTY_MESSAGE"),
			];
		}

		if (
			array_key_exists('ToHead', $arTestProperties)
			&& $arTestProperties['ToHead'] == 'N'
			&& empty($arTestProperties['ToUsers'])
		)
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "ToUsers",
				"message" => GetMessage("CRM_CTRNA_EMPTY_TO_USERS"),
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = ""
	)
	{
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$errors
	)
	{
		$errors = [];
		$properties = [
			"MessageText" => $arCurrentValues["message_text"],
		];

		$toUsers = CBPHelper::UsersStringToArray(
			$arCurrentValues["to_users"],
			$documentType,
			$errors,
		);

		if ($errors)
		{
			return false;
		}

		$toHead = false;
		$toUsers = array_filter($toUsers, function ($user) use (&$toHead) {
			if ($user === 'responsible_head')
			{
				$toHead = true;

				return false;
			}

			return true;
		});


		$properties["ToUsers"] = $toUsers;
		$properties["ToHead"] =
			(
				isset($arCurrentValues["to_head"])
				&& $arCurrentValues["to_head"] === 'N'
				|| !isset($arCurrentValues["to_head"])
				&& !$toHead
			)
			? 'N'
			: 'Y'
		;

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if ($errors)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'MessageText' => [
				'Name' => GetMessage('CRM_CTRNA_MESSAGE'),
				'Description' => GetMessage('CRM_CTRNA_MESSAGE'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true,
			],
			'ToHead' => [
				'Name' => GetMessage('CRM_CTRNA_TO_HEAD'),
				'FieldName' => 'to_head',
				'Type' => 'bool',
				'Default' => 'Y',
			],
			'ToUsers' => [
				'Name' => GetMessage('CRM_CTRNA_TO_USERS'),
				'FieldName' => 'to_users',
				'Type' => 'user',
				'Default' => 'responsible_head',
				'Required' => true,
				'Multiple' => true,
			],
		];
	}

	private function logDebug($users)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$debugInfo = $this->getDebugInfo([
			'ToUsers' => array_map(
				fn ($userId) => 'user_' . $userId,
				$users
			),
		]);

		unset($debugInfo['ToHead']);

		$this->writeDebugInfo($debugInfo);
	}

	private function getMessageText()
	{
		$messageText = $this->MessageText;
		if (is_array($messageText))
		{
			$messageText = implode(', ', CBPHelper::MakeArrayFlat($messageText));
		}

		$messageText = (string)$messageText;

		if ($messageText)
		{
			$messageText = strip_tags($messageText);
			$messageText = CBPHelper::convertBBtoText($messageText);
		}

		return $messageText;
	}

	private function getPushText(string $htmlMessage): string
	{
		$text = mb_substr(HTMLToTxt($htmlMessage, '', [], 0), 0, 200);
		if (mb_strlen($text) === 200)
		{
			$text .= '...';
		}

		return $text;
	}
}
