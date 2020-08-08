<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmChangeResponsibleActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Responsible" => null,
			"ModifiedBy" => null,
		);
	}

	public function Execute()
	{
		if ($this->Responsible == null || !CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$runtime = CBPRuntime::GetRuntime();
		/** @var CBPDocumentService $ds */
		$ds = $runtime->GetService('DocumentService');

		$document = $ds->GetDocument($documentId);
		$responsibleFieldName = $this->getResponsibleFieldName($documentId);
		if (isset($document[$responsibleFieldName]))
		{
			$documentResponsible = CBPHelper::ExtractUsers($document[$responsibleFieldName], $documentId, true);
			$targetResponsibles = CBPHelper::ExtractUsers($this->Responsible, $documentId);

			$searchKey = array_search($documentResponsible, $targetResponsibles);
			if ($searchKey !== false)
			{
				unset($targetResponsibles[$searchKey]);
			}
			shuffle($targetResponsibles);

			if ($targetResponsibles)
			{
				$documentResponsible = 'user_'.$targetResponsibles[0];
				$ds->UpdateDocument(
					$documentId,
					[$responsibleFieldName => $documentResponsible],
					$this->ModifiedBy
				);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties["Responsible"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "Responsible", "message" => GetMessage("CRM_CHANGE_RESPONSIBLE_EMPTY_PROP"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
		{
			return '';
		}

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

		$map = [
			'Responsible' => [
				'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_NEW'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Required' => true,
				'Multiple' => true
			],
			'ModifiedBy' => [
				'Name' => GetMessage('CRM_CHANGE_RESPONSIBLE_MODIFIED_BY'),
				'FieldName' => 'modified_by',
				'Type' => 'user'
			]
		];

		$dialog->setMap($map);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = array(
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $errors),
			'ModifiedBy' => CBPHelper::UsersStringToArray($arCurrentValues["modified_by"], $documentType, $errors)
		);

		if (count($errors) > 0)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private function getResponsibleFieldName($documentId)
	{
		if (mb_strpos($documentId[2], 'ORDER_') === 0 || mb_strpos($documentId[2], 'INVOICE_') === 0)
		{
			return 'RESPONSIBLE_ID';
		}
		return 'ASSIGNED_BY_ID';
	}
}