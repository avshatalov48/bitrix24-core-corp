<?php
namespace Bitrix\Crm\Volume;

/**
 * @internal
 */
final class TmpEventIndexFix
{
	/**
	 * @internal
	 */
	public static function fixIndex()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->queryExecute("UPDATE b_crm_event SET FILES = NULL WHERE FILES IS NOT NULL AND (FILES = '' OR FILES = 'a:0:{}') LIMIT 100");
		if ($connection->getAffectedRowsCount() > 0)
		{
			return get_called_class(). '::fixIndex();';
		}

		return '';
	}

	/**
	 * @internal
	 */
	public static function hasJobDone()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$result = $connection->query("SELECT count(*) AS CNT FROM b_crm_event WHERE FILES IS NOT NULL AND (FILES = '' OR FILES = 'a:0:{}')");
		if ($row = $result->fetch())
		{
			return ($row['CNT'] == 0);
		}

		return true;
	}
}