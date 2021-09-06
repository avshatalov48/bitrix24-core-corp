<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Monitor\Constant\Group;
use Bitrix\Timeman\Monitor\History\History;
use Bitrix\Timeman\Monitor\History\ReportComment;
use Bitrix\Timeman\Monitor\History\UserChart;
use Bitrix\Timeman\Monitor\Utils\Time;

class Report
{
	private $dateStart;
	private $dateFinish;
	private $userId;
	private $history;
	private $report;
	private $timeline;
	private $comment;

	public function __construct(int $userId, Date $dateStart, Date $dateFinish)
	{
		$this->userId = $userId;
		$this->dateStart = $dateStart;
		$this->dateFinish = $dateFinish;

		$this->history = History::getForPeriod($this->userId, $this->dateStart, $this->dateFinish);
		$this->timeline = UserChart::getReportOnDate($this->userId, $this->dateStart);
		$this->comment = ReportComment::getOnDate($this->userId, $this->dateStart);

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
			'timeline' => $this->timeline,
			'report' => $this->report,
			'comment' => $this->comment,
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