<?php

use Bitrix\Main\Controller\UserFieldConfig;
use Bitrix\Rpa\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime()->IncludeActivityFile('RpaApproveActivity');

class CBPRpaRequestActivity extends CBPRpaApproveActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'Name' => null,
			'Description' => null,
			'Responsible' => null,
			'Actions' => [],
			'FieldsToShow' => [],
			'FieldsToSet' => [],

			'TaskId' => 0,
		];

		$this->SetPropertiesTypes([
			'TaskId' => ['Type' => 'int'],
		]);
	}

	protected function buildTaskParameters()
	{
		$params =  parent::buildTaskParameters();
		$params['FIELDS_TO_SET'] = $this->FieldsToSet;
		return $params;
	}

	public static function getTaskControls($arTask)
	{
		$actions = $arTask['PARAMETERS']['ACTIONS'];
		return array(
			'BUTTONS' => array(
				array(
					'TYPE'  => 'submit',
					'TARGET_USER_STATUS' => CBPTaskUserStatus::Ok,
					'NAME'  => 'complete',
					'VALUE' => 'Y',
					'TEXT'  => $actions[0]['label'],
					'COLOR' => $actions[0]['color']
				)
			)
		);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
		{
			return;
		}

		if (empty($arEventParameters["USER_ID"]))
		{
			return;
		}

		if (empty($arEventParameters['COMPLETED']))
		{
			return;
		}

		if (empty($arEventParameters["REAL_USER_ID"]))
		{
			$arEventParameters["REAL_USER_ID"] = $arEventParameters["USER_ID"];
		}

		$taskUsers = \CBPTaskService::getTaskUserIds($this->taskId);

		$arEventParameters["USER_ID"] = intval($arEventParameters["USER_ID"]);
		$arEventParameters["REAL_USER_ID"] = intval($arEventParameters["REAL_USER_ID"]);
		if (!in_array($arEventParameters["REAL_USER_ID"], $taskUsers))
		{
			return;
		}

		$taskService = $this->workflow->GetService("TaskService");
		$taskService->MarkCompleted($this->taskId, $arEventParameters["REAL_USER_ID"], CBPTaskUserStatus::Ok);

		$this->taskStatus = CBPTaskStatus::CompleteOk;

		$taskId = $this->taskId;
		$this->Unsubscribe($this);
		$this->ExecuteAction($this->Actions[0], $taskId, 'user_'.$arEventParameters['REAL_USER_ID'], $arEventParameters['FIELDS']);
	}

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "", $realUserId = null)
	{
		$arErrors = array();

		try
		{
			$userId = intval($userId);
			if ($userId <= 0)
				throw new CBPArgumentNullException("userId");

			$arEventParameters = array(
				"USER_ID" => $userId,
				"REAL_USER_ID" => $realUserId,
				"USER_NAME" => $userName,
			);

			if (!empty($arRequest['complete']))
			{
				$arEventParameters['COMPLETED'] = true;
			}

			$arEventParameters['FIELDS'] = $arRequest['fields'] ?? [];
			$isValidFields = true;

			foreach ($arEventParameters['FIELDS'] as $fieldKey => $value)
			{
				if (\CBPHelper::isEmptyValue($value))
				{
					$isValidFields = false;
					break;
				}
			}

			if (!$isValidFields)
			{
				throw new Exception(GetMessage('RPA_BP_RA_FIELD_ERROR_FIELDS'));
			}

			CBPRuntime::SendExternalEvent($arTask["WORKFLOW_ID"], $arTask["ACTIVITY_NAME"], $arEventParameters);

			return true;
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]",
			);
		}

		return false;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$dialog = parent::GetPropertiesDialog(...func_get_args());
		$map = $dialog->getMap();

		array_pop($map['Actions']['Default']);
		$map['Actions']['Default'][0]['label'] = GetMessage('RPA_BP_RA_FIELD_APPROVE_BTN_TEXT');

		$dialog->setMap([
			'Name' => $map['Name'],
			'Description' => $map['Description'],
			'Responsible' => $map['Responsible'],
			'Actions' => $map['Actions'],
			'FieldsToShow' => $map['FieldsToShow'],
			'FieldsToSet' => [
				'Name' => GetMessage('RPA_BP_RA_FIELD_FIELDS_TO_SET'),
				'Type' => 'mixed',
				'FieldName' => 'fields_to_set',
				'Multiple' => true,
				'Settings' => self::getFieldsToSetSettings($documentType),
				'Default' => [],
			],
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$res = parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $errors);

		if ($res)
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			$arCurrentActivity["Properties"]["FieldsToSet"] = [];
		}

		if ($res && !empty($arCurrentValues['fields_to_set']))
		{
			foreach ($arCurrentValues['fields_to_set'] as $fieldToShow => $value)
			{
				if ($value === 'Y')
				{
					$arCurrentActivity["Properties"]["FieldsToSet"][] = $fieldToShow;
				}
			}
			$errors = self::ValidateProperties($arCurrentActivity["Properties"], new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
			if (count($errors) > 0)
			{
				return false;
			}
		}

		return $res;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = parent::ValidateProperties($arTestProperties, $user);

		if (empty($arTestProperties['FieldsToSet']))
		{
			$errors[] = ['code' => 'NotExist', 'parameter' => 'FieldsToSet', 'message' => GetMessage('RPA_BP_RA_VALIDATION_ERROR_FIELDS_TO_SET')];
		}
		return $errors;
	}

	protected static function getFieldsToSetSettings(array $documentType): array
	{
		$settings = [];
		$type = self::getItemType($documentType);

		if ($type)
		{
			$settings['entityId'] = $type->getItemUserFieldsEntityId();
			$controller = new UserFieldConfig();
			$userFieldsCollection = $type->getUserFieldCollection();
			foreach($userFieldsCollection as $userField)
			{
				$settings['fields'][$userField->getName()] = $controller->preparePublicData($userField->toArray(), Driver::MODULE_ID);
			}
			$settings['typeId'] = $type->getId();
			$settings['isCreationEnabled'] = true;
		}

		return $settings;
	}
}