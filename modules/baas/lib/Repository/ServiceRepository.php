<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository;

use Bitrix\Baas;
use Bitrix\Main;

class ServiceRepository implements ServiceRepositoryInterface
{
	use Baas\Internal\Trait\SingletonConstructor;

	protected function __construct()
	{
	}

	public function findByCode(string $code): ?Baas\Model\EO_Service
	{
		return Baas\Model\ServiceTable::query()
			->setSelect(['*'])
			->where('CODE', $code)
			->fetchObject()
		;
	}

	public function purge(): void
	{
		Baas\Model\ServiceTable::deleteBatch(['!CODE' => null]);
		Baas\Model\ServiceAdsTable::deleteBatch(['!SERVICE_CODE' => null]);
	}

	public function save(
		Baas\Model\EO_Service_Collection $services,
		Baas\Model\EO_ServiceAds_Collection $servicesAds,
	): void
	{
		$result = $services->save();
		if (!$result->isSuccess())
		{
			throw  new Main\SystemException(
				'Error saving services: ' . implode(' ', $result->getErrorMessages()),
			);
		}

		$result = $servicesAds->save();
		if (!$result->isSuccess())
		{
			throw  new Main\SystemException(
				'Error saving services: ' . implode(' ', $result->getErrorMessages()),
			);
		}
	}

	public function getAdsInfo(Baas\Entity\Service $service, ?string $languageId = null): ?Baas\Model\EO_ServiceAds
	{
		$query = Baas\Model\ServiceAdsTable::query()
			->setSelect(['*'])
			->where('SERVICE_CODE', $service->getCode())
			->setCacheTtl(86400)
			->where('LANGUAGE_ID', $languageId)
		;
		if ($languageId)
		{
			$query->where('LANGUAGE_ID', $languageId);
		}

		return $query->fetchObject();
	}

	public function getAll(): Baas\Model\EO_Service_Collection
	{
		return Baas\Model\ServiceTable::query()
			->setSelect(['*'])
			->setOrder(['CODE' => 'ASC'])
			->setCacheTtl(86400)
			->fetchCollection()
		;
	}

	public function getList(
		?int $offset = null,
		?int $limit = null,
	): Baas\Model\EO_Service_Collection
	{
		return Baas\Model\ServiceTable::query()
			->setSelect(['*'])
			->setOrder(['CODE' => 'ASC'])
			->setCacheTtl(86400)
			->setOffset($offset)
			->setLimit($limit)
			->fetchCollection()
		;
	}
}
