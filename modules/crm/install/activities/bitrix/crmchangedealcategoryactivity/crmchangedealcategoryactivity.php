<?php

use Bitrix\Bizproc\WorkflowInstanceTable;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmChangeDealCategoryActivity extends CBPActivity
{
	private static $cycleCounter = [];
	const CYCLE_LIMIT = 3;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'CategoryId' => 0,
			'StageId' => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->logDebug();

		$documentId = $this->GetDocumentId();
		$this->checkCycling($documentId);

		$sourceDealId = explode('_', $documentId[2])[1];
		$sourceFields = [];

		if ($sourceDealId > 0)
		{
			$dbResult = \CCrmDeal::GetListEx(
				[],
				['=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				['ID', 'ASSIGNED_BY_ID', 'STAGE_ID', 'CATEGORY_ID']
			);
			$sourceFields = $dbResult->Fetch();
		}

		if (!$sourceFields)
		{
			$this->WriteToTrackingService(Loc::getMessage('CRM_CDCA_NO_SOURCE_FIELDS'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$resultError = \CCrmDeal::MoveToCategory(
			$sourceDealId,
			(int)$this->CategoryId,
			[
				'ENABLE_WORKFLOW_CHECK' => false,
				'USER_ID' => $sourceFields['ASSIGNED_BY_ID'],
				'PREFERRED_STAGE_ID' => $this->StageId,
			]
		);

		if ($resultError === Crm\Category\DealCategoryChangeError::NONE)
		{
			//stop all Workflows
			$documentId = $this->GetDocumentId();
			$instanceIds = WorkflowInstanceTable::getIdsByDocument($documentId);
			$instanceIds[] = $this->GetWorkflowInstanceId();
			$instanceIds = array_unique($instanceIds);

			foreach ($instanceIds as $instanceId)
			{
				\CBPDocument::TerminateWorkflow(
					$instanceId,
					$documentId,
					$errors,
					Loc::getMessage('CRM_CDCA_MOVE_TERMINATION_TITLE')
				);
			}

			//Fake document update for clearing document cache
			/** @var CBPDocumentService $ds */
			$ds = $this->workflow->GetService('DocumentService');
			$ds->UpdateDocument($documentId, []);

			$dbResult = \CCrmDeal::GetListEx(
				[],
				['=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				['ID', 'ASSIGNED_BY_ID', 'STAGE_ID', 'CATEGORY_ID']
			);
			$newFields = $dbResult->Fetch();

			if ($newFields)
			{
				//Region automation
				$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $sourceDealId);
				$starter->setContextToBizproc();
				$starter->runOnUpdate($newFields, $sourceFields);
				//End region
			}

			//Stop running queue
			throw new Exception("TerminateWorkflow");
		}
		else
		{
			$this->WriteToTrackingService(
				$this->resolveMoveCategoryErrorText($resultError),
				0,
				CBPTrackingType::Error
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function logDebug()
	{
		$this->writeDebugInfo($this->getDebugInfo([
			'StageId' => Crm\Category\DealCategory::getStageName($this->StageId),
		]));
	}

	private function checkCycling(array $documentId)
	{
		//check deal only.
		if (!($documentId[0] === 'crm' && $documentId[1] === 'CCrmDocumentDeal'))
		{
			return true;
		}

		$key = $this->GetName();
		$documentIdKey = implode('@', $documentId);

		if (!isset(self::$cycleCounter[$key][$documentIdKey]))
		{
			self::$cycleCounter[$key][$documentIdKey] = 0;
		}

		self::$cycleCounter[$key][$documentIdKey]++;
		if (self::$cycleCounter[$key][$documentIdKey] > self::CYCLE_LIMIT)
		{
			$this->WriteToTrackingService(
				Loc::getMessage("CRM_CDCA_CYCLING_ERROR"),
				0,
				CBPTrackingType::Error
			);

			throw new Exception(Loc::getMessage('CRM_CDCA_CYCLING_EXCEPTION_MESSAGE'));
		}

		return true;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if ($arTestProperties["CategoryId"] === null || $arTestProperties["CategoryId"] === '')
		{
			$errors[] = ["code" => "NotExist", "parameter" => "CategoryId", "message" => Loc::getMessage("CRM_CDCA_EMPTY_CATEGORY")];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
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

		$dialog->setMap(static::getPropertiesDialogMap());

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$arProperties = ['CategoryId' => $arCurrentValues['category_id']];

		if ($arProperties['CategoryId'] === '' && static::isExpression($arCurrentValues['category_id_text']))
		{
			$arProperties['CategoryId'] = $arCurrentValues['category_id_text'];
		}

		$documentService = CBPRuntime::GetRuntime(true)->getDocumentService();
		$field = $documentService->getFieldTypeObject($documentType, static::getPropertiesDialogMap()['StageId']);
		if ($field)
		{
			$arProperties['StageId'] = $field->extractValue(
				['Field' => 'stage_id'],
				$arCurrentValues,
				$errors
			);
		}

		$errors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	protected static function getPropertiesDialogMap(): array
	{
		return [
			'CategoryId' => [
				'Name' => Loc::getMessage('CRM_CDCA_CATEGORY'),
				'FieldName' => 'category_id',
				'Type' => 'deal_category',
			],
			'StageId' => [
				'Name' => Loc::getMessage('CRM_CDCA_STAGE'),
				'FieldName' => 'stage_id',
				'Type' => 'deal_stage',
			],
		];
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = static::getPropertiesDialogMap();
		$map['StageId']['Type'] = \Bitrix\Bizproc\FieldType::STRING;

		return $map;
	}

	private function resolveMoveCategoryErrorText($errorCode)
	{
		switch ($errorCode)
		{
			case Crm\Category\DealCategoryChangeError::CATEGORY_NOT_FOUND:
			{
				$text = Loc::getMessage('CRM_CDCA_MOVE_ERROR_CATEGORY_NOT_FOUND');
				break;
			}
			case Crm\Category\DealCategoryChangeError::CATEGORY_NOT_CHANGED:
			{
				$text = Loc::getMessage('CRM_CDCA_MOVE_ERROR_CATEGORY_NOT_CHANGED');
				break;
			}
			case Crm\Category\DealCategoryChangeError::RESPONSIBLE_NOT_FOUND:
			{
				$text = Loc::getMessage('CRM_CDCA_MOVE_ERROR_RESPONSIBLE_NOT_FOUND');
				break;
			}
			case Crm\Category\DealCategoryChangeError::STAGE_NOT_FOUND:
			{
				$text = Loc::getMessage('CRM_CDCA_MOVE_ERROR_STAGE_NOT_FOUND');
				break;
			}
			case Crm\Category\DealCategoryChangeError::RESTRICTION_APPLIED:
			{
				$text = Crm\Restriction\RestrictionManager::getWebFormResultsRestriction()->getErrorMessage();
				break;
			}

			default:
			{
				$text = Loc::getMessage('CRM_CDCA_MOVE_ERROR', ['#ERROR_CODE#' => $errorCode]);
			}
		}

		return $text;
	}
}