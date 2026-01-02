<?php

namespace Bitrix\Crm\RepeatSale\Log;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Query\Query;

final class LogReport
{
	use Singleton;

	public function getEntitiesCount(array $segmentIds, ?string $phaseSemanticId = null): array
	{
		if (empty($segmentIds))
		{
			return [];
		}

		$query = (new Query(RepeatSaleLogTable::getEntity()))
			->setSelect([
				'SEGMENT_ID',
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'),
			])
			->addGroup('SEGMENT_ID')
			->addFilter('@SEGMENT_ID', $segmentIds)
		;

		$filteredPhases = [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE];
		if (in_array($phaseSemanticId, $filteredPhases, true))
		{
			$query->addFilter('=STAGE_SEMANTIC_ID', $phaseSemanticId);
		}

		$rows = $query->exec()->fetchAll();
		$result = [];
		foreach ($rows as $row)
		{
			$result[$row['SEGMENT_ID']] = $row['CNT'];
		}

		return $result;
	}
}
