<?php

namespace Bitrix\Crm\RepeatSale\Widget;

use Bitrix\Main\Type\Date;
use CUserOptions;

final class StartBanner
{
	private const SHOWED_OPTION_NAME = 'repeat-sale-showed-start-banner';
	private const MAX_SHOWED_COUNT = 6;
	private const INTERVAL_SHOWED = '-1 day';

	private ?array $showedStatisticsData = null;

	public function incrementShowedCount(): void
	{
		if ($this->isShowMaxCountExceeded())
		{
			return;
		}

		$currentTimestamp = (new Date())->getTimestamp();

		$showedData = $this->getShowedStatisticsData();
		$showedCount = $showedData['showedCount'] ?? 0;

		CUserOptions::SetOption(
			'crm',
			self::SHOWED_OPTION_NAME,
			[
				'lastShowedTimestamp' => $currentTimestamp,
				'showedCount' => $showedCount + 1,
			],
		);
	}

	public function isNeedShowImmediately(): bool
	{
		if ($this->isShowMaxCountExceeded())
		{
			return false;
		}

		return $this->isLastShowedWasLongTimeAgo();
	}

	private function isShowMaxCountExceeded(): bool
	{
		$showedStatisticsData = $this->getShowedStatisticsData();
		$showedCount = $showedStatisticsData['showedCount'] ?? 0;

		return $showedCount > self::MAX_SHOWED_COUNT;
	}

	private function isLastShowedWasLongTimeAgo(): bool
	{
		$showedStatisticsData = $this->getShowedStatisticsData();
		$lastShowedTimestamp = $showedStatisticsData['lastShowedTimestamp'] ?? null;

		if ($lastShowedTimestamp === null)
		{
			return true;
		}

		$lastShowedTimestampWithOffset = Date::createFromTimestamp($lastShowedTimestamp)
			->add(self::INTERVAL_SHOWED)
			->getTimestamp()
		;

		$currentTimestamp = (new Date())->getTimestamp();

		return $lastShowedTimestampWithOffset > $currentTimestamp;
	}

	private function getShowedStatisticsData(): array
	{
		if ($this->showedStatisticsData === null)
		{
			$this->showedStatisticsData = CUserOptions::GetOption('crm', self::SHOWED_OPTION_NAME, []);
		}

		return $this->showedStatisticsData;
	}

	public function dropShowedStatisticsData(): void
	{
		CUserOptions::DeleteOption('crm', self::SHOWED_OPTION_NAME);

		$this->showedStatisticsData = [];
	}
}
