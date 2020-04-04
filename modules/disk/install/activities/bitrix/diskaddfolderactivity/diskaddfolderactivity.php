<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskAddFolderActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"EntityType" => "",
			"EntityId" => "",
			'CreatedBy' => null,
			"FolderName" => '',

			//return properties
			'ObjectId' => 0,
			'DetailUrl' => '',
		);

		//return properties mapping
		$this->SetPropertiesTypes(array(
			'ObjectId' => array(
				'Type' => 'int',
			),
			'DetailUrl' => array(
				'Type' => 'string',
			),
		));
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->ObjectId = 0;
		$this->DetailUrl = '';
	}

	private function getTargetFolder($entityType, $entityId)
	{
		if (is_array($entityId))
		{
			$entityId = current($entityId);
		}

		if ($entityType == 'folder')
		{
			return \Bitrix\Disk\Folder::loadById($entityId);
		}

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		switch ($entityType)
		{
			case 'user':
				$entityType = \Bitrix\Disk\ProxyType\User::className();
				$entityId = CBPHelper::ExtractUsers($entityId, $documentId, true);
				break;
			case 'sg':
				$entityType = \Bitrix\Disk\ProxyType\Group::className();
				break;
			case 'common':
				$entityType = \Bitrix\Disk\ProxyType\Common::className();
				break;
			default:
				$entityType = null;
		}

		if ($entityType)
		{
			$storage = \Bitrix\Disk\Storage::load(array(
				'=ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType,
			));
			if ($storage)
				return $storage->getRootObject();
		}
		return false;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("disk"))
			return CBPActivityExecutionStatus::Closed;

		$folder = $this->getTargetFolder($this->EntityType, $this->EntityId);

		if (!$folder)
		{
			$this->WriteToTrackingService(GetMessage('BPDAF_TARGET_ERROR'));
			return CBPActivityExecutionStatus::Closed;
		}

		$folderName = \Bitrix\Disk\Ui\Text::correctFolderName($this->FolderName);
		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

		$createdBy = CBPHelper::ExtractUsers($this->CreatedBy, $this->GetDocumentId(), true);
		if (!$createdBy)
			$createdBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

		$newFolder = $folder->addSubFolder(array('NAME' => $folderName, 'CREATED_BY' => $createdBy));
		if(!$newFolder && $folder->getErrorByCode(\Bitrix\Disk\BaseObject::ERROR_NON_UNIQUE_NAME))
		{
			$newFolder = \Bitrix\Disk\Folder::load(array(
				'=NAME' => $folderName,
				'PARENT_ID' => $folder->getId()
			));
		}
		if ($newFolder)
		{
			$this->ObjectId = $newFolder->getId();
			$this->DetailUrl = $urlManager->encodeUrn($urlManager->getHostUrl().$urlManager->getPathFolderList($newFolder));
		}
		else
			$this->WriteToTrackingService(GetMessage('BPDAF_ADD_FOLDER_ERROR'));

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
				"message"   => GetMessage("BPDAF_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['EntityType']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityType", "message" => GetMessage("BPDAF_EMPTY_ENTITY_TYPE"));
		if (empty($arTestProperties['EntityId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityId", "message" => GetMessage("BPDAF_EMPTY_ENTITY_ID"));
		if (empty($arTestProperties['FolderName']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "FolderName", "message" => GetMessage("BPDAF_EMPTY_FOLDER_NAME"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $currentValues = null, $formName = "")
	{
		if (!CModule::IncludeModule("disk"))
			return '';

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"EntityType" => "entity_type",
			"EntityId" => "entity_id",
			"FolderName" => 'folder_name',
			'CreatedBy'	=> 'created_by',
		);

		if (!is_array($currentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			foreach ($arMap as $k => $v)
			{
				$currentValues[$arMap[$k]] = isset($arCurrentActivity["Properties"][$k]) ? $arCurrentActivity["Properties"][$k] : '';
			}
		}

		if (empty($currentValues['entity_type']))
			$currentValues['entity_type'] = 'user';
		if (!empty($currentValues['entity_id_'.$currentValues['entity_type']]))
			$currentValues['entity_id'] = $currentValues['entity_id_'.$currentValues['entity_type']];

		if (
			empty($currentValues['entity_id'])
			&& isset($currentValues['entity_id_'.$currentValues['entity_type'].'_x'])
			&& CBPDocument::IsExpression($currentValues['entity_id_'.$currentValues['entity_type'].'_x'])
		)
			$currentValues['entity_id'] = $currentValues['entity_id_'.$currentValues['entity_type'].'_x'];

		if ($currentValues['entity_type'] == 'user' && !CBPDocument::IsExpression($currentValues['entity_id']))
			$currentValues['entity_id'] = CBPHelper::UsersArrayToString($currentValues['entity_id'], $arWorkflowTemplate, $documentType);

		if (!CBPDocument::IsExpression($currentValues['created_by']))
			$currentValues['created_by'] = CBPHelper::UsersArrayToString($currentValues['created_by'], $arWorkflowTemplate, $documentType);

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

		$arMap = array(
			"entity_type" => "EntityType",
			"folder_name" => "FolderName",
			'created_by' => 'CreatedBy',
		);

		$arProperties = array('EntityId' => '');
		foreach ($arMap as $key => $value)
		{
			$arProperties[$value] = $currentValues[$key];
		}

		if (in_array($arProperties['EntityType'], array('user', 'sg', 'common', 'folder')))
			$arProperties['EntityId'] = $currentValues['entity_id_'.$arProperties['EntityType']];

		if (
			empty($arProperties['EntityId'])
			&& isset($currentValues['entity_id_'.$arProperties['EntityType'].'_x'])
			&& CBPDocument::IsExpression($currentValues['entity_id_'.$arProperties['EntityType'].'_x'])
		)
			$arProperties['EntityId'] = $currentValues['entity_id_'.$arProperties['EntityType'].'_x'];

		if ($arProperties['EntityType'] == 'user' && !CBPDocument::IsExpression($arProperties['EntityId']))
			$arProperties['EntityId'] = CBPHelper::UsersStringToArray($arProperties['EntityId'], $documentType, $arErrors);

		if (!CBPDocument::IsExpression($arProperties['CreatedBy']))
			$arProperties['CreatedBy'] = CBPHelper::UsersStringToArray($arProperties['CreatedBy'], $documentType, $arErrors);

		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>