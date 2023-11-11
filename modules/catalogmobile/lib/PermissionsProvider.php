<?php

namespace Bitrix\CatalogMobile;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access\Model\StoreDocument;
use Bitrix\Catalog\Access\Permission\PermissionDictionary;
use Bitrix\Catalog\StoreDocumentTable;

/**
 * Class PermissionsBuilder
 *
 * @package Bitrix\CatalogMobile
 * @internal
 */
final class PermissionsProvider
{
	/** @var PermissionsProvider */
	private static $instance;

	/** @var AccessController */
	private $accessController;

	/**
	 * ShipmentRepository constructor.
	 */
	private function __construct()
	{
		$this->accessController = AccessController::getCurrent();
	}

	/**
	 * @return PermissionsProvider
	 */
	public static function getInstance(): PermissionsProvider
	{
		if (is_null(static::$instance))
		{
			static::$instance = new PermissionsProvider();
		}

		return static::$instance;
	}

	/**
	 * @return array[]
	 */
	public function getPermissions(): array
	{
		$result = [];

		$actionsList = [
			ActionDictionary::ACTION_CATALOG_READ,
			ActionDictionary::ACTION_PRODUCT_ADD,
			ActionDictionary::ACTION_PRODUCT_EDIT,
			ActionDictionary::ACTION_PRODUCT_PURCHASE_INFO_VIEW,
			ActionDictionary::ACTION_PRICE_EDIT,
			ActionDictionary::ACTION_STORE_MODIFY,
			ActionDictionary::ACTION_DEAL_PRODUCT_RESERVE,
		];
		foreach ($actionsList as $action)
		{
			$result[$action] = $this->accessController->check($action);
		}

		$allowedPriceEntities = $this->accessController->getPermissionValue(
			ActionDictionary::ACTION_PRICE_ENTITY_EDIT
		);
		$result[ActionDictionary::ACTION_PRICE_ENTITY_EDIT] = is_array($allowedPriceEntities)
			? $allowedPriceEntities
			: [];

		$allowedDiscountEntities = $this->accessController->getPermissionValue(
			ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET
		);
		$result[ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET] = is_array($allowedDiscountEntities)
			? $allowedDiscountEntities
			: [];

		$allowedStores = $this->accessController->getPermissionValue(
			ActionDictionary::ACTION_STORE_VIEW
		);
		$allowedStores = is_array($allowedStores) ? $allowedStores : [];

		$result[ActionDictionary::ACTION_STORE_VIEW] = $allowedStores;
		$result['catalog_store_all'] = (
			is_array($allowedStores)
			&& in_array(
				PermissionDictionary::VALUE_VARIATION_ALL,
				$allowedStores,
				true
			)
		);

		$documentTypes = [
			...StoreDocumentTable::getTypeList(),
			StoreDocumentTable::TYPE_SALES_ORDERS,
		];

		foreach ($documentTypes as $type)
		{
			$storeDocumentActions = [
				ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
				ActionDictionary::ACTION_STORE_DOCUMENT_DELETE,
				ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT,
				ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL,
			];
			foreach ($storeDocumentActions as $storeDocumentAction)
			{
				$result['document'][$type][$storeDocumentAction] = $this->accessController->check(
					$storeDocumentAction,
					StoreDocument::createFromArray(['DOC_TYPE' => $type])
				);
			}
		}

		return $result;
	}
}
