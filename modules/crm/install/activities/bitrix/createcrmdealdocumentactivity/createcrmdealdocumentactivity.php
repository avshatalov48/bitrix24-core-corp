<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

class CBPCreateCrmDealDocumentActivity extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties["DealId"] = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal);
		$documentService = $this->workflow->GetService('DocumentService');

		$fields = $this->Fields;
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
							'Document',
							$fields[$dateFields]
						);
					}
				}
			}
		}

		$this->DealId = $documentService->CreateDocument($documentType, $fields);

		return CBPActivityExecutionStatus::Closed;
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
