<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\CounterTable;
use Bitrix\Main\Type\Date;

class CounterRepository extends BaseRepository
{
	/**
	 * Return info about last request in baas with increment limit
	 */
	public function getLastDate(): array|false
	{
		return CounterTable::query()
			->setSelect([
				'VALUE'
			])
			->where('NAME', CounterTable::COUNTER_LAST_REQUEST_IN_BAAS)
			->fetch()
		;
	}

	/**
	 * Insert or update date last request in baas with increment limit
	 */
	public function updateLastRequest(): void
	{
		$name = CounterTable::COUNTER_LAST_REQUEST_IN_BAAS;
		$date = (new Date())->toString();

		CounterTable::merge(
			['NAME' => $name, 'VALUE' => $date],
			['VALUE' => $date]
		);
	}
}
