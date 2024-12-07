<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\Config\State;
use Bitrix\Catalog\Store\EnableWizard;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\CrmMobile\Dto\VatRate;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteExtraFields;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteStores;
use Bitrix\CrmMobile\ProductGrid\Enricher\ConvertCurrency;
use Bitrix\CrmMobile\ProductGrid\Enricher\EnricherContract;
use Bitrix\CrmMobile\ProductGrid\Enricher\UpdateFieldsForTaxMode;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Catalog;
use Bitrix\CatalogMobile\PermissionsProvider;
use Bitrix\CatalogMobile\Repository\MeasureRepository;
use Bitrix\Mobile\Query;
use Bitrix\Crm\Restriction\RestrictionManager;

Loader::requireModule('crm');
Loader::requireModule('catalog');
Loader::requireModule('catalogmobile');

class ProductGridQuery extends Query
{
	protected Item $entity;

	protected Accounting $accounting;

	protected string $currencyId;

	protected PermissionsProvider $permissionsProvider;

	public function __construct(Item $entity, ?string $currencyId = null)
	{
		$this->entity = $entity;
		$this->accounting = Container::getInstance()->getAccounting();
		$this->permissionsProvider = PermissionsProvider::getInstance();
		$this->currencyId = $currencyId ?? (string)$this->entity->getCurrencyId();
	}

	public function execute(): array
	{
		$products = $this->fetchItems();
		$summaryQuery = $this->getSummaryQuery($products);

		return [
			'entity' => $this->prepareEntityData(),
			'products' => $products,
			'summary' => $summaryQuery->execute(),
			'catalog' => [
				'id' => Catalog::getDefaultId(),
				'basePriceId' => Catalog::getBasePrice(),
				'currencyId' => Catalog::getBaseCurrency(),
			],
			'inventoryControl' => [
				'isAllowedReservation' => $this->isAllowedReservation(),
				'isReservationRestrictedByPlan' => $this->isReservationRestrictedByPlan(),
				'mode' => EnableWizard\Manager::getCurrentMode(),
				'isOnecRestrictedByPlan' => EnableWizard\TariffChecker::isOnecInventoryManagementRestricted(),
				'defaultDateReserveEnd' => ReservationService::getInstance()->getDefaultDateReserveEnd()->getTimestamp(),
				'isCatalogHidden' => State::isExternalCatalog(),
			],
			'measures' => array_values(MeasureRepository::findAll()),
			'taxes' => [
				'vatRates' => $this->fetchVatRates(),
				'productRowTaxUniform' => $this->isProductRowTaxUniform(),
			],
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
		];
	}

	protected function getSummaryQuery(array $products): SummaryQuery
	{
		return new SummaryQuery($this->entity, $products, $this->currencyId);
	}

	private function prepareEntityData(): array
	{
		$categoryId = $this->entity->isCategoriesSupported() ? $this->entity->getCategoryId() : null;

		return [
			'id' => $this->entity->getId(),
			'typeId' => $this->entity->getEntityTypeId(),
			'categoryId' => $categoryId,
			'typeName' => \CCrmOwnerType::ResolveName($this->entity->getEntityTypeId()),
			'editable' => $this->isEntityEditable(),
			'currencyId' => $this->currencyId,
			'detailPageUrl' => \CCrmOwnerType::GetDetailsUrl($this->entity->getEntityTypeId(), $this->entity->getId()),
		];
	}

	private function fetchItems(): array
	{
		$items = array_map(
			fn ($row) => new ProductRowViewModel($row, $this->entity->getCurrencyId()),
			$this->getEntityProductRows()
		);

		$enrichers = $this->getEnrichers();
		/** @var EnricherContract[] $enrichers */
		foreach ($enrichers as $enricher)
		{
			$items = $enricher->enrich($items);
		}

		return array_map(static fn ($item) => $item->toArray(), $items);
	}

	protected function getEnrichers(): array
	{
		return [
			new UpdateFieldsForTaxMode($this->accounting, $this->entity),
			new CompleteExtraFields($this->accounting, $this->permissionsProvider, $this->entity),
			new ConvertCurrency($this->currencyId),
			new CompleteStores($this->entity),
		];
	}

	protected function getEntityProductRows(): array
	{
		$result = $this->entity->getProductRows();

		return $result ? $result->getAll() : [];
	}

	/**
	 * @return VatRate[]
	 */
	private function fetchVatRates(): array
	{
		$vatRates = \CCrmTax::GetVatRateInfos();
		return array_map(static fn($fields) => VatRate::make($fields), $vatRates);
	}

	private function isEntityEditable(): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($this->entity->isNew())
		{
			$isEditable = $userPermissions->checkAddPermissions(
				$this->entity->getEntityTypeId(),
				$this->entity->getCategoryId()
			);
		}
		else
		{
			$isEditable = $userPermissions->checkUpdatePermissions(
				$this->entity->getEntityTypeId(),
				$this->entity->getId(),
				$this->entity->getCategoryId()
			);
		}

		return $isEditable;
	}

	private function isAllowedReservation(): bool
	{
		return \CCrmSaleHelper::isAllowedReservation(
			$this->entity->getEntityTypeId(),
			$this->entity->getCategoryId() ?? 0
		);
	}

	private function isReservationRestrictedByPlan(): bool
	{
		return
			EnableWizard\Manager::isOnecMode()
				? EnableWizard\TariffChecker::isOnecInventoryManagementRestricted()
				: !RestrictionManager::getInventoryControlIntegrationRestriction()->hasPermission()
		;
	}

	private function isProductRowTaxUniform(): bool
	{
		return Option::get('crm', 'product_row_tax_uniform', 'Y') === 'Y';
	}
}
