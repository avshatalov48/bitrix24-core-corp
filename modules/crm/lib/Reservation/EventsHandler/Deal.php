<?php

namespace Bitrix\Crm\Reservation\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale;

Main\Localization\Loc::loadLanguageFile(__FILE__);

final class Deal
{
	private static $isProcessInventoryManagementExecuting = false;

	/**
	 * @param array $dealFields
	 * @return Main\Result
	 */
	public static function processInventoryManagement(array $dealFields): Main\Result
	{
		$result = new Main\Result();

		if (!self::isProcessInventoryManagementAvailable())
		{
			return $result;
		}

		if (self::$isProcessInventoryManagementExecuting)
		{
			$result->setData([
				'IS_EXECUTING' => self::$isProcessInventoryManagementExecuting,
			]);
			return $result;
		}

		$dealId = $dealFields['ID'];

		$currentStage = self::getCurrentStage($dealId);
		$currentSemanticId = \CCrmDeal::GetSemanticID($currentStage);
		if (
			empty($currentSemanticId)
			|| $currentSemanticId === Crm\PhaseSemantics::SUCCESS
			|| $currentSemanticId === Crm\PhaseSemantics::FAILURE
		)
		{
			return $result;
		}

		self::$isProcessInventoryManagementExecuting = true;
		$result = new Main\Result();
		$result->setData([
			'IS_EXECUTING' => self::$isProcessInventoryManagementExecuting,
		]);

		$stageId = $dealFields['STAGE_ID'] ?? '';
		$semanticId = \CCrmDeal::GetSemanticID($stageId);

		$processInventoryManagementResult = null;
		if ($semanticId === Crm\PhaseSemantics::SUCCESS)
		{
			$processInventoryManagementResult = self::ship($dealId);
		}
		elseif ($semanticId === Crm\PhaseSemantics::FAILURE)
		{
			$processInventoryManagementResult = self::unReserve($dealId);
		}

		if ($processInventoryManagementResult && !$processInventoryManagementResult->isSuccess())
		{
			Crm\Activity\Provider\StoreDocument::addActivity($dealId);

			$result->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('CRM_RESERVATION_DEAL_CLOSE_ERROR')
				)
			);
		}

		self::$isProcessInventoryManagementExecuting = false;
		$result->setData([
			'IS_EXECUTING' => self::$isProcessInventoryManagementExecuting,
		]);

		return $result;
	}

	private static function ship(int $dealId): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder->setOwnerTypeId(\CCrmOwnerType::Deal);
		$entityBuilder->setOwnerId($dealId);

		$dealProducts = self::getDealProducts($dealId);

		$basketReservation = new Crm\Reservation\BasketReservation();
		$basketReservation->addProducts($dealProducts);
		$reservationMap = $basketReservation->getReservationMap();

		$defaultStore = Catalog\StoreTable::getDefaultStoreId();
		foreach ($dealProducts as $product)
		{
			$storeId = (int)$product['STORE_ID'] > 0 ? $product['STORE_ID'] : $defaultStore;

			$xmlId = null;
			if (isset($reservationMap[$product['ID']]))
			{
				$basketReservationData = Sale\Reservation\Internals\BasketReservationTable::getById(
					$reservationMap[$product['ID']]
				)->fetch();
				if ($basketReservationData)
				{
					$basketItem = Sale\Repository\BasketItemRepository::getInstance()->getById(
						$basketReservationData['BASKET_ID']
					);
					if ($basketItem)
					{
						$xmlId = $basketItem->getField('XML_ID');
					}
				}
			}

			$entityBuilder->addProduct(
				new Crm\Reservation\Product($product['ID'], $product['QUANTITY'], $storeId, $xmlId)
			);
		}

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->ship();
	}

	private static function unReserve(int $dealId): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder
			->setOwnerTypeId(\CCrmOwnerType::Deal)
			->setOwnerId($dealId)
		;

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->unReserve();
	}

	private static function isUsedInventoryManagement(): bool
	{
		if (Main\Loader::includeModule('catalog'))
		{
			return Catalog\Config\State::isUsedInventoryManagement();
		}

		return false;
	}

	private static function isInventoryManagementIntegrationRestricted(): bool
	{
		return !Crm\Restriction\RestrictionManager::getInventoryControlIntegrationRestriction()->hasPermission();
	}

	private static function getDealProducts(int $dealId): array
	{
		$dealProducts = [];

		foreach (\CCrmDeal::LoadProductRows($dealId) as $dealProduct)
		{
			$dealProducts[$dealProduct['ID']] = $dealProduct;
		}

		return $dealProducts;
	}

	private static function getCurrentStage(int $dealId): string
	{
		$deal = \CCrmDeal::GetByID($dealId, false);
		return $deal['STAGE_ID'] ?? '';
	}

	private static function isProcessInventoryManagementAvailable(): bool
	{
		return (
			self::isUsedInventoryManagement()
			&& !self::isInventoryManagementIntegrationRestricted()
			&& Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
		);
	}
}
