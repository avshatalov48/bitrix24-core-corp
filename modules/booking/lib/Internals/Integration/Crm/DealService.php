<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Entity\ExternalData\ItemType\CrmDealItemType;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use CCrmDeal;
use CCrmOwnerType;

class DealService
{
	private ServiceSkuProvider $serviceSkuProvider;

	public function __construct(ServiceSkuProvider $serviceSkuProvider)
	{
		$this->serviceSkuProvider = $serviceSkuProvider;
	}

	public function createDealForBooking(Booking $booking): void
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$client = $booking->getClientCollection()->getPrimaryClient();
		$clientType = $client->getType();

		if (!($clientType && $clientType->getModuleId() === 'crm'))
		{
			return;
		}

		$clientId = (int)$client->getId();
		$clientTypeCode = $clientType->getCode();
		if (
			!(
				in_array($clientTypeCode, [CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName], true)
				&& $clientId
			)
		)
		{
			return;
		}

		$dealId = $this->createDeal($clientTypeCode, $clientId);
		if (!$dealId)
		{
			return;
		}

		$this->addProductsToDeal(
			$dealId,
			$booking->getExternalDataCollection()->filterByType((new CatalogSkuItemType())->buildFilter())->getValues()
		);

		$this->attachBookingToDeal($dealId, $booking);
	}

	private function createDeal(string $clientTypeCode, int $clientId): int|null
	{
		$fields = [
			'SOURCE_ID' => 'BOOKING',
			'TYPE_ID' => 'SERVICES',
		];

		if ($clientTypeCode === CCrmOwnerType::CompanyName)
		{
			$fields['COMPANY_ID'] = $clientId;
		}
		elseif ($clientTypeCode === CCrmOwnerType::ContactName)
		{
			$fields['CONTACT_IDS'] = [$clientId];
		}

		$dealId = (int)(new CCrmDeal(false))->add(
			$fields,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
			]
		);

		return $dealId > 0 ? $dealId : null;
	}

	private function addProductsToDeal(int $dealId, array $skuIds): void
	{
		$dealProducts = [];
		$skus = $this->serviceSkuProvider->get($skuIds);
		foreach ($skus as $sku)
		{
			$dealProducts[] = [
				'PRODUCT_ID' => $sku->getId(),
				'PRODUCT_NAME' => $sku->getName(),
				'PRICE' => $sku->getPrice(),
				'QUANTITY' => 1,
			];
		}
		if (!empty($dealProducts))
		{
			CCrmDeal::SaveProductRows($dealId, $dealProducts, false);
		}
	}

	private function attachBookingToDeal(int $dealId, Booking $booking): void
	{
		$booking->getExternalDataCollection()->add(
			(new CrmDealItemType())->createItem()->setValue((string)$dealId)
		);

		(new UpdateBookingCommand(
			updatedBy: (int)CurrentUser::get()->getId(),
			booking: $booking,
		))->run();
	}
}
