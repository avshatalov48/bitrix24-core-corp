<?php

namespace Bitrix\Crm\Agent\Security\EntityPerms;

use Bitrix\Crm\EntityPermsTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class CleanUnnecessaryEntityPerms
{
	private const ENTITY_CODES_TO_CLEAN = [
		'LEAD', 'COMPANY', 'CONTACT', 'DEAL'
	];

	private const BATCH_SIZE = 100;

	private const DONE = false;

	private const CONTINUE = true;

	public function execute(): bool
	{
		$ids = $this->getIdsToRemove($this->getLimit());

		if (empty($ids))
		{
			return self::DONE;
		}

		$this->removeEntityPermsByIds($ids);

		return self::CONTINUE;
	}

	/**
	 * @return int[]
	 */
	private function getIdsToRemove(int $limit): array
	{
		$entityTypes = $this->queryEntityTypesToRemove();

		if (empty($entityTypes))
		{
			return [];
		}

		return EntityPermsTable::query()
			->setSelect(['ID'])
			->whereIn('ENTITY', $entityTypes)
			->setLimit($limit)
			->fetchCollection()
			->getIdList();
	}

	private function queryEntityTypesToRemove(): array
	{
		$ct = new ConditionTree();
		$ct->logic(ConditionTree::LOGIC_OR);

		foreach (self::ENTITY_CODES_TO_CLEAN as $entityCode)
		{
			$ct->whereLike('ENTITY', $entityCode . '%');
		}

		$entityTypesQ = EntityPermsTable::query()
			->setSelect(['ENTITY'])
			->where($ct)
			->setCacheTtl(60 * 60)
			->setDistinct();

		return array_column($entityTypesQ->fetchAll(), 'ENTITY');
	}

	/**
	 * @param int[] $ids
	 * @return void
	 */
	private function removeEntityPermsByIds(array $ids): void
	{
		EntityPermsTable::deleteByIds($ids);
	}

	private function getLimit(): int
	{
		$limit = (int)Option::get('crm', 'CleanUnnecessaryEntityPerms', self::BATCH_SIZE);

		return $limit > 0 ? $limit : self::BATCH_SIZE;
	}

}