<?
use Bitrix\Disk\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDiskDetailActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"SourceId" => "",

			//return properties
			'ObjectId' => '',
			'Type' => '',
			'Name' => '',
			'SizeBytes' => 0,
			'SizeFormatted' => '',
			'DetailUrl' => '',
			'DownloadUrl' => '',
		);

		//return properties mapping
		$this->SetPropertiesTypes(array(
			'ObjectId' => array(
				'Type' => 'int',
				'Multiple' => true,
			),
			'Type' => array(
				'Type' => 'string',
				'Multiple' => true,
			),
			'Name' => array(
				'Type' => 'string',
				'Multiple' => true,
			),
			'SizeBytes' => array(
				'Type' => 'int',
				'Multiple' => true,
			),
			'SizeFormatted' => array(
				'Type' => 'string',
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
		$this->ObjectId = '';
		$this->Type = '';
		$this->Name = '';
		$this->SizeBytes = 0;
		$this->SizeFormatted = '';
		$this->DetailUrl = '';
		$this->DownloadUrl = '';
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("disk"))
			return CBPActivityExecutionStatus::Closed;

		$ids = $types = $names = $sizes = $formattedSizes = $detailUrls = $downloadUrls = array();
		$urlManager = Driver::getInstance()->getUrlManager();
		$sourceIds = (array) $this->SourceId;

		foreach ($sourceIds as $sourceId)
		{
			$sourceObject = \Bitrix\Disk\BaseObject::loadById($sourceId);

			if (!$sourceObject)
			{
				$this->WriteToTrackingService(GetMessage('BPDD_SOURCE_ID_ERROR'));
				continue;
			}

			$isFile = ($sourceObject instanceof \Bitrix\Disk\File);
			$size = $sourceObject->getSize();

			$ids[] = $sourceId;
			$types[] = $isFile ? 'FILE' : 'FOLDER';
			$names[] = $sourceObject->getName();
			$sizes[] = $size;
			$formattedSizes[] = \CFile::FormatSize($size);
			$downloadUrls[] = $isFile ? $urlManager->getUrlForDownloadFile($sourceObject, true) : '';
			$detailUrls[] = $urlManager->encodeUrn(
				$urlManager->getHostUrl()
				.($isFile ? $urlManager->getPathFileDetail($sourceObject) : $urlManager->getPathFolderList($sourceObject))
			);

		}

		$this->ObjectId = $ids;
		$this->Type = $types;
		$this->Name = $names;
		$this->SizeBytes = $sizes;
		$this->SizeFormatted = $formattedSizes;
		$this->DetailUrl = $detailUrls;
		$this->DownloadUrl = $downloadUrls;

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
				"message"   => GetMessage("BPDD_ACCESS_DENIED")
			);
		}

		if (empty($arTestProperties['SourceId']))
			$arErrors[] = array("code" => "NotExist", "parameter" => "SourceId", "message" => GetMessage("BPDD_EMPTY_SOURCE_ID"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $currentValues = null, $formName = "")
	{
		if (!CModule::IncludeModule("disk"))
			return '';

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"SourceId" => 'source_id',
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

		$properties = array('SourceId' => $currentValues['source_id']);

		if (
			empty($properties['SourceId'])
			&& isset($currentValues['source_id_x'])
			&& CBPDocument::IsExpression($currentValues['source_id_x'])
		)
			$properties['SourceId'] = $currentValues['source_id_x'];

		$arErrors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}
}
?>