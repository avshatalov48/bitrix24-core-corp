<?php

declare(strict_types=1);

namespace Bitrix\Baas\Public\Provider;

use Bitrix\Main;
use Bitrix\Baas;

class PackageProvider
{
	use DailyBillingSyncTrait;
/**
 * @var Baas\Entity\Package[]
 */
	private array $repo = [];

	public function __construct(
		protected Baas\Repository\PackageRepositoryInterface $packageRepository,
	)
	{
		$this->checkAndSyncOncePerDay();
	}

	public function getByCode(string $code, ?Baas\Model\EO_Package $data = null): ?Baas\Entity\Package
	{
		if (!array_key_exists($code, $this->repo))
		{
			if ($data || ($data = $this->packageRepository->findByCode($code)))
			{
				$this->repo[$code] = new Baas\Entity\Package($code, $data);
			}
		}

		return $this->repo[$code] ?? null;
	}

	public function getAll(): iterable
	{
		$collection = $this->packageRepository->getAll();

		foreach ($collection as $packageObject)
		{
			yield $this->getByCode($packageObject['CODE'], $packageObject);
		}
	}

	/**
	 * @param Baas\Entity\Service $service
	 * @return Baas\Entity\Package[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getDistributedByBaasForService(Baas\Entity\Service $service): array
	{
		$collection = $this->packageRepository->getDistributedByBaasForService(
			$service
		);

		$packages = [];
		foreach ($collection as $packageObject)
		{
			$packages[] = $this->getByCode($packageObject['CODE'], $packageObject);
		}

		return $packages;
	}

	public function getDistributedByBaas(): iterable
	{
		$collection = $this->packageRepository->getDistributedByBaas();

		foreach ($collection as $packageObject)
		{
			yield $this->getByCode($packageObject['CODE'], $packageObject);
		}
	}

	public static function create(): static
	{
		return new static(
			Baas\Repository\PackageRepository::getInstance(),
		);
	}
}
