<?php

declare(strict_types=1);

namespace Bitrix\Disk\Promo\Boards;

use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Main\Type\DateTime;
use CUserOptions;

class BoardsPopup
{
	private const USER_OPTION_CATEGORY = 'promo-boards-popup';
	private const REPEAT_SHOW_AFTER_DAYS = 4;
	private const TERMINATION_DATE = '15.05.2025';

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

		return match ($state) {
			BoardsPopupState::New => true,
			BoardsPopupState::Viewed => $this->isTimeForSecondShow(),
			BoardsPopupState::Completed => false,
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

		return BoardsPopupState::New;
	}

	/**
	 * Unused method, left just in case
	 * @return bool
	 */
	private function isTimeForFirstShow(): bool
	{
		$releaseDate = new DateTime('17.02.2025'); // fixme: hardcoded

		$now = new DateTime();
		$releaseThreshold = (clone $releaseDate)->setTime(14, 0); // todo: make time a constant

		if ($releaseDate >= $releaseThreshold)
		{
			$releaseThreshold->add('+1 day');
		}

		return $now >= $releaseThreshold;
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