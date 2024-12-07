<?php

use Bitrix\Crm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmChangeStatusActivity extends CBPActivity
{
	private static $counter = [];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'TargetStatus' => null,
			'ModifiedBy' => null,
		];
	}

	public function Execute()
	{
		if ($this->TargetStatus == null || !CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$targetStatus = (string)$this->TargetStatus;
		if (is_numeric($targetStatus))
		{
			$targetStatus = (int)$targetStatus;
		}

		//check recursion
		if (!isset(static::$counter[$documentId[2]]))
		{
			static::$counter[$documentId[2]] = [];
		}
		if (!isset(static::$counter[$documentId[2]][$targetStatus]))
		{
			static::$counter[$documentId[2]][$targetStatus] = 0;
		}

		++static::$counter[$documentId[2]][$targetStatus];

		if (static::$counter[$documentId[2]][$targetStatus] > 2)
		{
			$this->WriteToTrackingService(
				\Bitrix\Main\Localization\Loc::getMessage('CRM_CHANGE_STATUS_RECURSION_MSGVER_1'),
				0,
				CBPTrackingType::Error
			);

			CBPDocument::TerminateWorkflow(
				$this->GetWorkflowInstanceId(),
				$documentId,
				$arErrorsTmp,
				GetMessage('CRM_CHANGE_STATUS_RECURSION_MSGVER_1')
			);

			//Stop running queue
			throw new Exception('TerminateWorkflow');
		}
		// end check recursion

		[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);
		$fieldKey = null;
		$stages = [];

		switch ($entityTypeName)
		{
			case \CCrmOwnerType::DealName:
				$fieldKey = 'STAGE_ID';
				$stages = $this->getDealStages($entityId);
				break;
			case \CCrmOwnerType::LeadName:
				$fieldKey = 'STATUS_ID';
				$stages = $this->getLeadStages($entityId);
				break;
			case \CCrmOwnerType::OrderName:
				$fieldKey = 'STATUS_ID';
				$stages = $this->getOrderStages($entityId);
				break;
			case \CCrmOwnerType::InvoiceName:
				$fieldKey = 'STATUS_ID';
				break;
			default:
				$targetStatus = (string)$targetStatus;
				$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
				$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);

				if (isset($factory))
				{
					$fieldKey = $factory->getEntityFieldNameByMap(Crm\Item::FIELD_NAME_STAGE_ID);
					$stages = $this->getItemStages($entityTypeId, $entityId);
					break;
				}
		}

		if ($this->workflow->isDebug())
		{
			$debugFields = [
				'TargetStatus' => $this->TargetStatus,
				'ModifiedBy' => $this->ModifiedBy,
			];
			$this->writeDebugInfo($this->getDebugInfo($debugFields));
		}

		if ($stages && !in_array($targetStatus, $stages, true))
		{
			$this->WriteToTrackingService(GetMessage('CRM_CHANGE_STATUS_INCORRECT_STAGE_MSGVER_1'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		if ($fieldKey && $entityId > 0)
		{
			$runtime = CBPRuntime::GetRuntime();
			/** @var CBPDocumentService $ds */
			$ds = $runtime->GetService('DocumentService');

			$ds->UpdateDocument(
				$documentId,
				[$fieldKey => $targetStatus],
				$this->ModifiedBy
			);
		}

		CBPDocument::TerminateWorkflow(
			$this->GetWorkflowInstanceId(),
			$documentId,
			$arErrorsTmp,
			GetMessage('CRM_CHANGE_STATUS_TERMINATED_MSGVER_1')
		);

		//Stop running queue
		throw new Exception('TerminateWorkflow');
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties['TargetStatus']))
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'TargetStatus',
				'message' => GetMessage('CRM_CHANGE_STATUS_EMPTY_PROP_MSGVER_1'),
			];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
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

		$dialog->setMapCallback([__CLASS__, 'getPropertiesDialogMap']);

		return $dialog;
	}

	/**
	 * @param \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
	 * @return array Map.
	 */
	public static function getPropertiesDialogMap($dialog)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return [];
		}

		$documentType = $dialog->getDocumentType()[2];
		$context = $dialog->getContext();
		$categoryId = isset($context['DOCUMENT_CATEGORY_ID']) ? (int)$context['DOCUMENT_CATEGORY_ID'] : null;

		$targetStatusProperty = [
			'Name' => GetMessage('CRM_CHANGE_STATUS_STAGE'),
			'FieldName' => 'target_status',
			'Type' => 'select',
			'Required' => true,
		];

		switch ($documentType)
		{
			case \CCrmOwnerType::DealName:
				$targetStatusProperty['Name'] = GetMessage('CRM_CHANGE_STATUS_STAGE');
				$targetStatusProperty['Type'] = 'deal_stage';
				$targetStatusProperty['Settings'] = ['categoryId' => $categoryId];
				break;

			case \CCrmOwnerType::LeadName:
				$targetStatusProperty['Type'] = 'lead_status';
				break;

			case \CCrmOwnerType::OrderName:
				$targetStatusProperty['Options'] = [];

				$statuses = \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat();
				foreach ($statuses as $id => $statusInfo)
				{
					$targetStatusProperty['Options'][$statusInfo['STATUS_ID']] = $statusInfo['NAME'];
				}

				break;

			case \CCrmOwnerType::InvoiceName:
				$targetStatusProperty['Options'] = [];

				$statuses = CCrmStatus::GetStatus('INVOICE_STATUS');
				foreach ($statuses as $id => $statusInfo)
				{
					$targetStatusProperty['Options'][$statusInfo['STATUS_ID']] = $statusInfo['NAME'];
				}

				break;
			default:
				$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::ResolveID($documentType));
				$targetStatusProperty['Options'] =
					isset($factory)
						? static::getTargetStatusOptionsByFactory($factory, $categoryId)
						: []
				;

				break;
		}

		return [
			'TargetStatus' => $targetStatusProperty,
			'ModifiedBy' => [
				'Name' => GetMessage('CRM_CHANGE_STATUS_MODIFIED_BY'),
				'FieldName' => 'modified_by',
				'Type' => 'user',
			],
		];
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType
		]);
		return static::getPropertiesDialogMap($dialog);
	}

	protected static function getTargetStatusOptionsByFactory(Crm\Service\Factory $factory, ?int $categoryId): array
	{
		$statuses = [];

		if (!$factory->isCategoriesSupported())
		{
			$categories = [null];
		}
		elseif (isset($categoryId))
		{
			$currentCategory = $factory->getCategory($categoryId);
			$categories = isset($currentCategory) ? [$currentCategory] : [];
		}
		else
		{
			$categories = $factory->getCategories();
		}
		$shouldSpecifyCategoryName = count($categories) > 1;

		if ($factory->isStagesEnabled())
		{
			foreach ($categories as $category)
			{
				$categoryId = isset($category) ? $category->getId() : null;
				$statusPrefix = $shouldSpecifyCategoryName ? $category->getName() . ' / ' : '';
				foreach ($factory->getStages($categoryId) as $status)
				{
					$statuses[$status->getStatusId()] = $statusPrefix . $status->getName();
				}
			}
		}

		return $statuses;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = [
			'TargetStatus' => $arCurrentValues['target_status'],
			'ModifiedBy' => CBPHelper::UsersStringToArray($arCurrentValues['modified_by'], $documentType, $errors),
		];

		if (
			empty($properties['TargetStatus'])
			&& !empty($arCurrentValues['target_status_text'])
			&& static::isExpression($arCurrentValues['target_status_text'])
		)
		{
			$properties['TargetStatus'] = $arCurrentValues['target_status_text'];
		}

		if ($errors)
		{
			return false;
		}

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);
		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	private function getLeadStages($id)
	{
		return array_keys(\CCrmStatus::GetStatusList('STATUS'));
	}

	private function getDealStages($id)
	{
		$dbRes = \CCrmDeal::GetListEx(
			[],
			[
				'=ID' => $id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['CATEGORY_ID']
		);
		$entity = $dbRes->Fetch();
		$categoryId = isset($entity['CATEGORY_ID']) ? (int)$entity['CATEGORY_ID'] : 0;

		return array_keys(Crm\Category\DealCategory::getStageList($categoryId));
	}

	private function getOrderStages($id)
	{
		return array_keys(Crm\Order\OrderStatus::getListInCrmFormat());
	}

	protected function getItemStages(int $entityTypeId, int $entityId): array
	{
		$stages = [];

		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		$item = isset($factory) ? $factory->getItem($entityId) : null;

		if (isset($item))
		{
			foreach ($factory->getStages($item->getCategoryId()) as $stage)
			{
				$stages[] = $stage->getStatusId();
			}
		}

		return $stages;
	}
}
