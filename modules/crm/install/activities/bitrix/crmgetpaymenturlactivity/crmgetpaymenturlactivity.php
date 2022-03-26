<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\FieldType;
use Bitrix\Main;
use Bitrix\Salescenter\Integration;
use Bitrix\Salescenter\Builder;
use Bitrix\Sale\Helpers;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Order;

class CBPCrmGetPaymentUrlActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'OrderId' => null,
			'Autocreate' => false,

			//return
			'Url' => null,
		];

		$this->SetPropertiesTypes([
			'Url' => [
				'Type' => 'string',
			]
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->Url = null;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm')
			|| !CModule::IncludeModule('salescenter')
		)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$orderId = is_array($this->OrderId) ? reset($this->OrderId) : $this->OrderId;

		if (CBPHelper::getBool($this->Autocreate))
		{
			$this->Url = $this->getUrlByDealId($this->getCurrentDealId());
		}
		elseif ($orderId)
		{
			$this->Url = $this->getUrlByOrderId((int)$orderId);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getCurrentDealId(): int
	{
		return CCrmBizProcHelper::resolveEntityId($this->getDocumentId())[1];
	}

	private function getUrlByDealId(int $dealId): ?string
	{
		$payment = $this->createPayment($dealId);
		if (!$payment)
		{
			return null;
		}

		$urlInfo = Integration\LandingManager::getInstance()->getUrlInfoByOrder(
			$payment->getOrder(),
			['paymentId' => $payment->getId()]
		);

		return $urlInfo['shortUrl'];
	}

	private function createPayment(int $dealId) :? Order\Payment
	{
		$deal = CCrmDeal::GetByID($dealId, false);
		if (!$deal)
		{
			return null;
		}

		$builder = Builder\Manager::getBuilder(
			Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT
		);

		try
		{
			$builder->build(
				$this->getBuilderData($deal)
			);

			$order = $builder->getOrder();
		}
		catch (Helpers\Order\Builder\BuildingException $exception)
		{
			return null;
		}

		$r = $order->save();
		if (!$r->isSuccess())
		{
			return null;
		}

		foreach ($order->getPaymentCollection() as $payment)
		{
			return $payment;
		}

		return null;
	}

	private function getBuilderData(array $deal) : array
	{
		return [
			'CURRENCY' => $deal['CURRENCY_ID'],
			'SITE_ID' => SITE_ID,
			'OWNER_ID' => $deal['ID'],
			'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
			'CLIENT' => [
				'CONTACT_IDS' => Binding\DealContactTable::getDealContactIDs($deal['ID']),
				'COMPANY_ID' => (int)$deal['COMPANY_ID']
			],
			'PRODUCT' => $this->getBuilderProductData($deal['ID'])
		];
	}

	private function getBuilderProductData(int $dealId) : array
	{
		$manager = new Order\ProductManager(CCrmOwnerType::Deal, $dealId);

		$products = [];

		foreach ($manager->getPayableItems() as $item)
		{
			$products[$item['BASKET_CODE']] = $item;
		}

		return $products;
	}

	private function getUrlByOrderId(int $orderId): ?string
	{
		if (Main\Loader::includeModule('salescenter') && $orderId > 0)
		{
			$urlInfo = Integration\LandingManager::getInstance()->getUrlInfoByOrderId($orderId);
			return $urlInfo['url'] ?? null;
		}

		return null;
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
			'siteId' => $siteId
		]);

		$dialog->setMap(static::getPropertiesDialogMap());

		return $dialog;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$workflowTemplate,
		&$workflowParameters,
		&$workflowVariables,
		$currentValues,
		&$errors
	)
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return false;
		}

		$documentService = CBPRuntime::GetRuntime(true)->getDocumentService();
		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
		]);

		$properties = [];
		foreach (static::getPropertiesDialogMap() as $fieldId => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($dialog->getDocumentType(), $fieldProperties);
			if(!$field)
			{
				continue;
			}

			$properties[$fieldId] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	private static function getPropertiesDialogMap(): array
	{
		return [
			'Autocreate' => [
				'Name' => GetMessage('CRM_BP_GPU_AUTOCREATE'),
				'FieldName' => 'autocreate',
				'Type' => FieldType::BOOL,
				'Required' => true,
				'Default' => 'N',
			],
			'OrderId' => [
				'Name' => GetMessage('CRM_BP_GPU_ORDER_ID'),
				'FieldName' => 'order_id',
				'Type' => FieldType::INT,
			],
		];
	}
}