<?php

use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSetShipmentField
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"TargetStatus" => null,
			"TargetAllowDelivery" => null,
			"TargetDeducted" => null,
			"DeliveryId" => null,
		];
	}

	public function Execute()
	{

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeName, $entityId] = explode('_', $this->GetDocumentId()[2]);

		if ($entityTypeName !== \CCrmOwnerType::OrderName)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SSF_ORDER_ERROR'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$targetStatus = (string) $this->TargetStatus;
		$targetAllowDelivery = \CBPHelper::getBool($this->TargetAllowDelivery) ? 'Y' : 'N';
		$targetDeducted = \CBPHelper::getBool($this->TargetDeducted) ? 'Y' : 'N';
		$deliveryId = (int) $this->DeliveryId;

		$order = \Bitrix\Crm\Order\Order::load($entityId);

		if (!$order)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SSF_ORDER_NOT_FOUND'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		/** @var \Bitrix\Crm\Order\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem() || ($deliveryId > 0 && $shipment->getDeliveryId() !== $deliveryId))
			{
				continue;
			}

			$toUpdate = self::getFieldsToUpdate($shipment, [
				'STATUS_ID' => $targetStatus,
				'DEDUCTED' => $targetDeducted,
				'ALLOW_DELIVERY' => $targetAllowDelivery
			]);

			if (!$toUpdate)
			{
				continue;
			}

			$result = $shipment->setFields($toUpdate);
			if (!$result->isSuccess())
			{
				foreach ($result->getErrorMessages() as $errorMessage)
				{
					$this->WriteToTrackingService($errorMessage, 0, \CBPTrackingType::Error);
				}
				return CBPActivityExecutionStatus::Closed;
			}
		}

		$result = $order->save();
		if (!$result->isSuccess())
		{
			foreach ($result->getErrorMessages() as $errorMessage)
			{
				$this->WriteToTrackingService($errorMessage, 0, \CBPTrackingType::Error);
			}
			return CBPActivityExecutionStatus::Closed;
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getFieldsToUpdate(\Bitrix\Crm\Order\Shipment $shipment, array $targets): array
	{
		$toUpdate = [];
		foreach ($targets as $targetKey => $value)
		{
			if ($shipment->getField($targetKey) != $value)
			{
				$toUpdate[$targetKey] = $value;
			}
		}
		return $toUpdate;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
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

		$dialog->setMap([
			'TargetStatus' => [
				'Name' => GetMessage('CRM_SSF_TARGET_STATUS_NAME'),
				'FieldName' => 'target_status',
				'Type' => 'select',
				'Options' => \Bitrix\Crm\Order\DeliveryStatus::getAllStatusesNames(),
			],
			'TargetAllowDelivery' => [
				'Name' => GetMessage('CRM_SSF_TARGET_AD_NAME'),
				'FieldName' => 'target_allow_delivery',
				'Type' => 'bool',
			],
			'TargetDeducted' => [
				'Name' => GetMessage('CRM_SSF_TARGET_DD_NAME'),
				'FieldName' => 'target_deducted',
				'Type' => 'bool',
			],
			'DeliveryId' => [
				'Name' => GetMessage('CRM_SSF_DELIVERY_ID_NAME'),
				'FieldName' => 'delivery_id',
				'Type' => 'select',
				'Options' => self::getDeliveryOptions()
			],
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$properties = array(
			'TargetStatus' => $arCurrentValues['target_status'],
			'TargetAllowDelivery' => $arCurrentValues['target_allow_delivery'],
			'TargetDeducted' => $arCurrentValues['target_deducted'],
			'DeliveryId' => $arCurrentValues['delivery_id'],
		);

		$arErrors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private static function getDeliveryOptions(): array
	{
		$result = [];
		if (Loader::includeModule('sale'))
		{
			foreach(\Bitrix\Sale\Delivery\Services\Manager::getActiveList() as $service)
			{
				$result[$service['ID']] = $service['NAME'];
			}
		}
		return $result;
	}
}