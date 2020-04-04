<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskRemoveActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"SourceId" => null,
			'DeletedBy' => null,
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("disk"))
			return CBPActivityExecutionStatus::Closed;

		$deletedBy = CBPHelper::ExtractUsers($this->DeletedBy, $this->GetDocumentId(), true);
		if (!$deletedBy)
			$deletedBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

		foreach ((array) $this->SourceId as $sourceId)
		{
			$sourceObject = \Bitrix\Disk\BaseObject::loadById($sourceId);

			if (!$sourceObject)
			{
				$this->WriteToTrackingService(GetMessage('BPDRMV_SOURCE_ERROR'));
				continue;
			}

			if (!$sourceObject->markDeleted($deletedBy))
			{
				$this->WriteToTrackingService(GetMessage('BPDRMV_REMOVE_ERROR'));
			}
		}
		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();
		if ($user && !$user->isAdmin())
		{
			$arErrors[] = array(
				"code"      => "AccessDenied",
				"parameter" => "Admin",
				"message"   => GetMessage("BPDRMV_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['SourceId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceId", "message" => GetMessage("BPDRMV_EMPTY_SOURCE_ID"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $currentValues = null, $formName = "")
	{
		if (!CModule::IncludeModule("disk"))
			return '';

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"SourceId" => 'source_id',
			'DeletedBy' => 'deleted_by'
		);

		if (!is_array($currentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			foreach ($arMap as $k => $v)
			{
				$currentValues[$arMap[$k]] = isset($arCurrentActivity["Properties"][$k]) ? $arCurrentActivity["Properties"][$k] : '';
			}
		}

		if (
			empty($currentValues['source_id'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$currentValues['source_id'] = $currentValues['source_id_x'];

		if (!CBPDocument::IsExpression($currentValues['deleted_by']))
			$currentValues['deleted_by'] = CBPHelper::UsersArrayToString($currentValues['deleted_by'], $arWorkflowTemplate, $documentType);

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $currentValues,
				"formName" => $formName,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $currentValues, &$arErrors)
	{
		$arErrors = array();

		$properties = array('SourceId' => $currentValues['source_id'], 'DeletedBy' => $currentValues['deleted_by']);

		if (
			empty($properties['SourceId'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$properties['SourceId'] = $currentValues['source_id_x'];

		if (!CBPDocument::IsExpression($properties['DeletedBy']))
			$properties['DeletedBy'] = CBPHelper::UsersStringToArray($properties['DeletedBy'], $documentType, $arErrors);

		$arErrors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}
}
?>