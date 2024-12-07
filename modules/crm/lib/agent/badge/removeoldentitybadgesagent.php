<?php

namespace Bitrix\Crm\Agent\Badge;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class RemoveOldEntityBadgesAgent extends AgentBase
{
	public static function doRun(): bool
	{
		$instance = new self();
		$instance->execute();

		return true;
	}

	private function execute(): void
	{
		$intervalInSeconds = ActivitySettings::getCurrent()->getRemoveEntityBadgesIntervalDays() * 24 * 60 * 60;
		$timestamp = time() + \CTimeZone::getOffset() - $intervalInSeconds;

		$orm = BadgeTable::getList([
			'filter' => [
				'<=CREATED_DATE' => DateTime::createFromTimestamp($timestamp),
			],
			'limit' => self::getLimit(),
			'order' => ['CREATED_DATE' => 'ASC'],
		]);

		while ($row = $orm->fetch())
		{
			BadgeTable::delete($row['ID']);
		}
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'RemoveOldEntityBadgesLimit', 100);
	}
}