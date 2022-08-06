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
		$this->arProperties = array(
			"Title" => "",
			"MessageText" => "",
			"ToHead" => "Y",
			"ToUsers" => null,
		);
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
		$skipAbsent = CModule::IncludeModule('intranet');

		$userDepartmentId = array();
		$userIterator = CUser::GetByID($userId);
		if ($user = $userIterator->Fetch())
		{
			if (isset($user["UF_DEPARTMENT"]))
			{
				if (!is_array($user["UF_DEPARTMENT"]))
					$user["UF_DEPARTMENT"] = array($user["UF_DEPARTMENT"]);

				foreach ($user["UF_DEPARTMENT"] as $v)
					$userDepartmentId[] = $v;
			}
		}

		$userDepartments = array();
		$departmentIBlockId = COption::GetOptionInt('intranet', 'iblock_structure');
		foreach ($userDepartmentId as $departmentId)
		{
			$ar = array();
			$dbPath = CIBlockSection::GetNavChain($departmentIBlockId, $departmentId);
			while ($arPath = $dbPath->GetNext())
				$ar[] = $arPath["ID"];

			$userDepartments[] = array_reverse($ar);
		}

		$heads = array();
		$absentHeads = array();
		$maxLevel = 1;
		foreach ($userDepartments as $arV)
		{
			foreach ($arV as $level => $deptId)
			{
				if ($maxLevel > 0 && $level + 1 > $maxLevel)
					break;

				$dbRes = CIBlockSection::GetList(
					array(),
					array(
						'IBLOCK_ID' => $departmentIBlockId,
						'ID'        => $deptId,
					),
					false,
					array('ID', 'UF_HEAD')
				);
				while ($arRes = $dbRes->Fetch())
				{
					if ($arRes["UF_HEAD"] == $userId || intval($arRes["UF_HEAD"]) <= 0)
					{
						$maxLevel++;
						continue;
					}

					if ($skipAbsent && CIntranetUtils::IsUserAbsent($arRes["UF_HEAD"]))
					{
						if (!isset($absentHeads[$level]))
							$absentHeads[$level] = array();

						$absentHeads[$level][] = $arRes["UF_HEAD"];
						$maxLevel++;
						continue;
					}
					if (!in_array($arRes["UF_HEAD"], $heads))
						$heads[] = $arRes["UF_HEAD"];
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

		$messageText = $this->MessageText;

		$CCTP = new CTextParser();
		$CCTP->allow = array(
			"HTML" => "N",
			"USER" => "N",
			"ANCHOR" => "Y",
			"BIU" => "Y",
			"IMG" => "Y", "QUOTE" => "N", "CODE" => "N", "FONT" => "Y", "LIST" => "Y",
			"SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N",
			"CUT_ANCHOR" => "N", "ALIGN" => "N"
		);

		$attach = new CIMMessageParamAttach(1, '#468EE5');
		$attach->AddUser(Array(
			'NAME' => GetMessage('CRM_CTRNA_FORMAT_ROBOT'),
			'AVATAR' => '/bitrix/images/bizproc/message_robot.png'
		));
		$attach->AddDelimiter();
		$attach->AddGrid(Array(
			Array(
				"NAME" => $documentService->getDocumentTypeName($this->GetDocumentType()) . ':',
				"VALUE" => $documentService->getDocumentName($documentId),
				"LINK" => $documentService->GetDocumentAdminPage($documentId),
				'DISPLAY' => 'BLOCK'
			),
		));
		$attach->AddDelimiter();
		$attach->AddHtml('<span style="color: #6E6E6E">'.
			$CCTP->convertText(htmlspecialcharsbx($messageText))
			.'</span>'
		);

		$message = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"MESSAGE_OUT" => CBPHelper::ConvertTextForMail($messageText),
			"ATTACH" => $attach,
			'NOTIFY_TAG' => 'ROBOT|'.implode('|', array_map('mb_strtoupper', $documentId)),
			'NOTIFY_MODULE' => 'bizproc',
			'NOTIFY_EVENT' => 'activity',
			'PUSH_MESSAGE' => $messageText,
		);

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

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists("MessageText", $arTestProperties) || $arTestProperties["MessageText"] == '')
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_CTRNA_EMPTY_MESSAGE"));

		if (array_key_exists('ToHead', $arTestProperties) && $arTestProperties['ToHead'] == 'N' && empty($arTestProperties['ToUsers']))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ToUsers", "message" => GetMessage("CRM_CTRNA_EMPTY_TO_USERS"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues
		));

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$arProperties = array(
			"MessageText" => $arCurrentValues["message_text"]
		);

		[$toUsers, $toHead] = CBPHelper::UsersStringToArray(
			$arCurrentValues["to_users"],
			$documentType,
			$arErrors,
			function($user)
			{
				if ($user == 'CONTROL_HEAD')
					return $user;

				return null;
			}
		);
		if (count($arErrors) > 0)
			return false;
		$arProperties["ToUsers"] = $toUsers;
		$arProperties["ToHead"] = isset($arCurrentValues["to_head"]) && $arCurrentValues["to_head"] == 'N'
			|| !isset($arCurrentValues["to_head"]) && !$toHead ? 'N': 'Y';

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

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
				'Required' => true
			],
			'ToHead' => [
				'Name' => GetMessage('CRM_CTRNA_TO_HEAD'),
				'FieldName' => 'to_head',
				'Type' => 'bool',
				'Default' => 'Y'
			],
			'ToUsers' => [
				'Name' => GetMessage('CRM_CTRNA_TO_USERS'),
				'FieldName' => 'to_users',
				'Type' => 'user',
				'Default' => 'CONTROL_HEAD',
				'Required' => true,
				'Multiple' => true,
				'Settings' => [
					'additionalFields' => [
						[
							'id' => 'CONTROL_HEAD',
							'entityId' => 'CONTROL_HEAD',
							'name' => GetMessage('CRM_CTRNA_TO_HEAD'),
						]
					]
				]
			],
		];
	}

	private function logDebug($users)
	{
		$debugInfo = $this->getDebugInfo([
			'ToUsers' => array_map(
				fn($userId) => 'user_' . $userId,
				$users
			),
		]);

		unset($debugInfo['ToHead']);

		$this->writeDebugInfo($debugInfo);
	}
}
