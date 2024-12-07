<?php

namespace Bitrix\Crm\Agent\Quote;

use Bitrix\Crm\Agent\AgentBase;

class QuoteStorageElementIdsAgent extends AgentBase
{
	private const AGENT_DONE_STOP_IT = false;

	private const AGENT_CONTINUE = true;

	public static function doRun(): bool
	{
		$isDone = StorageElementIdsToAttachedObject::getInstance()->execute();

		if ($isDone)
		{
			StorageElementIdsToAttachedObject::cleanOptions();

			return self::AGENT_DONE_STOP_IT;
		}

		return self::AGENT_CONTINUE;
	}
}