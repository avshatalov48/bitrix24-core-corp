<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskCopyMoveActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"EntityType" => "",
			"EntityId" => null,
			"SourceId" => '',
			"Operation" => 'copy',
			'Operator' => null,

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
			$this->WriteToTrackingService(GetMessage('BPDCM_TARGET_ERROR'));
			return CBPActivityExecutionStatus::Closed;
		}

		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$objectIds = $detailUrls = $downloadUrls = array();

		$operator = CBPHelper::ExtractUsers($this->Operator, $this->GetDocumentId(), true);
		if (!$operator)
			$operator = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

		foreach ((array) $this->SourceId as $sourceId)
		{

			$sourceObject = \Bitrix\Disk\BaseObject::loadById($sourceId);

			if (!$sourceObject)
			{
				$this->WriteToTrackingService(GetMessage('BPDCM_SOURCE_ERROR'));
				continue;
			}

			if ($this->Operation == 'move')
			{
				$sourceObject = $sourceObject->moveTo($folder, $operator, true);
			}
			else
			{
				$sourceObject = $sourceObject->copyTo($folder, $operator, true);
			}

			if (!$sourceObject)
			{
				$this->WriteToTrackingService(GetMessage('BPDCM_OPERATION_ERROR'));
				continue;
			}

			$isFolder = ($sourceObject instanceof \Bitrix\Disk\Folder);

			$objectIds[] = $sourceObject->getId();
			$detailUrls[] = $urlManager->encodeUrn(
				$urlManager->getHostUrl()
				.($isFolder ? $urlManager->getPathFolderList($sourceObject) : $urlManager->getPathFileDetail($sourceObject))
			);

			$downloadUrls[] = $isFolder ? '' : $urlManager->getUrlForDownloadFile($sourceObject, true);
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
				"message"   => GetMessage("BPDCM_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['EntityType']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityType", "message" => GetMessage("BPDCM_EMPTY_ENTITY_TYPE"));
		if (empty($arTestProperties['EntityId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "EntityId", "message" => GetMessage("BPDCM_EMPTY_ENTITY_ID"));
		if (empty($arTestProperties['SourceId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceId", "message" => GetMessage("BPDCM_EMPTY_SOURCE_ID"));

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
			"SourceId" => 'source_id',
			'Operation' => 'operation',
			'Operator' => 'operator',
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

		if (
			empty($currentValues['source_id'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$currentValues['source_id'] = $currentValues['source_id_x'];

		if (empty($currentValues['operation']))
			$currentValues['operation'] = 'copy';

		if ($currentValues['entity_type'] == 'user' && !CBPDocument::IsExpression($currentValues['entity_id']))
			$currentValues['entity_id'] = CBPHelper::UsersArrayToString($currentValues['entity_id'], $arWorkflowTemplate, $documentType);

		if (!CBPDocument::IsExpression($currentValues['operator']))
			$currentValues['operator'] = CBPHelper::UsersArrayToString($currentValues['operator'], $arWorkflowTemplate, $documentType);

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
			"source_id" => "SourceId",
			'operator' => 'Operator',
		);

		$properties = array('EntityId' => '', 'Operation' => 'copy');
		foreach ($arMap as $key => $value)
		{
			$properties[$value] = $currentValues[$key];
		}

		if (in_array($properties['EntityType'], array('user', 'sg', 'common', 'folder')))
			$properties['EntityId'] = $currentValues['entity_id_'.$properties['EntityType']];

		if (
			empty($properties['EntityId'])
			&& isset($currentValues['entity_id_'.$properties['EntityType'].'_x'])
			&& CBPDocument::IsExpression($currentValues['entity_id_'.$properties['EntityType'].'_x'])
		)
			$properties['EntityId'] = $currentValues['entity_id_'.$properties['EntityType'].'_x'];

		if (
			empty($properties['SourceId'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$properties['SourceId'] = $currentValues['source_id_x'];

		if ($currentValues['operation'] == 'move')
			$properties['Operation'] = 'move';

		if ($properties['EntityType'] == 'user' && !CBPDocument::IsExpression($properties['EntityId']))
			$properties['EntityId'] = CBPHelper::UsersStringToArray($properties['EntityId'], $documentType, $arErrors);

		if (!CBPDocument::IsExpression($properties['Operator']))
			$properties['Operator'] = CBPHelper::UsersStringToArray($properties['Operator'], $documentType, $arErrors);

		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}
}
?>