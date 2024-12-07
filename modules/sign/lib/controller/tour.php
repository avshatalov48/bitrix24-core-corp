<?php

namespace Bitrix\Sign\Controller;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Config\Storage;

final class Tour extends Controller
{
	private const OPTIONS_CATEGORY_NAME = 'sign.tour';
	private const OPTIONS_VISIT_VALUE_PREFIX = 'visit_date_';
	private const VISIT_DATE_FORMAT = \DateTimeInterface::ATOM;

	public function saveVisitAction(string $tourId): void
	{
		$validateTourIdResult = $this->validateTourId($tourId);
		if (!$validateTourIdResult->isSuccess())
		{
			$this->addErrors($validateTourIdResult->getErrors());
			return;
		}

		$userId = CurrentUser::get()->getId();

		$saveSuccess = $this->saveVisit($tourId, $userId, new DateTime());

		if (!$saveSuccess)
		{
			$this->addError(new Error('Cant save tour visit date'));
		}
	}

	/**
	 * @param string $tourId
	 * @return array{lastVisitDate: int|null}
	 */
	public function getLastVisitDateAction(string $tourId): array
	{
		$validateTourIdResult = $this->validateTourId($tourId);
		if (!$validateTourIdResult->isSuccess())
		{
			$this->addErrors($validateTourIdResult->getErrors());
			return [];
		}

		$userId = CurrentUser::get()->getId();
		$visitDate = $this->getLastVisitDate($tourId, $userId);

		return [
			'lastVisitDate' => $visitDate?->format(self::VISIT_DATE_FORMAT),
		];
	}

	public function isAllToursDisabledAction(): bool
	{
		return Storage::instance()->isToursDisabled();
	}

	private function validateTourId(string $tourId): Result
	{
		if ($tourId === '')
		{
			return (new Result())->addError(new Error('Tour id cant be empty'));
		}

		return new Result();
	}

	private function saveVisit(string $tourId, int $userId, DateTime $value): bool
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

	private function getLastVisitDate(string $tourId, int $userId): ?DateTime
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