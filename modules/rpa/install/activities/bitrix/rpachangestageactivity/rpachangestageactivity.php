<?php

use Bitrix\Rpa;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPRpaChangeStageActivity extends CBPActivity
{
	private const NEXT_STAGE = ':next:';
	private const PREVIOUS_STAGE = ':previous:';

	private static $counter = [];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"ModifiedBy" => null,
			'TargetStageId' => null
		);
	}

	public function Execute()
	{
		if (!\Bitrix\Main\Loader::includeModule('rpa'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$targetStageId = $this->TargetStageId;
		$currentStage = $this->getCurrentStage();
		$allStages = array_keys(self::getTypeStages($this->getDocumentType()));

		if ($this->isVirtualStage($targetStageId))
		{
			$targetStageId = $this->resolveVirtualStage($currentStage, $targetStageId, $allStages);
		}
		$targetStageId = (int) $targetStageId;

		if ($currentStage === $targetStageId)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!in_array($targetStageId, $allStages, true))
		{
			$this->WriteToTrackingService(GetMessage("RPA_BP_CHS_INCORRECT_TARGET_STAGE_ID"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$this->checkCycling($targetStageId, $this->getDocumentId());

		$this->workflow->GetService('DocumentService')->UpdateDocument(
			$this->getDocumentId(),
			['STAGE_ID' => $targetStageId],
			$this->ModifiedBy
		);

		$this->workflow->Terminate();

		throw new Exception(GetMessage('RPA_BP_CHS_TERMINATED'), CBPRuntime::EXCEPTION_CODE_INSTANCE_TERMINATED);
	}

	private function getCurrentStage()
	{
		$document = $this->workflow->getService('DocumentService')->getDocument($this->getDocumentId());

		return (int) $document['STAGE_ID'];
	}

	private function isVirtualStage($stageId)
	{
		return ($stageId === self::NEXT_STAGE || $stageId === self::PREVIOUS_STAGE);
	}

	private function resolveVirtualStage($current, $target, $allStages)
	{
		$key = array_search($current, $allStages);

		if ($key !== false)
		{
			if ($target === self::NEXT_STAGE && isset($allStages[$key+1]))
			{
				return $allStages[$key+1];
			}
			elseif ($target === self::PREVIOUS_STAGE && isset($allStages[$key-1]))
			{
				return $allStages[$key-1];
			}
		}

		return null;
	}


	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties['TargetStageId']))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "Name", "message" => GetMessage("RPA_BP_CHS_EMPTY_TARGET_STAGE_ID"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		if (!\Bitrix\Main\Loader::includeModule('rpa'))
		{
			return false;
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues
		]);

		$stages = self::getDocumentStageOptions($documentType);

		$dialog->setMap([
			"TargetStageId" => [
				'Name' => GetMessage('RPA_BP_CHS_TARGET_STAGE_ID'),
				'FieldName' => 'target_stage_id',
				'Type' => 'select',
				'Options' => $stages
			],
			"ModifiedBy" => [
				'Name' => GetMessage('RPA_BP_CHS_MODIFIED_BY'),
				'FieldName' => 'modified_by',
				'Type' => 'user',
				'Required' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
			],
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$arProperties["ModifiedBy"] = CBPHelper::UsersStringToArray($arCurrentValues["modified_by"], $documentType, $errors);
		$arProperties["TargetStageId"] = $arCurrentValues['target_stage_id'];
		if ($errors)
		{
			return false;
		}

		$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private static function getDocumentStageOptions(array $docType)
	{
		$stages = [
			self::NEXT_STAGE => sprintf('[%s]', GetMessage('RPA_BP_CHS_NEXT_STAGE_ID')),
			self::PREVIOUS_STAGE => sprintf('[%s]', GetMessage('RPA_BP_CHS_PREVIOUS_STAGE_ID')),
		];

		return $stages + self::getTypeStages($docType);
	}

	private static function getTypeStages(array $docType)
	{
		$stages = [];
		$typeId = str_replace('T', '', $docType[2]);
		$type = Rpa\Model\TypeTable::getById($typeId)->fetchObject();
		if ($type)
		{
			foreach($type->getStages() as $stage)
			{
				$stages[(int) $stage->getId()] = $stage->getName();
			}
		}
		return $stages;
	}

	private function checkCycling($stageId, $documentId)
	{
		if (!isset(static::$counter[$documentId[2]]))
			static::$counter[$documentId[2]] = [];
		if (!isset(static::$counter[$documentId[2]][$stageId]))
			static::$counter[$documentId[2]][$stageId] = 0;

		++static::$counter[$documentId[2]][$stageId];

		if (static::$counter[$documentId[2]][$stageId] > 2)
		{
			CBPDocument::TerminateWorkflow(
				$this->GetWorkflowInstanceId(),
				$documentId,
				$errors,
				GetMessage('RPA_BP_CHS_TERMINATED_CYCLING')
			);

			//Stop running queue
			throw new Exception("TerminateWorkflow");
		}
	}
}