<?php

namespace Bitrix\Sign\Controller;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Container;

final class Tour extends Controller
{
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

		$saveSuccess = Container::instance()->getTourService()->saveVisit($tourId, $userId, new DateTime());

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
		$visitDate = Container::instance()->getTourService()->getLastVisitDate($tourId, $userId);

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
}
