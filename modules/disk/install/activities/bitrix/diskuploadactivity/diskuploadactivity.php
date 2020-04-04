<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskUploadActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"EntityType" => "",
			"EntityId" => "",
			"SourceFile" => null,
			'CreatedBy' => null,

			//return properties
			'ObjectId' => null,
			'DetailUrl' => null,
			'DownloadUrl' => null,
		);

		//return properties mapping
		$this->SetPropertiesTypes(array(
			'ObjectId' => array(
				'Type' => 'int',
				'Multiple' => true,
			),
			'DetailUrl' => array(
				'Type' => 'string',
				'Multiple' => true,
			),
			'DownloadUrl' => array(
				'Type' => 'string',
				'Multiple' => true,
			),
		));
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->ObjectId = null;
		$this->DetailUrl = null;
		$this->DownloadUrl = null;
	}

	private function getTargetFolder($entityType, $entityId)
	{
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
			$this->WriteToTrackingService(GetMessage('BPDUA_TARGET_ERROR'));
			return CBPActivityExecutionStatus::Closed;
		}

		$files = (array) $this->ParseValue($this->getRawProperty('SourceFile'), 'file');
		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$objectIds = $detailUrls = $downloadUrls = array();

		$createdBy = CBPHelper::ExtractUsers($this->CreatedBy, $this->GetDocumentId(), true);
		if (!$createdBy)
			$createdBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

		foreach ($files as $file)
		{
			//$file = (int) $file; // some documents return file field value based on file path, CFile::MakeFileArray handles this situation

			$fileArray = CFile::MakeFileArray($file);
			if (!is_array($fileArray))
			{
				$this->WriteToTrackingService(GetMessage('BPDUA_SOURCE_ERROR'));
				continue;
			}

			$uploadedFile = $folder->uploadFile($fileArray, array(
				'NAME'       => $fileArray['name'],
				'CREATED_BY' => $createdBy,
			), array(), true
			);
			if ($uploadedFile)
			{
				$objectIds[] = $uploadedFile->getId();
				$downloadUrls[] = $urlManager->getUrlForDownloadFile($uploadedFile, true);
				$detailUrls[] = $urlManager->encodeUrn($urlManager->getHostUrl().$urlManager->getPathFileDetail($uploadedFile));
			}
			else
				$this->WriteToTrackingService(GetMessage('BPDUA_UPLOAD_ERROR'));
		}

		$this->ObjectId = $objectIds;
		$this->DownloadUrl = $downloadUrls;
		$this->DetailUrl = $detailUrls;

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
				"message"   => GetMessage("BPDUA_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['EntityType']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityType", "message" => GetMessage("BPDUA_EMPTY_ENTITY_TYPE"));
		if (empty($arTestProperties['EntityId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityId", "message" => GetMessage("BPDUA_EMPTY_ENTITY_ID"));
		if (empty($arTestProperties['SourceFile']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceFile", "message" => GetMessage("BPDUA_EMPTY_SOURCE_FILE"));

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
			"SourceFile" => 'source_file',
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
			"source_file" => "SourceFile",
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