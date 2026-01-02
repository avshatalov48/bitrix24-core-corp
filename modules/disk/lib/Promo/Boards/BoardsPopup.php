<?php

declare(strict_types=1);

namespace Bitrix\Disk\Promo\Boards;

use Bitrix\Bitrix24\License;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CBitrix24;
use CUserOptions;

class BoardsPopup
{
	private const USER_OPTION_CATEGORY = 'promo-boards-popup';
	private const REPEAT_SHOW_AFTER_DAYS = 4;
	private const TERMINATION_DATE = '31.08.2025';

	public function __construct(private int $userId)
	{
	}

	public function shouldShowPopup(): bool
	{
		if (!Configuration::isBoardsEnabled())
		{
			return false;
		}

		if ($this->isTerminated())
		{
			return false;
		}

		$state = $this->getState();

		if ($state === BoardsPopupState::New)
		{
			$this->setAcknowledged(); // side effect
		}

		return match ($state) {
			BoardsPopupState::New, BoardsPopupState::Completed => false,
			BoardsPopupState::Acknowledged => $this->isTimeForFirstShow(),
			BoardsPopupState::Viewed => $this->isTimeForSecondShow(),
		};
	}

	public function markAsViewed(): void
	{
		if ($this->getState() === BoardsPopupState::Viewed)
		{
			$this->setCompleted();
		}
		else
		{
			$this->setViewed();
		}
	}

	public function setCompleted(): void
	{
		$this->setDate(BoardsPopupState::Completed);
	}

	private function isTerminated(): bool
	{
		$now = new DateTime();
		$terminationDate = new DateTime(self::TERMINATION_DATE, 'd.m.Y');

		return $now > $terminationDate;
	}

	private function setAcknowledged(): void
	{
		$this->setDate(BoardsPopupState::Acknowledged);
	}

	private function setViewed(): void
	{
		$this->setDate(BoardsPopupState::Viewed);
	}

	private function getState(): BoardsPopupState
	{
		if ($this->getDate(BoardsPopupState::Completed) !== null)
		{
			return BoardsPopupState::Completed;
		}

		if ($this->getDate(BoardsPopupState::Viewed) !== null)
		{
			return BoardsPopupState::Viewed;
		}

		if ($this->getDate(BoardsPopupState::Acknowledged) !== null)
		{
			return BoardsPopupState::Acknowledged;
		}

		return BoardsPopupState::New;
	}

	private function isTimeForFirstShow(): bool
	{
		$dateAcknowledging = $this->getDate(BoardsPopupState::Acknowledged);

		if (isset($dateAcknowledging))
		{
			$now = new DateTime();
			$interval = $now->getDiff($dateAcknowledging);

			$delayInDaysForFirstShow = $this->getDelayInDaysForFirstShow();
			if ($interval->days >= $delayInDaysForFirstShow)
			{
				return true;
			}
		}

		return false;
	}

	private function getDelayInDaysForFirstShow(): int
	{
		if (Loader::includeModule('bitrix24'))
		{
			$license = License::getCurrent();

			$isWestRegion = !in_array($license->getRegion(), ['ru', 'kz', 'by'], true);

			if ($isWestRegion && CBitrix24::IsLicensePaid())
			{
				return 1;
			}

			return 7;
		}

		return PHP_INT_MAX; // for the on-premise version
	}

	private function isTimeForSecondShow(): bool
	{
		$dateFirstViewing = $this->getDate(BoardsPopupState::Viewed);

		if (isset($dateFirstViewing))
		{
			$now = new DateTime();
			$interval = $now->getDiff($dateFirstViewing);

			if ($interval->days >= self::REPEAT_SHOW_AFTER_DAYS)
			{
				return true;
			}
		}

		return false;
	}

	private function getDate(BoardsPopupState $dateType): ?DateTime
	{
		$completedDateTimestamp = (int)CUserOptions::GetOption(self::USER_OPTION_CATEGORY, $dateType->value, 0, $this->userId);

		if ($completedDateTimestamp > 0)
		{
			return DateTime::createFromTimestamp($completedDateTimestamp);
		}

		return null;
	}

	private function setDate(BoardsPopupState $dateType): void
	{
		CUserOptions::setOption(self::USER_OPTION_CATEGORY, $dateType->value, time(), false, $this->userId);
	}
}