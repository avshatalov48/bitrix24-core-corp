<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmCreateLeadContactActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'Responsible' => null,

			//return
			'ContactId' => 0
		);

		$this->SetPropertiesTypes(array(
			'ContactId' => array(
				'Type' => 'int'
			)
		));
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->ContactId = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$entityId = explode('_', $this->GetDocumentId()[2])[1];

		$customerFields = \CCrmLead::getCustomerFields();
		unset($customerFields['COMPANY_TITLE']);

		$leadFields = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array_merge($customerFields, ['ASSIGNED_BY_ID', 'CONTACT_ID', 'STATUS_ID'])
		)->Fetch();

		if(!$leadFields)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CRLC_LEAD_NOT_EXISTS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$leadFields['FM'] = Bitrix\Crm\Entity\Lead::getInstance()->getEntityMultifields($entityId, array('skipEmpty' => true));

		if($leadFields['CONTACT_ID'] > 0)
		{
			$this->ContactId = $leadFields['CONTACT_ID'];
			$this->WriteToTrackingService(GetMessage('CRM_CRLC_LEAD_HAS_CONTACT'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if (\CCrmLead::GetSemanticID($leadFields['STATUS_ID']) === \Bitrix\Crm\PhaseSemantics::SUCCESS)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CRLC_LEAD_WRONG_STATUS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$responsibles = CBPHelper::ExtractUsers($this->Responsible, $this->GetDocumentId());
		if (count($responsibles) > 1)
		{
			shuffle($responsibles);
		}
		elseif (!$responsibles)
		{
			$responsibles[] = $leadFields['ASSIGNED_BY_ID'];
		}

		if (empty($leadFields['NAME']))
		{
			$leadFields['NAME'] = GetMessage('CRM_CRLC_CONTACT_NAME_DEFAULT');
		}

		$contactFields = $leadFields;

		$contactFields['ASSIGNED_BY_ID'] = $responsibles[0];
		$contactFields['LEAD_ID'] = $entityId;
		unset($contactFields['CONTACT_ID']);

		$contactEntity = new \CCrmContact(false);

		$id = $contactEntity->Add($contactFields, true, [
			'REGISTER_SONET_EVENT' => true,
			'CURRENT_USER' => 0,
			'DISABLE_USER_FIELD_CHECK' => true
		]);

		if (!$id)
		{
			$this->WriteToTrackingService($contactEntity->LAST_ERROR, 0, CBPTrackingType::Error);
		}
		else
		{
			$this->ContactId = $id;
			$leadEntity = new \CCrmLead(false);
			$leadUpdateFields = ['CONTACT_ID' => $id];
			$leadEntity->Update(
				$entityId,
				$leadUpdateFields,
				true,
				true,
				[
					'EXCLUDE_FROM_RELATION_REGISTRATION' => [
						new \Bitrix\Crm\ItemIdentifier(\CCrmOwnerType::Contact, $id),
					]
				]);

			if (\COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
			{
				$CCrmBizProc = new \CCrmBizProc('CONTACT');
				if ($CCrmBizProc->CheckFields(false, true))
				{
					$CCrmBizProc->StartWorkflow($id);
				}
			}
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
			'Responsible' => array(
				'Name' => GetMessage('CRM_CRLC_RESPONSIBLE'),
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
