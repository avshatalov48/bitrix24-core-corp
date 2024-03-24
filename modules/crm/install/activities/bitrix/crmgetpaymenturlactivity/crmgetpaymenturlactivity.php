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
use Bitrix\Crm\Item;

/**
 * @property ?string Title
 * @property ?string Url
 * @property array|int OrderId
 * @property bool Autocreate
 */
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

	protected function reInitialize()
	{
		parent::ReInitialize();
		$this->Url = null;
	}

	public function execute()
	{
		if (!$this->checkModules())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$orderId = is_array($this->OrderId) ? reset($this->OrderId) : $this->OrderId;

		$this->logOrderId($orderId);
		if (CBPHelper::getBool($this->Autocreate))
		{
			$this->Url = $this->getUrlAuto();
		}
		elseif ($orderId)
		{
			$this->Url = $this->getUrlByOrderId((int)$orderId);
		}

		$this->logUrl();

		return CBPActivityExecutionStatus::Closed;
	}

	private function checkModules(): bool
	{
		return (
			Main\Loader::includeModule('crm')
			&& Main\Loader::includeModule('salescenter')
			&& Main\Loader::includeModule('catalog')
			&& Main\Loader::includeModule('iblock')
		);
	}

	private function logOrderId($orderId): void
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$map = static::getPropertiesDialogMap();

		$this->writeDebugInfo(
			$this->getDebugInfo(
				['OrderId' => $orderId],
				['OrderId' => $map['OrderId']]
			)
		);
	}

	private function getUrlAuto(): ?string
	{
		[$entityTypeId, $entityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		switch ($entityTypeId)
		{
			case CCrmOwnerType::SmartInvoice:
				$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
				if (!$factory)
				{
					return null;
				}
				/** @var Item\SmartInvoice $item */
				$item = $factory->getItem($entityId);
				if (!$item)
				{
					return null;
				}
				$buildData = $this->getBuilderDataBySmartInvoice($item);
				break;
			case CCrmOwnerType::Deal:
				$deal = CCrmDeal::GetByID($entityId, false);
				if (!$deal)
				{
					return null;
				}
				$buildData = $this->getBuilderDataByDeal($deal);
				break;
			default:
				return null;
		}

		if (empty($buildData['PRODUCT']))
		{
			return null;
		}

		$payment = $this->createPayment($buildData);
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

	private function createPayment(array $buildData): ?Order\Payment
	{
		$builder = Builder\Manager::getBuilder(
			Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT
		);

		try
		{
			$builder->build($buildData);

			$order = $builder->getOrder();
		}
		catch (Helpers\Order\Builder\BuildingException $exception)
		{
			return null;
		}

		$payment = null;

		/** @var Order\Payment $item */
		foreach ($order->getPaymentCollection() as $item)
		{
			if ($item->getId() === 0)
			{
				$payment = $item;
				break;
			}
		}

		$r = $order->save();
		if (!$r->isSuccess())
		{
			return null;
		}

		return $payment;
	}

	private function getBuilderDataBySmartInvoice(Item\SmartInvoice $invoice): array
	{
		$order = $this->findOrderForEntity(\CCrmOwnerType::SmartInvoice, $invoice->getId());

		return [
			'ID' => $order ? $order->getId() : 0,
			'CURRENCY' => $invoice->getCurrencyId(),
			'SITE_ID' => SITE_ID,
			'OWNER_ID' => $invoice->getId(),
			'OWNER_TYPE_ID' => \CCrmOwnerType::SmartInvoice,
			'CLIENT' => [
				'CONTACT_IDS' => $invoice->getContactIds(),
				'COMPANY_ID' => (int)$invoice->getCompanyId(),
			],
			'PRODUCT' => $this->getBuilderProductData(CCrmOwnerType::SmartInvoice, $invoice->getId()),
		];
	}

	private function getBuilderDataByDeal(array $deal): array
	{
		$order = $this->findOrderForEntity(\CCrmOwnerType::Deal, $deal['ID']);

		return [
			'ID' => $order ? $order->getId() : 0,
			'CURRENCY' => $deal['CURRENCY_ID'],
			'SITE_ID' => SITE_ID,
			'OWNER_ID' => $deal['ID'],
			'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
			'CLIENT' => [
				'CONTACT_IDS' => Binding\DealContactTable::getDealContactIDs($deal['ID']),
				'COMPANY_ID' => (int)$deal['COMPANY_ID'],
			],
			'PRODUCT' => $this->getBuilderProductData(CCrmOwnerType::Deal, $deal['ID']),
		];
	}

	private function getBuilderProductData(int $ownerTypeId, int $ownerId): array
	{
		$manager = new Order\ProductManager($ownerTypeId, $ownerId);

		$order = $this->findOrderForEntity($ownerTypeId, $ownerId);
		if ($order)
		{
			$manager->setOrder($order);
		}

		$products = $manager->getPayableItems();
		$products = $this->fillProductsProperties($products);
		// re-index the products array by the 'BASKET_CODE' key
		return array_combine(array_column($products, 'BASKET_CODE'), $products);
	}

	private function findOrderForEntity(int $ownerTypeId, int $ownerId) : ?Order\Order
	{
		static $entityToOrderMap = [];

		$key = $ownerTypeId.'_'.$ownerId;

		if (!array_key_exists($key, $entityToOrderMap))
		{
			$entityToOrderMap[$key] = null;

			$dbRes = Order\EntityBinding::getList([
				'select' => ['ORDER_ID'],
				'filter' => [
					'=OWNER_ID' => $ownerId,
					'=OWNER_TYPE_ID' => $ownerTypeId
				],
				'order' => ['ORDER_ID' => 'DESC'],
				'limit' => 1
			]);

			if ($row = $dbRes->fetch())
			{
				$entityToOrderMap[$key] = Order\Order::load($row['ORDER_ID']);
			}
		}

		return $entityToOrderMap[$key];
	}

	private function fillProductsProperties(array $products): array
	{
		$productIds = array_column($products, 'OFFER_ID');
		if (empty($productIds))
		{
			return $products;
		}

		$productParams = Helpers\Admin\Blocks\OrderBasket::getProductsData(
			$productIds,
			SITE_ID,
			['PROPS']
		);

		foreach ($products as $index => $product)
		{
			$props = $productParams[$product['OFFER_ID']]['PROPS'] ?? [];

			$products[$index]['PROPS'] = $props;
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

	private function logUrl(): void
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$this->writeDebugInfo(
			$this->getDebugInfo(['Url' => $this->Url], static::getReturnPropertiesMap())
		);
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
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

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
			$workflowTemplate,
			$activityName
		);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	private static function getPropertiesDialogMap(): array
	{
		return [
			'Autocreate' => [
				'Name' => Main\Localization\Loc::getMessage('CRM_BP_GPU_AUTOCREATE'),
				'FieldName' => 'autocreate',
				'Type' => FieldType::BOOL,
				'Required' => true,
				'Default' => 'N',
			],
			'OrderId' => [
				'Name' => Main\Localization\Loc::getMessage('CRM_BP_GPU_ORDER_ID'),
				'FieldName' => 'order_id',
				'Type' => FieldType::INT,
			],
		];
	}

	protected static function getReturnPropertiesMap(): array
	{
		return [
			'Url' => [
				'Name' => Main\Localization\Loc::getMessage('CRM_BP_GPU_URL_CREATED'),
				'FieldName' => 'url',
				'Type' => FieldType::STRING,
			]
		];
	}
}