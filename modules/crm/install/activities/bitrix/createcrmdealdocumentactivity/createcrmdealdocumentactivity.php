<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

/** @property-write string|null ErrorMessage */
class CBPCreateCrmDealDocumentActivity extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		//return
		$this->arProperties["DealId"] = 0;
		$this->arProperties["ErrorMessage"] = null;

		$this->setPropertiesTypes([
			'DealId' => ['Type' => 'int'],
			'ErrorMessage' => ['Type' => 'string'],
		]);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentType = $this->getCreatedDocumentType();
		$documentService = $this->workflow->GetService('DocumentService');

		$fields = $this->Fields;
		if (!is_array($fields))
		{
			$fields = [];
		}

		if (method_exists($this, 'prepareFieldsValues'))
		{
			$fields = $this->prepareFieldsValues($documentType, $fields);
		}
		else
		{
			foreach (['BEGINDATE', 'CLOSEDATE'] as $dateFields)
			{
				if (isset($fields[$dateFields]))
				{
					$fieldTypeObject = $documentService->getFieldTypeObject($documentType, ['Type' => 'datetime']);
					if ($fieldTypeObject)
					{
						$fields[$dateFields] = $fieldTypeObject->externalizeValue(
							'Document', // FieldType::EXTERNALIZE_CONTEXT_DOCUMENT
							$fields[$dateFields]
						);
					}
				}
			}
		}

		try
		{
			$this->DealId = $documentService->CreateDocument($documentType, $fields);
		}
		catch (Exception $e)
		{
			$this->WriteToTrackingService($e->getMessage(), 0, CBPTrackingType::Error);
			$this->ErrorMessage = $e->getMessage();
		}

		if ($this->DealId)
		{
			$this->fixResult($this->makeResultFromId($this->DealId));
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getCreatedDocumentType(): array
	{
		return \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->DealId = 0;
		$this->ErrorMessage = null;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return (['code' => 'NotLoaded', 'module' => 'crm', 'message' => GetMessage('BPCDA_MODULE_NOT_LOADED')]);
		};

		$arErrors = [];

		$arDocumentFields = CCrmDocumentDeal::GetDocumentFields('DEAL');

		$arTestFields = isset($arTestProperties['Fields']) && is_array($arTestProperties['Fields'])
			? $arTestProperties['Fields'] : [];
		$title = isset($arTestFields['TITLE']) ? $arTestFields['TITLE'] : '';
		if ($title === '')
		{
			$arErrors[] = [
				'code' => 'NotExist',
				'parameter' => 'TITLE',
				'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', ['#NAME#' => $arDocumentFields['TITLE']['Name']]),
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters,
		$arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters,
			$arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate,
		&$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate,
			$arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
