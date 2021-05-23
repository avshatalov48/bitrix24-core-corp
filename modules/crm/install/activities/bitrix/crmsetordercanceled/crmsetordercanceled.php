<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSetOrderCanceled
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"CancelStatusId" => "",
			"CancelReason" => ""
		];
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
			$this->WriteToTrackingService(GetMessage('CRM_SOCCL_ORDER_ERROR'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$order = \Bitrix\Crm\Order\Order::load($entityId);

		if (!$order)
		{
			$this->WriteToTrackingService(GetMessage('CRM_SOCCL_ORDER_NOT_FOUND'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if ($order->isCanceled())
		{
			$this->WriteToTrackingService(GetMessage('CRM_SOCCL_ORDER_IS_CANCELED'), 0, \CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$cancelReason = (string)$this->CancelReason;
		$cancelStatusId = (string)$this->CancelStatusId;

		$result = $order->setField('STATUS_ID', $cancelStatusId);
		if ($result->isSuccess() && $cancelReason)
		{
			$result = $order->setField('REASON_CANCELED', $cancelReason);
		}

		if ($result->isSuccess())
		{
			$result = $order->save();
		}

		if (!$result->isSuccess())
		{
			foreach ($result->getErrorMessages() as $errorMessage)
			{
				$this->WriteToTrackingService($errorMessage, 0, \CBPTrackingType::Error);
			}
			return CBPActivityExecutionStatus::Closed;
		}

		CBPDocument::TerminateWorkflow(
			$this->GetWorkflowInstanceId(),
			$this->GetDocumentId(),
			$errors,
			GetMessage('CRM_SOCCL_TERMINATE')
		);

		//Stop running queue
		throw new Exception("TerminateWorkflow");
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

		$statuses = [];
		foreach (\Bitrix\Crm\Order\OrderStatus::getAllStatusesNames() as $statusId => $name)
		{
			if (\Bitrix\Crm\Order\OrderStatus::getSemanticID($statusId) === \Bitrix\Crm\PhaseSemantics::FAILURE)
			{
				$statuses[$statusId] = $name;
			}
		}

		$dialog->setMap([
			'CancelStatusId' => [
				'Name' => GetMessage('CRM_SOCCL_STATUS_NAME'),
				'FieldName' => 'cancel_status_id',
				'Type' => 'select',
				'Options' => $statuses
			],
			'CancelReason' => [
				'Name' => GetMessage('CRM_SOCCL_COMMENT_NAME'),
				'Description' => GetMessage('CRM_SOCCL_COMMENT_NAME'),
				'FieldName' => 'cancel_reason',
				'Type' => 'text'
			]
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$properties = array(
			'CancelStatusId' => $arCurrentValues['cancel_status_id'],
			'CancelReason' => $arCurrentValues['cancel_reason'],
		);

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];
		if (empty($arTestProperties["CancelStatusId"]))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "TargetStatus", "message" => GetMessage("CRM_SOCCL_STATUS_ERROR"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}
}