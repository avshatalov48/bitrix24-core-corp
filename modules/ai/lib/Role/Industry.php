<?php

declare(strict_types = 1);

namespace Bitrix\AI\Role;

use Bitrix\AI\Model\RoleIndustryTable;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;

class Industry
{
	/**
	 * Removes all industry from DB.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		$industries = RoleIndustryTable::query()
			->setSelect(['ID'])
			->exec()
		;
		foreach ($industries as $industry)
		{
			RoleIndustryTable::delete($industry['ID'])->isSuccess();
		}
	}
}