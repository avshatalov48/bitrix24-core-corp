<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

class CBPCrmCreateReturnLeadActivity extends CBPActivity
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

		$docId = $this->GetDocumentId();
		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($docId);
		$responsibleUserId = $this->getResponsibleUserId();

		$leadFields = $this->getLeadFields($entityTypeId, $entityId, $responsibleUserId);

		if (empty($leadFields))
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CRL_DATA_NOT_EXISTS'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		if (empty($leadFields['CONTACT_ID']) && empty($leadFields['COMPANY_ID']))
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CRL_NO_CLIENTS'),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$leadTitle = $this->LeadTitle;
		if (empty($leadTitle) || !is_string($leadTitle))
		{
			$leadTitle = Loc::getMessage('CRM_CRL_LEAD_TITLE_DEFAULT');
		}

		if ($this->workflow->isDebug())
		{
			$this->logDebug($leadTitle, $leadFields['ASSIGNED_BY_ID']);
		}

		$leadFields['TITLE'] = $leadTitle;

		$leadEntity = new \CCrmLead(false);

		$id = $leadEntity->Add(
			$leadFields,
			true,
			[
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => 0,
				'DISABLE_USER_FIELD_CHECK' => true,
			]
		);

		if (!$id)
		{
			$this->WriteToTrackingService($leadEntity->LAST_ERROR, 0, CBPTrackingType::Error);
		}
		else
		{
			if ($this->workflow->isDebug())
			{
				$this->logDebugId($id);
			}

			$this->LeadId = $id;
			if (\COption::GetOptionString("crm", "start_bp_within_bp", "N") === "Y")
			{
				$CCrmBizProc = new \CCrmBizProc('LEAD');
				if ($CCrmBizProc->CheckFields(false, true))
				{
					$CCrmBizProc->StartWorkflow($id);
				}
			}

			//Region automation
			$starter = new Starter(\CCrmOwnerType::Lead, $id);
			$starter->setContextToBizproc()->runOnAdd();
			//End region
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getResponsibleUserId(): ?int
	{
		$responsible = CBPHelper::extractUsers($this->Responsible, $this->getDocumentId());
		if ($responsible)
		{
			shuffle($responsible);

			return (int)$responsible[0];
		}

		return null;
	}

	protected function getLeadFields(int $entityTypeId, int $entityId, ?int $responsibleUserId): ?array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		$item = $factory->getItem($entityId);

		if (!$item)
		{
			return null;
		}

		if (!$responsibleUserId)
		{
			$responsibleUserId = (int)$item->getAssignedById();
		}

		return [
			'ASSIGNED_BY_ID' => $responsibleUserId,
			'CONTACT_ID' => $item->getContactId(),
			'COMPANY_ID' => $item->getCompanyId(),
		];
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
		if (!CModule::IncludeModule("crm"))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors
	)
	{
		$arErrors = [];

		$arProperties = [
			'LeadTitle' => $arCurrentValues["lead_title"],
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $arErrors),
		];

		if (!empty($arErrors))
		{
			return false;
		}

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (!empty($arErrors))
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'LeadTitle' => [
				'Name' => Loc::getMessage('CRM_CRL_LEAD_TITLE'),
				'Description' => Loc::getMessage('CRM_CRL_LEAD_TITLE'),
				'FieldName' => 'lead_title',
				'Type' => 'string'
			],
			'Responsible' => [
				'Name' => Loc::getMessage('CRM_CRL_RESPONSIBLE'),
				'FieldName' => 'responsible',
				'Type' => 'user'
			],
		];
	}

	private function logDebug($leadTitle, $responsible)
	{
		$debugInfo = $this->getDebugInfo([
			'LeadTitle' => $leadTitle,
			'Responsible' => 'user_' . $responsible,
		]);

		$this->writeDebugInfo($debugInfo);
	}

	private function logDebugId($id)
	{
		$debugInfo = $this->getDebugInfo(
			['LeadId' => $id],
			['LeadId' => GetMessage('CRM_CRL_CREATED_LEAD')],
		);

		$this->writeDebugInfo($debugInfo);
	}
}
