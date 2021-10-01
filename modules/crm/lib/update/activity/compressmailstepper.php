<?php

namespace Bitrix\Crm\Update\Activity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class CompressMailStepper
 *
 * <code>
 * \Bitrix\Main\Update\Stepper::bindClass('Bitrix\Crm\Update\Activity\CompressMailStepper', 'crm');
 * </code>
 *
 * @package Bitrix\Crm\Update
 */
final class CompressMailStepper extends Main\Update\Stepper
{
	private const LIMIT = 50;
	protected static $moduleId = 'crm';

	public function execute(array &$option)
	{
		//return self::FINISH_EXECUTION; -- ON EMERGENCY

		$lastId = (int)($option['lastId'] ?? 0);

		$maxResult = \Bitrix\Crm\ActivityTable::getList([
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
			'select' => ['ID'],
		])->fetch();

		$maxID = (int) $maxResult['ID'];
		if ($lastId >= $maxID)
		{
			return self::FINISH_EXECUTION;
		}

		$ids = [];
		$listIDResult = \Bitrix\Crm\ActivityTable::getList([
			'order' => ['ID' => 'ASC'],
			'limit' => self::LIMIT,
			'filter' => ['>ID' => $lastId],
			'select' => ['ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID'],
		]);

		foreach ($listIDResult as $row)
		{
			$row['ASSOCIATED_ENTITY_ID'] = (int) $row['ASSOCIATED_ENTITY_ID'];

			if ($row['TYPE_ID'] == \CCrmActivityType::Email && $row['ASSOCIATED_ENTITY_ID'] == 0)
			{
				$ids[] = $row['ID'];
			}

			$option['lastId'] = $row['ID'];
		}

		if (!empty($ids))
		{
			$listResult = \Bitrix\Crm\ActivityTable::getList([
				'order' => ['ID' => 'ASC'],
				'filter' => ['@ID' => $ids],
				'select' => ['ID', 'DESCRIPTION', 'DESCRIPTION_TYPE', 'DIRECTION', 'SETTINGS'],
			]);

			foreach ($listResult as $row)
			{
				$id = $row['ID'];
				unset($row['ID']);

				if ($this->isRobotMail($row))
				{
					Crm\Activity\Provider\Email::compressActivity($row);
					Crm\ActivityTable::update($id, $row);
				}
			}
		}

		return self::CONTINUE_EXECUTION;
	}

	private function isRobotMail(array $activity)
	{
		return (
			(int)$activity['DIRECTION'] === \CCrmActivityDirection::Outgoing
			&& isset($activity['SETTINGS']['BP_ACTIVITY_ID'])
		);
	}
}
