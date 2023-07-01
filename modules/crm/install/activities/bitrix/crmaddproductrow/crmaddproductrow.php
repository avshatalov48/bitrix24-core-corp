<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;
use Bitrix\Crm\Activity\Access\CatalogAccessChecker;
use Bitrix\Main;

class CBPCrmAddProductRow extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'ProductId' => null,
			'RowPriceAccount' => null,
			'RowQuantity' => 1,
			'RowDiscountRate' => null
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		if (
			$entityTypeId === \CCrmOwnerType::Order
			&& !Main\Loader::includeModule('sale')
			&& !Main\Loader::includeModule('catalog')
		)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$id = $this->getProductId();
		$product = $this->getProduct($id);

		if (!$product)
		{
			$this->writeDebugInfo($this->getDebugInfo(
				['ProductId' => $id],
				['ProductId' => static::getPropertiesMap($this->getDocumentType())['ProductId']]
			));

			$this->WriteToTrackingService(GetMessage('CRM_APR_GET_PRODUCT_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$row = [
			'PRODUCT_ID' => $id,
			'QUANTITY' => (float)$this->RowQuantity,
		];

		$discountRate = $this->RowDiscountRate;
		if (!CBPHelper::isEmptyValue($discountRate))
		{
			$row['DISCOUNT_TYPE_ID'] = Crm\Discount::PERCENTAGE;
			$row['DISCOUNT_RATE'] = (float)$discountRate;
		}

		if ($product['VAT_RATE'])
		{
			$row['TAX_RATE'] = $product['VAT_RATE'];
			$row['TAX_INCLUDED'] = $product['VAT_INCLUDED'];
		}

		$price = $this->RowPriceAccount;
		if (CBPHelper::isEmptyValue($price))
		{
			$price = $product['PRICE'];
		}

		$this->calculatePrices($row, $price);

		$entity = $this->getDocumentId()[1];
		$addResult = false;

		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			$addResult = \CCrmDeal::addProductRows($entityId, [$row], [], false);
		}
		elseif (class_exists($entity) && method_exists($entity, 'addProductRows'))
		{
			$productRow = Crm\ProductRow::createFromArray($row);
			$addResult = $entity::addProductRows($this->getDocumentId()[2], [$productRow])->isSuccess();
		}
		elseif ($entityTypeId === CCrmOwnerType::Order)
		{
			$order = Crm\Order\Order::load($entityId);
			if ($order === null)
			{
				$this->writeToTrackingService(GetMessage('CRM_EMPTY_ORDER_ERROR'), 0, CBPTrackingType::Error);
				return CBPActivityExecutionStatus::Closed;
			}

			$basket = $order->getBasket();

			$result = Catalog\Product\Basket::addProductToBasket(
				$basket,
				[
					'LID' => $order->getSiteId(),
					'PRODUCT_ID' => $row['PRODUCT_ID'],
					'QUANTITY' => $row['QUANTITY'],
					'PRICE' => $row['PRICE'],
					'CUSTOM_PRICE' => 'Y',
					'CURRENCY' => $order->getCurrency(),
				],
				[]
			);

			$basketItem = $result->getData()['BASKET_ITEM'] ?? null;

			if (
				$result->isSuccess()
				&& $basketItem !== null
			)
			{
				$singleStrategy = Basket\RefreshFactory::createSingle($basketItem->getBasketCode());
				$basket->refresh($singleStrategy);

				$addResult = $order->save()->isSuccess();
			}
		}
		else
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->writeDebugInfo($this->getDebugInfo([
			'ProductId' => $row['PRODUCT_ID'],
			'RowPriceAccount' => $price,
			'RowQuantity' => $row['QUANTITY'],
			'RowDiscountRate' => $row['DISCOUNT_RATE']
		]));

		if (!$addResult)
		{
			$this->WriteToTrackingService(GetMessage('CRM_APR_ADD_ERROR'), 0, CBPTrackingType::Error);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getProductId(): int
	{
		$id = $this->ProductId;
		if (is_array($id))
		{
			$id = current(CBPHelper::MakeArrayFlat($id));
		}

		return (int)$id;
	}

	private function getProduct(int $id): ?array
	{
		$dbProduct = CCrmProduct::GetList(
			[],
			['ID' => $id],
			['ID', 'NAME', 'PRICE', 'VAT_ID', 'VAT_INCLUDED']
		);

		$product = $dbProduct->fetch();

		if ($product)
		{
			$product['VAT_RATE'] = null;

			if ($product['VAT_ID'])
			{
				$product['VAT_RATE'] = self::getVatRate($product['VAT_ID']);
			}

			return $product;
		}

		return null;
	}

	private function calculatePrices(array &$row, $price): void
	{
		if (isset($row['DISCOUNT_RATE']))
		{
			$price = Crm\Discount::calculatePrice($price, $row['DISCOUNT_RATE']);
		}

		$row['PRICE'] = $price;
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

		if (!CatalogAccessChecker::hasAccess())
		{
			$dialog->setRenderer(CatalogAccessChecker::getDialogRenderer());
		}
		else
		{
			$dialog->setMap(static::getPropertiesMap($documentType));
		}

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$productSettings = [];
		if (Main\Loader::includeModule('iblock') && Main\Loader::includeModule('catalog'))
		{
			$productSettings = [
				'iblockId' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
				'basePriceId' => \Bitrix\Crm\Product\Price::getBaseId(),
			];
		}

		return [
			'ProductId' => [
				'Name' => GetMessage('CRM_APR_PRODUCT_ID'),
				'FieldName' => 'product_id',
				'Type' => 'int',
				'Required' => true,
				'AllowSelection' => true,
				'Settings' => $productSettings
			],
			'RowPriceAccount' => [
				'Name' => GetMessage('CRM_APR_ROW_PRICE_ACCOUNT'),
				'FieldName' => 'row_price_account',
				'Type' => 'double',
				'AllowSelection' => true,
			],
			'RowQuantity' => [
				'Name' => GetMessage('CRM_APR_ROW_QUANTITY'),
				'FieldName' => 'row_quantity',
				'Type' => 'double',
				'Default' => 1,
				'AllowSelection' => true,
			],
			'RowDiscountRate' => [
				'Name' => GetMessage('CRM_APR_ROW_DISCOUNT_RATE'),
				'FieldName' => 'row_discount_rate',
				'Type' => 'double',
				'AllowSelection' => true,
			],
		];
	}

	private static function getVatRate(int $vatId): ?float
	{
		foreach (CCrmTax::GetVatRateInfos() as $vatRow)
		{
			if ($vatId === $vatRow['ID'])
			{
				return $vatRow['VALUE'];
			}
		}

		return null;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		if (!CatalogAccessChecker::hasAccess())
		{
			return false;
		}

		$errors = [];
		$properties = [];

		$documentService = CBPRuntime::GetRuntime(true)->getDocumentService();

		foreach (static::getPropertiesMap($documentType) as $key => $property)
		{
			$properties[$key] = $documentService->GetFieldInputValue(
				$documentType,
				$property,
				$property['FieldName'],
				$arCurrentValues,
				$errors
			);
		}

		if ($errors)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$activity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$activity['Properties'] = $properties;

		return true;
	}
}
