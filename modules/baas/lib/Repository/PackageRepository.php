<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository;

use Bitrix\Baas;
use Bitrix\Main;
use Bitrix\Baas\Internal\Entity;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class PackageRepository implements PackageRepositoryInterface
{
	use Baas\Internal\Trait\SingletonConstructor;

	protected function __construct()
	{
	}

	public function purge(): void
	{
		Baas\Model\PackageTable::deleteBatch(['!CODE' => null]);
		Baas\Model\ServiceInPackageTable::deleteBatch(['!PACKAGE_CODE' => null]);
	}

	public function save(
		Baas\Model\EO_Package_Collection $packages,
		Baas\Model\EO_ServiceInPackage_Collection $packageServices,
	): void
	{
		$result = $packages->save();
		if (!$result->isSuccess())
		{
			throw  new Main\SystemException(
				'Error saving services: ' . implode(' ', $result->getErrorMessages()),
			);
		}
		$result = $packageServices->save();
		if (!$result->isSuccess())
		{
			throw  new Main\SystemException(
				'Error saving services: ' . implode(' ', $result->getErrorMessages()),
			);
		}
	}

	public function findByCode($code): ?Baas\Model\EO_Package
	{
		return Baas\Model\PackageTable::query()
			->setSelect(['*'])
			->where('CODE', $code)
			->setCacheTtl(86400)
			->exec()
			->fetchObject()
		;
	}

	public function getAll(): Baas\Model\EO_Package_Collection
	{
		return Baas\Model\PackageTable::query()
			->setSelect(['*'])
			->setOrder(['SORT' => 'ASC'])
			->setCacheTtl(86400)
			->exec()
			->fetchCollection()
		;
	}

	public function getDistributedByBaas(): Baas\Model\EO_Package_Collection
	{
		$res = Baas\Model\PackageTable::query()
			->setSelect(['*'])
			->setOrder(['SORT' => 'ASC'])
			->setCacheTtl(86400)
			->where(
				(new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->where('DISTRIBUTION_STRATEGY', Entity\Enum\PackageDistributionStrategy::BY_BAAS->value)
					->whereNull('DISTRIBUTION_STRATEGY')
			)
			->exec()
			->fetchCollection()
		;

		return $res;
	}

	public function getDistributedByBaasForService(Baas\Entity\Service $service): Baas\Model\EO_Package_Collection
	{
		return Baas\Model\PackageTable::query()
			->setSelect(['*'])
			->where('SERVICE_IN_PACKAGE.SERVICE_CODE', $service->getCode())
			->where(
				(new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->where('DISTRIBUTION_STRATEGY', Entity\Enum\PackageDistributionStrategy::BY_BAAS->value)
					->whereNull('DISTRIBUTION_STRATEGY')
			)
			->setOrder(['SORT' => 'ASC'])
			->setCacheTtl(86400)
			->exec()
			->fetchCollection()
		;
	}
}
