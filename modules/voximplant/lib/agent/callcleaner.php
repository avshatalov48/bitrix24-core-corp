<?php

namespace Bitrix\Voximplant\Agent;

use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\CallTable;

class CallCleaner
{
	/**
	 * Finishes calls started more than day ago. Seems safe to do without additional checks.
	 */
	public static function finishStaleCalls(): string
	{
		CallTable::updateBatch(
			[
				'STATUS' => CallTable::STATUS_FINISHED
			],
			[
				'!=STATUS' => CallTable::STATUS_FINISHED,
				'!=ACCESS_URL' => '',
				'<DATE_CREATE' => (new DateTime())->add('-1D'),
				'>DATE_CREATE' => (new DateTime())->add('-30D')
			],
			1000
		);

		return __METHOD__ . '();';
	}

	/**
	 * Deletes calls started more than 30 days ago.
	 */
	public static function deleteOldCalls(): string
	{
		CallTable::deleteBatch(['<DATE_CREATE' => (new DateTime())->add('-30D')], 1000);

		return __METHOD__. '();';
	}
}