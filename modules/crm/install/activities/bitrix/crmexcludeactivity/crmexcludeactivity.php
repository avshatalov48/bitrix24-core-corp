<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmExcludeActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => ""
		];
	}

	public function Execute()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		[$entityTypeName, $entityId] = explode('_', $documentId[2]);

		//add to exclusion list
		try
		{
			\Bitrix\Crm\Exclusion\Manager::excludeEntity(
				\CCrmOwnerType::ResolveID($entityTypeName),
				$entityId,
				false,
				['COMMENT' => GetMessage('CRM_EXA_COMMENT')]
			);

			$map = $this->getDebugInfo(
				[
					'ExcludedId' => $entityId
				],
				[
					'ExcludedId' => [
						'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_EXA_DEBUG_MESSAGE'),
						'Type' => 'text',
						'Required' => true,
					]
				],
			);
			$this->writeDebugInfo($map);
		}
		catch(\Bitrix\Main\SystemException $ex)
		{
			$this->WriteToTrackingService($ex->getMessage(), 0, CBPTrackingType::Error);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
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

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		return true;
	}
}