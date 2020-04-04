<?php

namespace Bitrix\Voximplant\Agent;

use Bitrix\Main\Type;
use Bitrix\Voximplant\Model\CallTable;

class CallCleaner
{
	/**
	 * Finishes calls started more than day ago. Seems safe to do without additional checks.
	 */
	public static function finishStaleCalls()
	{
		CallTable::updateBatch(
			[
				'STATUS' => \Bitrix\Voximplant\Model\CallTable::STATUS_FINISHED
			],
			[
				'!=STATUS' => \Bitrix\Voximplant\Model\CallTable::STATUS_FINISHED,
				'!=ACCESS_URL' => '',
				'<DATE_CREATE' => (new \Bitrix\Main\Type\DateTime())->add('-1D'),
				'>DATE_CREATE' => (new \Bitrix\Main\Type\DateTime())->add('-30D')
			],
			1000
		);

		return '\Bitrix\Voximplant\Agent\CallCleaner::finishStaleCalls();';
	}

	/**
	 * Deletes calls started more than 30 days ago.
	 */
	public static function deleteOldCalls()
	{
		CallTable::deleteBatch(['<DATE_CREATE' => (new \Bitrix\Main\Type\DateTime())->add('-30D')], 1000);

		return '\Bitrix\Voximplant\Agent\CallCleaner::deleteOldCalls();';
	}
}