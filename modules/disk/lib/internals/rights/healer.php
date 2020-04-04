<?php

namespace Bitrix\Disk\Internals\Rights;

final class Healer
{
	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Restarts setup sessions which were pending.
	 *
	 * @return string
	 */
	public static function restartSetupSession()
	{
		$portion = 3;
		$maxExecutionTime = 10;
		$startTime = time();

		$rows = SetupSession::getList(array(
			'filter' => array(
				'=STATUS' => SetupSession::STATUS_STARTED,
				'=IS_EXPIRED' => true,
			),
			'limit' => $portion,
		));

		foreach ($rows as $row)
		{
			if(time() - $startTime > $maxExecutionTime)
			{
				break;
			}
			
			/** @var SetupSession $setupSession */
			$setupSession = SetupSession::buildFromArray($row);
			$setupSession->forkAndRestart();

			break;
		}
		
		return static::className() . "::restartSetupSession();";
	}

	/**
	 * Marks as bad sessions which were calculated without success many times and
	 * deletes tmp simple rights by these sessions.
	 *
	 * @return string
	 */
	public static function markBadSetupSession()
	{
		Table\RightSetupSessionTable::markAsBad();

		return static::className() . "::markBadSetupSession();";
	}
}