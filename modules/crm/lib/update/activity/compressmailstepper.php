<?php

namespace Bitrix\Crm\Update\Activity;

use Bitrix\Main;

/**
 * Class CompressMailStepper
 *
 * <code>
 * \Bitrix\Main\Update\Stepper::bindClass('Bitrix\Crm\Update\Activity\CompressMailStepper', 'crm');
 * </code>
 *
 * @package Bitrix\Iblock\Update
 */
final class CompressMailStepper extends Main\Update\Stepper
{
	private const LIMIT = 100;

	protected static $moduleId = 'crm';

	function execute(array &$option)
	{
		$lastId = (int)($option['lastId'] ?? 0);

		$listResult = \Bitrix\Crm\ActivityTable::getList([
			'order' => ['ID' => 'ASC'],
			'limit' => self::LIMIT,
			'filter' => [
				'>ID' => $lastId,
				'=TYPE_ID' => \CCrmActivityType::Email
			],
			'select' => ['ID', 'DESCRIPTION']
		]);

		$selectedRowsCount = $listResult->getSelectedRowsCount();

		if (!$selectedRowsCount)
		{
			return self::FINISH_EXECUTION;
		}

		foreach ($listResult as $row)
		{
			$compressed = \CCrmActivity::compressDescription($row['DESCRIPTION']);

			if ($compressed !== $row['DESCRIPTION'])
			{
				\Bitrix\Crm\ActivityTable::update($row['ID'], ['DESCRIPTION' => $compressed]);
			}
			$option['lastId'] = $row['ID'];
		}
		return self::CONTINUE_EXECUTION;
	}
}
