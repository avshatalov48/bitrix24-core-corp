<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

/** @property-write string|null ErrorMessage */
class CBPCreateCrmLeadDocumentActivity extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		//return
		$this->arProperties["LeadId"] = 0;
		$this->arProperties["ErrorMessage"] = null;

		$this->setPropertiesTypes([
			'LeadId' => ['Type' => 'int'],
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

		try
		{
			$this->LeadId = $documentService->CreateDocument($documentType, $fields);
		}
		catch (Exception $e)
		{
			$this->WriteToTrackingService($e->getMessage(), 0, CBPTrackingType::Error);
			$this->ErrorMessage = $e->getMessage();
		}

		if ($this->LeadId)
		{
			$this->fixResult($this->makeResultFromId($this->LeadId));
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getCreatedDocumentType(): array
	{
		return \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Lead);
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->LeadId = 0;
		$this->ErrorMessage = null;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return(array('code' => 'NotLoaded', 'module'=> 'crm', 'message'=> GetMessage('BPCDA_MODULE_NOT_LOADED')));
		};

		$arErrors = array();

		$arDocumentFields = CCrmDocumentDeal::GetDocumentFields('DEAL');

		$arTestFields = isset($arTestProperties['Fields']) && is_array($arTestProperties['Fields']) ? $arTestProperties['Fields'] : array();
		$title = isset($arTestFields['TITLE']) ? $arTestFields['TITLE'] : '';
		if($title === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'TITLE', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['TITLE']['Name'])));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}


	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Lead);
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Lead);
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
