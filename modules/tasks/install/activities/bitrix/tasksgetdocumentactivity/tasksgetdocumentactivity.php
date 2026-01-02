<?php

use Bitrix\Tasks\Integration\Bizproc\Document\Task;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPTasksGetDocumentActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'TaskId' => null,
			'Fields' => null,
			'FieldsMap' => null,
		];
	}

	public function ReInitialize()
	{
		parent::ReInitialize();

		$fields = $this->Fields;
		if ($fields && is_array($fields))
		{
			foreach ($fields as $field)
			{
				$this->{$field} = null;
			}
		}
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('tasks'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$taskId = $this->TaskId;
		$logMap = [];

		if ($this->workflow->isDebug())
		{
			$logMap = static::getPropertiesMap($this->getDocumentType());
			unset($logMap['Fields']);
			$logMap = $this->getDebugInfo(['TaskId' => $taskId], $logMap);
		}

		if (!is_numeric($taskId))
		{
			$this->writeDebugInfo($logMap);

			$this->WriteToTrackingService(
				GetMessage('TASKS_GLDA_ERROR_EMPTY_DOCUMENT_1'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$taskId = (int)$taskId;
		$map = $this->FieldsMap;
		$select = self::getSelectFieldsFromMap($map);
		$task = \CTasks::GetByID($taskId, false, ['select' => array_unique($select)]);
		$fields = $task ? $task->fetch() : null;

		if (!$fields || !is_array($map))
		{
			$this->writeDebugInfo($logMap);

			$this->WriteToTrackingService(
				GetMessage('TASKS_GLDA_ERROR_EMPTY_DOCUMENT_1'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->SetPropertiesTypes($map);

		if (CModule::IncludeModule('forum'))
		{
			if (isset($map['COMMENT_RESULT']))
			{
				$fields['COMMENT_RESULT'] =
					(new ResultManager(0))
						->getTaskResults($taskId)
				;
			}
			if (isset($map['COMMENT_RESULT_LAST']))
			{
				$fields['COMMENT_RESULT_LAST'] = ResultManager::getLastResult(
					$taskId
				);
			}
		}

		if (isset($map['HALF_TIME_BEFORE_EXPIRE_MESSAGE']) || isset($map['HIMSELF_ADMIN_TASK_NOT_TAKEN_MESSAGE']))
		{
			$fields = Task::setFlowMessages($fields);
		}

		Task::convertFieldsToDocument($fields, $map);

		foreach ($map as $id => $field)
		{
			$this->arProperties[$id] = $fields[$id];
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo($fields, array_merge($logMap, $map)));
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = "",
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!CModule::IncludeModule("tasks"))
		{
			return false;
		}

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = [];
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

			if (!empty($arCurrentActivity["Properties"]['TaskId']))
			{
				$arCurrentValues['lists_element_id'] = $arCurrentActivity["Properties"]['TaskId'];
			}
			if (!empty($arCurrentActivity["Properties"]['Fields']))
			{
				$arCurrentValues['fields'] = $arCurrentActivity["Properties"]['Fields'];
			}
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(
			__FILE__,
			[
				'documentType' => $documentType,
				'activityName' => $activityName,
				'workflowTemplate' => $arWorkflowTemplate,
				'workflowParameters' => $arWorkflowParameters,
				'workflowVariables' => $arWorkflowVariables,
				'currentValues' => $arCurrentValues,
				'formName' => $formName,
				'siteId' => $siteId,
			]
		);

		$dialog->setMap(static::getPropertiesMap($documentType));

		$dialog->setRuntimeData(['isAdmin' => static::checkAdminPermission()]);

		return $dialog;
	}

	private static function getDocumentFieldsOptions(): array
	{
		$fields = Task::getDocumentFields(null);

		$options = [];
		foreach ($fields as $fieldKey => $fieldValue)
		{
			$options[$fieldKey] = $fieldValue['Name'];
		}

		return $options;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$errors
	): bool
	{
		if (!CModule::IncludeModule("tasks"))
		{
			return false;
		}

		$properties = [
			'TaskId' => $arCurrentValues['lists_element_id'],
			'Fields' => $arCurrentValues['fields'],
		];

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if (count($errors) > 0)
		{
			return false;
		}

		$properties['FieldsMap'] = self::buildFieldsMap($properties['Fields']);

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null): array
	{
		$errors = [];

		if (!static::checkAdminPermission())
		{
			$errors[] = [
				"code" => "AccessDenied",
				"parameter" => "Admin",
				"message" => GetMessage("TASKS_GLDA_ACCESS_DENIED_1"),
			];

			return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
		}

		if (empty($arTestProperties['TaskId']))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => "TaskId",
				"message" => GetMessage("TASKS_GLDA_ERROR_ELEMENT_ID"),
			];
		}

		if (empty($arTestProperties['Fields']))
		{
			$errors[] = [
				"code" => "NotExist",
				"parameter" => "Fields",
				"message" => GetMessage("TASKS_GLDA_ERROR_FIELDS_1"),
			];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	private static function buildFieldsMap($fields): array
	{
		$documentFields = Task::getDocumentFields(null);

		$map = [];
		if (is_array($fields))
		{
			foreach ($fields as $field)
			{
				if (isset($documentFields[$field]))
				{
					$map[$field] =\Bitrix\Bizproc\FieldType::normalizeProperty($documentFields[$field]);
				}
			}
		}

		return $map;
	}

	private static function getSelectFieldsFromMap(array $map): array
	{
		$select = array_keys($map);
		if (isset($map['IS_IMPORTANT']))
		{
			$select[] = 'PRIORITY';
		}
		if (isset($map['STATUS']))
		{
			$select[] = 'REAL_STATUS';
		}
		if (isset($map['IS_EXPIRED']))
		{
			$select = array_merge($select, ['DEADLINE', 'REAL_STATUS', 'CLOSED_DATE']);
		}

		return $select;
	}

	private static function checkAdminPermission()
	{
		$user = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);

		return $user->isAdmin();
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'TaskId' => [
				'Name' => GetMessage('TASKS_GLDA_ELEMENT_ID'),
				'FieldName' => 'lists_element_id',
				'Type' => 'string',
				'Required' => true,
			],
			'Fields' => [
				'Name' => GetMessage('TASKS_GLDA_FIELDS_LABEL'),
				'FieldName' => 'fields',
				'Type' => 'select',
				'Required'  => true,
				'Multiple' => true,
				'Options' => self::getDocumentFieldsOptions(),
			],
		];
	}
}
