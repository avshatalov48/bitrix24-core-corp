<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;

class Pending extends AbstractStage
{
	public function getFilterParams(array $filter = []): array
	{
		$this->transformFilter($filter);

		$filter['COMPLETED'] = 'N';

		if (
			!empty($filter['ACTIVITY_COUNTER'])
			&& in_array(EntityCounterType::INCOMING_CHANNEL, $filter['ACTIVITY_COUNTER'])
		)
		{
			$additionalCondition = false;
			if (isset($filter[self::RESPONSIBLE_ID_FIELD_NAME]))
			{
				$additionalCondition = 'ICT.RESPONSIBLE_ID=' . (int)$filter[self::RESPONSIBLE_ID_FIELD_NAME];
			}
			elseif (isset($filter['!' . self::RESPONSIBLE_ID_FIELD_NAME]))
			{
				$additionalCondition = 'ICT.RESPONSIBLE_ID <>' . (int)$filter['!' . self::RESPONSIBLE_ID_FIELD_NAME];
			}

			if ($additionalCondition)
			{
				$filter['__JOINS'] = [
					[
						'TYPE' => 'INNER',
						'SQL' => 'INNER JOIN ' . IncomingChannelTable::getTableName() . " AS ICT ON A.ID = ICT.ACTIVITY_ID and ICT.COMPLETED='N' and " . $additionalCondition,
					],
				];
			}
		}
		elseif (
			!empty($filter['ACTIVITY_COUNTER'])
			&& in_array(EntityCounterType::OVERDUE, $filter['ACTIVITY_COUNTER'])
		)
		{
			$filter['__JOINS'] = [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN ' . ActCounterLightTimeTable::getTableName() . ' AS ACLTT ON A.ID = ACLTT.ACTIVITY_ID',
				],
			];

			$filter['>=DEADLINE'] = (new DatePeriods())->today();
			$filter['<DEADLINE'] = (new DatePeriods())->tomorrow();
			$filter['__CONDITIONS'] = [
				[
					'SQL' => 'ACLTT.LIGHT_COUNTER_AT < NOW()',
				],
			];
		}
		else
		{
			global $DB;
			$filter['__CONDITIONS'] = [
				[
					'SQL' => "(A.DEADLINE >= " . $DB->CharToDateFunction((new DatePeriods())->today()) . " AND A.DEADLINE < " . $DB->CharToDateFunction((new DatePeriods())->tomorrow()) . ") OR ICT.ACTIVITY_ID is not null",
				],
			];

			$filter['__JOINS'] = [
				[
					'TYPE' => 'INNER',
					'SQL' => 'LEFT JOIN ' . IncomingChannelTable::getTableName() . ' AS ICT ON A.ID = ICT.ACTIVITY_ID',
				],
			];
		}

		unset($filter['ACTIVITY_COUNTER']);

		return $filter;
	}
}