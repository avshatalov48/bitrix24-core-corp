<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main;
use Bitrix\Main\Update\Stepper;

/**
 * Class CounterRecount
 *
 * Kept due to backward compatibility
 *
 * @package Bitrix\Tasks\Update
 */
class CounterRecount extends Stepper
{
	protected static $moduleId = "tasks";

	/**
	 * @param array $result
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function execute(array &$result): bool
	{
		return false;
	}
}