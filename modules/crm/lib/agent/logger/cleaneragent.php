<?php


namespace Bitrix\Crm\Agent\Logger;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Crm\Service\Logger\Model\LogTable;

class CleanerAgent
{
	public static function run(): string
	{
		$connection = Application::getConnection();
		$query = new SqlExpression(
			'DELETE FROM ?# WHERE VALID_TO < ' . $connection->getSqlHelper()->getCurrentDateTimeFunction(),
			LogTable::getTableName(),
		);
		$connection->query($query);
		LogTable::cleanCache();

		$nextExec = LogTable::query()
			->setSelect(['VALID_TO'])
			->setOrder(['VALID_TO' => 'ASC'])
			->setLimit(1)
			->fetchObject()
			?->getValidTo()
		;

		if ($nextExec)
		{
			global $pPERIOD;

			// next run when need to delete something
			$pPERIOD = $nextExec->getTimestamp() - time();
			if ($pPERIOD < 60)
			{
				$pPERIOD = 60;
			}

			return self::getAgentString();
		}

		return ''; // next run is not required
	}

	public static function getAgentString(): string
	{
		return self::class . '::run();';
	}
}
