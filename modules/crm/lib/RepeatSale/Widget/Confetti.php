<?php

namespace Bitrix\Crm\RepeatSale\Widget;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\FlowController;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use CUserOptions;

final class Confetti
{
	private const SHOWED_STATISTICS_DATA_OPTION_NAME = 'repeat-sale-showed-statistics-confetti';
	private const MAX_SHOWED_STATISTICS_COUNT = 3;
	private const INTERVAL_SHOWED_STATISTICS = '-7 days';

	private ?array $showedStatisticsData = null;
	private ?int $successItemsCount = null;

	public function incrementShowedCount(): void
	{
		if ($this->isShowMaxCountExceeded())
		{
			return;
		}

		$currentTimestamp = (new Date())->getTimestamp();

		$showedStatisticsConfettiData = $this->getShowedStatisticsData();
		$showedCount = $showedStatisticsConfettiData['showedCount'] ?? 0;

		CUserOptions::SetOption(
			'crm',
			self::SHOWED_STATISTICS_DATA_OPTION_NAME,
			[
				'lastShowedTimestamp' => $currentTimestamp,
				'showedCount' => $showedCount + 1,
				'successItemsCount' => $this->getSuccessItemsCount(),
			],
		);
	}

	public function isNeedShowConfetti(): bool
	{
		if ($this->isShowMaxCountExceeded())
		{
			return false;
		}

		if (!$this->hasNewSuccessItems())
		{
			return false;
		}

		if ($this->isUserRegisteredTooLate())
		{
			return false;
		}

		return $this->isLastShowedWasLongTimeAgo();
	}

	private function isShowMaxCountExceeded(): bool
	{
		$showedStatisticsData = $this->getShowedStatisticsData();
		$showedCount = $showedStatisticsData['showedCount'] ?? 0;

		return $showedCount > self::MAX_SHOWED_STATISTICS_COUNT;
	}

	private function hasNewSuccessItems(): bool
	{
		$showedStatisticsData = $this->getShowedStatisticsData();
		$successItemsCount = $showedStatisticsData['successItemsCount'] ?? null;

		if ($successItemsCount === null && $this->getSuccessItemsCount() === 0)
		{
			return false;
		}

		return $successItemsCount < $this->getSuccessItemsCount();
	}

	private function getSuccessItemsCount(): int
	{
		if ($this->successItemsCount === null)
		{
			$result = RepeatSaleLogTable::query()
				->setSelect(['CNT'])
				->registerRuntimeField('CNT', new ExpressionField('CNT', 'COUNT(*)'))
				->where('STAGE_SEMANTIC_ID', PhaseSemantics::SUCCESS)
				->setCacheTtl(86400)
				->fetch()
			;

			$this->successItemsCount = (int)($result['CNT'] ?? 0);
		}

		return $this->successItemsCount;
	}

	private function isUserRegisteredTooLate(): bool
	{
		$flowEnableDate = FlowController::getInstance()->getEnableDate();
		if (!$flowEnableDate)
		{
			return true;
		}

		$userRegisterMaxTimestamp = $flowEnableDate->add('-2 days')->getTimestamp();

		$user = UserTable::query()
			->setSelect(['DATE_REGISTER'])
			->where('ID', Container::getInstance()->getContext()->getUserId())
			->setCacheTtl(86400 * 7)
			->fetch()
		;

		$dateRegister = $user['DATE_REGISTER'] ?? null;
		if ($dateRegister === null)
		{
			return true;
		}

		return $dateRegister->getTimestamp() > $userRegisterMaxTimestamp;
	}

	private function isLastShowedWasLongTimeAgo(): bool
	{
		$showedStatisticsData = $this->getShowedStatisticsData();
		$lastShowedTimestamp = $showedStatisticsData['lastShowedTimestamp'] ?? null;

		if ($lastShowedTimestamp === null)
		{
			return true;
		}

		$lastShowedTimestampWithOffset = (Date::createFromTimestamp($lastShowedTimestamp))
			->add(self::INTERVAL_SHOWED_STATISTICS)
			->getTimestamp()
		;

		$currentTimestamp = (new Date())->getTimestamp();

		return $lastShowedTimestampWithOffset > $currentTimestamp;
	}

	private function getShowedStatisticsData(): array
	{
		if ($this->showedStatisticsData === null)
		{
			$this->showedStatisticsData = CUserOptions::GetOption(
				'crm',
				self::SHOWED_STATISTICS_DATA_OPTION_NAME,
				[]
			);
		}

		return $this->showedStatisticsData;
	}
}
