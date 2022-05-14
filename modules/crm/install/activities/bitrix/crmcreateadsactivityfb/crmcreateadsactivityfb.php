<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CBPCrmCreateAdsActivityVk');

class CBPCrmCreateAdsActivityFb extends CBPCrmCreateAdsActivityVk
{
	protected static function getAdsType()
	{
		return 'facebook';
	}

	public function Execute()
	{
		if (static::isRestricted())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		return parent::Execute();
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
		if (static::isRestricted())
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

			$dialog->setRenderer(function () {
				return '<div class="ui-alert ui-alert-danger">
					<span class="ui-alert-message">'
					. GetMessage('CRM_CREATE_ADS_FB_RESTRICTED')
					.'</span>
				</div>';
			});

			return $dialog;
		}

		return parent::GetPropertiesDialog(
			$documentType,
			$activityName,
			$arWorkflowTemplate,
			$arWorkflowParameters,
			$arWorkflowVariables,
			$arCurrentValues,
			$formName,
			$popupWindow,
			$siteId
		);
	}

	protected static function isRestricted(): bool
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		return ($region === null || $region === 'ru');
	}
}
