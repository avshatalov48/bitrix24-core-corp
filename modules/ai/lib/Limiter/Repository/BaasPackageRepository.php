<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Limiter\Model\BaasPackageTable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\ORM\Data\AddResult;

class BaasPackageRepository extends BaseRepository
{
	/**
	 * Add new package
	 */
	public function addPackage(Date $startDate, Date $expiredDate): AddResult
	{
		return BaasPackageTable::add([
			'DATE_START' => $startDate,
			'DATE_EXPIRED' => $expiredDate,
		]);
	}

	/**
	 * Return info about max date expired
	 */
	public function getLatestPackageByExpiration(): array
	{
		$data = BaasPackageTable::query()
			->setSelect([
				'DATE_EXPIRED'
			])
			->setOrder(['DATE_EXPIRED' => 'DESC'])
			->setLimit(1)
			->fetch()
		;

		if (!is_array($data) || empty($data))
		{
			return [];
		}

		return $data;
	}
}
