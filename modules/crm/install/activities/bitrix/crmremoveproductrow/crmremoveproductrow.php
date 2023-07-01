<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmRemoveProductRow extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		$currentIds = $this->workflow->isDebug() ? $this->getCurrentIds($entityTypeId, $entityId) : [];

		if (!$this->deleteRows($entityTypeId, $entityId))
		{
			$this->WriteToTrackingService(
				GetMessage('CRM_RMPR_REMOVE_PRODUCTS_ERROR'),
				0,
				CBPTrackingType::Error
			);
		}
		elseif ($currentIds)
		{
			$this->logDebug($currentIds);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function deleteRows($entityTypeId, $entityId): bool
	{
		$complexDocumentId = CCrmBizProcHelper::ResolveDocumentId((int)$entityTypeId, (int)$entityId);
		$entityDocument = $complexDocumentId[1];

		if (class_exists($entityDocument) && method_exists($entityDocument, 'setProductRows'))
		{
			return $entityDocument::setProductRows($complexDocumentId[2], [])->isSuccess();
		}

		if ($entityTypeId === CCrmOwnerType::Order)
		{
			$order = \Bitrix\Crm\Order\Order::load($entityId);
			if (!$order)
			{
				return false;
			}

			$basket = $order->getBasket();
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			foreach ($basket->getBasketItems() as $basketItem)
			{
				$basketItem->delete();
			}

			return $order->save()->isSuccess();
		}

		$entityType = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);

		return \CCrmProductRow::SaveRows($entityType, $entityId, [], null, false);
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

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = [];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if ($errors)
		{
			return false;
		}

		$activity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$activity['Properties'] = $properties;

		return true;
	}

	private function getCurrentIds($entityTypeId, $entityId)
	{
		return array_column(
			\CCrmProductRow::LoadRows(
				\CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId),
				$entityId,
				true
			),
			'ID'
		);
	}

	private function logDebug(array $ids)
	{
		$this->writeDebugInfo(
			$this->getDebugInfo(
				['DeletedId' => $ids],
				[
					'DeletedId' => [
						'Name' => GetMessage('CRM_RMPR_DELETED_IDS'),
						'Type' => 'string',
						'Multiple' => true,
					],
				]
			)
		);
	}
}
