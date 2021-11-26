<?php

namespace Bitrix\Crm\Controller\v2;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

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
		$entityTypeId = \CCrmOwnerType::ResolveID($ownerType);
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
		return
			($entityTypeId === \CCrmOwnerType::Quote)
			|| \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
		;
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

		$productUpdateResult = $item->updateProductRow($originalProductRow->getId(), $this->convertKeysToUpper($fields));
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
}
