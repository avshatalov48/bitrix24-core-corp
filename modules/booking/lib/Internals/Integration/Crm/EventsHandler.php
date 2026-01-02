<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Command\Booking\BookingResult;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Timeline\Booking\Controller;
use Bitrix\Main\Event;
use Bitrix\Main\Result;
use CCrmOwnerType;
use DateTimeZone;
use DateTimeImmutable;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\Client\Client;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmContext;
use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\Type\BookingStatus;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm;
use Bitrix\Crm\Service\Timeline\Monitor;

class EventsHandler
{
	private const MODULE_ID = 'crm';

	public static function onContactDelete(int $contactId): void
	{
		self::onClientDelete(CCrmOwnerType::ContactName, $contactId);
	}

	public static function onCompanyDelete(int $companyId): void
	{
		self::onClientDelete(CCrmOwnerType::CompanyName, $companyId);
	}

	private static function onClientDelete(string $entityTypeName, int $entityId): void
	{
		$clientProvider = Container::getProviderManager()::getProviderByModuleId(self::MODULE_ID)?->getClientProvider();
		if (!$clientProvider)
		{
			return;
		}

		$clientTypeCollection = $clientProvider->getClientTypeCollection();

		$foundClientType = null;
		foreach ($clientTypeCollection as $clientType)
		{
			if ($clientType->getModuleId() === 'crm' && $clientType->getCode() === $entityTypeName)
			{
				$foundClientType = $clientType;

				break;
			}
		}

		if (!$foundClientType)
		{
			return;
		}

		$clientTypeRow = ClientTypeTable::getList([
			'filter' => [
				'=MODULE_ID' => $foundClientType->getModuleId(),
				'=CODE' => $foundClientType->getCode(),
			],
			'limit' => 1,
		])->fetch();
		if (!$clientTypeRow)
		{
			return;
		}

		Container::getBookingClientRepository()->unLinkByFilter([
			'=CLIENT_TYPE_ID' => (int)$clientTypeRow['ID'],
			'=CLIENT_ID' => $entityId,
		]);
	}

	public static function onDealDelete(int $dealId): void
	{
		Container::getBookingExternalDataRepository()->unLinkByFilter([
			'=MODULE_ID' => self::MODULE_ID,
			'=ENTITY_TYPE_ID' => CCrmOwnerType::DealName,
			'=VALUE' => (string)$dealId,
		]);
	}

	public static function onDynamicItemDelete(Event $event): void
	{
		$id = (string)$event->getParameter('id');
		if (!$id)
		{
			return;
		}

		/** @var Item $item */
		$item = $event->getParameter('item');

		if (!$item instanceof Item)
		{
			return;
		}

		$entityTypeId = CCrmOwnerType::ResolveName($item->getEntityTypeId());
		if (!$entityTypeId)
		{
			return;
		}

		Container::getBookingExternalDataRepository()->unLinkByFilter([
			'=MODULE_ID' => self::MODULE_ID,
			'=ENTITY_TYPE_ID' => $entityTypeId,
			'=VALUE' => $id,
		]);
	}
	
	public static function onCrmBookingFormFilled(Event $event): string
	{
		$value = $event->getParameter('VALUE');

		$resources = isset($value['resources']) && is_array($value['resources']) ? $value['resources'] : [];
		$crmEntityList = $event->getParameter('CRM_ENTITY_LIST');
		$crmEntityList = is_array($crmEntityList) ? $crmEntityList : [];

		$booking = new Booking();

		if (
			isset($value['dateFromTs'])
			&& isset($value['dateToTs'])
			&& isset($value['timezone'])
		)
		{
			$booking->setDatePeriod(
				new DatePeriod(
					(new DateTimeImmutable('@' . (int)$value['dateFromTs']))
						->setTimezone(new DateTimeZone((string)$value['timezone']))
					,
					(new DateTimeImmutable('@' . (int)$value['dateToTs']))
						->setTimezone(new DateTimeZone((string)$value['timezone']))
				),
			);
		}

		$resourceCollection = new ResourceCollection();
		$externalDataCollection = new ExternalDataCollection();
		foreach ($resources as $resource)
		{
			if (!isset($resource['id']))
			{
				continue;
			}

			$resourceCollection->add((new Resource())->setId((int)$resource['id']));

			if (isset($resource['skus']))
			{
				foreach ($resource['skus'] as $sku)
				{
					$externalDataCollection->add(
						(new CatalogSkuItemType())->createItem()->setValue((string)$sku['id'])
					);
				}
			}
		}

		$booking->setResourceCollection($resourceCollection);
		$booking->setExternalDataCollection($externalDataCollection);

		$timelineBindings = [];
		$booking
			->setClientCollection(
				self::getClientCollectionFromEntityList(
					$crmEntityList,
					$timelineBindings
				)
			)
			->setExternalDataCollection(
				self::getExternalDataCollectionFromEntityList(
					$crmEntityList,
					$timelineBindings
				)
			)
		;

		/** @var Result|BookingResult $addResult */
		$addResult = (new AddBookingCommand(
			createdBy: (int)CurrentUser::get()->getId(),
			booking: $booking,
		))->run();

		if (!$addResult->isSuccess())
		{
			self::handleBookingCreationError($event, $timelineBindings);

			//@todo add failure url
			return '';
		}

		return (new BookingConfirmLink())->getLink(
			$addResult->getBooking(),
			BookingConfirmContext::Info
		);
	}

	private static function handleBookingCreationError(Event $event, array $timelineBindings): void
	{
		Controller::getInstance()->onBookingCreationError(
			$timelineBindings,
			[
				'entityTypeId' => $event->getParameter('CRM_ENTITY_TYPE'),
				'entityId' => $event->getParameter('CRM_ENTITY_ID'),
				'phoneNumber' => $event->getParameter('PHONE_NUMBER'),
			]
		);

		if (!empty($timelineBindings))
		{
			$badge = Crm\Service\Container::getInstance()->getBadge(
				Badge::BOOKING_STATUS_TYPE,
				BookingStatus::NOT_BOOKED_CLIENT
			);

			$sourceIdentifier = new SourceIdentifier(
				SourceIdentifier::BOOKING_BOOKING_TYPE_PROVIDER,
				0,
				0
			);

			foreach ($timelineBindings as $binding)
			{
				$itemIdentifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

				$badge->upsert($itemIdentifier, $sourceIdentifier);

				Monitor::getInstance()->onBadgesSync($itemIdentifier);
			}
		}
	}

	private static function getClientCollectionFromEntityList(
		array $crmEntityList,
		array &$timelineBindings
	): ClientCollection
	{
		$clientCollection = new ClientCollection();

		foreach ($crmEntityList as $crmEntity)
		{
			if ($crmEntity['ENTITY_TYPE'] === \CCrmOwnerType::ContactName)
			{
				$clientCollection->add(
					(new Client())
						->setId((int)$crmEntity['ENTITY_ID'])
						->setType(
							(new ClientType())
								->setModuleId('crm')
								->setCode(\CCrmOwnerType::ContactName)
						)
				);

				$timelineBindings[] = [
					'OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
					'OWNER_ID' => (int)$crmEntity['ENTITY_ID'],
				];
			}

			if ($crmEntity['ENTITY_TYPE'] === \CCrmOwnerType::CompanyName)
			{
				$clientCollection->add(
					(new Client())
						->setId((int)$crmEntity['ENTITY_ID'])
						->setType(
							(new ClientType())
								->setModuleId('crm')
								->setCode(\CCrmOwnerType::CompanyName)
						)
				);

				$timelineBindings[] = [
					'OWNER_TYPE_ID' => \CCrmOwnerType::Company,
					'OWNER_ID' => (int)$crmEntity['ENTITY_ID'],
				];
			}
		}

		return $clientCollection;
	}

	private static function getExternalDataCollectionFromEntityList(
		array $crmEntityList,
		array &$timelineBindings
	): ExternalDataCollection
	{
		$externalDataCollection = new ExternalDataCollection();

		foreach ($crmEntityList as $crmEntity)
		{
			if (
				$crmEntity['ENTITY_TYPE'] === CCrmOwnerType::DealName
				|| mb_strpos($crmEntity['ENTITY_TYPE'], CCrmOwnerType::DynamicTypePrefixName) === 0
			)
			{
				$externalDataCollection->add(
					(new ExternalDataItem())
						->setModuleId('crm')
						->setValue((string)$crmEntity['ENTITY_ID'])
						->setEntityTypeId($crmEntity['ENTITY_TYPE'])
				);


				$timelineBindings[] = [
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($crmEntity['ENTITY_TYPE']),
					'OWNER_ID' => (int)$crmEntity['ENTITY_ID'],
				];
			}
		}

		return $externalDataCollection;
	}
}
