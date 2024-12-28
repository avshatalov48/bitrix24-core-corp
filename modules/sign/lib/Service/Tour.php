<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\Type\DateTime;

class Tour
{
	private const VISIT_DATE_FORMAT = \DateTimeInterface::ATOM;
	private const OPTIONS_CATEGORY_NAME = 'sign.tour';
	private const OPTIONS_VISIT_VALUE_PREFIX = 'visit_date_';

	public function saveVisit(string $tourId, int $userId, DateTime $value): bool
	{
		$formattedDateTime = $value->format(self::VISIT_DATE_FORMAT);

		return \CUserOptions::SetOption(
			self::OPTIONS_CATEGORY_NAME,
			self::OPTIONS_VISIT_VALUE_PREFIX . $tourId,
			$formattedDateTime,
			false,
			$userId
		);
	}

	public function getLastVisitDate(string $tourId, int $userId): ?DateTime
	{
		$visitDate = \CUserOptions::GetOption(
			self::OPTIONS_CATEGORY_NAME,
			self::OPTIONS_VISIT_VALUE_PREFIX . $tourId,
			null,
			$userId
		);

		return $visitDate === null ? null : DateTime::tryParse((string)$visitDate, self::VISIT_DATE_FORMAT);
	}
}
