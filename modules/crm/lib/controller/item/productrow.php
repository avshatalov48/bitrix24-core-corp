<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\Order;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Loader;
use Bitrix\Catalog;

class ProductRow extends Base
{
	/** @var string|ProductRowTable */
	protected $dataManager = ProductRowTable::class;

	public function getAutoWiredParameters(): array
	{
		$params = parent::getAutoWiredParameters();

		$params[] = new ExactParameter(
			\Bitrix\Crm\ProductRow::class,
			'productRow',
			function ($className, array $fields): \Bitrix\Crm\ProductRow {

				$fields = $this->convertKeysToUpper($fields);
				$fields = current($this->prepareForSave([$fields])) ?: [];

				return \Bitrix\Crm\ProductRow::createFromArray($fields);
			}
		);

		return $params;
	}

	public function addAction(\Bitrix\Crm\ProductRow $productRow): ?array
	{
		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItemByProductRow($productRow);
		if (!isset($factory) || !isset($item))
		{
			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return null;
		}

		$productAddResult = $item->addToProductRows($productRow);
		if (!$productAddResult->isSuccess())
		{
			$this->addErrors($productAddResult->getErrors());

			return null;
		}

		$itemUpdateResult = $factory->getUpdateOperation($item)->launch();
		if (!$itemUpdateResult->isSuccess())
		{
			$this->addErrors($itemUpdateResult->getErrors());

			return null;
		}

		return [
			'productRow' => $productRow,
		];
	}

	protected function getFactoryAndItemByProductRow(\Bitrix\Crm\ProductRow $productRow): ?array
	{
		return $this->getFactoryAndItem(
			(string)$productRow->getOwnerType(),
			(int)$productRow->getOwnerId(),
		);
	}

	protected function getFactoryAndItem(string $ownerType, int $ownerId): ?array
	{
		$entityTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($ownerType);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$this->isEntityTypeSupported($entityTypeId))
		{
			$this->addError(
				ErrorCode::getEntityTypeNotSupportedError($entityTypeId)
			);

			return null;
		}

		$item = $factory->getItem($ownerId);
		if (!$item)
		{
			$this->addError(
				ErrorCode::getOwnerNotFoundError()
			);

			return null;
		}

		return [$factory, $item];
	}

	/**
	 * Check if the provided entity type is supported. Since there are factories that don't fully support CRUD yet,
	 * they are not available via these REST/AJAX methods
	 *
	 * @todo Remove when all factories fully support CRUD
	 * @see Factory\Deal::getUpdateOperation()
	 * @see Factory\Deal::getItems()
	 * and etc.
	 *
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	protected function isEntityTypeSupported(int $entityTypeId): bool
	{
		return \CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId);
	}

	public function getAction(int $id): ?array
	{
		$productRow = $this->dataManager::getById($id)->fetchObject();
		if (!$productRow)
		{
			$this->addError(
				ErrorCode::getNotFoundError()
			);

			return null;
		}

		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItemByProductRow($productRow);
		if (!isset($factory) || !isset($item))
		{
			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canReadItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return null;
		}

		return [
			'productRow' => $productRow,
		];
	}

	public function deleteAction(int $id): void
	{
		$productRow = $this->dataManager::getById($id)->fetchObject();
		if (!$productRow)
		{
			$this->addError(
				ErrorCode::getNotFoundError()
			);

			return;
		}

		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItemByProductRow($productRow);
		if (!isset($factory) || !isset($item))
		{
			return;
		}

		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return;
		}

		$item->removeFromProductRows($productRow);

		$itemUpdateResult = $factory->getUpdateOperation($item)->launch();
		if (!$itemUpdateResult->isSuccess())
		{
			$this->addErrors($itemUpdateResult->getErrors());
		}
	}

	public function updateAction(int $id, array $fields): ?array
	{
		$originalProductRow = $this->dataManager::getById($id)->fetchObject();
		if (!$originalProductRow)
		{
			$this->addError(
				ErrorCode::getNotFoundError()
			);

			return null;
		}

		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItemByProductRow($originalProductRow);
		if (!isset($factory) || !isset($item))
		{
			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return null;
		}

		$productRowsWithSnakeCaseKeys = $this->convertKeysToUpper($fields);
		$productRowsWithSnakeCaseKeys = current($this->prepareForSave([$productRowsWithSnakeCaseKeys]));

		if ($productRowsWithSnakeCaseKeys)
		{
			$productUpdateResult = $item->updateProductRow($originalProductRow->getId(), $productRowsWithSnakeCaseKeys);
			if (!$productUpdateResult->isSuccess())
			{
				$this->addErrors($productUpdateResult->getErrors());

				return null;
			}

			$itemUpdateResult = $factory->getUpdateOperation($item)->launch();
			if (!$itemUpdateResult->isSuccess())
			{
				$this->addErrors($itemUpdateResult->getErrors());

				return null;
			}
		}
		else
		{
			foreach (array_keys($fields) as $fieldName)
			{
				$this->addError(
					new Error("Field '{$fieldName}' not available for update", ErrorCode::INVALID_ARG_VALUE),
				);
			}

			return null;
		}

		return [
			'productRow' => $item->getProductRows() ? $item->getProductRows()->getByPrimary($id) : null,
		];
	}

	public function listAction(?array $order = null, ?array $filter = null, ?PageNavigation $pageNavigation = null): ?Page
	{
		$getListParams = $this->prepareGetListParamsFromArgs($order, $filter, $pageNavigation);

		if (!$this->checkReadPermissions($getListParams))
		{
			return null;
		}

		if (!$this->isGetListParamsValid($getListParams))
		{
			return null;
		}

		$collection = $this->dataManager::getList($getListParams)->fetchCollection();

		return new Page(
			'productRows',
			$collection,
			function () use ($getListParams): int {
				return $this->dataManager::getCount($getListParams['filter']);
			}
		);
	}

	protected function prepareGetListParamsFromArgs(?array $order, ?array $filter, ?PageNavigation $pageNavigation): array
	{
		$params = [
			'order' => $this->prepareOrderFromArgs($order),
			'filter' => $this->prepareFilterFromArgs($filter),
		];

		if ($pageNavigation)
		{
			$params['offset'] = $pageNavigation->getOffset();
			$params['limit'] = $pageNavigation->getLimit();
		}

		return $params;
	}

	protected function prepareOrderFromArgs(?array $order): array
	{
		if (!is_array($order))
		{
			$order = [];
		}

		return $this->convertKeysToUpper($order);
	}

	protected function prepareFilterFromArgs(?array $filter): array
	{
		if (!is_array($filter))
		{
			$filter = [];
		}

		$filter = $this->convertKeysToUpper($filter);

		return $this->removeDotsFromKeys($filter);
	}

	protected function checkReadPermissions(array $getListParams): bool
	{
		$ownerType = $getListParams['filter']['=OWNER_TYPE'] ?? null;
		if (empty($ownerType))
		{
			$this->addError(
				ErrorCode::getRequiredArgumentMissingError('=ownerType')
			);

			return false;
		}

		$ownerId = $getListParams['filter']['=OWNER_ID'] ?? null;
		if ($ownerId <= 0)
		{
			$this->addError(
				ErrorCode::getRequiredArgumentMissingError('=ownerId')
			);

			return false;
		}

		$isReadPermitted = Container::getInstance()->getUserPermissions()->checkReadPermissions(
			\CCrmOwnerTypeAbbr::ResolveTypeID($ownerType),
			(int)$ownerId
		);

		if (!$isReadPermitted)
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);
		}

		return $isReadPermitted;
	}

	protected function isGetListParamsValid(array $getListParams): bool
	{
		return $this->isFilterValid($getListParams['filter'] ?? []);
	}

	protected function isFilterValid(array $filter): bool
	{
		$isErrorProneOperation = static function(string $key): bool {
			return (
				preg_match('/([<>])/u', $key)
				&& mb_strpos($key, '><') === false
			);
		};
		$isValidType = fn($value) => is_string($value) || is_numeric($value);

		foreach ($filter as $key => $value)
		{
			if (is_string($key) && $isErrorProneOperation($key) && !$isValidType($value))
			{
				$this->addError(
					new Error("Value for filter key '{$key}' should be either string or number", ErrorCode::INVALID_ARG_VALUE),
				);

				return false;
			}

			if (is_array($value) && !$this->isFilterValid($value))
			{
				return false;
			}
		}

		return true;
	}

	public function setAction(string $ownerType, int $ownerId, array $productRows): ?array
	{
		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItem($ownerType, $ownerId);
		if (!isset($factory) || !isset($item))
		{
			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return null;
		}

		$productRowsWithSnakeCaseKeys = $this->convertKeysToUpper($productRows);
		$productRowsWithSnakeCaseKeys = $this->prepareForSave($productRowsWithSnakeCaseKeys);

		$productsSetResult = $item->setProductRowsFromArrays($productRowsWithSnakeCaseKeys);
		if (!$productsSetResult->isSuccess())
		{
			$this->addErrors($productsSetResult->getErrors());

			return null;
		}

		$itemUpdateResult = $factory->getUpdateOperation($item)->launch();
		if (!$itemUpdateResult->isSuccess())
		{
			$this->addErrors($itemUpdateResult->getErrors());

			return null;
		}

		return [
			'productRows' => $item->getProductRows() ? $item->getProductRows()->getAll() : [],
		];
	}

	public function fieldsAction(): array
	{
		$fieldsInfo = $this->getFieldsInfo();
		$fieldsInfoInRestFormat = \CCrmRestHelper::prepareFieldInfos($fieldsInfo);

		// intentionally not recursive to preserve keys like 'isRequired'
		$converter = new Converter(Converter::KEYS | Converter::TO_CAMEL | Converter::LC_FIRST);

		return [
			'fields' => $converter->process($fieldsInfoInRestFormat)
		];
	}

	protected function getFieldsInfo(): array
	{
		$fieldsInfo = \CCrmProductRow::GetFieldsInfo();

		foreach ($fieldsInfo as $fieldName => &$singleFieldInfo)
		{
			$singleFieldInfo['CAPTION'] = \CCrmProductRow::GetFieldCaption($fieldName);

			// some fields are read-only in new api, but fully changeable in old api
			// we can't simply modify \CCrmProductRow::GetFieldsInfo() as we have 2 different cases
			// therefore, add read-only attribute to new api fields in runtime
			if (
				!\CCrmFieldInfoAttr::isFieldReadOnly($singleFieldInfo)
				&& in_array($fieldName, $this->getReadOnlyFieldNames(), true)
			)
			{
				$singleFieldInfo['ATTRIBUTES'][] = \CCrmFieldInfoAttr::ReadOnly;
			}
		}

		return $fieldsInfo;
	}

	/**
	 * @return string[]
	 */
	protected function getReadOnlyFieldNames(): array
	{
		return [
			'PRICE_EXCLUSIVE',
			'PRICE_NETTO',
			'PRICE_BRUTTO',
			'PRICE_ACCOUNT',
		];
	}

	/**
	 * Prepares product fields for save:
	 * Checks attributes
	 * Calculates TYPE and PRODUCT_NAME
	 *
	 * @param $productRows
	 * @return array
	 */
	protected function prepareForSave($productRows): array
	{
		$result = [];
		$fieldsInfo = $this->getFieldsInfo();

		foreach ($productRows as $index => $productRow)
		{
			$internalizedFields = $this->internalizeFields($productRow, $fieldsInfo);
			if ($internalizedFields)
			{
				$result[$index] = $internalizedFields;
			}

			if (isset($productRow['TAX_RATE']))
			{
				if (
					(float)$productRow['TAX_RATE'] > 0
					|| $productRow['TAX_RATE'] === 0
					|| (
						is_string($productRow['TAX_RATE'])
						&& isset($productRow['TAX_RATE'][0])
						&& $productRow['TAX_RATE'][0] === '0'
					)
				)
				{
					$result[$index]['TAX_RATE'] = (float)$productRow['TAX_RATE'];
				}
				else
				{
					$result[$index]['TAX_RATE'] = null;
				}
			}
		}

		$productIds = array_filter(array_column($result, 'PRODUCT_ID'));
		if ($productIds && Loader::includeModule('catalog'))
		{
			$productData = [];

			$productTableIterator = Catalog\ProductTable::getList([
				'select' => [
					'ID',
					'PRODUCT_NAME' => 'IBLOCK_ELEMENT.NAME',
					'TYPE',
				],
				'filter' => [
					'@ID' => $productIds,
				],
			]);
			while ($productItem = $productTableIterator->fetch())
			{
				$productData[$productItem['ID']] = $productItem;
			}

			foreach ($result as $index => $productRow)
			{
				$productId = $productRow['PRODUCT_ID'] ?? null;

				if ($productId && isset($productData[$productId]))
				{
					$result[$index]['TYPE'] = (int)$productData[$productId]['TYPE'];
					$result[$index]['PRODUCT_NAME'] ??= $productData[$productId]['PRODUCT_NAME'];
				}
			}
		}

		return $result;
	}

	/**
	 * Removes from $fields ReadOnly and Hidden values
	 *
	 * @param array $fields
	 * @param array $fieldsInfo
	 * @return array
	 */
	protected function internalizeFields(array $fields, array $fieldsInfo): array
	{
		$result = [];

		$ignoredAttributes = [
			\CCrmFieldInfoAttr::ReadOnly,
			\CCrmFieldInfoAttr::Hidden
		];

		foreach ($fields as $fieldName => $fieldsValue)
		{
			$info = $fieldsInfo[$fieldName] ?? null;
			if (!$info)
			{
				unset($fields[$fieldName]);
				continue;
			}

			$attrs = $info['ATTRIBUTES'] ?? [];

			$attrs = array_intersect($ignoredAttributes, $attrs);
			if (!empty($attrs))
			{
				unset($fields[$fieldName]);
				continue;
			}

			$result[$fieldName] = $fieldsValue;
		}

		return $result;
	}

	public function getAvailableForPaymentAction(int $ownerId, string $ownerType): ?array
	{
		/** @var Factory $factory */
		/** @var Item $item */
		[$factory, $item] = $this->getFactoryAndItem($ownerType, $ownerId);
		if (!isset($factory) || !isset($item))
		{
			return null;
		}

		if (!Container::getInstance()->getUserPermissions()->canReadItem($item))
		{
			$this->addError(
				ErrorCode::getAccessDeniedError()
			);

			return null;
		}

		$ownerTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($ownerType);

		$orderIds = OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);
		if (count($orderIds) > 1)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CONTROLLER_ITEM_PRODUCT_ROW_ERROR_MULTI_ORDERS_NOT_SUPPORTED')
				)
			);

			return null;
		}

		$manager = new Order\ProductManager($ownerTypeId, $ownerId);

		$orderId = current($orderIds);
		if ($orderId)
		{
			$order = Order\Order::load($orderId);

			$manager->setOrder($order);
		}

		$idToQuantityMap = [];

		foreach ($manager->getPayableItems() as $payableItem)
		{
			$rowId = Products\BasketXmlId::getRowIdFromXmlId($payableItem['XML_ID']);

			$idToQuantityMap[$rowId] = $payableItem['QUANTITY'];
		}

		if (!$idToQuantityMap)
		{
			return ['productRows' => []];
		}

		$result = [];
		foreach ($item->getProductRows() as $productRow)
		{
			if (!isset($idToQuantityMap[$productRow['ID']]))
			{
				continue;
			}

			$result[] = $productRow->setQuantity($idToQuantityMap[$productRow['ID']]);
		}

		return ['productRows' => $result];
	}
}
