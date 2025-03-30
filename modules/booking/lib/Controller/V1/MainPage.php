<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Bitrix24\License;
use Bitrix\Booking\Controller\V1\Response\MainPageGetCountersResponse;
use Bitrix\Booking\Controller\V1\Response\MainPageGetResponse;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Interfaces\ProviderInterface;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Service\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\ClientStatisticsProvider;
use Bitrix\Booking\Provider\FavoritesProvider;
use Bitrix\Booking\Provider\MoneyStatisticsProvider;
use Bitrix\Booking\Provider\OptionProvider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;
use DateTimeImmutable;
use DateInterval;

class MainPage extends BaseController
{
	private BookingProvider $bookingProvider;
	private FavoritesProvider $favoritesProvider;
	private ResourceTypeProvider $resourceTypeProvider;
	private CounterRepositoryInterface $counterRepository;
	private ProviderInterface|null $provider;
	private MoneyStatisticsProvider $moneyStatisticsProvider;
	private ClientStatisticsProvider $clientStatisticsProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingProvider = new BookingProvider();
		$this->favoritesProvider = new FavoritesProvider();
		$this->resourceTypeProvider = new ResourceTypeProvider();
		$this->counterRepository = Container::getCounterRepository();
		$this->provider = Container::getProviderManager()::getCurrentProvider();
		$this->moneyStatisticsProvider = new MoneyStatisticsProvider();
		$this->clientStatisticsProvider = new ClientStatisticsProvider();
	}

	public function getForBookingAction(
		int $dateTs,
		int $bookingId,
		string $timezone,
		array|null $resourcesIds,
	): MainPageGetResponse|null
	{
		try
		{
			$userId = (int)CurrentUser::get()->getId();

			$booking = $this->bookingProvider->getById($userId, $bookingId);

			$date = new DateTimeImmutable('@' . $dateTs);
			if ($dateTs <= 0 && !empty($booking))
			{
				$dateString = $booking->getDatePeriod()->getDateFrom()->format('Y-m-d');
				$date = new DateTimeImmutable($dateString, new \DateTimeZone($timezone));
			}

			$datePeriod = new DatePeriod(
				dateFrom: $date,
				dateTo: $date->add(new DateInterval('P1D')), // add 1 day
			);

			$bookings = new Entity\Booking\BookingCollection();
			if ($booking)
			{
				$resourceId = $booking->getResourceCollection()->getFirstCollectionItem()->getId();
				$resourcesIds = array_merge($resourcesIds ?? [], [$resourceId]);
				$bookings = $this->getBookings($userId, $datePeriod, $resourcesIds);
			}

			$resourceTypes = $this->getResourceTypes($userId);

			return new MainPageGetResponse(
				favorites: null,
				bookingCollection: $bookings,
				resourceTypeCollection: $resourceTypes,
				providerModuleId: $this->provider?->getModuleId(),
				clientsDataRecent: $this->getClientsDataRecent(),
				isCurrentSenderAvailable: MessageSenderPicker::canUseCurrentSender(),
				isIntersectionForAll: true,
				counters: $this->counterRepository->getList($userId),
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function getAction(int $dateTs): MainPageGetResponse|null
	{
		try
		{
			$userId = (int)CurrentUser::get()->getId();

			$date = new DateTimeImmutable('@' . $dateTs);
			$datePeriod = new DatePeriod(
				dateFrom: $date,
				dateTo: $date->add(new DateInterval('P1D')), // add 1 day
			);

			$favorites = $this->getFavorites($userId, $datePeriod);
			$favoritesIds = $favorites->getResources()->getEntityIds();
			$bookings = $this->getBookings($userId, $datePeriod, $favoritesIds);
			$resourceTypes = $this->getResourceTypes($userId);

			return new MainPageGetResponse(
				favorites: $favorites,
				bookingCollection: $bookings,
				resourceTypeCollection: $resourceTypes,
				providerModuleId: $this->provider?->getModuleId(),
				clientsDataRecent: $this->getClientsDataRecent(),
				isCurrentSenderAvailable: MessageSenderPicker::canUseCurrentSender(),
				isIntersectionForAll: $this->isIntersectionForAll($userId),
				counters: $this->counterRepository->getList($userId),
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function getCountersAction(): MainPageGetCountersResponse|null
	{
		try
		{
			$userId = (int)CurrentUser::get()->getId();

			return new MainPageGetCountersResponse(
				totalClients: $this->clientStatisticsProvider->getTotalClients(),
				totalClientsToday: $this->clientStatisticsProvider->getTotalClientsToday($userId),
				counters: $this->counterRepository->getList($userId),
				moneyStatistics: $this->moneyStatisticsProvider->get($userId),
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function activateDemoAction(): bool
	{
		return (
			Loader::includeModule('bitrix24')
			&& License::getCurrent()->getDemo()->isAvailable()
			&& !License::getCurrent()->getDemo()->activate()->isSuccess()
		);
	}

	private function isIntersectionForAll(int $userId): bool
	{
		return (new OptionProvider())->isIntersectionForAll($userId);
	}

	private function getFavorites(int $userId, DatePeriod $datePeriod): Entity\Favorites\Favorites
	{
		return $this->favoritesProvider->getList(
			managerId: $userId,
			datePeriod: $datePeriod,
			withCounters: true,
		);
	}

	private function getBookings(
		int $userId,
		DatePeriod $datePeriod,
		array $resourcesIds,
	): Entity\Booking\BookingCollection
	{
		if (empty($resourcesIds))
		{
			return new Entity\Booking\BookingCollection();
		}

		$bookings = $this->bookingProvider->getList(
			new GridParams(
				filter: new BookingFilter([
					'RESOURCE_ID_OR_HAS_COUNTERS_USER_ID' => [
						'RESOURCE_ID' => $resourcesIds,
						'HAS_COUNTERS_USER_ID' => (int)CurrentUser::get()->getId(),
					],
					'WITHIN' => [
						'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
						'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
					],
				]),
				select: new BookingSelect([
					'CLIENTS',
					'RESOURCES',
					'EXTERNAL_DATA',
					'NOTE',
				]),
			),
			userId: $userId,
		);

		$this->bookingProvider
			->withCounters($bookings, $userId)
			->withClientsData($bookings)
			->withExternalData($bookings)
		;

		return $bookings;
	}

	private function getResourceTypes(int $userId): Entity\ResourceType\ResourceTypeCollection
	{
		return $this->resourceTypeProvider->getList(new GridParams(), $userId);
	}

	private function getClientsDataRecent(): array
	{
		return $this->provider?->getClientProvider()?->getClientDataRecent() ?? [];
	}
}
