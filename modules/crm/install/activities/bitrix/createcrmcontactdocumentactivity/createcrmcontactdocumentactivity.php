<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

/** @property-write string|null ErrorMessage */
class CBPCreateCrmContactDocumentActivity extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		//return
		$this->arProperties["ContactId"] = 0;
		$this->arProperties["ErrorMessage"] = null;

		$this->setPropertiesTypes([
			'ContactId' => ['Type' => 'int'],
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

		if (is_array($fields) && method_exists($this, 'prepareFieldsValues'))
		{
			$fields = $this->prepareFieldsValues($documentType, $fields);
		}

		try
		{
			$this->ContactId = $documentService->CreateDocument($documentType, $fields);
		}
		catch (Exception $e)
		{
			$this->WriteToTrackingService($e->getMessage(), 0, CBPTrackingType::Error);
			$this->ErrorMessage = $e->getMessage();
		}

		if ($this->ContactId)
		{
			$this->fixResult($this->makeResultFromId($this->ContactId));
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getCreatedDocumentType(): array
	{
		return \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->ContactId = 0;
		$this->ErrorMessage = null;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return(array('code' => 'NotLoaded', 'module'=> 'crm', 'message'=> GetMessage('BPCDA_MODULE_NOT_LOADED')));
		};

		$arErrors = array();

		$arDocumentFields = CCrmDocumentContact::GetDocumentFields('CONTACT');
		$arTestFields = isset($arTestProperties['Fields']) && is_array($arTestProperties['Fields']) ? $arTestProperties['Fields'] : array();

		$name = isset($arTestFields['NAME']) ? $arTestFields['NAME'] : '';
		if($name === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'NAME', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['NAME']['Name'])));
		}

		$lastName = isset($arTestFields['LAST_NAME']) ? $arTestFields['LAST_NAME'] : '';
		if($lastName === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'LAST_NAME', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['LAST_NAME']['Name'])));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		};

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Contact);
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
