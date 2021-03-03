<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Order;

class CBPCrmGetPaymentInfoActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'OrderId' => null,

			//return
			'PaymentId' => null,
			'PaymentAccountNumber' => null,
		);

		$this->SetPropertiesTypes([
			'PaymentId' => [
				'Type' => 'int'
			],
			'PaymentAccountNumber' => [
				'Type' => 'string'
			]
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->PaymentId = null;
		$this->PaymentAccountNumber = null;
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

		if (!$orderId)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$order = Order\Order::load($orderId);
		if ($order)
		{
			/** @var Order\Payment $payment */
			foreach ($order->getPaymentCollection() as $payment)
			{
				$this->PaymentId = $payment->getId();
				$this->PaymentAccountNumber = $payment->getField('ACCOUNT_NUMBER');

				break;
			}
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

		$dialog->setMap([
			'OrderId' => [
				'Name' => GetMessage('CRM_BP_GPI_ORDER_ID'),
				'FieldName' => 'order_id',
				'Type' => 'int'
			],
		]);

		return $dialog;
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