<?php

declare(strict_types=1);

namespace Bitrix\Baas\Public\Provider;

use Bitrix\Baas;
use Bitrix\Main\Provider\Params\PagerInterface;
use Bitrix\Baas\Internal\Entity\Enum\ServiceAdvertisingStrategy;
use Bitrix\Baas\Internal\Service\MarketplaceService;

class ServiceProvider
{
	use DailyBillingSyncTrait;

	/** @var Baas\Entity\Service[]  */
	private array $repo = [];
	protected Baas\Model\EO_Service_Collection $services;

	public function __construct(
		protected Baas\Repository\ServiceRepositoryInterface $serviceRepository,
		protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository,
	)
	{
		$this->checkAndSyncOncePerDay();
	}

	public function getByCode(string $code): Baas\Contract\Service
	{
		return $this->createService($code, null);
	}
	/**
	 * @return iterable<Baas\Contract\Service>
	 */
	public function getAll(): iterable
	{
		foreach ($this->getServices() as $service)
		{
			yield $this->createService($service->getCode(), $service);
		}
	}

	/**
	 * @param PagerInterface|null $pager
	 * @return Baas\Contract\Service[]
	 *
	 */
	public function getList(?PagerInterface $pager = null): array
	{
		$result = [];
		foreach ($this->serviceRepository->getList(
			offset: $pager?->getOffset(),
			limit: $pager?->getLimit(),
		) as $service)
		{
			$result[] = $this->createService($service->getCode(), $service);
		}

		return $result;
	}

	private function createService(string $code, ?Baas\Model\EO_Service $data): Baas\Entity\Service
	{
		if (!array_key_exists($code, $this->repo))
		{
			$data = $data ?? $this->getServices()->getByPrimary($code);
			// region remove it after 13.11.2025. this code is used only for temporary needs of the controller
			if (
				str_starts_with(\COption::GetOptionString('main', '~controller_group_name'), 'ru')
				&& $data?->getCode() === 'ai_copilot_token'
				&& $data?->getAdvertisingStrategy() !== ServiceAdvertisingStrategy::BY_MARKET->value
			)
			{
				$data->setAdvertisingStrategy(ServiceAdvertisingStrategy::BY_MARKET->value);
				MarketplaceService::createInstance()->adaptFeaturePromotionAndHelperCodes($data);
			}
			// endregion
			$this->repo[$code] = new Baas\Entity\Service($code, $data);
		}

		return $this->repo[$code];
	}

	private function getServices(): Baas\Model\EO_Service_Collection
	{
		if (!isset($this->services))
		{
			$this->services = $this->serviceRepository->getAll();
		}

		return $this->services;
	}

	public static function create(): static
	{
		return new static(
			Baas\Repository\ServiceRepository::getInstance(),
			Baas\Repository\PurchaseRepository::getInstance(),
		);
	}
}
