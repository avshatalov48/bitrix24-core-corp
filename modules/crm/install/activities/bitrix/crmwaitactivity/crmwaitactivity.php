<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmWaitActivity extends CBPActivity
{
	const WAIT_TYPE_AFTER = 'after';
	const WAIT_TYPE_BEFORE = 'before';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"WaitType" => null,
			"WaitDuration" => null,
			"WaitTarget" => null,
			"WaitDescription" => null,

			//reserved
			"WaitId" => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		[$ownerTypeName, $ownerId] = explode('_', $documentId[2]);
		$ownerTypeId = CCrmOwnerType::ResolveID($ownerTypeName);
		$responsibleId = CCrmOwnerType::GetResponsibleID($ownerTypeId, $ownerId, false);

		$waitType = $this->WaitType;
		$duration = max(1, (int)$this->WaitDuration);
		$description = trim((string)$this->WaitDescription);
		$targetField = (string)$this->WaitTarget;

		$waitDescription = $this->makeWaitIntervalDescription(
			$waitType,
			$duration,
			$targetField,
			CBPRuntime::GetRuntime()->GetService('DocumentService')->GetDocumentType($documentId)
		);
		$this->logDebugDescription($waitDescription);

		$now = new \Bitrix\Main\Type\DateTime();
		$start = $now;
		$end = null;

		if ($waitType === static::WAIT_TYPE_BEFORE)
		{
			$document = CBPRuntime::GetRuntime()
				->GetService('DocumentService')
				->GetDocument($documentId);

			if (!isset($document[$targetField]))
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_TARGET_1"), 0,
					CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}

			$targetDate = $document[$targetField];
			$time = $targetDate !== '' ? MakeTimeStamp($targetDate) : false;
			if (!$time)
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_TARGET_TIME_1"), 0,
					CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}

			$endTime = $time - ($duration * 86400) - CTimeZone::GetOffset();

			$currentDate = new \Bitrix\Main\Type\Date();
			$end = \Bitrix\Main\Type\Date::createFromTimestamp($endTime);

			if ($end->getTimestamp() <= $currentDate->getTimestamp())
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_BEFORE"), 0,
					CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}
		}

		if ($end === null)
		{
			$end = new \Bitrix\Main\Type\DateTime();
			$end->add("{$duration}D");
		}

		if ($description !== '')
		{
			$waitDescription .= PHP_EOL . $description;
		}
		$this->logDebugComment($description);

		$arFields = [
			'OWNER_TYPE_ID' => $ownerTypeId,
			'OWNER_ID' => $ownerId,
			'AUTHOR_ID' => $responsibleId,
			'START_TIME' => $start,
			'END_TIME' => $end,
			'COMPLETED' => 'N',
			'DESCRIPTION' => $waitDescription,
		];

		$result = \Bitrix\Crm\Pseudoactivity\WaitEntry::add($arFields);
		if ($result->isSuccess())
		{
			$this->WaitId = $result->getId();
		}
		else
		{
			foreach ($result->getErrorMessages() as $errorMessage)
			{
				$this->WriteToTrackingService($errorMessage, 0, CBPTrackingType::Error);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($testProps = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (
			empty($testProps["WaitType"])
			|| $testProps["WaitType"] !== static::WAIT_TYPE_AFTER && $testProps["WaitType"] !== static::WAIT_TYPE_BEFORE
		)
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "WaitType",
				"message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_TYPE"),
			];
		}

		if (empty($testProps["WaitDuration"]))
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "WaitDuration",
				"message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_DURATION"),
			];
		}

		if ($testProps["WaitType"] === static::WAIT_TYPE_BEFORE && empty($testProps["WaitTarget"]))
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "WaitTarget",
				"message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_TARGET"),
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($testProps, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters,
		$arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
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

		$targetFields = static::getTargetFields($documentType);

		$dialog->setMap(static::getPropertiesMap($documentType, ['fields' => $targetFields]));

		$dialog->setRuntimeData([
			'targetDateFields' => $targetFields,
		]);

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$targetFields = $context['fields'] ?? static::getTargetFields($documentType);

		return [
			'WaitType' => [
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_TYPE'),
				'FieldName' => 'wait_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => [
					static::WAIT_TYPE_AFTER => GetMessage('CRM_WAIT_ACTIVITY_WAIT_AFTER'),
					static::WAIT_TYPE_BEFORE => GetMessage('CRM_WAIT_ACTIVITY_WAIT_BEFORE'),
				],
				'Default' => static::WAIT_TYPE_AFTER,
			],
			'WaitDuration' => [
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DURATION'),
				'FieldName' => 'wait_duration',
				'Type' => 'int',
				'Default' => 1,
			],
			'WaitTarget' => [
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_TARGET'),
				'FieldName' => 'wait_target',
				'Type' => 'select',
				'Options' => static::makeSelectOptions($targetFields),
			],
			'WaitDescription' => [
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DESCRIPTION'),
				'Description' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DESCRIPTION'),
				'FieldName' => 'wait_description',
				'Type' => 'text',
			],
		];
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate,
		&$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = [];

		$arProperties = [
			'WaitType' => $arCurrentValues['wait_type'],
			'WaitDuration' => $arCurrentValues['wait_duration'],
			'WaitTarget' => $arCurrentValues['wait_target'],
			'WaitDescription' => $arCurrentValues['wait_description'],
		];

		$arErrors = self::ValidateProperties($arProperties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private function makeWaitIntervalDescription($type, $duration, $targetField = null, $documentType = null)
	{
		if ($type === static::WAIT_TYPE_BEFORE)
		{
			$dateCaption = $targetField;
			$targetFields = static::getTargetFields($documentType);
			foreach ($targetFields as $field)
			{
				if ($field['name'] === $targetField)
				{
					$dateCaption = $field['caption'];
					break;
				}
			}

			return GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_BEFORE', [
				'#DURATION#' => $this->getDurationText($duration),
				'#TARGET_DATE#' => $dateCaption,
			]);
		}

		return GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_AFTER', [
				'#DURATION#' => $this->getDurationText($duration),
			]
		);
	}

	private function getDurationText($duration)
	{
		if (($duration % 7) === 0)
		{
			$duration = $duration / 7;
			$result = Loc::getMessagePlural('CRM_WAIT_ACTIVITY_WEEK', $duration);
		}
		else
		{
			$result = Loc::getMessagePlural('CRM_WAIT_ACTIVITY_DAY', $duration);
		}

		return $duration . ' ' . $result;
	}

	private static function getTargetFields(array $documentType)
	{
		$targetFields = [];
		$documentFields = CBPRuntime::GetRuntime()
			->GetService('DocumentService')
			->GetDocumentFields($documentType);

		foreach ($documentFields as $fieldId => $field)
		{
			if (
				$fieldId === 'BEGINDATE'
				|| $fieldId === 'CLOSEDATE'
				|| $field['Type'] === 'UF:date'
				|| (
					mb_strpos($fieldId, 'UF_') === 0
					&& $field['Type'] === 'datetime'
				)
			)
			{
				$targetFields[] = [
					'name' => $fieldId,
					'caption' => $field['Name'],
				];
			}
		}

		return $targetFields;
	}

	private static function makeSelectOptions(array $targetFields)
	{
		$options = [];
		foreach ($targetFields as $field)
		{
			$options[$field['name']] = $field['caption'];
		}
		return $options;
	}

	private function logDebugDescription($description)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$this->writeDebugTrack(
			$this->getWorkflowInstanceId(),
			$this->getName(),
			$this->executionStatus,
			$this->executionResult,
			$this->Title ?? '',
			$this->preparePropertyForWritingToTrack($description)
		);
	}

	private function logDebugComment($comment)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$debugInfo = $this->getDebugInfo(
			['WaitDescription' => $comment],
			['WaitDescription' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DESCRIPTION')],
		);

		$this->writeDebugInfo($debugInfo);
	}
}
