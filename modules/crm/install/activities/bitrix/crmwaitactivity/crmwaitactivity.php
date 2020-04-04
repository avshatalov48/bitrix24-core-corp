<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmWaitActivity extends CBPActivity
{
	const WAIT_TYPE_AFTER = 'after';
	const WAIT_TYPE_BEFORE = 'before';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"WaitType" => null,
			"WaitDuration" => null,
			"WaitTarget" => null,
			"WaitDescription" => null,

			//reserved
			"WaitId" => null,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		$documentId = $this->GetDocumentId();
		list($ownerTypeName, $ownerId) = explode('_', $documentId[2]);
		$ownerTypeId = CCrmOwnerType::ResolveID($ownerTypeName);
		$responsibleId = CCrmOwnerType::GetResponsibleID($ownerTypeId, $ownerId, false);

		$waitType = $this->WaitType;
		$duration = max(1, (int)$this->WaitDuration);
		$description = trim((string)$this->WaitDescription);

		$now = new \Bitrix\Main\Type\DateTime();
		$start = $now;
		$end = null;

		if($waitType === static::WAIT_TYPE_BEFORE)
		{
			$targetField = (string)$this->WaitTarget;
			$document = CBPRuntime::GetRuntime()
				->GetService('DocumentService')
				->GetDocument($documentId);

			if (!isset($document[$targetField]))
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_TARGET"), 0, CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}

			$targetDate = $document[$targetField];
			$time = $targetDate !== '' ? MakeTimeStamp($targetDate) : false;
			if(!$time)
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_TARGET_TIME"), 0, CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}

			$endTime = $time - ($duration * 86400) - CTimeZone::GetOffset();

			$currentDate = new \Bitrix\Main\Type\Date();
			$end = \Bitrix\Main\Type\Date::createFromTimestamp($endTime);

			if($end->getTimestamp() <= $currentDate->getTimestamp())
			{
				$this->WriteToTrackingService(GetMessage("CRM_WAIT_ACTIVITY_EXECUTE_ERROR_WAIT_BEFORE"), 0, CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}
		}

		if($end === null)
		{
			$end = new \Bitrix\Main\Type\DateTime();
			$end->add("{$duration}D");
		}

		$waitDescription = $this->makeWaitIntervalDescription(
			$waitType,
			$duration,
			$targetField,
			CBPRuntime::GetRuntime()->GetService('DocumentService')->GetDocumentType($documentId)
		);

		if ($description !== '')
		{
			$waitDescription .= PHP_EOL . $description;
		}

		$arFields = array(
			'OWNER_TYPE_ID' => $ownerTypeId,
			'OWNER_ID' => $ownerId,
			'AUTHOR_ID' => $responsibleId,
			'START_TIME' => $start,
			'END_TIME' => $end,
			'COMPLETED' => 'N',
			'DESCRIPTION' => $waitDescription
		);

		$result = \Bitrix\Crm\Pseudoactivity\WaitEntry::add($arFields);
		if($result->isSuccess())
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

	public static function ValidateProperties($testProps = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (
			empty($testProps["WaitType"])
			|| $testProps["WaitType"] !== static::WAIT_TYPE_AFTER && $testProps["WaitType"] !== static::WAIT_TYPE_BEFORE
		)
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "WaitType", "message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_TYPE"));
		}

		if (empty($testProps["WaitDuration"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "WaitDuration", "message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_DURATION"));
		}

		if ($testProps["WaitType"] === static::WAIT_TYPE_BEFORE && empty($testProps["WaitTarget"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "WaitTarget", "message" => GetMessage("CRM_WAIT_ACTIVITY_ERROR_WAIT_TARGET"));
		}

		return array_merge($arErrors, parent::ValidateProperties($testProps, $user));
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

		$targetFields = static::getTargetFields($documentType);

		$dialog->setMap(array(
			'WaitType' => array(
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_TYPE'),
				'FieldName' => 'wait_type',
				'Type' => 'select',
				'Required' => true,
				'Options' => array(
					static::WAIT_TYPE_AFTER => GetMessage('CRM_WAIT_ACTIVITY_WAIT_AFTER'),
					static::WAIT_TYPE_BEFORE => GetMessage('CRM_WAIT_ACTIVITY_WAIT_BEFORE')
				),
				'Default' => static::WAIT_TYPE_AFTER
			),
			'WaitDuration' => array(
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DURATION'),
				'FieldName' => 'wait_duration',
				'Type' => 'int',
				'Default' => 1
			),
			'WaitTarget' => array(
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_TARGET'),
				'FieldName' => 'wait_target',
				'Type' => 'select',
				'Options' => static::makeSelectOptions($targetFields)
			),
			'WaitDescription' => array(
				'Name' => GetMessage('CRM_WAIT_ACTIVITY_WAIT_DESCRIPTION'),
				'FieldName' => 'wait_description',
				'Type' => 'text'
			),
		));

		$dialog->setRuntimeData(array(
			'targetDateFields' => $targetFields,
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$arProperties = array(
			'WaitType' => $arCurrentValues['wait_type'],
			'WaitDuration' => $arCurrentValues['wait_duration'],
			'WaitTarget' => $arCurrentValues['wait_target'],
			'WaitDescription' => $arCurrentValues['wait_description'],
		);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

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

			return GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_BEFORE', array(
				'#DURATION#' => $this->getDurationText($duration),
				'#TARGET_DATE#' => $dateCaption
			));
		}

		return GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_AFTER', array(
				'#DURATION#' => $this->getDurationText($duration),
			)
		);
	}

	private function getDurationText($duration)
	{
		if(($duration % 7) === 0)
		{
			$duration = $duration / 7;
			$result = $this->getNumberDeclension($duration,
				GetMessage('CRM_WAIT_ACTIVITY_WEEK_NOMINATIVE'),
				GetMessage('CRM_WAIT_ACTIVITY_WEEK_GENITIVE_SINGULAR'),
				GetMessage('CRM_WAIT_ACTIVITY_WEEK_GENITIVE_PLURAL')
			);

		}
		else
		{
			$result = $this->getNumberDeclension($duration,
				GetMessage('CRM_WAIT_ACTIVITY_DAY_NOMINATIVE'),
				GetMessage('CRM_WAIT_ACTIVITY_DAY_GENITIVE_SINGULAR'),
				GetMessage('CRM_WAIT_ACTIVITY_DAY_GENITIVE_PLURAL')
			);
		}

		return $duration . ' ' . $result;
	}

	private function getNumberDeclension($val, $nominative, $genitiveSingular, $genitivePlural)
	{
		if ($val > 20)
			$val = ($val % 10);

		if ($val == 1)
			return $nominative;
		elseif ($val > 1 && $val < 5)
			return $genitiveSingular;
		else
			return $genitivePlural;
	}

	private static function getTargetFields(array $documentType)
	{
		$targetFields = array();
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
					strpos($fieldId, 'UF_') === 0
					&& $field['Type'] === 'datetime'
				)
			)
			{
				$targetFields[] = array(
					'name' => $fieldId,
					'caption' => $field['Name']
				);
			}
		}

		return $targetFields;
	}

	private static function makeSelectOptions(array $targetFields)
	{
		$options = array();
		foreach ($targetFields as $field)
		{
			$options[$field['name']] = $field['caption'];
		}
		return $options;
	}
}