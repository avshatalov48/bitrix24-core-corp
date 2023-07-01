<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Activity\Access\CatalogAccessChecker;
use Bitrix\Catalog\Product;
use Bitrix\Crm\Order;
use Bitrix\Sale\Basket;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

class CBPCrmCopyMoveProductRow extends CBPActivity
{
	private const OP_COPY = 'cp';
	private const OP_MOVE = 'mv';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DstEntityType' => null,
			'DstEntityId' => null,
			'Operation' => self::OP_COPY,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		$dstEntity = $this->resolveDstEntityId();
		if (!$dstEntity)
		{
			$this->logDebug($this->DstEntityId);
			$this->WriteToTrackingService(GetMessage('CRM_CMPR_NO_DST_ENTITY'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		[$dstEntityTypeId, $dstEntityId] = $dstEntity;

		$this->logDebug($dstEntityId);

		if ($entityTypeId === $dstEntityTypeId && $entityId === $dstEntityId)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CMPR_SAME_ENTITY_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		if (
			($entityTypeId === \CCrmOwnerType::Order || $dstEntityTypeId === \CCrmOwnerType::Order)
			&& !Main\Loader::includeModule('sale')
		)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$copyResult = $this->copyRows($entityTypeId, $entityId, $dstEntityTypeId, $dstEntityId);

		if ($copyResult && $this->Operation === self::OP_MOVE)
		{
			$this->deleteRows($entityTypeId, $entityId);
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
		elseif ($entityTypeId === CCrmOwnerType::Order)
		{
			$order = Order\Order::load($entityId);
			if (!$order)
			{
				return false;
			}

			$basket = $order->getBasket();

			/** @var Order\BasketItem $basketItem */
			foreach ($basket->getBasketItems() as $basketItem)
			{
				$result = $basketItem->delete();
				if (!$result->isSuccess())
				{
					return false;
				}
			}

			return $order->save()->isSuccess();
		}
		else
		{
			$entityType = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);

			return \CCrmProductRow::SaveRows($entityType, $entityId, [], null, false);
		}
	}

	private function copyRows(int $entityTypeId, int $entityId, int $dstEntityTypeId, int $dstEntityId): bool
	{
		$sourceRows = $this->getSourceRows($entityTypeId, $entityId);

		if (!$sourceRows)
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CMPR_NO_SOURCE_PRODUCTS'),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		$saveResult = false;

		$entityDocument = CCrmBizProcHelper::ResolveDocumentName($dstEntityTypeId);

		if ($dstEntityTypeId === \CCrmOwnerType::Deal)
		{
			if ($entityTypeId === \CCrmOwnerType::Order)
			{
				$sourceRows = $this->convertToCrmProductRowFormat($sourceRows);
			}

			$saveResult = \CCrmDeal::addProductRows($dstEntityId, $sourceRows, [], false);
		}
		elseif (class_exists($entityDocument) && method_exists($entityDocument, 'addProductRows'))
		{
			if ($entityTypeId === \CCrmOwnerType::Order)
			{
				$sourceRows = $this->convertToCrmProductRowFormat($sourceRows);
			}

			$dstDocumentId = CCrmBizProcHelper::ResolveDocumentId($dstEntityTypeId, $dstEntityId);
			$productRows = array_map([Crm\ProductRow::class, 'createFromArray'], $sourceRows);

			$saveResult = $entityDocument::addProductRows($dstDocumentId[2], $productRows)->isSuccess();
		}
		elseif (
			$dstEntityTypeId === CCrmOwnerType::Order
			&& Main\Loader::includeModule('catalog')
		)
		{
			$order = Order\Order::load($dstEntityId);

			if (!$order)
			{
				$this->WriteToTrackingService(
					Loc::getMessage('CRM_CMPR_NO_DST_ENTITY'),
					0,
					CBPTrackingType::Error
				);

				return false;
			}

			if ($entityTypeId !== \CCrmOwnerType::Order)
			{
				$sourceRows = $this->convertToSaleBasketFormat($sourceRows);
			}

			$basket = $order->getBasket();

			$isAllRowsAdded = true;

			foreach ($sourceRows as $row)
			{
				$row['CURRENCY'] = $order->getCurrency();

				$result = Product\Basket::addProductToBasket(
					$basket,
					$row,
					[]
				);

				$basketItem = $result->getData()['BASKET_ITEM'] ?? null;

				if (
					!$result->isSuccess()
					|| $basketItem === null
				)
				{
					$isAllRowsAdded = false;
					break;
				}

				$singleStrategy = Basket\RefreshFactory::createSingle($basketItem->getBasketCode());
				$basket->refresh($singleStrategy);
			}

			if ($isAllRowsAdded)
			{
				$saveResult = $order->save()->isSuccess();
			}
		}

		if (!$saveResult)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDA_COPY_PRODUCTS_ERROR'), 0, CBPTrackingType::Error);
		}

		return $saveResult;
	}

	private function convertToSaleBasketFormat(array $products) : array
	{
		$converter = new Order\ProductManager\EntityProductConverter();

		$result = [];
		foreach ($products as $product)
		{
			$fields = $converter->convertToSaleBasketFormat($product);

			unset(
				$fields['OFFER_ID'],
				$fields['DISCOUNT_SUM'],
				$fields['DISCOUNT_RATE'],
				$fields['DISCOUNT_TYPE_ID']
			);

			$result[] = $fields;
		}

		return $result;
	}

	private function convertToCrmProductRowFormat(array $products) : array
	{
		$converter = new Order\ProductManager\EntityProductConverter();

		$result = [];
		foreach ($products as $product)
		{
			$result[] = $converter->convertToCrmProductRowFormat($product);
		}

		return $result;
	}

	private function resolveDstEntityId(): ?array
	{
		$entityType = $this->DstEntityType;
		if (CBPHelper::isEmptyValue($entityType))
		{
			return null;
		}

		$entityId = $this->DstEntityId;
		if (is_array($entityId))
		{
			$entityId = \CBPHelper::MakeArrayFlat($entityId);
			$entityId = reset($entityId);
		}

		if ($entityType === \CCrmOwnerTypeAbbr::Deal)
		{
			if (\CCrmDeal::Exists($entityId))
			{
				return [\CCrmOwnerType::Deal, (int)$entityId];
			}
		}
		elseif ($entityType === CCrmOwnerTypeAbbr::SmartInvoice)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::SmartInvoice);
			$item = isset($factory) ? $factory->getItem((int)$entityId) : null;

			return isset($item) ? [CCrmOwnerType::SmartInvoice, (int)$entityId] : null;
		}
		elseif ($entityType === CCrmOwnerTypeAbbr::SmartDocument)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::SmartDocument);
			$item = isset($factory) ? $factory->getItem((int)$entityId) : null;

			return isset($item) ? [CCrmOwnerType::SmartDocument, (int)$entityId] : null;
		}
		elseif (CCrmOwnerTypeAbbr::isDynamicTypeAbbreviation($entityType))
		{
			$dynamicTypeId = CCrmOwnerTypeAbbr::ResolveTypeID($entityType);

			$factory = Crm\Service\Container::getInstance()->getFactory($dynamicTypeId);
			$item = isset($factory) ? $factory->getItem((int)$entityId) : null;

			return isset($item) ? [$dynamicTypeId, (int)$entityId] : null;
		}
		elseif ($entityType === \CCrmOwnerTypeAbbr::Order)
		{
			if (Order\Order::load($entityId))
			{
				return [\CCrmOwnerType::Order, (int)$entityId];
			}
		}
		/*
		elseif ($entityType === \CCrmOwnerTypeAbbr::Invoice)
		{
			if (\CCrmInvoice::Exists($entityId))
			{
				return [\CCrmOwnerType::Invoice, $entityId];
			}
		}*/

		return null;
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

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$arWorkflowTemplate,
		&$arWorkflowParameters,
		&$arWorkflowVariables,
		$arCurrentValues,
		&$errors
	)
	{
		if (!CatalogAccessChecker::hasAccess())
		{
			return false;
		}

		$errors = [];

		$properties = [
			'DstEntityType' => $arCurrentValues['dst_entity_type'],
			'DstEntityId' => $arCurrentValues['dst_entity_id'],
			'Operation' => $arCurrentValues['operation'],
		];

		if ($properties['Operation'] !== self::OP_MOVE)
		{
			$properties['Operation'] = self::OP_COPY;
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

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$dstEntityTypeOptions = [
			\CCrmOwnerTypeAbbr::Deal => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal),
			\CCrmOwnerTypeAbbr::SmartInvoice => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
			\CCrmOwnerTypeAbbr::SmartDocument => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartDocument),
			\CCrmOwnerTypeAbbr::Order => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Order),
		];

		$dynamicTypesMap =
			Crm\Service\Container::getInstance()
				->getDynamicTypesMap()
				->load([
					'isLoadCategories' => false,
					'isLoadStages' => false,
				])
				->getTypes()
		;
		foreach ($dynamicTypesMap as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			if ($type->getIsLinkWithProductsEnabled())
			{
				$dstEntityTypeOptions[\CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId)] = $type->getTitle();
			}
		}

		return [
			'DstEntityType' => [
				'Name' => GetMessage('CRM_CMPR_DST_ENTITY_TYPE'),
				'FieldName' => 'dst_entity_type',
				'Type' => 'select',
				'Default' => \CCrmOwnerTypeAbbr::Deal,
				'Options' => $dstEntityTypeOptions,
				'Required' => true,
			],
			'DstEntityId' => [
				'Name' => GetMessage('CRM_CMPR_DST_ENTITY_ID'),
				'FieldName' => 'dst_entity_id',
				'Type' => 'int',
				'Required' => true,
				'AllowSelection' => true,
			],
			'Operation' => [
				'Name' => GetMessage('CRM_CMPR_OPERATION'),
				'FieldName' => 'operation',
				'Type' => 'select',
				'Default' => self::OP_COPY,
				'Options' => [
					self::OP_COPY => GetMessage('CRM_CMPR_OPERATION_CP'),
					self::OP_MOVE => GetMessage('CRM_CMPR_OPERATION_MV'),
				],
				'Required' => true,
			],
		];
	}

	private function logDebug($id)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$debugInfo = $this->getDebugInfo([
			'DstEntityId' => $id,
		]);

		$this->writeDebugInfo($debugInfo);
	}

	private function logDebugRows($operation, array $rowIds)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		$title = $operation === static::OP_COPY
			? GetMessage('CRM_CMPR_COPIED_ROWS')
			: GetMessage('CRM_CMPR_MOVED_ROWS')
		;

		$debugInfo = $this->getDebugInfo(
			['ProductRowId' => $rowIds],
			[
				'ProductRowId' => [
					'Name' => $title,
					'Type' => 'string',
					'Multiple' => true,
				],
			],
		);

		$this->writeDebugInfo($debugInfo);
	}

	private function getSourceRows(int $entityTypeId, int $entityId): array
	{
		$sourceRows = [];

		if ($entityTypeId === CCrmOwnerType::Order)
		{
			$order = Order\Order::load($entityId);
			if ($order === null)
			{
				return $sourceRows;
			}

			/** @var Order\BasketItem $basketItem */
			foreach ($order->getBasket() as $basketItem)
			{
				$sourceRows[] = [
					'NAME' => $basketItem->getField('NAME'),
					'MODULE' => $basketItem->getField('MODULE'),
					'PRODUCT_ID' => $basketItem->getProductId(),
					'QUANTITY' => $basketItem->getQuantity(),
					'PRICE' => $basketItem->getPrice(),
					'BASE_PRICE' => $basketItem->getBasePrice(),
					'CUSTOM_PRICE' => $basketItem->isCustomPrice() ? 'Y' : 'N',
					'DISCOUNT_PRICE' => $basketItem->getDiscountPrice(),
					'VAT_INCLUDED' => $basketItem->isVatInPrice() ? 'Y' : 'N',
					'VAT_RATE' => $basketItem->getVatRate(),
					'TYPE' => $basketItem->getField('TYPE'),
					'PRODUCT_PROVIDER_CLASS' => $basketItem->getField('PRODUCT_PROVIDER_CLASS'),
				];
			}
		}
		else
		{
			$sourceRows = \CCrmProductRow::LoadRows(
				\CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId),
				$entityId,
				true
			);
		}

		$this->logDebugRows($this->Operation, array_column($sourceRows, 'ID'));

		foreach ($sourceRows as $i => $product)
		{
			unset($sourceRows[$i]['ID'], $sourceRows[$i]['OWNER_ID'], $sourceRows[$i]['OWNER_TYPE']);
		}

		return $sourceRows;
	}

}
