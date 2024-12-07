<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Order;

class CBPCrmOrderPayActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		$this->arProperties = [
			'Title' => '',
			'OrderId' => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$orderId = $this->OrderId;
		if (is_array($orderId))
		{
			$orderId = reset($orderId);
		}

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}

		if(!$orderId)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$order = Order\Order::load($orderId);
		if ($order)
		{
			/** @var Order\Payment $payment */
			foreach ($order->getPaymentCollection() as $payment)
			{
				if ($payment->isPaid())
				{
					continue;
				}

				$service = $payment->getPaySystem();
				if ($service && $service->isRecurring($payment))
				{
					$result = $service->repeatRecurrent($payment);
					if (!$result->isSuccess())
					{
						return CBPActivityExecutionStatus::Closed;
					}
				}
			}
		}
		else
		{
			$this->writeToTrackingService(
				GetMessage('CRM_BP_OPAY_ORDER_NOT_FOUND'),
				0,
				CBPTrackingType::Error,
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		]);

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'OrderId' => [
				'Name' => GetMessage('CRM_BP_OPAY_ORDER_ID'),
				'FieldName' => 'order_id',
				'Type' => 'int'
			],
		];
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$properties = [
			'OrderId' => $arCurrentValues['order_id'],
		];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}
}