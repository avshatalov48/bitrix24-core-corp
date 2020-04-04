<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSetOrderDeducted
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array("Title" => "");
	}

	public function Execute()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		list($entityTypeName, $entityId) = explode('_', $this->GetDocumentId()[2]);

		if ($entityTypeName !== \CCrmOwnerType::OrderName)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SODD_ORDER_ERROR'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$order = \Bitrix\Crm\Order\Order::load($entityId);

		if (!$order)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SODD_ORDER_NOT_FOUND'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		/** @var \Bitrix\Crm\Order\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem())
			{
				continue;
			}
			$result = $shipment->setField('DEDUCTED', "Y");
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

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		return true;
	}
}