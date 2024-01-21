<?php

namespace Bitrix\Crm\Activity\FastSearch;


use Bitrix\Crm\Traits;

class ActivityFastSearchRepo
{
	use Traits\Singleton;


	public function add(array $row): void
	{
		ActivityFastSearchTable::add($row);
	}

	public function upsert(array $row): void
	{
		$entity = ActivityFastSearchTable::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		$sql = $helper->prepareMerge($entity->getDBTableName(), $entity->getPrimaryArray(), $row, $row);

		$sql = current($sql);
		if($sql <> '')
		{
			$connection->queryExecute($sql);
		}
	}

	public function delete(int $id): void
	{
		ActivityFastSearchTable::delete($id);
	}
}