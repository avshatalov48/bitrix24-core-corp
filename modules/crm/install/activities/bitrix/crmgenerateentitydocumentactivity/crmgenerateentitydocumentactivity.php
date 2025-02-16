<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\BaseType\Value;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator;
use Bitrix\Main\Loader;

/**
 * Class CBPCrmGenerateEntityDocumentActivity
 * @property-read int TemplateId
 * @property-read string UseSubscription
 * @property-read string WithStamps
 * @property-read int DocumentId
 * @property-read string DocumentUrl
 * @property-read int DocumentPdf
 * @property-read int DocumentDocx
 * @property-read string DocumentNumber
 * @property-read array Values
 * @property-read string EnablePublicUrl
 * @property-read int MyCompanyId
 * @property-read int MyCompanyRequisiteId
 * @property-read int MyCompanyBankDetailId
 */
class CBPCrmGenerateEntityDocumentActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'TemplateId' => null,
			'UseSubscription' => 'N',
			'WithStamps' => '',
			'Values' => [],
			'EnablePublicUrl' => 'Y',
			'MyCompanyId' => null,
			'MyCompanyRequisiteId' => null,
			'MyCompanyBankDetailId' => null,
			'CreateActivity' => 'N',

			//return
			'DocumentId' => null,
			'DocumentUrl' => null,
			'DocumentPdf' => null,
			'DocumentDocx' => null,
			'DocumentNumber' => null,
			'DocumentActivityId' => null,
		);

		$this->SetPropertiesTypes([
			'DocumentId' => ['Type' => 'int'],
			'DocumentUrl' => ['Type' => 'string'],
			'DocumentPdf' => ['Type' => 'file'],
			'DocumentDocx' => ['Type' => 'file'],
			'DocumentNumber' => ['Type' => 'string'],
			'DocumentActivityId' => ['Type' => 'int'],
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->DocumentId = null;
		$this->DocumentUrl = null;
		$this->DocumentPdf = null;
		$this->DocumentDocx = null;
		$this->DocumentNumber = null;
		$this->DocumentActivityId = null;
	}

	public function Cancel()
	{
		if ($this->UseSubscription === 'Y')
		{
			$this->Unsubscribe($this);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->TemplateId == null || !Loader::includeModule("crm"))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if(!DocumentGeneratorManager::getInstance()->isEnabled())
		{
			$this->WriteToTrackingService('No module documentgenerator', 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$itemIdentifier = $this->extractItemIdentifier($this->GetDocumentId());
		if (!$itemIdentifier)
		{
			$this->WriteToTrackingService('Could not parse entities', 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}
		$entityTypeId = $itemIdentifier->getEntityTypeId();
		$entityId = $itemIdentifier->getEntityId();
		$providerClassName = static::getDataProviderByEntityTypeId($entityTypeId);
		if(!$providerClassName)
		{
			$this->WriteToTrackingService('Unknown Entity Type', 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}
		$templateId = $this->TemplateId;
		$template = DocumentGenerator\Template::loadById($templateId);
		if(!$template || $template->isDeleted())
		{
			$this->WriteToTrackingService('Could not load template', 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}
		$template->setSourceType($providerClassName);
		$document = \Bitrix\DocumentGenerator\Document::createByTemplate($template, $entityId);
		if (!$document)
		{
			$this->WriteToTrackingService('Could not create document', 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}
		if($this->WithStamps === 'Y')
		{
			$document->enableStamps(true);
		}
		elseif($this->WithStamps === 'N')
		{
			$document->enableStamps(false);
		}
		$values = $this->Values;
		if(!is_array($values))
		{
			$values = [];
		}

		foreach($values as &$value)
		{
			$value = $this->prepareValue($value);
		}
		unset($value);

		$myCompanyId = (int) $this->MyCompanyId;
		if($myCompanyId > 0)
		{
			$values['MY_COMPANY'] = $myCompanyId;
		}
		$myCompanyRequisiteId = (int) $this->MyCompanyRequisiteId;
		if($myCompanyRequisiteId > 0)
		{
			$values['MY_COMPANY.REQUISITE'] = $myCompanyRequisiteId;
		}
		$myCompanyBankDetailId = (int) $this->MyCompanyBankDetailId;
		if($myCompanyBankDetailId > 0)
		{
			$values['MY_COMPANY.BANK_DETAIL'] = $myCompanyBankDetailId;
		}

		\Bitrix\DocumentGenerator\CreationMethod::markDocumentAsCreatedByAutomation($document);
		$targetUserId = CBPHelper::ExtractUsers($this->GetRootActivity()->{CBPDocument::PARAM_TAGRET_USER}, $this->GetDocumentId(), true);
		if (!$targetUserId)
		{
			$targetUserId = $this->getResponsibleId();
		}

		$document->setUserId($targetUserId);
		$document->setIsCheckAccess(false);
		$isWaitForPdf = ($this->UseSubscription === 'Y');

		$result = $document->setValues($values)->getFile(true, true);
		if(!$result->isSuccess())
		{
			$this->WriteToTrackingService(implode(',', $result->getErrorMessages()), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}
		$documentData = $result->getData();

		$this->DocumentId = $documentData['id'];
		$this->DocumentNumber = $documentData['number'];
		$this->DocumentDocx = \Bitrix\DocumentGenerator\Model\FileTable::getBFileId($document->FILE_ID);
		if($this->EnablePublicUrl === 'Y')
		{
			$result = $document->enablePublicUrl();
			if ($result->isSuccess())
			{
				$this->DocumentUrl = (string)$document->getPublicUrl();
			}
		}

		if ($this->CreateActivity === 'Y')
		{
			$createActivityResult = DocumentGeneratorManager::getInstance()->createDocumentActivity(
				$document,
				$itemIdentifier,
				$targetUserId,
			);
			if ($createActivityResult->isSuccess())
			{
				$this->DocumentActivityId = $createActivityResult->getData()['id'];
			}
			else
			{
				$this->WriteToTrackingService(implode(',', $result->getErrorMessages()), 0, CBPTrackingType::Error);
			}
		}

		//If don`t need to wait for PDF - close activity
		if (!$isWaitForPdf)
		{
			return CBPActivityExecutionStatus::Closed;
		}
		if (
			$isWaitForPdf
			&& isset($documentData['isTransformationError'])
			&& $documentData['isTransformationError'] === true
		)
		{
			$message = GetMessage('CRM_GEDA_TRANSFORMATION_ERROR') ?? 'Error trying to transform document: ';
			$message .= $documentData['transformationErrorMessage'] ?? '';
			$this->WriteToTrackingService(
				$message,
				0,
				CBPTrackingType::Error
			);
			return CBPActivityExecutionStatus::Faulting;
		}

		//Subscribe for PDF generation event.
		$this->Subscribe($this);
		$this->WriteToTrackingService(GetMessage("CRM_GEDA_NAME_WAIT_FOR_EVENT_LOG"));
		return CBPActivityExecutionStatus::Executing;
	}

	protected function getResponsibleId()
	{
		$itemIdentifier = $this->extractItemIdentifier($this->GetDocumentId());
		if ($itemIdentifier)
		{
			return CCrmOwnerType::GetResponsibleID(
				$itemIdentifier->getEntityTypeId(),
				$itemIdentifier->getEntityId(),
				false
			);
		}

		return 0;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->SubscribeOnEvent(
			$this->workflow->GetInstanceId(),
			$this->name,
			"documentgenerator",
			"onDocumentTransformationComplete",
			$this->DocumentId
		);

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}


	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnEvent(
			$this->workflow->GetInstanceId(),
			$this->name,
			"documentgenerator",
			"onDocumentTransformationComplete",
			$this->DocumentId
		);

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if($this->DocumentId != $arEventParameters[0])
		{
			return;
		}
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			$documentData = $arEventParameters[1];
			if(empty($documentData))
			{
				$this->WriteToTrackingService('Transformation Error', 0, CBPTrackingType::Error);
				$this->Unsubscribe($this);
				$this->workflow->CloseActivity($this);
			}
			else
			{
				$bFileId = null;
				$pdfId = $documentData['pdfId'];
				if($pdfId > 0)
				{
					$bFileId = DocumentGenerator\Model\FileTable::getBFileId($pdfId);
				}
				$this->DocumentPdf = $bFileId;
				$this->WriteToTrackingService(GetMessage("CRM_GEDA_NAME_WAIT_FOR_EVENT_LOG_COMPLETE"));
				$this->Unsubscribe($this);
				$this->workflow->CloseActivity($this);
			}
		}
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!Loader::includeModule("crm"))
		{
			return '';
		}
		if(!DocumentGeneratorManager::getInstance()->isEnabled())
		{
			return GetMessage('CRM_GEDA_MODULE_DOCGEN_ERROR');
		}

		$skipFieldTypes = [
			'IMAGE',
			'STAMP',
		];

		$entityTypeId = \CCrmOwnerType::ResolveID($documentType[2]);
		$providerClassName = static::getDataProviderByEntityTypeId($entityTypeId);
		if (!$providerClassName)
		{
			return GetMessage('CRM_GEDA_PROVIDER_NOT_FOUND');
		}

		$templatesList = [];
		$templates = DocumentGenerator\Model\TemplateTable::getListByClassName($providerClassName, \Bitrix\Main\Engine\CurrentUser::get()->getId());
		foreach($templates as $template)
		{
			$templatesList[$template['ID']] = $template['NAME'];
		}

		$myCompanies = [];
		$res = \CCrmCompany::GetListEx(
			['ID' => 'ASC'],
			['IS_MY_COMPANY' => 'Y'],
			false,
			false,
			['ID', 'TITLE']
		);
		while($myCompany = $res->Fetch())
		{
			$myCompanies[$myCompany['ID']] = $myCompany['TITLE'];
		}

		$requisiteIds = [];
		$myCompanyRequisites = [];
		if(!empty($myCompanies))
		{
			$requisite = new EntityRequisite();
			$res = $requisite->getList(
				array(
					'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
						'@ENTITY_ID' => array_keys($myCompanies)
					),
					'select' => array('ID', 'NAME', 'ENTITY_ID'),
				)
			);
			while($data = $res->fetch())
			{
				$myCompanyRequisites[$data['ENTITY_ID']][$data['ID']] = $data['NAME'];
				$requisiteIds[] = $data['ID'];
			}
		}
		$myCompanyBankDetails = [];
		if(!empty($requisiteIds))
		{
			$res = EntityBankDetail::getSingleInstance()->getList([
				'order' => ['NAME' => 'ASC'],
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'=ENTITY_ID' => $requisiteIds
				],
				'select' => ['ID', 'NAME', 'ENTITY_ID'],
			]);
			while($data = $res->fetch())
			{
				$myCompanyBankDetails[$data['ENTITY_ID']][$data['ID']] = $data['NAME'];
			}
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

		$map = array(
			'TemplateId' => array(
				'Name' => GetMessage('CRM_GEDA_NAME_TEMPLATE_ID'),
				'FieldName' => 'template_id',
				'Type' => 'select',
				'Required' => true,
				'Options' => $templatesList
			),
			'UseSubscription' => array(
				'Name' => GetMessage('CRM_GEDA_NAME_USE_SUBSCRIPTION'),
				'FieldName' => 'use_subscription',
				'Type' => 'bool',
				'Default' => 'N'
			),
			'WithStamps' => [
				'Name' => GetMessage('CRM_GEDA_NAME_WITH_STAMPS'),
				'FieldName' => 'with_stamps',
				'Type' => 'bool',
			],
			'Values' => [
				'FieldName' => 'values',
			],
			'EnablePublicUrl' => [
				'Name' => GetMessage('CRM_GEDA_NAME_PUBLIC_URL'),
				'FieldName' => 'public_url',
				'Default' => 'Y',
				'Type' => 'bool',
			],
			'MyCompanyId' => [
				'Name' => GetMessage('CRM_GEDA_NAME_MY_COMPANY_ID'),
				'FieldName' => 'my_company_id',
				'Type' => 'select',
				'Options' => $myCompanies,
				'FullMap' => [
					'myCompanyRequisites' => $myCompanyRequisites,
					'myCompanyBankDetails' => $myCompanyBankDetails,
				],
			],
			'MyCompanyRequisiteId' => [
				'Name' => GetMessage('CRM_GEDA_NAME_MY_COMPANY_REQUISITE_ID'),
				'FieldName' => 'my_company_requisite_id',
				'Type' => 'select',
			],
			'MyCompanyBankDetailId' => [
				'Name' => GetMessage('CRM_GEDA_NAME_MY_COMPANY_BANK_DETAIL_ID'),
				'FieldName' => 'my_company_bank_detail_id',
				'Type' => 'select',
			],
		);

		$dialog->setMap($map);
		$myCompanyId = $dialog->getCurrentValue('my_company_id');
		$myCompanyRequisiteId = $dialog->getCurrentValue('my_company_requisite_id');
		$templateId = $dialog->getCurrentValue('template_id');
		if($formName && $formName === 'bizproc_automation_robot_dialog' && !$templateId)
		{
			$templateId = key($templatesList);
		}
		if($templateId > 0)
		{
			$template = DocumentGenerator\Template::loadById($templateId);
			if($template && !$template->isDeleted())
			{
				$controller = new \Bitrix\Crm\Controller\DocumentGenerator\Template();
				$result = $controller->getFieldsAction($template, $entityTypeId);
				if(is_array($result))
				{
					foreach($result['templateFields'] as $name => $field)
					{
						if(isset($field['type']) && in_array($field['type'], $skipFieldTypes))
						{
							unset($result['templateFields'][$name]);
						}
					}
					$map['Values']['TemplateFields'] = $result['templateFields'];
				}
			}
		}
		if($myCompanyId > 0)
		{
			$map['MyCompanyRequisiteId']['Options'] = $myCompanyRequisites[$myCompanyId] ?? [];
		}
		if($myCompanyRequisiteId > 0)
		{
			$map['MyCompanyBankDetailId']['Options'] = $myCompanyBankDetails[$myCompanyRequisiteId] ?? [];
		}
		$dialog->setMap($map);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$useSubscription = $arCurrentValues['use_subscription'] ?? null;
		if(!empty($useSubscription))
		{
			$useSubscription = $useSubscription === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$useSubscription = $arCurrentValues['use_subscription_text'] ?? 'N';
		}

		$enablePublicUrl = $arCurrentValues['public_url'] ?? null;
		if(!empty($enablePublicUrl))
		{
			$enablePublicUrl = $enablePublicUrl === 'N' ? 'N' : 'Y';
		}
		else
		{
			$enablePublicUrl = $arCurrentValues['public_url_text'] ?? 'N';
		}
		$createActivity = $arCurrentValues['create_activity'] ?? null;
		if (!empty($createActivity))
		{
			$enablePublicUrl = $enablePublicUrl === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$createActivity = $arCurrentValues['create_activity'] ?? 'N';
		}

		$withStamps = $arCurrentValues['with_stamps'] ?? null;
		if(!empty($withStamps))
		{
			$withStamps = $withStamps === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$withStamps = $arCurrentValues['with_stamps_text'] ?? 'N';
		}

		$properties = [
			'TemplateId' => !empty($arCurrentValues['template_id']) ? $arCurrentValues['template_id'] : $arCurrentValues['template_id_text'],
			'UseSubscription' => $useSubscription,
			'EnablePublicUrl' => $enablePublicUrl,
			'CreateActivity' => $createActivity,
			'WithStamps' => $withStamps,
			'Values' => $arCurrentValues['Values'],
			'MyCompanyId' => !empty($arCurrentValues['my_company_id']) ? $arCurrentValues['my_company_id'] : $arCurrentValues['my_company_id_text'],
			'MyCompanyRequisiteId' => !empty($arCurrentValues['my_company_requisite_id']) ? $arCurrentValues['my_company_requisite_id'] : $arCurrentValues['my_company_requisite_id_text'],
			'MyCompanyBankDetailId' => !empty($arCurrentValues['my_company_bank_detail_id']) ? $arCurrentValues['my_company_bank_detail_id'] : $arCurrentValues['my_company_bank_detail_id_text'],
		];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$activity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$activity['Properties'] = $properties;

		return true;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (empty($arTestProperties['TemplateId']))
		{
			$arErrors[] = [
				"code" => "NotExist",
				"parameter" => "TemplateId",
				"message" => GetMessage("CRM_GEDA_EMPTY_TEMPLATE_ID")
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	/**
	 * @param int $entityTypeId
	 * @return null|string
	 */
	public static function getDataProviderByEntityTypeId($entityTypeId)
	{
		return DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvider($entityTypeId);
	}

	public static function getAjaxResponse($request)
	{
		$response = '';

		if(empty($request['customer_action']))
		{
			return $response;
		}

		if (!Loader::includeModule("crm"))
		{
			return '';
		}
		if(!DocumentGeneratorManager::getInstance()->isEnabled())
		{
			return '';
		}

		if($request['customer_action'] == 'getValuePropertyDialog')
		{
			if(!$request['templateId'] || !$request['entity_type'] || !$request['placeholder'])
			{
				return $response;
			}
			$template = DocumentGenerator\Template::loadById($request['templateId']);
			if(!$template)
			{
				return $response;
			}
			$entityTypeId = \CCrmOwnerType::ResolveID($request['entity_type']);
			$providerClassName = static::getDataProviderByEntityTypeId($entityTypeId);
			if(!$providerClassName)
			{
				return $response;
			}
			$template->setSourceType($providerClassName);
			$document = DocumentGenerator\Document::createByTemplate($template, ' ');
			$fields = $document->getFields([$request['placeholder']], true, true);
			$response = self::renderValuePropertyDialog($request['isRobot'] == 'y', $providerClassName, $request['placeholder'], \Bitrix\Main\Engine\Response\Converter::toJson()->process($fields[$request['placeholder']]));
		}

		return $response;
	}

	public static function renderValuePropertyDialog($isRobot, $providerClassName, $placeholder, array $field = null, $value = null)
	{
		if((!$value || empty($value)) && isset($field['chain']))
		{
			$value = $field['chain'];
		}

		if(is_object($value) || is_array($value))
		{
			$value = '';
		}

		$placeholderUri = false;
		if(is_array($field) && $field['chain'] && method_exists(DocumentGenerator\Driver::getInstance(), 'getPlaceholdersListUri'))
		{
			$placeholderUri = DocumentGenerator\Driver::getInstance()->getPlaceholdersListUri($providerClassName, 'crm', $placeholder);
		}
		if(empty($field['group']))
		{
			$field['group'] = [];
		}
		if($field['title'])
		{
			$field['group'][] = $field['title'];
		}

		if($isRobot)
		{
			$value = htmlspecialcharsbx($value);

			$result = '<div class="bizproc-automation-popup-settings" data-placeholder="'.$placeholder.'">';
			$result .= '<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">';
			if($placeholderUri)
			{
				$result.= '<a class="bp-geda-fields-link" href="'.$placeholderUri->getLocator().'">';
			}
			$result .= $placeholder;
			if($placeholderUri)
			{
				$result.= '</a>';
			}
			if(!empty($field['group']))
			{
				$result .= '<br />'.implode(' -> ', $field['group']);
			}
			$result .= '</span>';
			$result .= '<div>';
			$result .= '<input class="bizproc-automation-popup-input" data-role="inline-selector-target" name="Values['.$placeholder.']" autocomplete="off" type="text" value="'.$value.'">';
			$result .= '</div><a class="bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light" href="#">'.GetMessage('CRM_GEDA_NAME_DELETE').'</a>';
			$result .= '</div>';
		}
		else
		{
			$result = '<td data-placeholder="'.$placeholder.'" align="right" class="adm-detail-content-cell-l">';
			if($placeholderUri)
			{
				$result.= '<a class="bp-geda-fields-link" href="'.$placeholderUri->getLocator().'">';
			}
			$result .= $placeholder;
			if($placeholderUri)
			{
				$result .= '</a>';
			}
			$result .= ':';
			if(!empty($field['group']))
			{
				$result .= '<br />'.implode(' -> ', $field['group']);
			}
			$result .= '</td><td>'.CBPDocument::ShowParameterField("string", 'Values['.$placeholder.']', $value).'&nbsp;<a class="bp-geda-delete-row">'.GetMessage('CRM_GEDA_NAME_DELETE').'</a></td>';
		}

		return $result;
	}

	protected function prepareValue($value)
	{
		if(is_object($value))
		{
			if($value instanceof Value\Date)
			{
				$value = $value->toSystemObject();
			}
			else
			{
				$value = $this->ParseValue($value, 'string');
			}
		}
		elseif(is_array($value))
		{
			foreach($value as &$val)
			{
				$val = $this->prepareValue($val);
			}
		}

		return $value;
	}

	/**
	 * Get ItemIdentifier object from documentId.
	 *
	 * @param array $documentId
	 * @return \Bitrix\Crm\ItemIdentifier|null
	 */
	protected function extractItemIdentifier(array $documentId): ?\Bitrix\Crm\ItemIdentifier
	{
		if (count($documentId) === 3 && isset($documentId[2]))
		{
			[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			$entityId = (int)$entityId;
			if ($entityTypeId > 0 && $entityId > 0)
			{
				return new \Bitrix\Crm\ItemIdentifier($entityTypeId, $entityId);
			}
		}

		return null;
	}
}
