<?
use Bitrix\Disk\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskUploadVersionActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"SourceId" => "",
			"SourceFile" => 0,
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

	public function Execute()
	{
		if (!CModule::IncludeModule("disk"))
			return CBPActivityExecutionStatus::Closed;

		$sourceId = $this->SourceId;
		if (is_array($sourceId)) //can be multiple
		{
			reset($sourceId);
			$sourceId = current($sourceId);
		}

		/** @var \Bitrix\Disk\File $file */
		$file = \Bitrix\Disk\File::loadById($sourceId);
		if (!$file)
		{
			$this->WriteToTrackingService(GetMessage('BPDUV_SOURCE_ID_ERROR'));
			return CBPActivityExecutionStatus::Closed;
		}

		$files = (array) $this->ParseValue($this->getRawProperty('SourceFile'), 'file');
		$urlManager = Driver::getInstance()->getUrlManager();
		$objectIds = $detailUrls = $downloadUrls = array();

		$createdBy = CBPHelper::ExtractUsers($this->CreatedBy, $this->GetDocumentId(), true);
		if (!$createdBy)
			$createdBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

		foreach ($files as $versionFile)
		{
			//$versionFile = (int) $versionFile; // some documents return file field value based on file path, CFile::MakeFileArray handles this situation

			$fileArray = CFile::MakeFileArray($versionFile);
			if (!is_array($fileArray))
			{
				$this->WriteToTrackingService(GetMessage('BPDUV_SOURCE_FILE_ERROR'));
				continue;
			}

			$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;
			$fileId = \CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);

			if(!$fileId)
			{
				\CFile::delete($fileId);
				$this->WriteToTrackingService(GetMessage('BPDUV_SOURCE_FILE_ERROR'));
				continue;
			}

			$versionModel = $file->addVersion(array(
				'ID' => $fileId,
				'FILE_SIZE' => $fileArray['size'],
			), $createdBy, true);

			if ($versionModel)
			{
				$objectIds[] = $versionModel->getId();
				$detailUrls[] = $urlManager->encodeUrn($urlManager->getHostUrl().$urlManager->getPathFileDetail($file));
				$downloadUrls[] = $urlManager->getUrlForDownloadVersion($versionModel, true);
			}
			else
				$this->WriteToTrackingService(GetMessage('BPDUV_UPLOAD_ERROR'));
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
				"message"   => GetMessage("BPDUV_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['SourceId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceId", "message" => GetMessage("BPDUV_EMPTY_SOURCE_ID"));
		if (empty($arTestProperties['SourceFile']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceFile", "message" => GetMessage("BPDUV_EMPTY_SOURCE_FILE"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $currentValues = null, $formName = "")
	{
		if (!CModule::IncludeModule("disk"))
			return '';

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"SourceId" => "source_id",
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

		if (
			empty($currentValues['source_id'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$currentValues['source_id'] = $currentValues['source_id_x'];

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
			"source_id" => "SourceId",
			"source_file" => "SourceFile",
			'created_by' => 'CreatedBy',
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			$arProperties[$value] = $currentValues[$key];
		}

		if (
			empty($arProperties['SourceId'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$arProperties['SourceId'] = $currentValues['source_id_x'];

		if (!CBPDocument::IsExpression($arProperties['CreatedBy']))
			$arProperties['CreatedBy'] = CBPHelper::UsersStringToArray($arProperties['CreatedBy'], $documentType, $arErrors);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>