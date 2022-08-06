<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmCreateCallActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Subject" => null,
			"StartTime" => null,
			"EndTime" => null,
			"IsImportant" => null,
			"Description" => null,
			"NotifyType" => null,
			"NotifyValue" => null,
			"Responsible" => null,
			"AutoComplete" => null,
			//return
			"Id" => null
		);

		$this->SetPropertiesTypes(array(
			'Id' => array(
				'Type' => 'int'
			)
		));
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$start = (string)$this->StartTime;
		$end = (string)$this->EndTime;

		if ($start === '')
		{
			$start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
		}

		if ($end === '')
		{
			$end = $start;
		}

		$responsibleId = $this->getResponsibleId();
		/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
		$provider = \Bitrix\Crm\Activity\Provider\Call::className();

		$activityFields = array(
			'AUTHOR_ID' => $responsibleId,
			'START_TIME' => $start,
			'END_TIME' => $end,
			'TYPE_ID' =>  \CCrmActivityType::Call,
			'SUBJECT' => (string)$this->Subject,
			'PRIORITY' => ($this->IsImportant == 'Y') ? \CCrmActivityPriority::High : CCrmActivityPriority::Medium,
			'DESCRIPTION' => (string)$this->Description,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'PROVIDER_ID' => $provider::getId(),
			'PROVIDER_TYPE_ID' => $provider::getTypeId(array()),
			'DIRECTION' => CCrmActivityDirection::Outgoing,
			'RESPONSIBLE_ID' => $responsibleId,
			'AUTOCOMPLETE_RULE' => (
				$this->AutoComplete == 'Y' ?
					\Bitrix\Crm\Activity\AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED
					: \Bitrix\Crm\Activity\AutocompleteRule::NONE
			)
		);

		$provider::fillDefaultActivityFields($activityFields);

		$notifyType = (int)$this->NotifyType;
		$notifyValue = (int)$this->NotifyValue;

		if ($notifyType > 0 && $notifyValue > 0)
		{
			$activityFields['NOTIFY_VALUE'] = $notifyValue;
			$activityFields['NOTIFY_TYPE'] = $notifyType;
		}
		else
		{
			$defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array(), $responsibleId);
			if (isset($defaults['notify']) && isset($defaults['notify'][$provider::getId()]))
			{
				$activityFields['NOTIFY_VALUE'] = (int)$defaults['notify'][$provider::getId()]['value'];
				$activityFields['NOTIFY_TYPE'] = (int)$defaults['notify'][$provider::getId()]['type'];
			}
		}

		$communications = $activityFields['COMMUNICATIONS'] = $this->getCommunications();
		$activityFields['BINDINGS'] = $this->getBindings($communications);

		if (empty($activityFields['SUBJECT']) && !empty($communications))
		{
			$arCommInfo = array(
				'ENTITY_ID' => $communications[0]['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $communications[0]['ENTITY_TYPE_ID']
			);
			CCrmActivity::PrepareCommunicationInfo($arCommInfo);

			$activityFields['SUBJECT'] = \Bitrix\Crm\Activity\Provider\Call::generateSubject(
				$activityFields['PROVIDER_TYPE_ID'],
				CCrmActivityDirection::Outgoing,
				array(
					'#DATE#'=> $activityFields['START_TIME'],
					'#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : '',
					'#COMMUNICATION#' => ''
				)
			);
		}

		$this->writeDebugInfo($this->getDebugInfo(['Responsible' => $this->Responsible ?? 'user_' . $responsibleId]));

		if(!($id = CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true))))
		{
			$this->WriteToTrackingService(CCrmActivity::GetLastErrorMessage());

			return CBPActivityExecutionStatus::Closed;
		}

		if ($id > 0)
		{
			\Bitrix\Crm\Automation\Factory::registerActivity($id);
			$this->Id = $id;
			$this->WriteToTrackingService($id, 0, CBPTrackingType::AttachedEntity);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getResponsibleId()
	{
		$id = $this->Responsible;
		if (!$id)
		{
			$documentId = $this->GetDocumentId();
			list($typeName, $ownerID) = explode('_', $documentId[2]);
			$ownerTypeID = \CCrmOwnerType::ResolveID($typeName);

			return CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		}

		return CBPHelper::ExtractUsers($id, $this->GetDocumentId(), true);
	}

	private function getBindings(array $communications)
	{
		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);
		$typeId = CCrmOwnerType::ResolveID($typeName);
		$id = (int) $id;

		$bindings = [['OWNER_TYPE_ID' => $typeId, 'OWNER_ID' => $id]];

		foreach ($communications as $comm)
		{
			$commTypeId = (int) $comm['ENTITY_TYPE_ID'];
			$commId = (int) $comm['ENTITY_ID'];

			if (!($commTypeId === $typeId && $commId === $id))
			{
				$bindings[] = ['OWNER_TYPE_ID' => $commTypeId, 'OWNER_ID' => $commId];
			}
		}

		return $bindings;
	}

	private function getCommunications()
	{
		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);

		if ($typeName === CCrmOwnerType::DealName)
		{
			$communications = $this->getDealCommunications($id);
		}
		elseif ($typeName === CCrmOwnerType::OrderName)
		{
			$communications = $this->getOrderCommunications($id);
		}
		elseif ($typeName === CCrmOwnerType::LeadName)
		{
			$communications = $this->getLeadCommunications($id);
		}
		else
		{
			$communications = $this->getCommunicationsFromFM(
				CCrmOwnerType::ResolveID($typeName),
				$id
			);
		}

		$communications = array_slice($communications, 0, 1);
		return $communications;
	}

	private function getDealCommunications($id)
	{
		$communications = array();

		$entity = CCrmDeal::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID);
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $entityCompanyID);
		}

		if (empty($communications))
		{
			$communications = CCrmActivity::GetCommunicationsByOwner('DEAL', $id, 'PHONE');
		}

		return $communications;
	}

	private function getOrderCommunications($id)
	{
		$communications = [];

		$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList(array(
			'select' => array('ENTITY_ID', 'ENTITY_TYPE_ID'),
			'filter' => array(
				'=ORDER_ID' => $id,
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
				'IS_PRIMARY' => 'Y'
			),
			'order' => ['ENTITY_TYPE_ID' => 'ASC']
		));
		while ($row = $dbRes->fetch())
		{
			$communications = $this->getCommunicationsFromFM($row['ENTITY_TYPE_ID'], $row['ENTITY_ID']);
			if ($communications)
			{
				break;
			}
		}

		return $communications;
	}

	private function getLeadCommunications($id)
	{
		$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Lead, $id);

		if ($communications)
		{
			return $communications;
		}

		$entity = CCrmLead::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID);
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $entityCompanyID);
		}

		if (empty($communications))
		{
			$communications = CCrmActivity::GetCommunicationsByOwner('LEAD', $id, 'PHONE');
		}

		return $communications;
	}

	private function getCommunicationsFromFM($entityTypeId, $entityId)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		$iterator = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityTypeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => 'PHONE'
			)
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
				continue;

			$communications[] = array(
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => 'PHONE',
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE']
			);
		}

		return $communications;
	}

	public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = array();

		if (CModule::IncludeModule("crm"))
		{
			$fieldsMap = static::getPropertiesDialogMap();
			foreach ($fieldsMap as $propertyKey => $fieldProperties)
			{
				if (
					CBPHelper::getBool($fieldProperties['Required'])
					&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
				)
					$errors[] = array(
						"code" => "NotExist",
						"parameter" => $propertyKey,
						"message" => GetMessage("CRM_CREATE_CALL_EMPTY_PROP", array('#PROPERTY#' => $fieldProperties['Name']))
					);
			}
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
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

		$dialog->setMap(static::getPropertiesDialogMap($documentType));

		return $dialog;
	}

	private static function getPropertiesDialogMap(array $documentType = null)
	{
		$notifyTypes = \CCrmActivityNotifyType::GetAllDescriptions();
		unset($notifyTypes[\CCrmActivityNotifyType::None]);

		return array(
			'Subject' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_SUBJECT'),
				'Description' => GetMessage('CRM_CREATE_CALL_SUBJECT'),
				'FieldName' => 'subject',
				'Type' => 'string'
			),
			'StartTime' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_START_TIME'),
				'FieldName' => 'start_time',
				'Type' => 'datetime'
			),
			'EndTime' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_END_TIME'),
				'FieldName' => 'end_time',
				'Type' => 'datetime'
			),
			'Description' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_DESCRIPTION'),
				'Description' => GetMessage('CRM_CREATE_CALL_DESCRIPTION'),
				'FieldName' => 'description',
				'Type' => 'text'
			),
			'NotifyValue' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_NOTIFY_VALUE'),
				'FieldName' => 'notify_value',
				'Type' => 'int'
			),
			'NotifyType' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_NOTIFY_TYPE'),
				'FieldName' => 'notify_type',
				'Type' => 'select',
				'Options' => $notifyTypes
			),
			'Responsible' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_RESPONSIBLE_ID'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Default' => ($documentType
					? \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
					: 'author'
				)
			),
			'IsImportant' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_IS_IMPORTANT'),
				'FieldName' => 'is_important',
				'Type' => 'bool'
			),
			'AutoComplete' => array(
				'Name' => GetMessage('CRM_CREATE_CALL_AUTO_COMPLETE'),
				'FieldName' => 'auto_completed',
				'Type' => 'bool',
				'Default' => 'N'
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $currentValues, &$errors)
	{
		if (!CModule::IncludeModule("crm"))
		{
			return false;
		}

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$errors = $properties = array();
		/** @var CBPDocumentService $documentService */
		$documentService = $runtime->GetService('DocumentService');

		$fieldsMap = static::getPropertiesDialogMap();
		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
			{
				continue;
			}

			$properties[$propertyKey] = $field->extractValue(
				array('Field' => $fieldProperties['FieldName']),
				$currentValues,
				$errors
			);
		}

		//convert special robot datetime interval
		$startTimeFieldsPrefix = $fieldsMap['StartTime']['FieldName'];
		if (isset($currentValues[$startTimeFieldsPrefix.'_interval_d']) && isset($currentValues[$startTimeFieldsPrefix.'_interval_t']))
		{
			$interval = array('d' => $currentValues[$startTimeFieldsPrefix.'_interval_d']);
			$time = \Bitrix\Crm\Automation\Helper::parseTimeString($currentValues[$startTimeFieldsPrefix.'_interval_t']);
			$interval['h'] = $time['h'];
			$interval['i'] = $time['i'];
			$properties['StartTime'] = \Bitrix\Crm\Automation\Helper::getDateTimeIntervalString($interval);
			++$interval['h'];
			$properties['EndTime'] = \Bitrix\Crm\Automation\Helper::getDateTimeIntervalString($interval);
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	protected function getDebugInfo(array $values = [], array $map = []): array
	{
		$onlyDesignerFields = ['EndTime', 'NotifyValue', 'NotifyType'];

		if (count($map) <= 0)
		{
			$map = static::getPropertiesDialogMap($this->getDocumentType());
		}

		// temporary
		foreach ($onlyDesignerFields as $key)
		{
			unset($map[$key]);
		}

		return parent::getDebugInfo($values, $map);
	}
}