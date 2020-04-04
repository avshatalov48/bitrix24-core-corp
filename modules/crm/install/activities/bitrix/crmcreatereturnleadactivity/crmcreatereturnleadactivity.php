<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmCreateReturnLeadActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'LeadTitle' => '',
			'Responsible' => null,

			//return
			'LeadId' => 0
		);

		$this->SetPropertiesTypes(array(
			'LeadId' => array(
				'Type' => 'int'
			)
		));
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->LeadId = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$entityId = explode('_', $this->GetDocumentId()[2])[1];

		$dealFields = \CCrmDeal::GetListEx(
			array(),
			array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('COMPANY_ID', 'CONTACT_ID', 'ASSIGNED_BY_ID')
		)->Fetch();

		if(!$dealFields)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CRL_DEAL_NOT_EXISTS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if (empty($dealFields['CONTACT_ID']) && empty($dealFields['COMPANY_ID']))
		{
			$this->WriteToTrackingService(GetMessage('CRM_CRL_NO_CLIENTS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$leadTitle = $this->LeadTitle;
		$responsibles = CBPHelper::ExtractUsers($this->Responsible, $this->GetDocumentId());
		if (count($responsibles) > 1)
		{
			shuffle($responsibles);
		}
		elseif (!$responsibles)
		{
			$responsibles[] = $dealFields['ASSIGNED_BY_ID'];
		}

		if (empty($leadTitle))
		{
			$leadTitle = GetMessage('CRM_CRL_LEAD_TITLE_DEFAULT');
		}

		$leadFields = [
			'TITLE' => $leadTitle,
			'ASSIGNED_BY_ID' => $responsibles[0],
			'CONTACT_ID' => $dealFields['CONTACT_ID'],
			'COMPANY_ID' => $dealFields['COMPANY_ID']
		];

		$leadEntity = new \CCrmLead(false);

		$id = $leadEntity->Add(
			$leadFields,
			true,
			[
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => 0,
				'DISABLE_USER_FIELD_CHECK' => true
			]
		);

		if (!$id)
		{
			$this->WriteToTrackingService($leadEntity->LAST_ERROR, 0, CBPTrackingType::Error);
		}
		else
		{
			$this->LeadId = $id;
			if (\COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
			{
				$CCrmBizProc = new \CCrmBizProc('LEAD');
				if ($CCrmBizProc->CheckFields(false, true))
				{
					$CCrmBizProc->StartWorkflow($id);
				}
			}

			//Region automation
			\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $id);
			//End region
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

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

		$dialog->setMap(array(
			'LeadTitle' => array(
				'Name' => GetMessage('CRM_CRL_LEAD_TITLE'),
				'FieldName' => 'lead_title',
				'Type' => 'string'
			),
			'Responsible' => array(
				'Name' => GetMessage('CRM_CRL_RESPONSIBLE'),
				'FieldName' => 'responsible',
				'Type' => 'user'
			),
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = [];

		$arProperties = array(
			'LeadTitle' => $arCurrentValues["lead_title"],
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $arErrors),
		);

		if (count($arErrors) > 0)
		{
			return false;
		}

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}