<?php

namespace Bitrix\Crm\Agent\MovedByField;

use Bitrix\Crm\DealTable;

class DealFieldAgent extends BaseFieldAgent
{
	protected function getList(int $lastId, int $limit)
	{
		return DealTable::query()
			->setSelect([
				'ID',
				'DATE_MODIFY',
				'MODIFY_BY_ID',
				'MOVED_TIME',
				'MOVED_BY_ID',
			])
			->where('ID', '>', $lastId)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->exec()
			->fetchAll()
		;
	}

	protected function getLastHistoryRecord(int $id): ?array
	{
		$result = \Bitrix\Crm\History\Entity\DealStageHistoryTable::query()
			->where('OWNER_ID', $id)
			->setSelect(['ID', 'CREATED_TIME'])
			->setLimit(1)
			->setOrder(['ID' => 'DESC'])
			->fetch();

		return $result ?: null;
	}

	protected function update(int $id, array $fieldsToUpdate): void
	{
		DealTable::update($id, $fieldsToUpdate);
	}

	protected function onStepperComplete(): void
	{
		\COption::RemoveOption('crm', 'need_set_deal_moved_by_field');
	}
}
