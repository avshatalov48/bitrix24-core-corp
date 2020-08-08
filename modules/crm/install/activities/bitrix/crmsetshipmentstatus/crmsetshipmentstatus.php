<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSetShipmentStatus
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"TargetStatus" => null,
		];
	}

	public function Execute()
	{
		$targetStatus = (string)$this->TargetStatus;

		if (!$targetStatus || !\Bitrix\Main\Loader::includeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		list($entityTypeName, $entityId) = explode('_', $this->GetDocumentId()[2]);

		if ($entityTypeName !== \CCrmOwnerType::OrderName)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SSS_ORDER_ERROR'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$order = \Bitrix\Crm\Order\Order::load($entityId);

		if (!$order)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SSS_ORDER_NOT_FOUND'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		/** @var \Bitrix\Crm\Order\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem())
			{
				continue;
			}
			$result = $shipment->setField('STATUS_ID', $targetStatus);
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

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties["TargetStatus"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "TargetStatus", "message" => GetMessage("CRM_SSS_TARGET_STATUS_EMPTY"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
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
				'Name' => GetMessage('CRM_SSS_TARGET_STATUS_NAME'),
				'FieldName' => 'target_status',
				'Type' => 'select',
				'Required' => true,
				'Options' => \Bitrix\Crm\Order\DeliveryStatus::getAllStatusesNames(),
				'Default' => 'DF'
			]
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$properties = array(
			'TargetStatus' => $arCurrentValues['target_status'],
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
}