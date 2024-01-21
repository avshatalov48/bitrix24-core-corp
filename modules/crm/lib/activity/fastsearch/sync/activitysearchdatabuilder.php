<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Traits;

final class ActivitySearchDataBuilder
{
	use Traits\Singleton;

	public function __construct()
	{
	}

	public function build(array $fields): ActivitySearchData
	{
		$type = $this->getType($fields);

		$kind = $this->getKind($fields);

		if (empty($fields['CREATED']))
		{
			$created = new DateTime();
		}
		else
		{
			$created = $fields['CREATED'];
		}

		if (empty($fields['DEADLINE']))
		{
			$deadline = \CCrmDateTimeHelper::getMaxDatabaseDateObject();
		}
		else
		{
			$deadline = $fields['DEADLINE'];
		}


		$authorId = empty($fields['AUTHOR_ID']) ? null : (int)$fields['AUTHOR_ID'];

		return new ActivitySearchData(
			(int)$fields['ID'],
			$this->makeDate($created),
			$this->makeDate($deadline),
			(int)$fields['RESPONSIBLE_ID'],
			$fields['COMPLETED'] === 'Y',
			$type,
			$kind,
			$authorId
		);
	}

	private function makeDate(string|DateTime $date): DateTime
	{
		if ($date instanceof DateTime)
		{
			return $date;
		}

		return DateTime::createFromUserTime($date);
	}

	private function getType(array $fields): string
	{
		$provider = \CCrmActivity::GetActivityProviderSafelyByDisabled($fields);

		if (empty($provider))
		{
			return ActivitySearchData::TYPE_UNSUPPORTED;
		}

		if (!$provider::isActivitySearchSupported())
		{
			return ActivitySearchData::TYPE_UNSUPPORTED;
		}

		return $provider::makeTypeCode($fields);
	}

	private function getKind(array $fields): int
	{
		return ($fields['IS_INCOMING_CHANNEL'] ?? null) === 'Y'
			? ActivitySearchData::KIND_INCOMING
			: ActivitySearchData::KIND_COMMON;
	}
}