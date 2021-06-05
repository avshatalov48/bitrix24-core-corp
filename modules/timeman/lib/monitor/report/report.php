<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Monitor\Group\Group;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\Utils\Time;

class Report
{
	private $dateStart;
	private $dateFinish;
	private $userId;
	private $history;
	private $report;
	private $line;

	public function __construct(int $userId, Date $dateStart, Date $dateFinish)
	{
		$this->userId = $userId;
		$this->dateStart = $dateStart;
		$this->dateFinish = $dateFinish;

		$this->history = History::getForPeriod($this->userId, $this->dateStart, $this->dateFinish);

		if ($this->history)
		{
			$this->createReport();
		}
	}

	public function getData(): array
	{
		return [
			'userId' => $this->userId,
			'dateStart' => $this->dateStart,
			'dateFinish' => $this->dateFinish,
			'line' => $this->line,
			'report' => $this->report
		];
	}

	protected function createReport(): void
	{
		$report = [];
		foreach ($this->history as $entry)
		{
			$entry['TIME_FORMATTED'] = Time::format((int)$entry['TIME_SPEND']);

			$report[Group::WORKING]['DATA']['TIME_SPEND'] += (int)$entry['TIME_SPEND'];

			$report[Group::WORKING]['DETAIL'][] = $entry;
		}

		$report[Group::WORKING]['DATA']['TIME_FORMATTED'] =
			Time::format($report[Group::WORKING]['DATA']['TIME_SPEND'])
		;

		usort($report[Group::WORKING]['DETAIL'], static function ($current, $next) {
			return $next['TIME_SPEND'] - $current['TIME_SPEND'];
		});

		$this->report = $report;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getPeriod(): array
	{
		return [
			'start' => $this->dateStart,
			'finish' => $this->dateFinish,
		];
	}
}